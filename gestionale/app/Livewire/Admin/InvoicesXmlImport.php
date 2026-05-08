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
    
    // Dati fattura (tutti disabilitati, presi dall'XML)
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
    
    // Dati Committente (Cessionario) - XML
    public $committente_denominazione = '';
    public $committente_partita_iva = '';
    public $committente_codice_fiscale = '';
    public $committente_indirizzo = '';
    public $committente_cap = '';
    public $committente_comune = '';
    public $committente_provincia = '';
    public $committente_nazione = '';
    
    // Dati fornitore (Cedente) - XML
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
    
    // Corrispondenza fornitore nel DB
    public $supplier_found = false;
    public $supplier_not_found = false;
    public $supplier_display = '';
    public $supplier_created_by_system = false;
    
    // Dati proprietà selezionata manualmente
    public $ownership_display = '';

    // Righe fattura
    public $rows = [];
    
    // Autocomplete per Centro di Costo (su TUTTE le righe)
    public $cost_center_all_search = '';
    public $cost_center_all_results = [];
    public $show_cost_center_all_dropdown = false;
    
    // Autocomplete per Centro di Costo (per SINGOLA riga)
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
        
        // Debug: controlla se ci sono risultati
        Log::info('Centri di costo caricati: ' . count($this->all_costCenters));
    }

    // ============================================
    // AUTOCOMPLETE CENTRO DI COSTO (per TUTTE le righe)
    // ============================================
    public function updatedCostCenterAllSearch()
    {
        Log::info('=== updatedCostCenterAllSearch chiamato ===', [
            'search_value' => $this->cost_center_all_search
        ]);
        
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
        
        Log::info('Risultati: ' . count($this->cost_center_all_results));
    }

    public function applyCostCenterToAllRows($id)
    {
        Log::info('=== applyCostCenterToAllRows chiamato ===', ['id' => $id]);
        
        $cc = collect($this->all_costCenters)->firstWhere('id', (int)$id);
        
        if ($cc) {
            // Applica a tutte le righe
            foreach ($this->rows as $index => $row) {
                $this->rows[$index]['id_cost_center'] = (int)$id;
                $this->rows[$index]['cost_center_name'] = $cc['name'];
                $this->row_cost_center_search[$index] = $cc['name'];
            }
            
            $this->cost_center_all_search = $cc['name'];
            $this->cost_center_all_results = [];
            $this->show_cost_center_all_dropdown = false;
            
            // Dispatch dell'alert con la struttura corretta
            $this->dispatch('alert', type: 'success', message: "Centro di costo '{$cc['name']}' applicato a tutte le " . count($this->rows) . " righe");
            
            Log::info('Centro costo applicato a ' . count($this->rows) . ' righe');
        } else {
            Log::error('Centro costo non trovato per id: ' . $id);
            $this->dispatch('alert', type: 'error', message: 'Centro di costo non trovato');
        }
    }

    // ============================================
    // AUTOCOMPLETE CENTRO DI COSTO (per SINGOLA riga)
    // ============================================
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
            
            $this->parseXmlInvoiceRobusto($xml);
            
            // Verifica se fattura già esiste (duplicato)
            if ($this->checkInvoiceExists()) {
                $this->dispatch('alert', ['type' => 'error', 'message' => "❌ FATTURA DUPLICATA! Questa fattura è già stata importata.\nFornitore: {$this->fornitore_partita_iva}\nNumero: {$this->n_invoice}\nData: {$this->data_invoice}"]);
                return;
            }
            
            $this->xml_parsed = true;
            $this->dispatch('alert', ['type' => 'success', 'message' => 'XML analizzato con successo!']);
            
        } catch (\Exception $e) {
            $this->addError('xml_file', 'Errore durante la lettura del file: ' . $e->getMessage());
        }
    }

    /**
     * Verifica se la fattura è già stata importata (controllo su P.IVA + N.Fattura + Data)
     */
    private function checkInvoiceExists()
    {
        if (empty($this->fornitore_partita_iva) || empty($this->n_invoice) || empty($this->data_invoice)) {
            return false;
        }
        
        // Cerca per partita IVA fornitore, numero fattura e data
        $exists = InvoiceReceived::whereHas('entity', function($query) {
            $query->where('partita_iva', $this->fornitore_partita_iva);
        })->where('n_invoice', $this->n_invoice)
          ->whereDate('data_invoice', $this->data_invoice)
          ->exists();
        
        if ($exists) {
            Log::warning('Tentativo di importazione fattura duplicata', [
                'fornitore_piva' => $this->fornitore_partita_iva,
                'n_fattura' => $this->n_invoice,
                'data_fattura' => $this->data_invoice,
                'filename' => $this->xml_filename
            ]);
            return true;
        }
        
        // Cerca anche per hash del file
        if (!empty($this->file_hash)) {
            $hashExists = InvoiceReceived::where('file_hash', $this->file_hash)->exists();
            if ($hashExists) {
                Log::warning('Tentativo di importazione file duplicato', [
                    'hash' => $this->file_hash,
                    'filename' => $this->xml_filename
                ]);
                return true;
            }
        }
        
        return false;
    }

    /**
     * Parsing XML robusto
     */
    private function parseXmlInvoiceRobusto($xml)
    {
        $xmlString = $xml->asXML();
        
        // ============================================
        // 1. DATI COMMITTENTE (Cessionario)
        // ============================================
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
        
        // ============================================
        // 2. DATI FORNITORE (Cedente)
        // ============================================
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
            
            // Contatti
            if (preg_match('/<Contatti>(.*?)<\/Contatti>/is', $cedenteXml, $contattiMatch)) {
                $contattiXml = $contattiMatch[1];
                if (preg_match('/<Telefono>(.*?)<\/Telefono>/i', $contattiXml, $match)) $this->fornitore_telefono = trim($match[1]);
                if (preg_match('/<Email>(.*?)<\/Email>/i', $contattiXml, $match)) $this->fornitore_email = trim($match[1]);
            }
        }
        
        // ============================================
        // 3. DATI FATTURA
        // ============================================
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
        
        // Fallback su DatiFattura
        if (empty($this->n_invoice) || empty($this->data_invoice)) {
            if (preg_match('/<DatiFattura>(.*?)<\/DatiFattura>/is', $xmlString, $datiFatturaMatch)) {
                $datiFatturaXml = $datiFatturaMatch[1];
                
                if (empty($this->type_invoice) && preg_match('/<TipoDocumento>(.*?)<\/TipoDocumento>/i', $datiFatturaXml, $match)) $this->type_invoice = trim($match[1]);
                if (empty($this->n_invoice) && preg_match('/<NumeroFattura>(.*?)<\/NumeroFattura>/i', $datiFatturaXml, $match)) $this->n_invoice = trim($match[1]);
                if (empty($this->data_invoice) && preg_match('/<DataFattura>(.*?)<\/DataFattura>/i', $datiFatturaXml, $match)) $this->data_invoice = trim($match[1]);
                if ($this->importo_totale == 0 && preg_match('/<ImportoTotaleDocumento>(.*?)<\/ImportoTotaleDocumento>/i', $datiFatturaXml, $match)) $this->importo_totale = floatval(str_replace(',', '.', trim($match[1])));
                if (empty($this->divisa) && preg_match('/<Divisa>(.*?)<\/Divisa>/i', $datiFatturaXml, $match)) $this->divisa = trim($match[1]);
                if (preg_match('/<ProgressivoInvio>(.*?)<\/ProgressivoInvio>/i', $datiFatturaXml, $match)) $this->sdi_id = trim($match[1]);
                if (preg_match('/<Causale>(.*?)<\/Causale>/i', $datiFatturaXml, $match)) $this->causale = trim($match[1]);
            }
        }
        
        // ============================================
        // 4. RIGHE FATTURA
        // ============================================
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
        
        // ============================================
        // 5. CERCA FORNITORE NEL DB
        // ============================================
        $this->supplier_found = false;
        $this->supplier_not_found = false;
        $this->supplier_created_by_system = false;
        
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
        
        // ============================================
        // 6. CERCA PROPRIETÀ (COMMITTENTE) NEL DB
        // ============================================
        Log::info('Ricerca proprietà per committente', [
            'partita_iva' => $this->committente_partita_iva,
            'denominazione' => $this->committente_denominazione
        ]);

        if (!empty($this->committente_partita_iva)) {
            $ownership = Ownership::where('PivaPr', $this->committente_partita_iva)->first();
            if ($ownership) {
                $this->id_ownership = $ownership->id_proprieta;
                $this->ownership_display = $ownership->Rag_Soc_intest ?: $ownership->RagSocialePr;
                Log::info('Proprietà trovata per P.IVA', [
                    'id_ownership' => $this->id_ownership,
                    'name' => $this->ownership_display
                ]);
            } else {
                Log::warning('Nessuna proprietà trovata per P.IVA: ' . $this->committente_partita_iva);
            }
        }

        // Se non trovato per partita IVA, cerca per denominazione (fallback)
        if (empty($this->id_ownership) && !empty($this->committente_denominazione)) {
            $ownership = Ownership::where('Rag_Soc_intest', 'like', '%' . $this->committente_denominazione . '%')
                ->orWhere('RagSocialePr', 'like', '%' . $this->committente_denominazione . '%')
                ->first();
            if ($ownership) {
                $this->id_ownership = $ownership->id_proprieta;
                $this->ownership_display = $ownership->Rag_Soc_intest ?: $ownership->RagSocialePr;
                Log::info('Proprietà trovata per denominazione simile', [
                    'id_ownership' => $this->id_ownership,
                    'name' => $this->ownership_display
                ]);
            } else {
                Log::warning('Nessuna proprietà trovata per denominazione: ' . $this->committente_denominazione);
            }
        }
        
        // ============================================
        // 7. STATO: default "bozza"
        // ============================================
        $this->status = 'bozza';
        
        // ============================================
        // 8. CALCOLA TOTALE
        // ============================================
        $this->calculateTotal();
        
        // ============================================
        // 9. INIZIALIZZA ARRAY PER AUTOCOMPLETE
        // ============================================
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
            'fornitore_piva' => $this->fornitore_partita_iva,
            'n_fattura' => $this->n_invoice,
            'data_fattura' => $this->data_invoice,
            'totale' => $this->importo_totale,
            'num_rows' => count($this->rows)
        ]);
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

    /**
     * Crea fornitore automaticamente con flag created_by_system = 1
     */
    public function createSupplierAutomatically()
    {
        if (empty($this->fornitore_denominazione)) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Nessun dato fornitore disponibile']);
            return;
        }
        
        try {
            // Verifica se esiste già nel frattempo
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

    /**
     * Salva solo il nome del file XML nel DB (non salva fisicamente il file)
     * Formato: {PartitaIVA_fornitore}.xml
     */
    private function saveXmlFile()
    {
        if (!$this->xml_file) {
            return null;
        }
        
        // Pulisci la partita IVA (togli IT, spazi, etc.)
        $fornitorePiva = preg_replace('/[^A-Za-z0-9]/', '', $this->fornitore_partita_iva);
        
        // Se la partita IVA è vuota, usa un default con timestamp
        if (empty($fornitorePiva)) {
            $fornitorePiva = 'piva_non_trovata_' . time();
        }
        
        $filename = $fornitorePiva . '.xml';
        
        Log::info('Nome file generato per il DB', [
            'filename' => $filename,
            'fornitore_piva' => $this->fornitore_partita_iva,
            'originale' => $this->xml_file->getClientOriginalName()
        ]);
        
        // Restituisci solo il nome del file (non il percorso)
        return $filename;
    }

    public function save()
    {
        // Verifica duplicati prima del salvataggio
        if ($this->checkInvoiceExists()) {
            $this->dispatch('alert', ['type' => 'error', 'message' => "❌ FATTURA DUPLICATA! Impossibile importare."]);
            return;
        }
        
        // Se il fornitore non esiste, lo creiamo automaticamente
        if (!$this->supplier_found && $this->supplier_not_found && !$this->id_entities) {
            $this->createSupplierAutomatically();
        }
        
        $this->validate();

        try {
            DB::beginTransaction();

            // Salva il file XML nella cartella storage
            $xmlStoragePath = $this->saveXmlFile();

            $invoice = InvoiceReceived::create([
                'id_ownership' => $this->id_ownership,  // ← Questo deve avere un valore!
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

            session()->flash('success', 'Fattura importata con successo!');
            return redirect()->route('admin.invoices-received.index');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore salvataggio fattura: ' . $e->getMessage());
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