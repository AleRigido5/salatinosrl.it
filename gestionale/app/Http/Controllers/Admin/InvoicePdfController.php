<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoiceReceived;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoicePdfController extends Controller
{
    public function showMinisteriale($id)
    {
        Log::info('=== GENERAZIONE PDF MINISTERIALE ===', ['invoice_id' => $id]);
        
        ini_set('memory_limit', '4096M');
        ini_set('max_execution_time', 300);
        
        try {
            $invoice = InvoiceReceived::with([
                'entity', 
                'ownership', 
                'rows.costCenter',
                'payments',
                'vatSummaries'
            ])->findOrFail($id);
            
            // Recupera l'XML originale
            $xmlContent = DB::table('invoices_received')
                ->where('id', $invoice->id)
                ->value('xml_content');
            
            if (!$xmlContent) {
                throw new \Exception('Contenuto XML non trovato');
            }
            
            // Pulisci namespace
            $cleanXml = $this->cleanXmlFromNamespaces($xmlContent);
            
            // Carica con SimpleXML
            $xml = simplexml_load_string($cleanXml);
            
            if ($xml === false) {
                throw new \Exception('Impossibile parsare XML');
            }
            
            // Estrai dati con SimpleXML
            $cedenteDenominazione = (string)($xml->FatturaElettronicaHeader->CedentePrestatore->DatiAnagrafici->Anagrafica->Denominazione ?? $invoice->supplier_name);
            $cedentePiva = (string)($xml->FatturaElettronicaHeader->CedentePrestatore->DatiAnagrafici->IdFiscaleIVA->IdCodice ?? ($invoice->entity?->partita_iva ?? ''));
            $numeroDocumento = (string)($xml->FatturaElettronicaBody->DatiGenerali->DatiGeneraliDocumento->Numero ?? $invoice->n_invoice);
            $dataDocumento = (string)($xml->FatturaElettronicaBody->DatiGenerali->DatiGeneraliDocumento->Data ?? $invoice->data_invoice->format('Y-m-d'));
            $importoTotale = $invoice->importo_totale;
            
            // Estrai indirizzi cedente
            $cedenteIndirizzo = (string)($xml->FatturaElettronicaHeader->CedentePrestatore->Sede->Indirizzo ?? $invoice->entity?->indirizzo ?? '');
            $cedenteCap = (string)($xml->FatturaElettronicaHeader->CedentePrestatore->Sede->CAP ?? $invoice->entity?->cap ?? '');
            $cedenteComune = (string)($xml->FatturaElettronicaHeader->CedentePrestatore->Sede->Comune ?? $invoice->entity?->comune ?? '');
            $cedenteProvincia = (string)($xml->FatturaElettronicaHeader->CedentePrestatore->Sede->Provincia ?? $invoice->entity?->provincia ?? '');
            
            // Estrai dati cessionario
            $cessionarioDenominazione = (string)($xml->FatturaElettronicaHeader->CessionarioCommittente->DatiAnagrafici->Anagrafica->Denominazione ?? $invoice->ownership_name);
            $cessionarioIndirizzo = (string)($xml->FatturaElettronicaHeader->CessionarioCommittente->Sede->Indirizzo ?? $invoice->ownership?->Indirizzo ?? '');
            $cessionarioCap = (string)($xml->FatturaElettronicaHeader->CessionarioCommittente->Sede->CAP ?? $invoice->ownership?->CAP ?? '');
            $cessionarioComune = (string)($xml->FatturaElettronicaHeader->CessionarioCommittente->Sede->Comune ?? $invoice->ownership?->Comune ?? '');
            $cessionarioProvincia = (string)($xml->FatturaElettronicaHeader->CessionarioCommittente->Sede->Provincia ?? $invoice->ownership?->Provincia ?? '');
            
            // Tipo documento
            $tipoDocumento = (string)($xml->FatturaElettronicaBody->DatiGenerali->DatiGeneraliDocumento->TipoDocumento ?? $invoice->type_invoice);
            
            // Righe fattura
            $righe = [];
            if (isset($xml->FatturaElettronicaBody->DatiBeniServizi->DettaglioLinee)) {
                foreach ($xml->FatturaElettronicaBody->DatiBeniServizi->DettaglioLinee as $linea) {
                    $righe[] = (object)[
                        'descrizione' => (string)$linea->Descrizione,
                        'quantita' => (float)$linea->Quantita,
                        'prezzo_unitario' => (float)$linea->PrezzoUnitario,
                        'prezzo_totale' => (float)$linea->PrezzoTotale,
                        'aliquota_iva' => (float)$linea->AliquotaIVA,
                    ];
                }
            }
            
            // Pagamenti
            $pagamenti = [];
            if (isset($xml->FatturaElettronicaBody->DatiPagamento->DettaglioPagamento)) {
                foreach ($xml->FatturaElettronicaBody->DatiPagamento->DettaglioPagamento as $dettaglio) {
                    $pagamenti[] = (object)[
                        'modalita' => (string)$dettaglio->ModalitaPagamento,
                        'importo' => (float)$dettaglio->ImportoPagamento,
                        'scadenza' => isset($dettaglio->DataScadenzaPagamento) ? (string)$dettaglio->DataScadenzaPagamento : null,
                        'iban' => isset($dettaglio->IBAN) ? (string)$dettaglio->IBAN : null,
                    ];
                }
            }
            
            // Genera HTML
            $html = view('admin.invoices-received.pdf-ministeriale', [
                'invoice' => $invoice,
                'cedenteDenominazione' => $cedenteDenominazione,
                'cedentePiva' => $cedentePiva,
                'cedenteIndirizzo' => $cedenteIndirizzo,
                'cedenteCap' => $cedenteCap,
                'cedenteComune' => $cedenteComune,
                'cedenteProvincia' => $cedenteProvincia,
                'cessionarioDenominazione' => $cessionarioDenominazione,
                'cessionarioIndirizzo' => $cessionarioIndirizzo,
                'cessionarioCap' => $cessionarioCap,
                'cessionarioComune' => $cessionarioComune,
                'cessionarioProvincia' => $cessionarioProvincia,
                'tipoDocumento' => $tipoDocumento,
                'dataDocumento' => $dataDocumento,
                'numeroDocumento' => $numeroDocumento,
                'importoTotale' => $importoTotale,
                'righe' => $righe,
                'pagamenti' => $pagamenti,
                'payments' => $invoice->payments,
                'vatSummaries' => $invoice->vatSummaries,
            ])->render();
            
            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('a4', 'portrait');
            
            // Sanitizza il nome del file (rimuove caratteri non validi)
            $safeFilename = $this->sanitizeFilename('fattura_ministeriale_' . $invoice->n_invoice . '.pdf');
            
            return $pdf->stream($safeFilename);
            
        } catch (\Exception $e) {
            Log::error('Errore generazione PDF ministeriale: ' . $e->getMessage());
            
            // Fallback al PDF normale
            return redirect()->route('admin.invoices-received.xml-pdf', $id);
        }
    }
    
    /**
     * Sanitizza il nome del file rimuovendo caratteri non validi
     */
    private function sanitizeFilename(string $filename): string
    {
        // Caratteri non validi per i nomi file
        $invalidChars = ['/', '\\', ':', '*', '?', '"', '<', '>', '|', ' ', "\n", "\r", "\t"];
        
        // Sostituisci caratteri non validi con underscore
        $filename = str_replace($invalidChars, '_', $filename);
        
        // Rimuovi eventuali caratteri speciali rimasti
        $filename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $filename);
        
        // Evita nomi vuoti
        if (empty($filename) || $filename === '.' || $filename === '..') {
            $filename = 'fattura.pdf';
        }
        
        // Limita lunghezza
        if (strlen($filename) > 200) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 200 - strlen($ext) - 1) . '.' . $ext;
        }
        
        return $filename;
    }
    
    private function cleanXmlFromNamespaces(string $xmlString): string
    {
        if (empty($xmlString)) {
            return $xmlString;
        }
        
        // Rimuovi dichiarazioni xmlns
        $xmlString = preg_replace('/\s+xmlns(?::\w+)?="[^"]*"/', '', $xmlString);
        $xmlString = preg_replace("/\s+xmlns(?::\w+)?='[^']*'/", '', $xmlString);
        
        // Rimuovi prefissi dai tag
        $xmlString = preg_replace('/(<\/?)[a-zA-Z0-9]+:/', '$1', $xmlString);
        
        // Rimuovi XML stylesheet
        $xmlString = preg_replace('/<\?xml-stylesheet[^?]*\?>/', '', $xmlString);
        
        // Pulisci spazi
        $xmlString = preg_replace('/\s+/', ' ', $xmlString);
        
        return trim($xmlString);
    }
}