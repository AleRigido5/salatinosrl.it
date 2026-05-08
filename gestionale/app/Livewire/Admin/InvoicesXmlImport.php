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

    // File XML
    public $xml_file;
    public $xml_filename;
    public $file_hash;
    public $xml_parsed = false;
    public $extracted_attachments = [];
    
    // Dati fattura
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
    
    // Dati Committente
    public $committente_denominazione = '';
    public $committente_partita_iva = '';
    public $committente_codice_fiscale = '';
    public $committente_indirizzo = '';
    public $committente_cap = '';
    public $committente_comune = '';
    public $committente_provincia = '';
    public $committente_nazione = '';
    
    // Dati fornitore
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
    
    // Corrispondenze DB
    public $supplier_found = false;
    public $supplier_not_found = false;
    public $supplier_display = '';
    public $supplier_created_by_system = false;
    public $ownership_display = '';

    // Righe fattura
    public $rows = [];
    
    // Autocomplete
    public $cost_center_all_search = '';
    public $cost_center_all_results = [];
    public $show_cost_center_all_dropdown = false;
    public $row_cost_center_search = [];
    public $row_cost_center_results = [];
    public $show_row_cost_center_dropdown = [];
    
    // Liste complete
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
        
        Log::info('Centri di costo caricati: ' . count($this->all_costCenters));
    }

    // ============================================
    // AUTOCOMPLETE CENTRO DI COSTO
    // ============================================
    public function updatedCostCenterAllSearch()
    {
        if (empty($this->cost_center_all_search)) {
            $this->cost_center_all_results = [];
            $this->show_cost_center_all_dropdown = false;
            return;
        }

        $search = strtolower($this->cost_center_all_search);
        $this->cost_center_all_results = array_filter($this->all_costCenters, function($cc) use ($search) {
            return str_contains(strtolower($cc['name']), $search);
        });
        
        $this->cost_center_all_results = array_values(array_slice($this->cost_center_all_results, 0, 10));
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
            }
            
            $this->cost_center_all_search = $cc['name'];
            $this->cost_center_all_results = [];
            $this->show_cost_center_all_dropdown = false;
            
            $this->dispatch('alert', type: 'success', message: "Centro di costo '{$cc['name']}' applicato a tutte le " . count($this->rows) . " righe");
        }
    }

    public function updatedRowCostCenterSearch($value, $index)
    {
        if (empty($value)) {
            $this->row_cost_center_results[$index] = [];
            $this->show_row_cost_center_dropdown[$index] = false;
            return;
        }

        $search = strtolower($value);
        $this->row_cost_center_results[$index] = array_filter($this->all_costCenters, function($cc) use ($search) {
            return str_contains(strtolower($cc['name']), $search);
        });
        
        $this->row_cost_center_results[$index] = array_values(array_slice($this->row_cost_center_results[$index], 0, 10));
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
            
            // ESTRAZIONE ALLEGATI
            $attachments = $this->extractAndSaveAttachments($content);
            $this->extracted_attachments = $attachments;
            
            // Pulisci l'XML dagli allegati
            $cleanXmlContent = $this->removeAttachmentsFromXml($content);
            $cleanXml = simplexml_load_string($cleanXmlContent);
            
            // Parsing dell'XML pulito
            $this->parseXmlInvoiceRobusto($cleanXml);
            
            // Verifica duplicati
            if ($this->checkInvoiceExists()) {
                if (!empty($attachments)) {
                    foreach ($attachments as $attachment) {
                        if (Storage::disk('local')->exists($attachment['path'])) {
                            Storage::disk('local')->delete($attachment['path']);
                        }
                    }
                }
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
            $this->addError('xml_file', 'Errore: ' . $e->getMessage());
        }
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
        $xmlString = $xml->asXML();
        
        // DATI COMMITTENTE
        if (preg_match('/<CessionarioCommittente>(.*?)<\/CessionarioCommittente>/is', $xmlString, $cessionarioMatch)) {
            $cessionarioXml = $cessionarioMatch[1];
            
            if (preg_match('/<Denominazione>(.*?)<\/Denominazione>/i', $cessionarioXml, $match)) {
                $this->committente_denominazione = trim($match[1]);
            }
            if (empty($this->committente_denominazione)) {
                $nome = '';
                $cognome = '';
                if (preg_match('/<Nome>(.*?)<\/Nome>/i', $cessionarioXml, $match)) $nome = trim($match[1]);
                if (preg_match('/<Cognome>(.*?)<\/Cognome>/i', $cessionarioXml, $match)) $cognome = trim($match[1]);
                $this->committente_denominazione = trim($nome . ' ' . $cognome);
            }
            
            $this->committente_partita_iva = '';
            if (preg_match('/<IdPaese>(.*?)<\/IdPaese>/i', $cessionarioXml, $match)) $this->committente_partita_iva = trim($match[1]);
            if (preg_match('/<IdCodice>(.*?)<\/IdCodice>/i', $cessionarioXml, $match)) $this->committente_partita_iva .= trim($match[1]);
            
            if (preg_match('/<CodiceFiscale>(.*?)<\/CodiceFiscale>/i', $cessionarioXml, $match)) {
                $this->committente_codice_fiscale = trim($match[1]);
            }
        }
        
        // DATI FORNITORE
        if (preg_match('/<CedentePrestatore>(.*?)<\/CedentePrestatore>/is', $xmlString, $cedenteMatch)) {
            $cedenteXml = $cedenteMatch[1];
            
            if (preg_match('/<Denominazione>(.*?)<\/Denominazione>/i', $cedenteXml, $match)) {
                $this->fornitore_denominazione = trim($match[1]);
            }
            if (empty($this->fornitore_denominazione)) {
                $nome = '';
                $cognome = '';
                if (preg_match('/<Nome>(.*?)<\/Nome>/i', $cedenteXml, $match)) $nome = trim($match[1]);
                if (preg_match('/<Cognome>(.*?)<\/Cognome>/i', $cedenteXml, $match)) $cognome = trim($match[1]);
                $this->fornitore_denominazione = trim($nome . ' ' . $cognome);
            }
            
            $this->fornitore_partita_iva = '';
            if (preg_match('/<IdPaese>(.*?)<\/IdPaese>/i', $cedenteXml, $match)) $this->fornitore_partita_iva = trim($match[1]);
            if (preg_match('/<IdCodice>(.*?)<\/IdCodice>/i', $cedenteXml, $match)) $this->fornitore_partita_iva .= trim($match[1]);
            
            if (preg_match('/<CodiceFiscale>(.*?)<\/CodiceFiscale>/i', $cedenteXml, $match)) {
                $this->fornitore_codice_fiscale = trim($match[1]);
            }
            
            if (preg_match('/<Contatti>(.*?)<\/Contatti>/is', $cedenteXml, $contattiMatch)) {
                $contattiXml = $contattiMatch[1];
                if (preg_match('/<Telefono>(.*?)<\/Telefono>/i', $contattiXml, $match)) $this->fornitore_telefono = trim($match[1]);
                if (preg_match('/<Email>(.*?)<\/Email>/i', $contattiXml, $match)) $this->fornitore_email = trim($match[1]);
            }
        }
        
        // DATI FATTURA
        if (preg_match('/<DatiGeneraliDocumento>(.*?)<\/DatiGeneraliDocumento>/is', $xmlString, $datiGeneraliMatch)) {
            $datiGeneraliXml = $datiGeneraliMatch[1];
            
            if (preg_match('/<TipoDocumento>(.*?)<\/TipoDocumento>/i', $datiGeneraliXml, $match)) $this->type_invoice = trim($match[1]);
            if (preg_match('/<Divisa>(.*?)<\/Divisa>/i', $datiGeneraliXml, $match)) $this->divisa = trim($match[1]);
            if (preg_match('/<Data>(.*?)<\/Data>/i', $datiGeneraliXml, $match)) $this->data_invoice = trim($match[1]);
            if (preg_match('/<Numero>(.*?)<\/Numero>/i', $datiGeneraliXml, $match)) $this->n_invoice = trim($match[1]);
            if (preg_match('/<ImportoTotaleDocumento>(.*?)<\/ImportoTotaleDocumento>/i', $datiGeneraliXml, $match)) {
                $this->importo_totale = floatval(str_replace(',', '.', trim($match[1])));
            }
        }
        
        // RIGHE FATTURA
        $this->rows = [];
        if (preg_match_all('/<DettaglioLinee>(.*?)<\/DettaglioLinee>/is', $xmlString, $lineeMatches)) {
            foreach ($lineeMatches[1] as $index => $lineaXml) {
                $row = [
                    'description' => '',
                    'quantity' => 1,
                    'unit_price' => 0,
                    'discount_percentage' => 0,
                    'id_cost_center' => null,
                    'cost_center_name' => '',
                ];
                
                if (preg_match('/<Descrizione>(.*?)<\/Descrizione>/i', $lineaXml, $match)) $row['description'] = trim($match[1]);
                if (preg_match('/<Quantita>(.*?)<\/Quantita>/i', $lineaXml, $match)) $row['quantity'] = floatval(str_replace(',', '.', trim($match[1])));
                if (preg_match('/<PrezzoUnitario>(.*?)<\/PrezzoUnitario>/i', $lineaXml, $match)) $row['unit_price'] = floatval(str_replace(',', '.', trim($match[1])));
                
                if (preg_match('/<ScontoMaggiorazione>(.*?)<\/ScontoMaggiorazione>/is', $lineaXml, $scontoMatch)) {
                    if (preg_match('/<Percentuale>(.*?)<\/Percentuale>/i', $scontoMatch[1], $percMatch)) {
                        $row['discount_percentage'] = floatval(str_replace(',', '.', trim($percMatch[1])));
                    }
                }

                $this->rows[] = $row;
            }
        }
        
        // CERCA FORNITORE NEL DB
        $this->supplier_found = false;
        $this->supplier_not_found = false;
        
        if (!empty($this->fornitore_partita_iva)) {
            $entity = Entity::where('partita_iva', $this->fornitore_partita_iva)->first();
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
            if ($ownership) {
                $this->id_ownership = $ownership->id_proprieta;
                $this->ownership_display = $ownership->Rag_Soc_intest ?: $ownership->RagSocialePr;
            }
        }
        
        $this->status = 'bozza';
        $this->calculateTotal();
        
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
        
        Log::info('XML Parsing completato', [
            'fornitore' => $this->fornitore_denominazione,
            'n_fattura' => $this->n_invoice,
            'num_rows' => count($this->rows)
        ]);
    }

    /**
     * Estrae e salva gli allegati dal XML
     */
    private function extractAndSaveAttachments($xmlString)
    {
        $savedFiles = [];
        
        // Cerca il tag Allegati
        if (preg_match('/<Allegati>(.*?)<\/Allegati>/is', $xmlString, $allegatiMatch)) {
            $allegatiXml = $allegatiMatch[1];
            
            if (preg_match_all('/<Allegato>(.*?)<\/Allegato>/is', $allegatiXml, $allegatoMatches)) {
                foreach ($allegatoMatches[1] as $index => $allegatoInnerXml) {
                    $fileInfo = $this->processSingleAttachment($allegatoInnerXml, $index);
                    if ($fileInfo) {
                        $savedFiles[] = $fileInfo;
                    }
                }
            }
        }
        
        // Cerca FatturaFirmata
        if (preg_match('/<FatturaFirmata>(.*?)<\/FatturaFirmata>/is', $xmlString, $firmaMatch)) {
            $fileInfo = $this->processSingleAttachment($firmaMatch[1], 'firma');
            if ($fileInfo) {
                $savedFiles[] = $fileInfo;
            }
        }
        
        return $savedFiles;
    }

    /**
     * Processa un singolo allegato
     */
    private function processSingleAttachment($allegatoXml, $identifier)
    {
        // Estrai nome file
        $fileName = '';
        if (preg_match('/<NomeAttachment>(.*?)<\/NomeAttachment>/i', $allegatoXml, $match)) {
            $fileName = trim($match[1]);
        }
        
        // Estrai Base64
        $base64Content = '';
        if (preg_match('/<Attachment>(.*?)<\/Attachment>/is', $allegatoXml, $match)) {
            $base64Content = trim(preg_replace('/\s/', '', $match[1]));
        }
        
        if (empty($base64Content)) {
            return null;
        }
        
        $decodedContent = base64_decode($base64Content);
        if ($decodedContent === false) {
            return null;
        }
        
        if (empty($fileName)) {
            $extension = $this->detectFileExtension($decodedContent);
            $fileName = 'attachment_' . $identifier . '_' . date('Ymd_His') . '.' . $extension;
        } else {
            $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
        }
        
        // Determina estensione finale
        $finalFileName = $fileName;
        if (strpos($decodedContent, '%PDF') === 0 && !str_ends_with($fileName, '.pdf')) {
            $finalFileName = preg_replace('/\.[^.]+$/', '', $fileName) . '.pdf';
        }
        
        // Crea cartella
        $pivaFornitore = preg_replace('/[^A-Za-z0-9]/', '', $this->fornitore_partita_iva);
        if (empty($pivaFornitore)) {
            $pivaFornitore = 'piva_non_trovata';
        }
        
        $attachmentsFolder = 'allegati_fattura/' . $pivaFornitore;
        if (!Storage::disk('local')->exists($attachmentsFolder)) {
            Storage::disk('local')->makeDirectory($attachmentsFolder);
        }
        
        $filePath = $attachmentsFolder . '/' . $finalFileName;
        Storage::disk('local')->put($filePath, $decodedContent);
        
        Log::info('Allegato salvato', [
            'nome' => $fileName,
            'percorso' => $filePath,
            'size' => strlen($decodedContent)
        ]);
        
        return [
            'original_name' => $fileName,
            'saved_name' => $finalFileName,
            'path' => $filePath,
            'size' => strlen($decodedContent)
        ];
    }

    private function detectFileExtension($content)
    {
        if (strpos($content, '%PDF') === 0) return 'pdf';
        if (strpos($content, 'PK') === 0) return 'zip';
        if (strpos($content, '<?xml') === 0) return 'xml';
        return 'bin';
    }

    /**
     * Rimuove gli allegati dall'XML
     */
    private function removeAttachmentsFromXml($xmlString)
    {
        $xmlString = preg_replace('/<Allegati>.*?<\/Allegati>/is', '', $xmlString);
        $xmlString = preg_replace('/<Allegato>.*?<\/Allegato>/is', '', $xmlString);
        $xmlString = preg_replace('/<FatturaFirmata>.*?<\/FatturaFirmata>/is', '', $xmlString);
        $xmlString = preg_replace('/\n\s*\n/', "\n", $xmlString);
        return $xmlString;
    }

    private function checkVatAutomatically($vat)
    {
        $vat = preg_replace('/[^A-Za-z0-9]/', '', $vat);
        if (empty($vat)) return;
        
        if (preg_match('/^\d{11}$/', $vat)) {
            $isValid = $this->validateItalianVat($vat);
            if (!$isValid) {
                $this->dispatch('alert', ['type' => 'error', 'message' => "❌ Partita IVA fornitore {$vat} NON valida!"]);
            } else {
                $this->dispatch('alert', ['type' => 'success', 'message' => "✅ Partita IVA fornitore {$vat} valida"]);
            }
        }
    }

    private function validateItalianVat($vat)
    {
        if (strlen($vat) != 11 || !is_numeric($vat)) return false;
        
        $numbers = str_split($vat);
        $evenSum = 0;
        $oddSum = 0;
        
        for ($i = 0; $i < 10; $i++) {
            $digit = (int)$numbers[$i];
            if ($i % 2 == 0) {
                $oddSum += $digit;
            } else {
                $double = $digit * 2;
                $evenSum += $double > 9 ? $double - 9 : $double;
            }
        }
        
        $total = $oddSum + $evenSum;
        $remainder = $total % 10;
        $controlDigit = $remainder == 0 ? 0 : 10 - $remainder;
        
        return $controlDigit == (int)$numbers[10];
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
                'file_hash' => $this->file_hash,
                'imported_at' => now(),
            ]);

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