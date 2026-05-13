<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoiceReceived;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceXmlController extends Controller
{
    public function showAsPdf($id)
    {
        Log::info('=== PDF GENERATION START ===', ['invoice_id' => $id]);
        
        ini_set('memory_limit', '4096M');
        ini_set('max_execution_time', 300);
        
        try {
            // Recupera fattura con tutte le relazioni
            $invoice = InvoiceReceived::with([
                'entity', 
                'ownership', 
                'rows.costCenter',
                'payments',
                'vatSummaries'
            ])->findOrFail($id);
            
            Log::info('Fattura trovata', [
                'id' => $invoice->id,
                'numero' => $invoice->n_invoice,
                'rows' => $invoice->rows->count(),
                'payments' => $invoice->payments->count(),
                'vat_summaries' => $invoice->vatSummaries->count()
            ]);
            
            // Recupera XML content
            $xmlContent = $this->getXmlContentSafely($invoice);
            
            if ($xmlContent) {
                // Pulisci i namespace prima del parsing
                $cleanXml = $this->cleanXmlFromNamespaces($xmlContent);
                $xmlData = $this->parseXmlForDisplay($cleanXml);
            } else {
                // Usa dati dal database
                $xmlData = $this->buildXmlDataFromDatabase($invoice);
            }
            
            // Aggiungi pagamenti e riepilogo IVA ai dati
            $xmlData['payments_list'] = $invoice->payments;
            $xmlData['vat_summaries_list'] = $invoice->vatSummaries;
            
            // Genera PDF
            $pdf = Pdf::loadView('admin.invoices-received.xml-pdf', [
                'invoice' => $invoice,
                'xmlData' => $xmlData,
            ]);
            $pdf->setPaper('a4', 'portrait');
            
            // Sanitizza il nome del file
            $safeFilename = $this->sanitizeFilename('fattura_' . $invoice->n_invoice . '.pdf');
            
            Log::info('=== PDF GENERATION END ===', ['filename' => $safeFilename]);
            
            return $pdf->stream($safeFilename);
            
        } catch (\Exception $e) {
            Log::error('ERRORE generazione PDF', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            if (isset($invoice) && $invoice) {
                return $this->generatePdfFromDatabase($invoice);
            }
            
            abort(500, 'Errore generazione PDF: ' . $e->getMessage());
        }
    }
    
    /**
     * Rimuove TUTTI i namespace dall'XML in modo universale
     * Gestisce: ns0:Tag, ns1:Tag, ns2:Tag, ns3:Tag, p:Tag, ecc.
     */
    private function cleanXmlFromNamespaces(string $xmlString): string
    {
        // Rimuovi tutte le dichiarazioni xmlns (con apici doppi)
        $xmlString = preg_replace('/\s+xmlns(?::\w+)?="[^"]*"/', '', $xmlString);
        
        // Rimuovi dichiarazioni xmlns (con apici singoli)
        $xmlString = preg_replace("/\s+xmlns(?::\w+)?='[^']*'/", '', $xmlString);
        
        // Rimuovi prefissi dai tag di apertura e chiusura (es. ns0:Tag -> Tag)
        $xmlString = preg_replace('/(<\/?)[a-zA-Z0-9]+:/', '$1', $xmlString);
        
        // Rimuovi eventuali spazi doppi rimasti
        $xmlString = preg_replace('/\s+/', ' ', $xmlString);
        
        return $xmlString;
    }
    
    /**
     * Sanitizza il nome del file rimuovendo caratteri non validi
     */
    private function sanitizeFilename(string $filename): string
    {
        // Caratteri non validi per i nomi file su Windows/Linux
        $invalidChars = ['/', '\\', ':', '*', '?', '"', '<', '>', '|', ' ', "\n", "\r", "\t"];
        
        // Sostituisci caratteri non validi con underscore
        $filename = str_replace($invalidChars, '_', $filename);
        
        // Rimuovi eventuali caratteri speciali rimasti
        $filename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $filename);
        
        // Evita nomi vuoti o con solo punti
        if (empty($filename) || $filename === '.' || $filename === '..') {
            $filename = 'fattura.pdf';
        }
        
        // Limita la lunghezza (max 200 caratteri)
        if (strlen($filename) > 200) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 200 - strlen($ext) - 1) . '.' . $ext;
        }
        
        return $filename;
    }
    
    private function getXmlContentSafely($invoice): ?string
    {
        $xmlContent = DB::table('invoices_received')
            ->where('id', $invoice->id)
            ->value('xml_content');
        
        if ($xmlContent) {
            return $this->removeAttachmentsFromXml($xmlContent);
        }
        
        return null;
    }
    
    private function removeAttachmentsFromXml(string $xmlString): string
    {
        $xmlString = preg_replace('/<Allegati>.*?<\/Allegati>/is', '', $xmlString);
        $xmlString = preg_replace('/<Allegato>.*?<\/Allegato>/is', '', $xmlString);
        $xmlString = preg_replace('/<FatturaFirmata>.*?<\/FatturaFirmata>/is', '', $xmlString);
        return $xmlString;
    }
    
    private function buildXmlDataFromDatabase($invoice): array
    {
        return [
            'cedente' => [
                'denominazione' => $invoice->supplier_name,
                'id_paese' => 'IT',
                'id_codice' => $invoice->entity?->partita_iva ?? '',
                'indirizzo' => $invoice->entity?->indirizzo,
                'cap' => $invoice->entity?->cap,
                'comune' => $invoice->entity?->comune,
                'provincia' => $invoice->entity?->provincia,
            ],
            'cessionario' => [
                'denominazione' => $invoice->ownership_name,
                'indirizzo' => $invoice->ownership?->Indirizzo,
                'cap' => $invoice->ownership?->CAP,
                'comune' => $invoice->ownership?->Comune,
                'provincia' => $invoice->ownership?->Provincia,
            ],
            'documento' => [
                'tipo' => $invoice->type_invoice,
                'tipo_label' => $invoice->type_invoice_label,
                'numero' => $invoice->n_invoice,
                'data' => $invoice->data_invoice->format('Y-m-d'),
                'divisa' => $invoice->divisa,
            ],
            'righe' => [],
            'pagamenti' => [],
        ];
    }
    
    private function parseXmlForDisplay(string $xmlString): array
    {
        // L'XML è già stato pulito dai namespace, possiamo usare le regex normalmente
        $data = [
            'cedente' => [],
            'cessionario' => [],
            'documento' => [],
            'righe' => [],
            'pagamenti' => [],
        ];
        
        // DATI FORNITORE (CedentePrestatore)
        if (preg_match('/<CedentePrestatore>(.*?)<\/CedentePrestatore>/is', $xmlString, $cedenteMatch)) {
            $cedenteXml = $cedenteMatch[1];
            
            if (preg_match('/<Denominazione>(.*?)<\/Denominazione>/i', $cedenteXml, $match)) {
                $data['cedente']['denominazione'] = trim($match[1]);
            }
            if (preg_match('/<IdPaese>(.*?)<\/IdPaese>/i', $cedenteXml, $match)) {
                $data['cedente']['id_paese'] = trim($match[1]);
            }
            if (preg_match('/<IdCodice>(.*?)<\/IdCodice>/i', $cedenteXml, $match)) {
                $data['cedente']['id_codice'] = trim($match[1]);
            }
            if (preg_match('/<Indirizzo>(.*?)<\/Indirizzo>/i', $cedenteXml, $match)) {
                $data['cedente']['indirizzo'] = trim($match[1]);
            }
        }
        
        // DATI COMMITTENTE (CessionarioCommittente)
        if (preg_match('/<CessionarioCommittente>(.*?)<\/CessionarioCommittente>/is', $xmlString, $cessionarioMatch)) {
            $cessionarioXml = $cessionarioMatch[1];
            
            if (preg_match('/<Denominazione>(.*?)<\/Denominazione>/i', $cessionarioXml, $match)) {
                $data['cessionario']['denominazione'] = trim($match[1]);
            }
            if (preg_match('/<Indirizzo>(.*?)<\/Indirizzo>/i', $cessionarioXml, $match)) {
                $data['cessionario']['indirizzo'] = trim($match[1]);
            }
        }
        
        // DATI DOCUMENTO (DatiGeneraliDocumento)
        if (preg_match('/<DatiGeneraliDocumento>(.*?)<\/DatiGeneraliDocumento>/is', $xmlString, $datiGeneraliMatch)) {
            $datiGeneraliXml = $datiGeneraliMatch[1];
            
            if (preg_match('/<TipoDocumento>(.*?)<\/TipoDocumento>/i', $datiGeneraliXml, $match)) {
                $data['documento']['tipo'] = trim($match[1]);
                $data['documento']['tipo_label'] = config('gestionale.tipo_documento.' . $data['documento']['tipo'], $data['documento']['tipo']);
            }
            if (preg_match('/<Numero>(.*?)<\/Numero>/i', $datiGeneraliXml, $match)) {
                $data['documento']['numero'] = trim($match[1]);
            }
            if (preg_match('/<Data>(.*?)<\/Data>/i', $datiGeneraliXml, $match)) {
                $data['documento']['data'] = trim($match[1]);
            }
            if (preg_match('/<Divisa>(.*?)<\/Divisa>/i', $datiGeneraliXml, $match)) {
                $data['documento']['divisa'] = trim($match[1]);
            }
            if (preg_match('/<ImportoTotaleDocumento>(.*?)<\/ImportoTotaleDocumento>/i', $datiGeneraliXml, $match)) {
                $data['documento']['importo_totale'] = trim($match[1]);
            }
        }
        
        // RIGHE FATTURA (DettaglioLinee)
        if (preg_match_all('/<DettaglioLinee>(.*?)<\/DettaglioLinee>/is', $xmlString, $lineeMatches)) {
            foreach ($lineeMatches[1] as $lineaXml) {
                $riga = [];
                
                if (preg_match('/<Descrizione>(.*?)<\/Descrizione>/i', $lineaXml, $match)) {
                    $riga['descrizione'] = trim($match[1]);
                }
                if (preg_match('/<Quantita>(.*?)<\/Quantita>/i', $lineaXml, $match)) {
                    $riga['quantita'] = trim($match[1]);
                }
                if (preg_match('/<PrezzoUnitario>(.*?)<\/PrezzoUnitario>/i', $lineaXml, $match)) {
                    $riga['prezzo_unitario'] = trim($match[1]);
                }
                if (preg_match('/<PrezzoTotale>(.*?)<\/PrezzoTotale>/i', $lineaXml, $match)) {
                    $riga['prezzo_totale'] = trim($match[1]);
                }
                if (preg_match('/<AliquotaIVA>(.*?)<\/AliquotaIVA>/i', $lineaXml, $match)) {
                    $riga['aliquota_iva'] = trim($match[1]);
                }
                
                if (!empty($riga)) {
                    $data['righe'][] = $riga;
                }
            }
        }
        
        // DATI PAGAMENTO (DatiPagamento)
        if (preg_match('/<DatiPagamento>(.*?)<\/DatiPagamento>/is', $xmlString, $pagamentoMatch)) {
            $pagamentoXml = $pagamentoMatch[1];
            
            if (preg_match('/<ModalitaPagamento>(.*?)<\/ModalitaPagamento>/i', $pagamentoXml, $match)) {
                $modalitaCode = trim($match[1]);
                $data['pagamenti']['modalita'] = $modalitaCode;
                $data['pagamenti']['modalita_label'] = config('gestionale.modalita_pagamento.' . $modalitaCode, $modalitaCode);
            }
            if (preg_match('/<ImportoPagamento>(.*?)<\/ImportoPagamento>/i', $pagamentoXml, $match)) {
                $data['pagamenti']['importo'] = trim($match[1]);
            }
            if (preg_match('/<DataScadenzaPagamento>(.*?)<\/DataScadenzaPagamento>/i', $pagamentoXml, $match)) {
                $data['pagamenti']['scadenza'] = trim($match[1]);
            }
            if (preg_match('/<IBAN>(.*?)<\/IBAN>/i', $pagamentoXml, $match)) {
                $data['pagamenti']['iban'] = trim($match[1]);
            }
        }
        
        return $data;
    }
    
    private function generatePdfFromDatabase($invoice)
    {
        $xmlData = $this->buildXmlDataFromDatabase($invoice);
        $xmlData['payments_list'] = $invoice->payments;
        $xmlData['vat_summaries_list'] = $invoice->vatSummaries;
        
        $pdf = Pdf::loadView('admin.invoices-received.xml-pdf', [
            'invoice' => $invoice,
            'xmlData' => $xmlData,
        ]);
        $pdf->setPaper('a4', 'portrait');
        
        $safeFilename = $this->sanitizeFilename('fattura_' . $invoice->n_invoice . '.pdf');
        
        return $pdf->stream($safeFilename);
    }
}