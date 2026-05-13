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
                $xmlData = $this->parseXmlForDisplay($xmlContent);
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
            
            // Sanitizza il nome del file (rimuove caratteri non validi come / \ : * ? " < > |)
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
        // Rimuovi namespace per semplificare il parsing
        $cleanXml = preg_replace('/(<\/?)[\w]+:/', '$1', $xmlString);
        $cleanXml = preg_replace('/\s+xmlns(?::\w+)?="[^"]*"/', '', $cleanXml);
        
        $data = [
            'cedente' => [],
            'cessionario' => [],
            'documento' => [],
            'righe' => [],
            'pagamenti' => [],
        ];
        
        // DATI FORNITORE (CedentePrestatore)
        if (preg_match('/<CedentePrestatore>(.*?)<\/CedentePrestatore>/is', $cleanXml, $cedenteMatch)) {
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
        if (preg_match('/<CessionarioCommittente>(.*?)<\/CessionarioCommittente>/is', $cleanXml, $cessionarioMatch)) {
            $cessionarioXml = $cessionarioMatch[1];
            if (preg_match('/<Denominazione>(.*?)<\/Denominazione>/i', $cessionarioXml, $match)) {
                $data['cessionario']['denominazione'] = trim($match[1]);
            }
            if (preg_match('/<Indirizzo>(.*?)<\/Indirizzo>/i', $cessionarioXml, $match)) {
                $data['cessionario']['indirizzo'] = trim($match[1]);
            }
        }
        
        // DATI DOCUMENTO
        if (preg_match('/<DatiGeneraliDocumento>(.*?)<\/DatiGeneraliDocumento>/is', $cleanXml, $datiGeneraliMatch)) {
            $datiGeneraliXml = $datiGeneraliMatch[1];
            if (preg_match('/<Numero>(.*?)<\/Numero>/i', $datiGeneraliXml, $match)) {
                $data['documento']['numero'] = trim($match[1]);
            }
            if (preg_match('/<Data>(.*?)<\/Data>/i', $datiGeneraliXml, $match)) {
                $data['documento']['data'] = trim($match[1]);
            }
            if (preg_match('/<Divisa>(.*?)<\/Divisa>/i', $datiGeneraliXml, $match)) {
                $data['documento']['divisa'] = trim($match[1]);
            }
        }
        
        // RIGHE FATTURA
        if (preg_match_all('/<DettaglioLinee>(.*?)<\/DettaglioLinee>/is', $cleanXml, $lineeMatches)) {
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
        
        // Sanitizza il nome del file
        $safeFilename = $this->sanitizeFilename('fattura_' . $invoice->n_invoice . '.pdf');
        
        return $pdf->stream($safeFilename);
    }
}