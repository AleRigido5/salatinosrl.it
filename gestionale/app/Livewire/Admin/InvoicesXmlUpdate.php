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
use Illuminate\Support\Facades\Http;

class InvoicesXmlUpdate extends Component
{
    use WithFileUploads;

    // File XML
    public $xml_file;
    public $xml_content;
    public $xml_parsed = false;
    public $parsed_data = [];
    
    // Dati fattura
    public $id_ownership;
    public $id_entities;
    public $type_invoice = 'TD01';
    public $n_invoice;
    public $data_invoice;
    public $importo_totale;
    public $causale;
    public $divisa = 'EUR';
    public $status = 'bozza';
    public $sdi_id;
    
    // Dati fornitore dall'XML
    public $supplier_from_xml = [];
    public $supplier_found = false;
    public $supplier_not_found = false;
    
    // Righe fattura
    public $rows = [];
    
    // Dati per autocomplete
    public $search_ownership = '';
    public $search_cost_center = '';
    
    public $ownerships = [];
    public $costCenters = [];
    
    // Liste complete
    public $all_ownerships = [];
    public $all_costCenters = [];

    protected $rules = [
        'id_entities' => 'required|exists:entities,id_cliente',
        'id_ownership' => 'nullable|exists:ownership,id_proprieta',
        'type_invoice' => 'required|string|max:10',
        'n_invoice' => 'required|string|max:50',
        'data_invoice' => 'required|date',
        'importo_totale' => 'nullable|numeric|min:0',
        'causale' => 'nullable|string',
        'divisa' => 'required|string|size:3',
        'status' => 'required|string',
        'sdi_id' => 'nullable|string|max:100',
        'rows.*.id_cost_center' => 'nullable|exists:cost_centers,id',
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
        $this->all_ownerships = Ownership::where('valid', 1)
            ->select('id_proprieta as id', DB::raw("COALESCE(Rag_Soc_intest, RagSocialePr) as name"))
            ->orderBy('name')
            ->get()
            ->toArray();

        $this->all_costCenters = CostCenter::where('valid', 1)
            ->select('id', 'Nome as name')
            ->orderBy('Nome')
            ->get()
            ->toArray();

        $this->updatedSearchOwnership();
        $this->updatedSearchCostCenter();
    }

    // Autocomplete per Proprietà
    public function updatedSearchOwnership()
    {
        if (empty($this->search_ownership)) {
            $this->ownerships = array_slice($this->all_ownerships, 0, 10);
            return;
        }

        $this->ownerships = array_filter($this->all_ownerships, function($ownership) {
            return str_contains(strtolower($ownership['name']), strtolower($this->search_ownership));
        });
        
        $this->ownerships = array_values(array_slice($this->ownerships, 0, 10));
    }

    public function selectOwnership($id)
    {
        $this->id_ownership = $id;
        $ownership = collect($this->all_ownerships)->firstWhere('id', $id);
        if ($ownership) {
            $this->search_ownership = $ownership['name'];
            $this->ownerships = [];
        }
    }

    // Autocomplete per Centro di Costo (per righe)
    public function updatedSearchCostCenter()
    {
        if (empty($this->search_cost_center)) {
            $this->costCenters = array_slice($this->all_costCenters, 0, 10);
            return;
        }

        $this->costCenters = array_filter($this->all_costCenters, function($cc) {
            return str_contains(strtolower($cc['name']), strtolower($this->search_cost_center));
        });
        
        $this->costCenters = array_values(array_slice($this->costCenters, 0, 10));
    }

    public function selectCostCenter($id, $index)
    {
        $this->rows[$index]['id_cost_center'] = $id;
        $cc = collect($this->all_costCenters)->firstWhere('id', $id);
        if ($cc) {
            $this->rows[$index]['cost_center_name'] = $cc['name'];
        }
        $this->costCenters = [];
        $this->search_cost_center = '';
        $this->calculateTotal();
    }

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

