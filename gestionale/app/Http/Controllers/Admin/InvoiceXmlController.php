<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoiceReceived;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InvoiceXmlController extends Controller
{
    /**
     * Visualizza l'XML della fattura come PDF
     */
    public function showAsPdf($id)
    {
        $invoice = InvoiceReceived::with(['entity', 'ownership', 'rows.costCenter'])->findOrFail($id);
        
        $xmlContent = $invoice->xml_content;
        
        if (!$xmlContent) {
            abort(404, 'Contenuto XML non trovato per la fattura ID: ' . $invoice->id);
        }
        
        // Parsing XML per estrarre i dati formattati
        $xmlData = $this->parseXmlForDisplay($xmlContent);
        
        // Genera il PDF
        $pdf = Pdf::loadView('admin.invoices-received.xml-pdf', [
            'invoice' => $invoice,
            'xmlData' => $xmlData,
            'xmlContent' => $xmlContent
        ]);
        
        $pdf->setPaper('a4', 'portrait');
        
        return $pdf->stream('fattura_' . $invoice->n_invoice . '.pdf');
    }
    
    /**
     * Visualizza l'XML in formato HTML (alternativa al PDF)
     */
    public function showAsHtml($id)
    {
        $invoice = InvoiceReceived::with(['entity', 'ownership', 'rows.costCenter'])->findOrFail($id);
        
        $xmlContent = $invoice->xml_content;
        
        if (!$xmlContent) {
            abort(404, 'File XML non trovato');
        }
        
        $xmlData = $this->parseXmlForDisplay($xmlContent);
        
        return view('admin.invoices-received.xml-view', [
            'invoice' => $invoice,
            'xmlData' => $xmlData,
            'xmlContent' => htmlspecialchars($xmlContent)
        ]);
    }
    
    /**
     * Parsing XML per estrarre dati formattati
     */
    private function parseXmlForDisplay($xmlString)
    {
        // Rimuovi i namespace per semplificare il parsing
        $cleanXml = preg_replace('/(<\/?)[\w]+:/', '$1', $xmlString);
        $cleanXml = preg_replace('/\s+xmlns(?::\w+)?="[^"]*"/', '', $cleanXml);
        
        $naturaConfig = config('gestionale.natura_operazione', []);
        
        $data = [
            'cedente' => [],
            'cessionario' => [],
            'documento' => [],
            'righe' => [],
            'pagamenti' => [],
            'bollo' => 0,
            'arrotondamento' => 0
        ];
        
        // DATI FORNITORE (CedentePrestatore)
        if (preg_match('/<CedentePrestatore>(.*?)<\/CedentePrestatore>/is', $cleanXml, $cedenteMatch)) {
            $cedenteXml = $cedenteMatch[1];
            
            // Denominazione
            if (preg_match('/<Denominazione>(.*?)<\/Denominazione>/i', $cedenteXml, $match)) {
                $data['cedente']['denominazione'] = trim($match[1]);
            }
            
            // Partita IVA
            $idPaese = '';
            $idCodice = '';
            if (preg_match('/<IdPaese>(.*?)<\/IdPaese>/i', $cedenteXml, $match)) $idPaese = trim($match[1]);
            if (preg_match('/<IdCodice>(.*?)<\/IdCodice>/i', $cedenteXml, $match)) $idCodice = trim($match[1]);
            if (!empty($idPaese) && !empty($idCodice)) {
                $data['cedente']['id_paese'] = $idPaese;
                $data['cedente']['id_codice'] = $idCodice;
            }
            
            // Codice Fiscale
            if (preg_match('/<CodiceFiscale>(.*?)<\/CodiceFiscale>/i', $cedenteXml, $match)) {
                $data['cedente']['codice_fiscale'] = trim($match[1]);
            }
            
            // Sede
            if (preg_match('/<Sede>(.*?)<\/Sede>/is', $cedenteXml, $sedeMatch)) {
                $sedeXml = $sedeMatch[1];
                if (preg_match('/<Indirizzo>(.*?)<\/Indirizzo>/i', $sedeXml, $match)) $data['cedente']['indirizzo'] = trim($match[1]);
                if (preg_match('/<CAP>(.*?)<\/CAP>/i', $sedeXml, $match)) $data['cedente']['cap'] = trim($match[1]);
                if (preg_match('/<Comune>(.*?)<\/Comune>/i', $sedeXml, $match)) $data['cedente']['comune'] = trim($match[1]);
                if (preg_match('/<Provincia>(.*?)<\/Provincia>/i', $sedeXml, $match)) $data['cedente']['provincia'] = trim($match[1]);
            }
            
            // Contatti
            if (preg_match('/<Contatti>(.*?)<\/Contatti>/is', $cedenteXml, $contattiMatch)) {
                $contattiXml = $contattiMatch[1];
                if (preg_match('/<Telefono>(.*?)<\/Telefono>/i', $contattiXml, $match)) $data['cedente']['telefono'] = trim($match[1]);
                if (preg_match('/<Email>(.*?)<\/Email>/i', $contattiXml, $match)) $data['cedente']['email'] = trim($match[1]);
            }
        }
        
        // DATI COMMITTENTE (CessionarioCommittente)
        if (preg_match('/<CessionarioCommittente>(.*?)<\/CessionarioCommittente>/is', $cleanXml, $cessionarioMatch)) {
            $cessionarioXml = $cessionarioMatch[1];
            
            if (preg_match('/<Denominazione>(.*?)<\/Denominazione>/i', $cessionarioXml, $match)) {
                $data['cessionario']['denominazione'] = trim($match[1]);
            }
            
            $idPaese = '';
            $idCodice = '';
            if (preg_match('/<IdPaese>(.*?)<\/IdPaese>/i', $cessionarioXml, $match)) $idPaese = trim($match[1]);
            if (preg_match('/<IdCodice>(.*?)<\/IdCodice>/i', $cessionarioXml, $match)) $idCodice = trim($match[1]);
            if (!empty($idPaese) && !empty($idCodice)) {
                $data['cessionario']['id_paese'] = $idPaese;
                $data['cessionario']['id_codice'] = $idCodice;
            }
            
            if (preg_match('/<CodiceFiscale>(.*?)<\/CodiceFiscale>/i', $cessionarioXml, $match)) {
                $data['cessionario']['codice_fiscale'] = trim($match[1]);
            }
            
            if (preg_match('/<Sede>(.*?)<\/Sede>/is', $cessionarioXml, $sedeMatch)) {
                $sedeXml = $sedeMatch[1];
                if (preg_match('/<Indirizzo>(.*?)<\/Indirizzo>/i', $sedeXml, $match)) $data['cessionario']['indirizzo'] = trim($match[1]);
                if (preg_match('/<CAP>(.*?)<\/CAP>/i', $sedeXml, $match)) $data['cessionario']['cap'] = trim($match[1]);
                if (preg_match('/<Comune>(.*?)<\/Comune>/i', $sedeXml, $match)) $data['cessionario']['comune'] = trim($match[1]);
                if (preg_match('/<Provincia>(.*?)<\/Provincia>/i', $sedeXml, $match)) $data['cessionario']['provincia'] = trim($match[1]);
            }
        }
        
        // DATI DOCUMENTO
        if (preg_match('/<DatiGeneraliDocumento>(.*?)<\/DatiGeneraliDocumento>/is', $cleanXml, $datiGeneraliMatch)) {
            $datiGeneraliXml = $datiGeneraliMatch[1];
            
            if (preg_match('/<TipoDocumento>(.*?)<\/TipoDocumento>/i', $datiGeneraliXml, $match)) {
                $data['documento']['tipo'] = trim($match[1]);
                $data['documento']['tipo_label'] = config('gestionale.tipo_documento.' . $data['documento']['tipo'], $data['documento']['tipo']);
            }
            if (preg_match('/<Divisa>(.*?)<\/Divisa>/i', $datiGeneraliXml, $match)) $data['documento']['divisa'] = trim($match[1]);
            if (preg_match('/<Data>(.*?)<\/Data>/i', $datiGeneraliXml, $match)) $data['documento']['data'] = trim($match[1]);
            if (preg_match('/<Numero>(.*?)<\/Numero>/i', $datiGeneraliXml, $match)) $data['documento']['numero'] = trim($match[1]);
            if (preg_match('/<Causale>(.*?)<\/Causale>/i', $datiGeneraliXml, $match)) $data['documento']['causale'] = trim($match[1]);
            if (preg_match('/<Arrotondamento>(.*?)<\/Arrotondamento>/i', $datiGeneraliXml, $match)) {
                $data['arrotondamento'] = floatval(str_replace(',', '.', trim($match[1])));
            }
        }
        
        // RIGHE FATTURA
        if (preg_match_all('/<DettaglioLinee>(.*?)<\/DettaglioLinee>/is', $cleanXml, $lineeMatches)) {
            foreach ($lineeMatches[1] as $lineaXml) {
                $riga = [];
                
                // Codice Articolo
                if (preg_match_all('/<CodiceArticolo>(.*?)<\/CodiceArticolo>/is', $lineaXml, $codiceMatches)) {
                    $riga['codice_articolo'] = [];
                    foreach ($codiceMatches[1] as $codiceXml) {
                        $codiceTipo = '';
                        $codiceValore = '';
                        if (preg_match('/<CodiceTipo>(.*?)<\/CodiceTipo>/i', $codiceXml, $tipoMatch)) $codiceTipo = trim($tipoMatch[1]);
                        if (preg_match('/<CodiceValore>(.*?)<\/CodiceValore>/i', $codiceXml, $valoreMatch)) $codiceValore = trim($valoreMatch[1]);
                        if (!empty($codiceTipo) || !empty($codiceValore)) {
                            $riga['codice_articolo'][] = ['tipo' => $codiceTipo, 'valore' => $codiceValore];
                        }
                    }
                }
                
                // Descrizione
                if (preg_match('/<Descrizione>(.*?)<\/Descrizione>/i', $lineaXml, $match)) $riga['descrizione'] = trim($match[1]);
                
                // Quantità
                if (preg_match('/<Quantita>(.*?)<\/Quantita>/i', $lineaXml, $match)) $riga['quantita'] = trim($match[1]);
                
                // Unità di Misura
                if (preg_match('/<UnitaMisura>(.*?)<\/UnitaMisura>/i', $lineaXml, $match)) $riga['unita_misura'] = trim($match[1]);
                
                // Prezzo Unitario
                if (preg_match('/<PrezzoUnitario>(.*?)<\/PrezzoUnitario>/i', $lineaXml, $match)) $riga['prezzo_unitario'] = trim($match[1]);
                
                // Prezzo Totale
                if (preg_match('/<PrezzoTotale>(.*?)<\/PrezzoTotale>/i', $lineaXml, $match)) $riga['prezzo_totale'] = trim($match[1]);
                
                // Aliquota IVA
                if (preg_match('/<AliquotaIVA>(.*?)<\/AliquotaIVA>/i', $lineaXml, $match)) $riga['aliquota_iva'] = trim($match[1]);
                
                // Sconto
                if (preg_match('/<Sconto>(.*?)<\/Sconto>/i', $lineaXml, $match)) $riga['sconto'] = trim($match[1]);
                
                // Natura
                if (preg_match('/<Natura>(.*?)<\/Natura>/i', $lineaXml, $match)) {
                    $naturaCode = trim($match[1]);
                    $riga['natura'] = $naturaCode;
                    $riga['natura_label'] = $naturaConfig[$naturaCode] ?? '';
                }
                
                // Riferimento Amministrativo
                if (preg_match('/<RiferimentoAmministrativo>(.*?)<\/RiferimentoAmministrativo>/i', $lineaXml, $match)) {
                    $riga['riferimento_amministrativo'] = trim($match[1]);
                }
                
                if (!empty($riga)) {
                    $data['righe'][] = $riga;
                }
            }
        }
        
        // PAGAMENTI
        if (preg_match('/<DatiPagamento>(.*?)<\/DatiPagamento>/is', $cleanXml, $pagamentoMatch)) {
            $pagamentoXml = $pagamentoMatch[1];
            
            if (preg_match('/<CondizioniPagamento>(.*?)<\/CondizioniPagamento>/i', $pagamentoXml, $match)) {
                $data['pagamenti']['condizioni'] = trim($match[1]);
            }
            if (preg_match('/<ModalitaPagamento>(.*?)<\/ModalitaPagamento>/i', $pagamentoXml, $match)) {
                $modalita = trim($match[1]);
                $data['pagamenti']['modalita'] = $modalita;
                $data['pagamenti']['modalita_label'] = config('gestionale.modalita_pagamento.' . $modalita, $modalita);
            }
            if (preg_match('/<IBAN>(.*?)<\/IBAN>/i', $pagamentoXml, $match)) $data['pagamenti']['iban'] = trim($match[1]);
            if (preg_match('/<IstitutoFinanziario>(.*?)<\/IstitutoFinanziario>/i', $pagamentoXml, $match)) $data['pagamenti']['istituto'] = trim($match[1]);
            if (preg_match('/<ImportoPagamento>(.*?)<\/ImportoPagamento>/i', $pagamentoXml, $match)) $data['pagamenti']['importo'] = trim($match[1]);
            if (preg_match('/<DataScadenzaPagamento>(.*?)<\/DataScadenzaPagamento>/i', $pagamentoXml, $match)) $data['pagamenti']['scadenza'] = trim($match[1]);
        }
        
        return $data;
    }
}