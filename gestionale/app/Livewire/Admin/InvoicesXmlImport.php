<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\InvoiceReceived;
use App\Models\InvoiceRow;
use App\Models\Ownership;
use App\Models\Entity;
use App\Models\CostCenter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InvoicesXmlImport extends Component
{
    use WithFileUploads;

    public $xml_file;
    public $xml_filename;
    public $xml_content;
    public $file_hash;
    public $payments = [];
    public $vatSummaries = [];
    public $xml_parsed = false;
    public $extracted_attachments = [];
    
    public $id_ownership;
    public $id_entities;
    public $type_invoice = '';
    public $n_invoice = '';
    public $data_invoice = '';
    public $importo_totale = 0;
    public $causale = '';
    public $divisa = 'EUR';
    public $status = 'bozza';
    public $sdi_id = '';
    
    public $committente_denominazione = '';
    public $committente_partita_iva = '';
    public $committente_codice_fiscale = '';
    public $committente_indirizzo = '';
    public $committente_cap = '';
    public $committente_comune = '';
    public $committente_provincia = '';
    public $committente_nazione = '';
    
    public $fornitore_denominazione = '';
    public $fornitore_partita_iva = '';
    public $fornitore_codice_fiscale = '';
    public $fornitore_indirizzo = '';
    public $fornitore_cap = '';
    public $fornitore_comune = '';
    public $fornitore_provincia = '';
    public $fornitore_nazione = '';
    public $fornitore_telefono = '';
    public $fornitore_email = '';
    
    public $supplier_found = false;
    public $supplier_not_found = false;
    public $supplier_display = '';
    public $supplier_created_by_system = false;
    public $ownership_display = '';

    public $rows = [];
    
    public $cost_center_all_search = '';
    public $cost_center_all_results = [];
    public $show_cost_center_all_dropdown = false;
    public $row_cost_center_search = [];
    public $row_cost_center_results = [];
    public $show_row_cost_center_dropdown = [];
    
    public $all_ownerships = [];
    public $all_costCenters = [];

    protected $rules = [
        'id_entities' => 'required|exists:entities,id_cliente',
        'id_ownership' => 'nullable|exists:ownership,id_proprieta',
        'type_invoice' => 'required|string|max:10',
        'n_invoice' => 'required|string|max:100',
        'data_invoice' => 'required|date',
        'importo_totale' => 'nullable|numeric|min:0',
        'causale' => 'nullable|string',
        'divisa' => 'required|string|size:3',
        'status' => 'required|string',
        'sdi_id' => 'nullable|string|max:100',
        'rows.*.description' => 'required|string',
        'rows.*.quantity' => 'required|numeric|min:0',
        'rows.*.unit_price' => 'required|numeric|min:0',
        'rows.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
    ];

    public function mount()
    {
        $this->loadAllData();
    }

    public function loadAllData()
    {
        $this->all_costCenters = CostCenter::where('valid', 1)
            ->select('id', 'Nome as name')
            ->orderBy('Nome')
            ->get()
            ->toArray();
    }

    // ============================================
    // AUTOCOMPLETE CENTRO DI COSTO GLOBALE
    // ============================================
    public function updatedCostCenterAllSearch()
    {
        if (empty($this->cost_center_all_search)) {
            $this->cost_center_all_results = [];
            $this->show_cost_center_all_dropdown = false;
            return;
        }

        $search = strtolower($this->cost_center_all_search);
        $this->cost_center_all_results = array_values(array_slice(
            array_filter($this->all_costCenters, fn($cc) => str_contains(strtolower($cc['name']), $search)),
            0, 10
        ));
        $this->show_cost_center_all_dropdown = !empty($this->cost_center_all_results);
    }

    public function applyCostCenterToAllRows($id)
    {
        $cc = collect($this->all_costCenters)->firstWhere('id', (int)$id);
        
        if ($cc) {
            foreach ($this->rows as $index => $row) {
                $this->rows[$index]['id_cost_center'] = (int)$id;
                $this->rows[$index]['cost_center_name'] = $cc['name'];
                $this->row_cost_center_search[$index] = $cc['name'];
                $this->row_cost_center_results[$index] = [];
                $this->show_row_cost_center_dropdown[$index] = false;
            }
            
            $this->cost_center_all_search = $cc['name'];
            $this->cost_center_all_results = [];
            $this->show_cost_center_all_dropdown = false;
            
            $this->dispatch('alert', type: 'success', message: "Centro di costo '{$cc['name']}' applicato a tutte le " . count($this->rows) . " righe");
        }
    }

    // ============================================
    // AUTOCOMPLETE CENTRO DI COSTO PER RIGA
    // ============================================
    public function updatedRowCostCenterSearch($value, $index)
    {
        if (empty($value)) {
            $this->row_cost_center_results[$index] = [];
            $this->show_row_cost_center_dropdown[$index] = false;
            return;
        }

        $search = strtolower($value);
        $this->row_cost_center_results[$index] = array_values(array_slice(
            array_filter($this->all_costCenters, fn($cc) => str_contains(strtolower($cc['name']), $search)),
            0, 10
        ));
        $this->show_row_cost_center_dropdown[$index] = !empty($this->row_cost_center_results[$index]);
    }

    public function selectCostCenterForRow($id, $index)
    {
        $cc = collect($this->all_costCenters)->firstWhere('id', (int)$id);
        
        if ($cc) {
            $this->rows[$index]['id_cost_center'] = (int)$id;
            $this->rows[$index]['cost_center_name'] = $cc['name'];
            $this->row_cost_center_search[$index] = $cc['name'];
            $this->row_cost_center_results[$index] = [];
            $this->show_row_cost_center_dropdown[$index] = false;
            
            $this->dispatch('alert', type: 'success', message: "Centro di costo '{$cc['name']}' applicato alla riga " . ($index + 1));
        }
    }

    // ============================================
    // UPLOAD E PARSING XML
    // ============================================
    public function uploadXml()
    {
        $this->validate([
            'xml_file' => 'required|file|mimes:xml|max:10240',
        ]);

        try {
            $content = file_get_contents($this->xml_file->getRealPath());
            $xml = simplexml_load_string($content);
            
            if ($xml === false) {
                $this->addError('xml_file', 'File XML non valido');
                return;
            }

            $this->xml_filename = $this->xml_file->getClientOriginalName();
            $this->file_hash = hash('sha256', $content);
            
            // PASSO 1: Estrai gli allegati usando SimpleXML
            $attachments = $this->extractAttachmentsUsingSimpleXML($xml);
            $this->extracted_attachments = $attachments;
            
            // PASSO 2: Pulisci l'XML per il parsing
            $cleanXmlContent = $this->removeAttachmentsFromXml($content);
            $cleanXml = simplexml_load_string($cleanXmlContent);
            
            // PASSO 3: Parsing dell'XML pulito
            $this->parseXmlInvoiceRobusto($cleanXml);
            
            // PASSO 4: Rinomina la cartella con il nome del fornitore
            if (!empty($attachments) && !empty($this->fornitore_denominazione)) {
                $this->renameAttachmentsFolderFinal($attachments);
            }
            
            // Verifica duplicati
            if ($this->checkInvoiceExists()) {
                $this->dispatch('alert', [
                    'type' => 'error', 
                    'message' => "❌ FATTURA DUPLICATA! Questa fattura è già stata importata."
                ]);
                return;
            }
            
            $this->xml_parsed = true;
            
            if (!empty($attachments)) {
                $this->dispatch('alert', [
                    'type' => 'info', 
                    'message' => "📎 Estratti " . count($attachments) . " allegati dalla fattura"
                ]);
            }
            
            $this->dispatch('alert', ['type' => 'success', 'message' => 'XML analizzato con successo!']);
            
        } catch (\Exception $e) {
            Log::error('Errore upload XML: ' . $e->getMessage());
            $this->addError('xml_file', 'Errore: ' . $e->getMessage());
        }
    }

    /**
     * Estrae gli allegati usando SimpleXML
     */
    private function extractAttachmentsUsingSimpleXML($xml)
    {
        $savedFiles = [];
        
        // Crea una cartella temporanea
        $tempFolder = 'allegati_fatture/temp_' . $this->file_hash;
        
        // Assicurati che la directory esista (usa public disk)
        if (!Storage::disk('public')->exists($tempFolder)) {
            Storage::disk('public')->makeDirectory($tempFolder, 0755, true);
        }
        
        // Cerca il nodo Allegati (potrebbe essere in FatturaElettronicaBody)
        $allegati = null;
        
        if (isset($xml->FatturaElettronicaBody->Allegati)) {
            $allegati = $xml->FatturaElettronicaBody->Allegati;
        } elseif (isset($xml->FatturaElettronicaBody->Allegato)) {
            // Caso in cui c'è un solo allegato diretto
            $allegati = $xml->FatturaElettronicaBody;
        }
        
        if (!$allegati) {
            Log::warning('Nodo Allegati non trovato nell\'XML');
            return $savedFiles;
        }
        
        // Itera su ogni Allegato
        $allegatoNodes = isset($allegati->Allegato) ? $allegati->Allegato : [$allegati];
        
        foreach ($allegatoNodes as $index => $allegato) {
            try {
                // Estrai nome file
                $fileName = (string)$allegato->NomeAttachment;
                
                // Estrai contenuto Base64
                $base64Content = (string)$allegato->Attachment;
                
                if (empty($base64Content)) {
                    Log::warning('Contenuto Base64 vuoto per allegato', ['fileName' => $fileName]);
                    continue;
                }
                
                // Rimuovi spazi bianchi dal Base64
                $base64Content = preg_replace('/\s+/', '', $base64Content);
                
                Log::info('Decodifica Base64 in corso', [
                    'fileName' => $fileName,
                    'base64_length' => strlen($base64Content)
                ]);
                
                // Decodifica Base64
                $decodedContent = base64_decode($base64Content);
                
                if ($decodedContent === false || strlen($decodedContent) === 0) {
                    Log::error('Decodifica Base64 fallita', ['fileName' => $fileName]);
                    continue;
                }
                
                Log::info('Allegato decodificato', [
                    'fileName' => $fileName,
                    'decoded_size' => strlen($decodedContent)
                ]);
                
                // Genera nome file sicuro
                $safeFileName = $this->getSafeFileName($fileName, $decodedContent, $index);
                
                $filePath = $tempFolder . '/' . $safeFileName;
                
                // Salva il file
                $saved = Storage::disk('public')->put($filePath, $decodedContent);
                
                if ($saved) {
                    // URL pubblico (richiede php artisan storage:link)
                    $publicUrl = Storage::url($filePath);
                    
                    Log::info('Allegato salvato con successo', [
                        'path' => $filePath,
                        'url' => $publicUrl,
                        'size' => strlen($decodedContent)
                    ]);
                    
                    $savedFiles[] = [
                        'original_name' => $fileName,
                        'saved_name' => $safeFileName,
                        'temp_path' => $filePath,
                        'url' => $publicUrl,
                        'size' => strlen($decodedContent),
                        'temp_folder' => $tempFolder
                    ];
                } else {
                    Log::error('Salvataggio allegato fallito', ['path' => $filePath]);
                }
                
            } catch (\Exception $e) {
                Log::error('Errore processamento allegato: ' . $e->getMessage());
            }
        }
        
        return $savedFiles;
    }

    /**
     * Genera un nome file sicuro
     */
    private function getSafeFileName($originalName, $content, $index)
    {
        // Se c'è un nome originale, puliscilo
        if (!empty($originalName)) {
            $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
            $extension = pathinfo($name, PATHINFO_EXTENSION);
            
            // Se l'estensione non corrisponde al contenuto, correggila
            if (strpos($content, '%PDF') === 0 && strtolower($extension) !== 'pdf') {
                $name = pathinfo($name, PATHINFO_FILENAME) . '.pdf';
            }
            
            return $name;
        }
        
        // Nome generico basato sul contenuto
        if (strpos($content, '%PDF') === 0) {
            return 'allegato_' . ($index + 1) . '.pdf';
        }
        
        if (strpos($content, 'PK') === 0) {
            return 'allegato_' . ($index + 1) . '.zip';
        }
        
        return 'allegato_' . ($index + 1) . '.bin';
    }

    /**
     * Rinomina la cartella temporanea con il nome del fornitore
     */
    private function renameAttachmentsFolderFinal($attachments)
    {
        if (empty($attachments)) {
            return;
        }
        
        // Crea un nome cartella basato sul nome del fornitore
        $fornitoreSlug = $this->slugify($this->fornitore_denominazione);
        $pivaFornitore = preg_replace('/[^A-Za-z0-9]/', '', $this->fornitore_partita_iva);
        
        if (!empty($pivaFornitore)) {
            $finalFolderName = $fornitoreSlug . '_' . $pivaFornitore;
        } else {
            $finalFolderName = $fornitoreSlug;
        }
        
        // Cartella definitiva
        $finalFolder = 'allegati_fatture/' . $finalFolderName;
        
        // Ottieni la cartella temporanea dal primo allegato
        $tempFolder = $attachments[0]['temp_folder'] ?? null;
        
        if (!$tempFolder || !Storage::disk('public')->exists($tempFolder)) {
            Log::warning('Cartella temporanea non trovata', ['folder' => $tempFolder]);
            return;
        }
        
        // Crea la cartella finale se non esiste
        if (!Storage::disk('public')->exists($finalFolder)) {
            Storage::disk('public')->makeDirectory($finalFolder, 0755, true);
        }
        
        // Sposta tutti i file dalla cartella temp a quella finale
        $files = Storage::disk('public')->files($tempFolder);
        
        foreach ($files as $file) {
            $fileName = basename($file);
            $newPath = $finalFolder . '/' . $fileName;
            
            // Copia il file
            $copied = Storage::disk('public')->copy($file, $newPath);
            
            if ($copied) {
                Log::info('Allegato spostato', [
                    'from' => $file,
                    'to' => $newPath
                ]);
                
                // Aggiorna il percorso e URL nell'array degli attachments
                foreach ($this->extracted_attachments as &$attachment) {
                    if (isset($attachment['temp_path']) && $attachment['temp_path'] === $file) {
                        $attachment['path'] = $newPath;
                        $attachment['url'] = Storage::url($newPath);
                    }
                }
            } else {
                Log::error('Copia allegato fallita', ['from' => $file, 'to' => $newPath]);
            }
        }
        
        // Elimina la cartella temporanea
        Storage::disk('public')->deleteDirectory($tempFolder);
        Log::info('Cartella temporanea eliminata', ['folder' => $tempFolder]);
    }

    /**
     * Converte una stringa in slug
     */
    private function slugify($text)
    {
        // Replace non letter or digits by _
        $text = preg_replace('~[^\pL\d]+~u', '_', $text);
        
        // Transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        
        // Remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);
        
        // Trim
        $text = trim($text, '_');
        
        // Remove duplicate _
        $text = preg_replace('~-+~', '_', $text);
        
        // Lowercase
        $text = strtolower($text);
        
        if (empty($text)) {
            $text = 'fornitore';
        }
        
        return $text;
    }

    private function checkInvoiceExists()
    {
        if (empty($this->fornitore_partita_iva) || empty($this->n_invoice) || empty($this->data_invoice)) {
            return false;
        }
        
        $exists = InvoiceReceived::whereHas('entity', function($query) {
            $query->where('partita_iva', $this->fornitore_partita_iva);
        })->where('n_invoice', $this->n_invoice)
          ->whereDate('data_invoice', $this->data_invoice)
          ->exists();
        
        if ($exists) {
            Log::warning('Tentativo fattura duplicata', [
                'fornitore_piva' => $this->fornitore_partita_iva,
                'n_fattura' => $this->n_invoice,
                'data_fattura' => $this->data_invoice
            ]);
            return true;
        }
        
        if (!empty($this->file_hash)) {
            $hashExists = InvoiceReceived::where('file_hash', $this->file_hash)->exists();
            if ($hashExists) {
                Log::warning('Tentativo file duplicato', ['hash' => $this->file_hash]);
                return true;
            }
        }
        
        return false;
    }

    private function parseXmlInvoiceRobusto($xml)
    {
        // -------------------------------------------------------
        // Strategia: lavoriamo sull'XML grezzo PRIMA che SimpleXML
        // applichi i namespace, così le regex funzionano sempre.
        // -------------------------------------------------------
        $xmlString = $xml->asXML();

        // Rimuovi tutti i prefissi di namespace dai tag (es. ns0:Tag -> Tag)
        // e rimuovi le dichiarazioni xmlns dai tag di apertura
        $cleanXml = preg_replace('/(<\/?)[\w]+:/', '$1', $xmlString);
        $cleanXml = preg_replace('/\s+xmlns(?::\w+)?="[^"]*"/', '', $cleanXml);

        // Da questo momento lavoriamo su $cleanXml per tutte le regex
        // -------------------------------------------------------

        // DATI COMMITTENTE
        if (preg_match('/<CessionarioCommittente>(.*?)<\/CessionarioCommittente>/is', $cleanXml, $cessionarioMatch)) {
            $cessionarioXml = $cessionarioMatch[1];

            if (preg_match('/<Denominazione>(.*?)<\/Denominazione>/i', $cessionarioXml, $match)) {
                $this->committente_denominazione = trim($match[1]);
            }
            if (empty($this->committente_denominazione)) {
                $nome = $cognome = '';
                if (preg_match('/<Nome>(.*?)<\/Nome>/i', $cessionarioXml, $match)) $nome = trim($match[1]);
                if (preg_match('/<Cognome>(.*?)<\/Cognome>/i', $cessionarioXml, $match)) $cognome = trim($match[1]);
                $this->committente_denominazione = trim("$nome $cognome");
            }

            $this->committente_partita_iva = '';
            if (preg_match('/<IdFiscaleIVA>(.*?)<\/IdFiscaleIVA>/is', $cessionarioXml, $idMatch)) {
                $idXml = $idMatch[1];
                $paese = $codice = '';
                if (preg_match('/<IdPaese>(.*?)<\/IdPaese>/i', $idXml, $m)) $paese = trim($m[1]);
                if (preg_match('/<IdCodice>(.*?)<\/IdCodice>/i', $idXml, $m)) $codice = trim($m[1]);
                $this->committente_partita_iva = $paese . $codice;
            }

            if (preg_match('/<CodiceFiscale>(.*?)<\/CodiceFiscale>/i', $cessionarioXml, $match)) {
                $this->committente_codice_fiscale = trim($match[1]);
            }

            if (preg_match('/<Indirizzo>(.*?)<\/Indirizzo>/i', $cessionarioXml, $match)) $this->committente_indirizzo = trim($match[1]);
            if (preg_match('/<CAP>(.*?)<\/CAP>/i', $cessionarioXml, $match)) $this->committente_cap = trim($match[1]);
            if (preg_match('/<Comune>(.*?)<\/Comune>/i', $cessionarioXml, $match)) $this->committente_comune = trim($match[1]);
            if (preg_match('/<Provincia>(.*?)<\/Provincia>/i', $cessionarioXml, $match)) $this->committente_provincia = trim($match[1]);
            if (preg_match('/<Nazione>(.*?)<\/Nazione>/i', $cessionarioXml, $match)) $this->committente_nazione = trim($match[1]);
        }

        // DATI FORNITORE
        if (preg_match('/<CedentePrestatore>(.*?)<\/CedentePrestatore>/is', $cleanXml, $cedenteMatch)) {
            $cedenteXml = $cedenteMatch[1];

            if (preg_match('/<Denominazione>(.*?)<\/Denominazione>/i', $cedenteXml, $match)) {
                $this->fornitore_denominazione = trim($match[1]);
            }
            if (empty($this->fornitore_denominazione)) {
                $nome = $cognome = '';
                if (preg_match('/<Nome>(.*?)<\/Nome>/i', $cedenteXml, $match)) $nome = trim($match[1]);
                if (preg_match('/<Cognome>(.*?)<\/Cognome>/i', $cedenteXml, $match)) $cognome = trim($match[1]);
                $this->fornitore_denominazione = trim("$nome $cognome");
            }

            $this->fornitore_partita_iva = '';
            if (preg_match('/<IdFiscaleIVA>(.*?)<\/IdFiscaleIVA>/is', $cedenteXml, $idMatch)) {
                $idXml = $idMatch[1];
                $paese = $codice = '';
                if (preg_match('/<IdPaese>(.*?)<\/IdPaese>/i', $idXml, $m)) $paese = trim($m[1]);
                if (preg_match('/<IdCodice>(.*?)<\/IdCodice>/i', $idXml, $m)) $codice = trim($m[1]);
                $this->fornitore_partita_iva = $paese . $codice;
            }

            if (preg_match('/<CodiceFiscale>(.*?)<\/CodiceFiscale>/i', $cedenteXml, $match)) {
                $this->fornitore_codice_fiscale = trim($match[1]);
            }

            if (preg_match('/<Indirizzo>(.*?)<\/Indirizzo>/i', $cedenteXml, $match)) $this->fornitore_indirizzo = trim($match[1]);
            if (preg_match('/<CAP>(.*?)<\/CAP>/i', $cedenteXml, $match)) $this->fornitore_cap = trim($match[1]);
            if (preg_match('/<Comune>(.*?)<\/Comune>/i', $cedenteXml, $match)) $this->fornitore_comune = trim($match[1]);
            if (preg_match('/<Provincia>(.*?)<\/Provincia>/i', $cedenteXml, $match)) $this->fornitore_provincia = trim($match[1]);
            if (preg_match('/<Nazione>(.*?)<\/Nazione>/i', $cedenteXml, $match)) $this->fornitore_nazione = trim($match[1]);

            if (preg_match('/<Contatti>(.*?)<\/Contatti>/is', $cedenteXml, $contattiMatch)) {
                $contattiXml = $contattiMatch[1];
                if (preg_match('/<Telefono>(.*?)<\/Telefono>/i', $contattiXml, $match)) $this->fornitore_telefono = trim($match[1]);
                if (preg_match('/<Email>(.*?)<\/Email>/i', $contattiXml, $match)) $this->fornitore_email = trim($match[1]);
            }
        }

        // DATI FATTURA
        if (preg_match('/<DatiGeneraliDocumento>(.*?)<\/DatiGeneraliDocumento>/is', $cleanXml, $datiGeneraliMatch)) {
            $datiGeneraliXml = $datiGeneraliMatch[1];

            if (preg_match('/<TipoDocumento>(.*?)<\/TipoDocumento>/i', $datiGeneraliXml, $match)) $this->type_invoice = trim($match[1]);
            if (preg_match('/<Divisa>(.*?)<\/Divisa>/i', $datiGeneraliXml, $match)) $this->divisa = trim($match[1]);
            if (preg_match('/<Data>(.*?)<\/Data>/i', $datiGeneraliXml, $match)) $this->data_invoice = trim($match[1]);
            if (preg_match('/<Numero>(.*?)<\/Numero>/i', $datiGeneraliXml, $match)) $this->n_invoice = trim($match[1]);
        }

        // TOTALE: prende ImportoPagamento (somma di tutti i DettaglioPagamento)
        // Fallback su ImportoTotaleDocumento se non trovato
        $importoPagamentoTotale = 0;
        if (preg_match_all('/<ImportoPagamento>(.*?)<\/ImportoPagamento>/i', $cleanXml, $pagMatches)) {
            foreach ($pagMatches[1] as $val) {
                $importoPagamentoTotale += floatval(str_replace(',', '.', trim($val)));
            }
        }
        if ($importoPagamentoTotale > 0) {
            $this->importo_totale = round($importoPagamentoTotale, 2);
        } elseif (preg_match('/<ImportoTotaleDocumento>(.*?)<\/ImportoTotaleDocumento>/i', $cleanXml, $match)) {
            $this->importo_totale = floatval(str_replace(',', '.', trim($match[1])));
        }

        // RIGHE FATTURA - Versione aggiornata con nuovi campi
        $this->rows = [];
        if (preg_match_all('/<DettaglioLinee>(.*?)<\/DettaglioLinee>/is', $cleanXml, $lineeMatches)) {
            foreach ($lineeMatches[1] as $index => $lineaXml) {
                $row = [
                    'description'         => '',
                    'quantity'            => 1,
                    'unit_price'          => 0,
                    'discount_percentage' => 0,
                    'aliquota_iva'        => 0,
                    'id_cost_center'      => null,
                    'cost_center_name'    => '',
                    'codice_articolo'     => [],
                    'unita_misura'        => '',
                    'natura'              => '',
                    'riferimento_amministrativo' => '',
                ];

                // Descrizione
                if (preg_match('/<Descrizione>(.*?)<\/Descrizione>/i', $lineaXml, $match)) {
                    $row['description'] = trim($match[1]);
                }
                
                // Quantità
                if (preg_match('/<Quantita>(.*?)<\/Quantita>/i', $lineaXml, $match)) {
                    $row['quantity'] = floatval(str_replace(',', '.', trim($match[1])));
                }
                
                // Prezzo Unitario
                if (preg_match('/<PrezzoUnitario>(.*?)<\/PrezzoUnitario>/i', $lineaXml, $match)) {
                    $row['unit_price'] = floatval(str_replace(',', '.', trim($match[1])));
                }
                
                // Unità di Misura
                if (preg_match('/<UnitaMisura>(.*?)<\/UnitaMisura>/i', $lineaXml, $match)) {
                    $row['unita_misura'] = trim($match[1]);
                }
                
                // ALIQUOTA IVA - AGGIUNGI QUESTA PARTE
                if (preg_match('/<AliquotaIVA>(.*?)<\/AliquotaIVA>/i', $lineaXml, $match)) {
                    $row['aliquota_iva'] = floatval(str_replace(',', '.', trim($match[1])));
                }

                // Sconto
                if (preg_match('/<ScontoMaggiorazione>(.*?)<\/ScontoMaggiorazione>/is', $lineaXml, $scontoMatch)) {
                    if (preg_match('/<Percentuale>(.*?)<\/Percentuale>/i', $scontoMatch[1], $percMatch)) {
                        $row['discount_percentage'] = floatval(str_replace(',', '.', trim($percMatch[1])));
                    }
                }
                
                // Natura (es. N1, N2, N3, N4, N5, N6, N7)
                if (preg_match('/<Natura>(.*?)<\/Natura>/i', $lineaXml, $match)) {
                    $row['natura'] = trim($match[1]);
                }
                
                // Riferimento Amministrativo
                if (preg_match('/<RiferimentoAmministrativo>(.*?)<\/RiferimentoAmministrativo>/i', $lineaXml, $match)) {
                    $row['riferimento_amministrativo'] = trim($match[1]);
                }
                
                // Codice Articolo (codice tipo e valore)
                if (preg_match_all('/<CodiceArticolo>(.*?)<\/CodiceArticolo>/is', $lineaXml, $codiceMatches)) {
                    foreach ($codiceMatches[1] as $codiceXml) {
                        $codiceTipo = '';
                        $codiceValore = '';
                        if (preg_match('/<CodiceTipo>(.*?)<\/CodiceTipo>/i', $codiceXml, $tipoMatch)) {
                            $codiceTipo = trim($tipoMatch[1]);
                        }
                        if (preg_match('/<CodiceValore>(.*?)<\/CodiceValore>/i', $codiceXml, $valoreMatch)) {
                            $codiceValore = trim($valoreMatch[1]);
                        }
                        if (!empty($codiceTipo) || !empty($codiceValore)) {
                            $row['codice_articolo'][] = [
                                'tipo' => $codiceTipo,
                                'valore' => $codiceValore
                            ];
                        }
                    }
                }

                $this->rows[] = $row;
            }
        }

        // CERCA FORNITORE NEL DB
        $this->supplier_found = false;
        $this->supplier_not_found = false;

        if (!empty($this->fornitore_partita_iva)) {
            // Cerca con e senza prefisso paese (es. "IT02701740108" o "02701740108")
            $entity = Entity::where('partita_iva', $this->fornitore_partita_iva)
                ->orWhere('partita_iva', preg_replace('/^[A-Z]{2}/i', '', $this->fornitore_partita_iva))
                ->first();

            if ($entity) {
                $this->supplier_found = true;
                $this->id_entities = $entity->id_cliente;
                $this->supplier_display = $entity->ragione_sociale ?? ($entity->nome . ' ' . $entity->cognome);
                $this->supplier_created_by_system = $entity->created_by_system ?? false;
                if (!empty($entity->partita_iva)) {
                    $this->supplier_display .= ' (P.IVA: ' . $entity->partita_iva . ')';
                }
            }
        }

        if (!$this->supplier_found && !empty($this->fornitore_codice_fiscale)) {
            $entity = Entity::where('codice_fiscale', $this->fornitore_codice_fiscale)->first();
            if ($entity) {
                $this->supplier_found = true;
                $this->id_entities = $entity->id_cliente;
                $this->supplier_display = $entity->ragione_sociale ?? ($entity->nome . ' ' . $entity->cognome);
                $this->supplier_created_by_system = $entity->created_by_system ?? false;
            }
        }

        if (!$this->supplier_found) {
            $this->supplier_not_found = true;
            $this->supplier_display = $this->fornitore_denominazione;
            if (!empty($this->fornitore_partita_iva)) {
                $this->supplier_display .= ' (P.IVA: ' . $this->fornitore_partita_iva . ')';
            }
        }

        // CERCA PROPRIETÀ
        if (!empty($this->committente_partita_iva)) {
            $ownership = Ownership::where('PivaPr', $this->committente_partita_iva)->first();

            if (!$ownership) {
                $pivaNoPrefix = preg_replace('/^[A-Z]{2}/i', '', $this->committente_partita_iva);
                $ownership = Ownership::where('PivaPr', $pivaNoPrefix)->first();
            }

            if ($ownership) {
                $this->id_ownership = $ownership->id_proprieta;
                $this->ownership_display = $ownership->Rag_Soc_intest ?: $ownership->RagSocialePr;
            }
        }

        // ============================================
        // ESTRAZIONE PAGAMENTI
        // ============================================
        $this->payments = [];
        if (preg_match_all('/<DettaglioPagamento>(.*?)<\/DettaglioPagamento>/is', $cleanXml, $dettaglioMatches)) {
            foreach ($dettaglioMatches[1] as $dettaglioXml) {
                $payment = [
                    'due_date' => null,
                    'amount' => 0,
                    'payment_method' => null,
                    'iban' => null,
                ];
                
                if (preg_match('/<DataScadenzaPagamento>(.*?)<\/DataScadenzaPagamento>/i', $dettaglioXml, $match)) {
                    $payment['due_date'] = trim($match[1]);
                }
                if (preg_match('/<ImportoPagamento>(.*?)<\/ImportoPagamento>/i', $dettaglioXml, $match)) {
                    $payment['amount'] = floatval(str_replace(',', '.', trim($match[1])));
                }
                if (preg_match('/<ModalitaPagamento>(.*?)<\/ModalitaPagamento>/i', $dettaglioXml, $match)) {
                    $payment['payment_method'] = trim($match[1]);
                }
                if (preg_match('/<IBAN>(.*?)<\/IBAN>/i', $dettaglioXml, $match)) {
                    $payment['iban'] = trim($match[1]);
                }
                
                $this->payments[] = $payment;
            }
        }

        // ============================================
        // ESTRAZIONE RIEPILOGO IVA
        // ============================================
        $this->vatSummaries = [];
        if (preg_match_all('/<DatiRiepilogo>(.*?)<\/DatiRiepilogo>/is', $cleanXml, $riepilogoMatches)) {
            foreach ($riepilogoMatches[1] as $riepilogoXml) {
                $summary = [
                    'tax_rate' => 0,
                    'sdi_nature' => null,
                    'taxable_amount' => 0,
                    'tax_amount' => 0,
                    'vat_law_reference' => null,
                    'esigibilita_iva' => 'I',
                ];
                
                if (preg_match('/<AliquotaIVA>(.*?)<\/AliquotaIVA>/i', $riepilogoXml, $match)) {
                    $summary['tax_rate'] = floatval(str_replace(',', '.', trim($match[1])));
                }
                if (preg_match('/<Natura>(.*?)<\/Natura>/i', $riepilogoXml, $match)) {
                    $summary['sdi_nature'] = trim($match[1]);
                }
                if (preg_match('/<ImponibileImporto>(.*?)<\/ImponibileImporto>/i', $riepilogoXml, $match)) {
                    $summary['taxable_amount'] = floatval(str_replace(',', '.', trim($match[1])));
                }
                if (preg_match('/<Imposta>(.*?)<\/Imposta>/i', $riepilogoXml, $match)) {
                    $summary['tax_amount'] = floatval(str_replace(',', '.', trim($match[1])));
                }
                if (preg_match('/<RiferimentoNormativo>(.*?)<\/RiferimentoNormativo>/i', $riepilogoXml, $match)) {
                    $summary['vat_law_reference'] = trim($match[1]);
                }
                if (preg_match('/<EsigibilitaIVA>(.*?)<\/EsigibilitaIVA>/i', $riepilogoXml, $match)) {
                    $summary['esigibilita_iva'] = trim($match[1]);
                }
                
                $this->vatSummaries[] = $summary;
            }
        }

        $this->status = 'bozza';

        // Non ricalcolare il totale dalle righe: lo abbiamo già preso da ImportoPagamento
        // $this->calculateTotal(); // <-- commentato intenzionalmente

        // INIZIALIZZA ARRAY PER AUTOCOMPLETE
        $this->row_cost_center_search = [];
        $this->row_cost_center_results = [];
        $this->show_row_cost_center_dropdown = [];

        foreach ($this->rows as $index => $row) {
            $this->row_cost_center_search[$index] = $row['cost_center_name'] ?? '';
            $this->row_cost_center_results[$index] = [];
            $this->show_row_cost_center_dropdown[$index] = false;
        }

        $this->cost_center_all_search = '';
        $this->cost_center_all_results = [];
        $this->show_cost_center_all_dropdown = false;
    }

    /**
     * Ottiene l'etichetta della natura operazione
     */
    public function getNaturaLabel($natura)
    {
        return config('gestionale.natura_operazione.' . $natura, '');
    }

    private function removeAttachmentsFromXml($xmlString)
    {
        // Rimuovi il nodo Allegati con tutto il suo contenuto
        $xmlString = preg_replace('/<Allegati>.*?<\/Allegati>/is', '', $xmlString);
        $xmlString = preg_replace('/<Allegato>.*?<\/Allegato>/is', '', $xmlString);
        $xmlString = preg_replace('/<FatturaFirmata>.*?<\/FatturaFirmata>/is', '', $xmlString);
        $xmlString = preg_replace('/\n\s*\n/', "\n", $xmlString);
        return $xmlString;
    }

    public function createSupplierAutomatically()
    {
        if (empty($this->fornitore_denominazione)) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Nessun dato fornitore disponibile']);
            return;
        }
        
        try {
            $existingEntity = Entity::where('partita_iva', $this->fornitore_partita_iva)->first();
            if ($existingEntity) {
                $this->id_entities = $existingEntity->id_cliente;
                $this->supplier_found = true;
                $this->supplier_not_found = false;
                $this->dispatch('alert', ['type' => 'info', 'message' => 'Fornitore già esistente, riutilizzato']);
                return;
            }
            
            $newEntity = Entity::create([
                'entity_type' => 'fornitore',
                'ragione_sociale' => $this->fornitore_denominazione,
                'nome' => $this->fornitore_denominazione,
                'indirizzo' => $this->fornitore_indirizzo,
                'cap' => $this->fornitore_cap,
                'comune' => $this->fornitore_comune,
                'provincia' => $this->fornitore_provincia,
                'partita_iva' => $this->fornitore_partita_iva,
                'codice_fiscale' => $this->fornitore_codice_fiscale,
                'telefono' => $this->fornitore_telefono,
                'email' => $this->fornitore_email,
                'valid' => 1,
                'created_by_system' => 1,
            ]);
            
            $this->id_entities = $newEntity->id_cliente;
            $this->supplier_display = $this->fornitore_denominazione;
            if (!empty($this->fornitore_partita_iva)) {
                $this->supplier_display .= ' (P.IVA: ' . $this->fornitore_partita_iva . ')';
            }
            $this->supplier_found = true;
            $this->supplier_not_found = false;
            $this->supplier_created_by_system = true;
            
            $this->dispatch('alert', ['type' => 'success', 'message' => "✅ Fornitore creato automaticamente!"]);
            
        } catch (\Exception $e) {
            Log::error('Errore creazione fornitore: ' . $e->getMessage());
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Errore: ' . $e->getMessage()]);
        }
    }

    public function calculateTotal()
    {
        $total = 0;
        foreach ($this->rows as $row) {
            $subtotal = ($row['quantity'] ?? 0) * ($row['unit_price'] ?? 0);
            $discount = $subtotal * (($row['discount_percentage'] ?? 0) / 100);
            $total += $subtotal - $discount;
        }
        $this->importo_totale = round($total, 2);
    }

    private function saveXmlFile()
    {
        if (!$this->xml_file) {
            return null;
        }
        
        // Leggi il contenuto del file XML caricato
        $content = file_get_contents($this->xml_file->getRealPath());
        
        // Pulisci l'XML dagli allegati
        $cleanContent = $this->removeAttachmentsFromXml($content);
        
        // Salva il contenuto XML nella proprietà per essere salvato nel database
        $this->xml_content = $cleanContent;
        
        // Genera solo un nome per il file (opzionale, per riferimento)
        $fornitorePiva = preg_replace('/[^A-Za-z0-9]/', '', $this->fornitore_partita_iva);
        if (empty($fornitorePiva)) {
            $fornitorePiva = 'piva_non_trovata_' . time();
        }
        
        return $fornitorePiva . '.xml';
    }

    public function save()
    {
        if ($this->checkInvoiceExists()) {
            $this->dispatch('alert', ['type' => 'error', 'message' => "❌ FATTURA DUPLICATA! Impossibile importare."]);
            return;
        }
        
        if (!$this->supplier_found && $this->supplier_not_found && !$this->id_entities) {
            $this->createSupplierAutomatically();
        }
        
        $this->validate();

        try {
            DB::beginTransaction();

            $xmlStoragePath = $this->saveXmlFile();

            $invoice = InvoiceReceived::create([
                'id_ownership' => $this->id_ownership,
                'id_entities' => $this->id_entities,
                'type_invoice' => $this->type_invoice ?: 'TD01',
                'n_invoice' => $this->n_invoice,
                'data_invoice' => $this->data_invoice,
                'importo_totale' => $this->importo_totale,
                'causale' => $this->causale,
                'divisa' => $this->divisa,
                'status' => $this->status,
                'sdi_id' => $this->sdi_id,
                'xml_filename' => $xmlStoragePath,
                'xml_content' => $this->xml_content ?? null,
                'file_hash' => $this->file_hash,
                'imported_at' => now(),
            ]);

            // SALVA LE RIGHE
            foreach ($this->rows as $row) {
                InvoiceRow::create([
                    'document_id' => $invoice->id,
                    'document_type' => 'invoice_received',
                    'id_cost_center' => $row['id_cost_center'] ?? null,
                    'description' => $row['description'],
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'discount_percentage' => $row['discount_percentage'] ?? 0,
                ]);
            }
            
            // SALVA I PAGAMENTI
            foreach ($this->payments as $payment) {
                $invoice->payments()->create([
                    'due_date' => $payment['due_date'],
                    'amount' => $payment['amount'],
                    'payment_method' => $payment['payment_method'],
                    'iban' => $payment['iban'] ?? null,
                    'status' => 'pending',
                ]);
            }
            
            // SALVA IL RIEPILOGO IVA
            foreach ($this->vatSummaries as $summary) {
                $invoice->vatSummaries()->create([
                    'tax_rate' => $summary['tax_rate'],
                    'sdi_nature' => $summary['sdi_nature'],
                    'taxable_amount' => $summary['taxable_amount'],
                    'tax_amount' => $summary['tax_amount'],
                    'vat_law_reference' => $summary['vat_law_reference'],
                    'esigibilita_iva' => $summary['esigibilita_iva'],
                ]);
            }

            DB::commit();

            if (!empty($this->extracted_attachments)) {
                Log::info('Allegati collegati alla fattura', [
                    'invoice_id' => $invoice->id,
                    'n_fattura' => $this->n_invoice,
                    'allegati' => array_column($this->extracted_attachments, 'original_name')
                ]);
            }

            session()->flash('success', 'Fattura importata con successo!');
            return redirect()->route('admin.invoices-received.index');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore salvataggio: ' . $e->getMessage());
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Errore: ' . $e->getMessage()]);
        }
    }

    public function render()
    {
        $tipoDocumento = config('gestionale.tipo_documento', []);
        $currencies = config('gestionale.currencies', []);
        
        return view('livewire.admin.invoices-xml-import', [
            'tipoDocumento' => $tipoDocumento,
            'currencies' => $currencies,
        ]);
    }
}