            $this->xml_content = $content;
            $this->parsed_data = $this->parseXmlInvoice($xml);
            $this->populateFormFromXml($this->parsed_data);
            $this->xml_parsed = true;
            
            $this->dispatch('alert', ['type' => 'success', 'message' => 'XML analizzato con successo!']);
            
        } catch (\Exception $e) {
            $this->addError('xml_file', 'Errore durante la lettura del file: ' . $e->getMessage());
        }
    }

    /**
     * Parsing completo del file XML fattura elettronica
     */
    private function parseXmlInvoice($xml)
    {
        $namespaces = $xml->getNamespaces(true);
        $ns = $namespaces[''] ?? null;
        
        $getValue = function($node, $path, $ns = null) {
            try {
                $result = $ns ? $node->children($ns)->xpath($path) : $node->xpath($path);
                return !empty($result) ? trim((string)$result[0]) : null;
            } catch (\Exception $e) {
                return null;
            }
        };

        // ============================================
        // 1. DATI FORNITORE (Cedente/Prestatore)
        // ============================================
        $supplierData = [];
        try {
            // Cessionario/Committente è il nostro cliente, ma vogliamo il Cedente
            // Il Cedente/Prestatore è nel file XML
            $cedente = $ns ? $xml->children($ns)->FatturaElettronicaBody->DatiGenerali->DatiFatturaHeader->CessionarioCommittente 
                     : $xml->FatturaElettronicaBody->DatiGenerali->DatiFatturaHeader->CessionarioCommittente;
            
            if ($cedente) {
                $datiAnagrafici = $cedente->DatiAnagrafici;
                $anagrafica = $datiAnagrafici->Anagrafica;
                
                // Prendi denominazione o nome/cognome
                $denominazione = (string)($anagrafica->Denominazione ?? '');
                if (empty($denominazione)) {
                    $nome = (string)($anagrafica->Nome ?? '');
                    $cognome = (string)($anagrafica->Cognome ?? '');
                    $denominazione = trim($nome . ' ' . $cognome);
                }
                
                $partitaIva = (string)($datiAnagrafici->IdPaese ?? '') . (string)($datiAnagrafici->IdCodice ?? '');
                $codiceFiscale = (string)($datiAnagrafici->CodiceFiscale ?? '');
                $indirizzo = (string)($cedente->Sede->Indirizzo ?? '');
                $cap = (string)($cedente->Sede->CAP ?? '');
                $comune = (string)($cedente->Sede->Comune ?? '');
                $provincia = (string)($cedente->Sede->Provincia ?? '');
                $nazione = (string)($cedente->Sede->Nazione ?? 'IT');
                $telefono = (string)($cedente->IscrizioneREA->Telefono ?? '');
                
                $supplierData = [
                    'denominazione' => $denominazione,
                    'partita_iva' => $partitaIva,
                    'codice_fiscale' => $codiceFiscale,
                    'indirizzo' => $indirizzo,
                    'cap' => $cap,
                    'comune' => $comune,
                    'provincia' => $provincia,
                    'nazione' => $nazione,
                    'telefono' => $telefono,
                    'email' => (string)($cedente->IscrizioneREA->Email ?? ''),
                    'pec' => (string)($cedente->IscrizioneREA->PEC ?? ''),
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Errore parsing fornitore: ' . $e->getMessage());
        }

        // ============================================
        // 2. DATI FATTURA
        // ============================================
        $invoiceData = [];
        try {
            $datiGenerali = $ns ? $xml->children($ns)->FatturaElettronicaBody->DatiGenerali 
                          : $xml->FatturaElettronicaBody->DatiGenerali;
            
            if ($datiGenerali) {
                $datiFattura = $datiGenerali->DatiFatturaHeader->DatiFattura ?? $datiGenerali->DatiFatturaHeader;
                $invoiceData = [
                    'n_invoice' => (string)($datiFattura->NumeroFattura ?? ''),
                    'data_invoice' => (string)($datiFattura->DataFattura ?? ''),
                    'importo_totale' => (float)($datiFattura->ImportoTotaleDocumento ?? 0),
                    'divisa' => (string)($datiFattura->Divisa ?? 'EUR'),
                    'sdi_id' => (string)($datiFattura->ProgressivoInvio ?? ''),
                ];
                
                // Cerca anche eventuale causale
                if (isset($datiFattura->Causale)) {
                    $invoiceData['causale'] = (string)$datiFattura->Causale;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Errore parsing dati fattura: ' . $e->getMessage());
        }

        // ============================================
        // 3. RIGHE FATTURA
        // ============================================
        $rows = [];
        try {
            $datiBeniServizi = $ns ? $xml->children($ns)->FatturaElettronicaBody->DatiBeniServizi 
                              : $xml->FatturaElettronicaBody->DatiBeniServizi;
            
            if ($datiBeniServizi) {
                foreach ($datiBeniServizi->DettaglioLinee as $linea) {
                    $row = [
                        'description' => (string)($linea->Descrizione ?? ''),
                        'quantity' => (float)($linea->Quantita ?? 1),
                        'unit_price' => (float)($linea->PrezzoUnitario ?? 0),
                        'discount_percentage' => 0,
                        'id_cost_center' => null,
                        'cost_center_name' => '',
                    ];
                    
                    if (isset($linea->ScontoMaggiorazione)) {
                        $row['discount_percentage'] = (float)($linea->ScontoMaggiorazione->Percentuale ?? 0);
                    }
                    
                    $rows[] = $row;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Errore parsing righe: ' . $e->getMessage());
        }

        return [
            'supplier' => $supplierData,
            'invoice' => $invoiceData,
            'rows' => $rows
        ];
    }

    /**
     * Popola il form con i dati dell'XML e cerca automaticamente il fornitore
     */
    private function populateFormFromXml($parsed_data)
    {
        // Salva i dati del fornitore dall'XML
        $this->supplier_from_xml = $parsed_data['supplier'];
        
        // ============================================
        // RICERCA AUTOMATICA DEL FORNITORE NEL DB
        // ============================================
        $this->supplier_found = false;
        $this->supplier_not_found = false;
        
        if (!empty($this->supplier_from_xml)) {
            $partitaIva = $this->supplier_from_xml['partita_iva'] ?? '';
            $codiceFiscale = $this->supplier_from_xml['codice_fiscale'] ?? '';
            $denominazione = $this->supplier_from_xml['denominazione'] ?? '';
            
            // Cerca per partita IVA
            if (!empty($partitaIva)) {
                $entity = Entity::where('partita_iva', $partitaIva)->first();
                if ($entity) {
                    $this->supplier_found = true;
                    $this->id_entities = $entity->id_cliente;
                    $this->dispatch('alert', [
                        'type' => 'success', 
                        'message' => "✅ Fornitore trovato! {$entity->ragione_sociale} (P.IVA: {$partitaIva})"
                    ]);
                }
            }
            
            // Se non trovato per partita IVA, cerca per codice fiscale
            if (!$this->supplier_found && !empty($codiceFiscale)) {
                $entity = Entity::where('codice_fiscale', $codiceFiscale)->first();
                if ($entity) {
                    $this->supplier_found = true;
                    $this->id_entities = $entity->id_cliente;
                    $this->dispatch('alert', [
                        'type' => 'success', 
                        'message' => "✅ Fornitore trovato! {$entity->ragione_sociale} (CF: {$codiceFiscale})"
                    ]);
                }
            }
            
            // Se non trovato, mostra alert
            if (!$this->supplier_found) {
                $this->supplier_not_found = true;
                $this->dispatch('alert', [
                    'type' => 'warning', 
                    'message' => "⚠️ Fornitore non trovato nel database! P.IVA: {$partitaIva} - Verrà creato automaticamente al salvataggio."
                ]);
            }
            
            // Verifica anche la validità della partita IVA
            if (!empty($partitaIva)) {
                $this->checkVatAutomatically($partitaIva);
            }
        }
        
        // Popola dati fattura
        $this->n_invoice = $parsed_data['invoice']['n_invoice'] ?? '';
        $this->data_invoice = $parsed_data['invoice']['data_invoice'] ?? '';
        $this->importo_totale = $parsed_data['invoice']['importo_totale'] ?? 0;
        $this->divisa = $parsed_data['invoice']['divisa'] ?? 'EUR';
        $this->sdi_id = $parsed_data['invoice']['sdi_id'] ?? '';
        $this->causale = $parsed_data['invoice']['causale'] ?? '';
        
        // Popola righe
        if (!empty($parsed_data['rows'])) {
            $this->rows = $parsed_data['rows'];
            $this->calculateTotal();
        } else {
            $this->addRow();
        }
    }

    /**
     * Verifica automatica della partita IVA (senza alert, solo log)
     */
    private function checkVatAutomatically($vat)
    {
        $vat = preg_replace('/[^A-Za-z0-9]/', '', $vat);
        
        if (empty($vat)) return;
        
        // Pattern per partita IVA italiana (11 cifre numeriche)
        $italianVatPattern = '/^\d{11}$/';
        
        if (preg_match($italianVatPattern, $vat)) {
            $isValid = $this->validateItalianVat($vat);
            if (!$isValid) {
                $this->dispatch('alert', [
                    'type' => 'error', 
                    'message' => "❌ Partita IVA {$vat} NON valida!"
                ]);
            } else {
                $this->dispatch('alert', [
                    'type' => 'success', 
                    'message' => "✅ Partita IVA {$vat} valida"
                ]);
            }
        }
    }

    /**
     * Validazione partita IVA italiana
     */
    private function validateItalianVat($vat)
    {
        if (strlen($vat) != 11 || !is_numeric($vat)) {
            return false;
        }
        
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
     * Crea automaticamente il fornitore se non esiste
     */
    public function createSupplierAutomatically()
    {
        if (empty($this->supplier_from_xml)) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Nessun dato fornitore disponibile']);
            return;
        }
        
        try {
            $supplier = $this->supplier_from_xml;
            
            // Crea il nuovo fornitore
            $newEntity = Entity::create([
                'entity_type' => 'fornitore',
                'ragione_sociale' => $supplier['denominazione'] ?? '',
                'nome' => $supplier['denominazione'] ?? '',
                'indirizzo' => $supplier['indirizzo'] ?? '',
                'cap' => $supplier['cap'] ?? '',
                'comune' => $supplier['comune'] ?? '',
                'provincia' => $supplier['provincia'] ?? '',
                'partita_iva' => $supplier['partita_iva'] ?? '',
                'codice_fiscale' => $supplier['codice_fiscale'] ?? '',
                'telefono' => $supplier['telefono'] ?? '',
                'email' => $supplier['email'] ?? '',
                'pec' => $supplier['pec'] ?? '',
                'valid' => 1,
            ]);
            
            $this->id_entities = $newEntity->id_cliente;
            $this->supplier_found = true;
            $this->supplier_not_found = false;
            
            $this->dispatch('alert', [
                'type' => 'success', 
                'message' => "✅ Fornitore creato automaticamente! ID: {$newEntity->id_cliente}"
            ]);
            
        } catch (\Exception $e) {
            Log::error('Errore creazione fornitore automatica: ' . $e->getMessage());
            $this->dispatch('alert', [
                'type' => 'error', 
                'message' => 'Errore nella creazione automatica del fornitore: ' . $e->getMessage()
            ]);
        }
    }

    public function addRow()
    {
        $this->rows[] = [
            'id_cost_center' => null,
            'cost_center_name' => '',
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'discount_percentage' => 0,
        ];
    }

    public function removeRow($index)
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
        $this->calculateTotal();
    }

    public function calculateTotal()
    {
        $total = 0;
        foreach ($this->rows as $row) {
            $subtotal = ($row['quantity'] ?? 0) * ($row['unit_price'] ?? 0);
            $discount = $subtotal * (($row['discount_percentage'] ?? 0) / 100);
            $total += $subtotal - $discount;
        }
        $this->importo_totale = round($total, 6);
    }

    public function updatedRows()
    {
        $this->calculateTotal();
    }

    public function save()
    {
        // Se il fornitore non esiste, lo creiamo automaticamente prima del salvataggio
        if (!$this->supplier_found && $this->supplier_not_found) {
            $this->createSupplierAutomatically();
        }
        
        $this->validate();

        try {
            DB::beginTransaction();

            $ownershipData = $this->buildOwnershipData($this->id_ownership);
            $entityData = $this->buildEntityData($this->id_entities);

            $invoice = InvoiceReceived::create([
                'id_ownership' => $this->id_ownership,
                'id_entities' => $this->id_entities,
                'data_ownership' => $ownershipData,
                'data_entities' => $entityData,
                'type_invoice' => $this->type_invoice,
                'n_invoice' => $this->n_invoice,
                'data_invoice' => $this->data_invoice,
                'importo_totale' => $this->importo_totale,
                'causale' => $this->causale,
                'divisa' => $this->divisa,
                'status' => $this->status,
                'sdi_id' => $this->sdi_id,
                'xml_content' => $this->xml_content ?? null,
                'xml_filename' => $this->xml_file ? $this->xml_file->getClientOriginalName() : null,
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

    private function buildOwnershipData($ownershipId)
    {
        if (!$ownershipId) return null;
        $ownership = Ownership::find($ownershipId);
        if (!$ownership) return null;

        return [
            'Denominazione' => $ownership->Rag_Soc_intest ?: $ownership->RagSocialePr,
            'Indirizzo' => $ownership->IndirizzoPr,
            'CAP' => $ownership->CapPr,
            'Comune' => $ownership->LocalitPr,
            'Provincia' => $ownership->ProvinciaPr,
            'Nazione' => 'IT',
            'CodiceDestinatario' => $ownership->codice_SDI,
            'PECDestinatario' => $ownership->PEC,
            'PartitaIVA' => $ownership->PivaPr,
            'CodiceFiscale' => $ownership->CodFiscPr
        ];
    }

    private function buildEntityData($entityId)
    {
        if (!$entityId) return null;
        $entity = Entity::find($entityId);
        if (!$entity) return null;

        return [
            'Denominazione' => $entity->ragione_sociale ?: ($entity->nome . ' ' . $entity->cognome),
            'Nome' => $entity->nome,
            'Cognome' => $entity->cognome,
            'Indirizzo' => $entity->indirizzo ?? '',
            'CAP' => $entity->cap ?? '',
            'Comune' => $entity->comune ?? '',
            'Provincia' => $entity->provincia ?? '',
            'Nazione' => 'IT',
            'CodiceFiscale' => $entity->codice_fiscale,
            'PartitaIVA' => $entity->partita_iva,
            'RegimeFiscale' => 'RF01',
            'Telefono' => $entity->telefono ?? '',
            'Email' => $entity->email ?? '',
            'PEC' => $entity->pec ?? ''
        ];
    }

    public function render()
    {
        $tipoDocumento = config('gestionale.tipo_documento', []);
        $invoiceStatus = config('gestionale.invoice_status', []);
        $currencies = config('gestionale.currencies', []);
        
        return view('livewire.admin.invoices-xml-update', [
            'tipoDocumento' => $tipoDocumento,
            'invoiceStatus' => $invoiceStatus,
            'currencies' => $currencies,
        ]);
    }
}