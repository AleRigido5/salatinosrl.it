<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoiceReceived;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceXmlController extends Controller
{
    public function showAsHtml($id)
    {
        Log::info('=== HTML GENERATION START ===', ['invoice_id' => $id]);
        
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
                // Prepara l'XML (aggiunge dichiarazione se mancante, rimuove BOM)
                $xmlContent = $this->prepareXmlString($xmlContent);
                
                // Pulisci i namespace prima del parsing
                $cleanXml = $this->cleanXmlFromNamespaces($xmlContent);
                $xmlData = $this->parseXmlForDisplay($cleanXml);
                
                Log::info('XML processato', ['size' => strlen($cleanXml)]);
            } else {
                // Usa dati dal database
                $xmlData = $this->buildXmlDataFromDatabase($invoice);
                Log::info('Usati dati da database (XML non disponibile)');
            }
            
            // Aggiungi pagamenti e riepilogo IVA ai dati
            $xmlData['payments_list'] = $invoice->payments;
            $xmlData['vat_summaries_list'] = $invoice->vatSummaries;
            
            // RESTITUISCI LA VISTA HTML INVECE DEL PDF
            return view('admin.invoices-received.xml-view', [
                'invoice' => $invoice,
                'xmlData' => $xmlData,
            ]);

        } catch (\Exception $e) {
            Log::error('ERRORE generazione HTML', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            if (isset($invoice) && $invoice) {
                return $this->generateHtmlFromDatabase($invoice);
            }
            
            abort(500, 'Errore generazione HTML: ' . $e->getMessage());
        }
    }
    
    /**
     * Genera HTML dai dati del database (fallback)
     */
    private function generateHtmlFromDatabase($invoice)
    {
        $xmlData = $this->buildXmlDataFromDatabase($invoice);
        $xmlData['payments_list'] = $invoice->payments;
        $xmlData['vat_summaries_list'] = $invoice->vatSummaries;
        
        return view('admin.invoices-received.xml-view', [
            'invoice' => $invoice,
            'xmlData' => $xmlData,
        ]);
    }
    
    /**
     * Prepara la stringa XML aggiungendo la dichiarazione se mancante
     */
    private function normalizeXmlString(string $xmlString): string
    {
        // Verifica se c'è già la dichiarazione XML
        if (preg_match('/^<\?xml/', trim($xmlString))) {
            return $xmlString;
        }
        
        // Aggiungi la dichiarazione XML standard
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $xmlString;
    }
    
    private function sanitizeFilename(string $filename): string
    {
        $invalidChars = ['/', '\\', ':', '*', '?', '"', '<', '>', '|', ' ', "\n", "\r", "\t"];
        $filename = str_replace($invalidChars, '_', $filename);
        $filename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $filename);
        
        if (empty($filename) || $filename === '.' || $filename === '..') {
            $filename = 'fattura.pdf';
        }
        
        return $filename;
    }

    /**
     * Rimuove eventuali BOM (Byte Order Mark) all'inizio del file
     */
    private function removeBom(string $content): string
    {
        $bom = pack('H*','EFBBBF');
        if (str_starts_with($content, $bom)) {
            $content = substr($content, 3);
        }
        return $content;
    }
    
    /**
     * Prepara la stringa XML (normalizza dichiarazione e rimuove BOM)
     */
    private function prepareXmlString(string $content): string
    {
        // Rimuovi eventuale BOM
        $content = $this->removeBom($content);
        
        // Aggiungi dichiarazione XML se mancante
        $content = $this->normalizeXmlString($content);
        
        return $content;
    }
    
    /**
     * Rimuove TUTTI i namespace dall'XML in modo universale
     * Gestisce: ns0:Tag, ns1:Tag, ns2:Tag, ns3:Tag, p:Tag, ecc.
     */
    private function cleanXmlFromNamespaces(string $xmlString): string
    {
        // Se la stringa è vuota, restituiscila
        if (empty($xmlString)) {
            return $xmlString;
        }
        
        // Salva la dichiarazione XML se presente (verrà riaggiunta dopo)
        $xmlDeclaration = '';
        if (preg_match('/^<\?xml[^?]+\?>/', trim($xmlString), $declaration)) {
            $xmlDeclaration = $declaration[0];
            $xmlString = preg_replace('/^<\?xml[^?]+\?>/', '', trim($xmlString));
        }
        
        // Rimuovi tutte le dichiarazioni xmlns (con apici doppi)
        $xmlString = preg_replace('/\s+xmlns(?::\w+)?="[^"]*"/', '', $xmlString);
        
        // Rimuovi dichiarazioni xmlns (con apici singoli)
        $xmlString = preg_replace("/\s+xmlns(?::\w+)?='[^']*'/", '', $xmlString);
        
        // Rimuovi prefissi dai tag di apertura e chiusura (es. ns0:Tag -> Tag)
        $xmlString = preg_replace('/(<\/?)[a-zA-Z0-9]+:/', '$1', $xmlString);
        
        // Rimuovi eventuali istruzioni xml-stylesheet
        $xmlString = preg_replace('/<\?xml-stylesheet[^?]*\?>/', '', $xmlString);
        
        // Rimuovi eventuali spazi doppi rimasti
        $xmlString = preg_replace('/\s+/', ' ', $xmlString);
        
        // Riaggiungi la dichiarazione XML se presente
        if ($xmlDeclaration) {
            // Assicurati che non ci siano doppie dichiarazioni
            if (!str_contains($xmlString, '<?xml')) {
                $xmlString = $xmlDeclaration . "\n" . $xmlString;
            }
        }
        
        return trim($xmlString);
    }
    
    /**
     * Recupera il contenuto XML in modo sicuro
     */
    private function getXmlContentSafely($invoice): ?string
    {
        $xmlContent = DB::table('invoices_received')
            ->where('id', $invoice->id)
            ->value('xml_content');
        
        if ($xmlContent) {
            // Prepara l'XML (aggiunge dichiarazione se mancante, rimuove BOM)
            $xmlContent = $this->prepareXmlString($xmlContent);
            
            // Rimuovi gli allegati
            $xmlContent = $this->removeAttachmentsFromXml($xmlContent);
            
            return $xmlContent;
        }
        
        return null;
    }
    
    /**
     * Rimuove gli allegati dall'XML
     */
    private function removeAttachmentsFromXml(string $xmlString): string
    {
        $xmlString = preg_replace('/<Allegati>.*?<\/Allegati>/is', '', $xmlString);
        $xmlString = preg_replace('/<Allegato>.*?<\/Allegato>/is', '', $xmlString);
        $xmlString = preg_replace('/<FatturaFirmata>.*?<\/FatturaFirmata>/is', '', $xmlString);
        return $xmlString;
    }
    
    /**
     * Costruisce i dati dall'XML usando regex (XML già pulito dai namespace)
     */
    private function parseXmlForDisplay(string $xmlString): array
    {
        // Verifica che l'XML non sia vuoto
        if (empty(trim($xmlString))) {
            Log::warning('XML vuoto dopo la pulizia');
            return $this->getEmptyData();
        }

        // ============================================
        // RIMUOVI I MARKER CDATA PRIMA DEL PARSING
        // ============================================
        // Sostituisce <![CDATA[contenuto]]> con 'contenuto' (senza i marker)
        $xmlString = preg_replace('/<!\[CDATA\[(.*?)\]\]>/s', '$1', $xmlString);
        
        $data = $this->getEmptyData();
        
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
            if (preg_match('/<CAP>(.*?)<\/CAP>/i', $cedenteXml, $match)) {
                $data['cedente']['cap'] = trim($match[1]);
            }
            if (preg_match('/<Comune>(.*?)<\/Comune>/i', $cedenteXml, $match)) {
                $data['cedente']['comune'] = trim($match[1]);
            }
            if (preg_match('/<Provincia>(.*?)<\/Provincia>/i', $cedenteXml, $match)) {
                $data['cedente']['provincia'] = trim($match[1]);
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
            if (preg_match('/<CAP>(.*?)<\/CAP>/i', $cessionarioXml, $match)) {
                $data['cessionario']['cap'] = trim($match[1]);
            }
            if (preg_match('/<Comune>(.*?)<\/Comune>/i', $cessionarioXml, $match)) {
                $data['cessionario']['comune'] = trim($match[1]);
            }
            if (preg_match('/<Provincia>(.*?)<\/Provincia>/i', $cessionarioXml, $match)) {
                $data['cessionario']['provincia'] = trim($match[1]);
            }
        }
        
        // DATI DOCUMENTO
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
        
        // RIGHE FATTURA
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
        
        // DATI PAGAMENTO
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
    
    /**
     * Restituisce una struttura dati vuota
     */
    private function getEmptyData(): array
    {
        return [
            'cedente' => [],
            'cessionario' => [],
            'documento' => [],
            'righe' => [],
            'pagamenti' => [],
        ];
    }
    
    /**
     * Costruisce i dati dal database (fallback)
     */
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
    

    /**
     * Genera PDF dai dati del database (fallback)
     */
    private function generatePdfFromDatabase($invoice)
    {
        $xmlData = $this->buildXmlDataFromDatabase($invoice);
        $xmlData['payments_list'] = $invoice->payments;
        $xmlData['vat_summaries_list'] = $invoice->vatSummaries;
        
        $pdf = Pdf::loadView('admin.invoices-received.xml-view', [
            'invoice' => $invoice,
            'xmlData' => $xmlData,
        ]);
        $pdf->setPaper('a4', 'portrait');
        
        $safeFilename = $this->sanitizeFilename('fattura_' . $invoice->n_invoice . '.pdf');
        
        return $pdf->stream($safeFilename);
    }
}