<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\InvoiceReceived;
use App\Models\InvoiceRow;
use App\Models\Ownership;
use App\Models\Entity;
use App\Models\CostCenter;
use App\Models\VatRate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use SimpleXMLElement;

class InvoiceXmlUpload extends Component
{
    use WithFileUploads;
    
    public $xmlFile;
    public $isUploading = false;
    public $parsedData = null;
    public $hasData = false;
    
    // Dati parsati dal XML
    public $invoiceData = [];
    public $rows = [];
    
    // Costanti per mappatura
    protected $regimeFiscali = [];
    protected $tipiDocumento = [];
    protected $natureOperazione = [];
    
    public function mount()
    {
        $this->regimeFiscali = config('gestionale.regime_fiscale', []);
        $this->tipiDocumento = config('gestionale.tipo_documento', []);
        $this->natureOperazione = config('gestionale.natura_operazione', []);
    }
    
    public function uploadXml()
    {
        $this->validate([
            'xmlFile' => 'required|file|mimes:xml|max:5120', // max 5MB
        ]);
        
        $this->isUploading = true;
        
        try {
            $xmlContent = file_get_contents($this->xmlFile->getRealPath());
            $xml = new SimpleXMLElement($xmlContent);
            
            // Registra namespace FatturaElettronica
            $namespaces = $xml->getNamespaces(true);
            $ns = isset($namespaces['']) ? $namespaces[''] : null;
            
            if ($ns) {
                $xml->registerXPathNamespace('fe', $ns);
            }
            
            $this->parseFattura($xml, $ns);
            $this->hasData = true;
            
            $this->dispatch('showSuccess', message: 'File XML caricato con successo! Verifica i dati e conferma.');
            
        } catch (\Exception $e) {
            Log::error('Errore parsing XML: ' . $e->getMessage());
            $this->dispatch('showError', message: 'Errore durante la lettura del file XML: ' . $e->getMessage());
        } finally {
            $this->isUploading = false;
        }
    }
    
    protected function parseFattura($xml, $ns)
    {
        // Parse dati fattura
        $this->invoiceData = [];
        $this->rows = [];
        
        // Dati cedente/prestatore (fornitore)
        $cedente = [];
        if ($ns) {
            $cedenteNode = $xml->xpath('//fe:CedentePrestatore');
            if (!empty($cedenteNode)) {
                $cedente = $cedenteNode[0];
                
                // Dati anagrafici
                $denominazione = $cedente->xpath('.//fe:Denominazione');
                $nome = $cedente->xpath('.//fe:Nome');
                $cognome = $cedente->xpath('.//fe:Cognome');
                $codiceFiscale = $cedente->xpath('.//fe:CodiceFiscale');
                $partitaIva = $cedente->xpath('.//fe:PartitaIVA');
                
                $this->invoiceData['fornitore'] = [
                    'denominazione' => !empty($denominazione) ? (string)$denominazione[0] : '',
                    'nome' => !empty($nome) ? (string)$nome[0] : '',
                    'cognome' => !empty($cognome) ? (string)$cognome[0] : '',
                    'codice_fiscale' => !empty($codiceFiscale) ? (string)$codiceFiscale[0] : '',
                    'partita_iva' => !empty($partitaIva) ? (string)$partitaIva[0] : '',
                ];
                
                // Sede
                $sede = $cedente->xpath('.//fe:Sede');
                if (!empty($sede)) {
                    $indirizzo = $sede[0]->xpath('.//fe:Indirizzo');
                    $cap = $sede[0]->xpath('.//fe:CAP');
                    $comune = $sede[0]->xpath('.//fe:Comune');
                    $provincia = $sede[0]->xpath('.//fe:Provincia');
                    $nazione = $sede[0]->xpath('.//fe:Nazione');
                    
                    $this->invoiceData['fornitore']['indirizzo'] = !empty($indirizzo) ? (string)$indirizzo[0] : '';
                    $this->invoiceData['fornitore']['cap'] = !empty($cap) ? (string)$cap[0] : '';
                    $this->invoiceData['fornitore']['comune'] = !empty($comune) ? (string)$comune[0] : '';
                    $this->invoiceData['fornitore']['provincia'] = !empty($provincia) ? (string)$provincia[0] : '';
                    $this->invoiceData['fornitore']['nazione'] = !empty($nazione) ? (string)$nazione[0] : 'IT';
                }
            }
        }
        
        // Dati fattura
        $datiGenerali = $xml->xpath('//fe:DatiGenerali');
        if (!empty($datiGenerali)) {
            $datiFattura = $datiGenerali[0]->xpath('.//fe:DatiFattura');
            if (!empty($datiFattura)) {
                $tipoDocumento = $datiFattura[0]->xpath('.//fe:TipoDocumento');
                $numeroFattura = $datiFattura[0]->xpath('.//fe:NumeroFattura');
                $dataFattura = $datiFattura[0]->xpath('.//fe:DataFattura');
                $importoTotale = $datiFattura[0]->xpath('.//fe:ImportoTotaleDocumento');
                
                $this->invoiceData['tipo_documento'] = !empty($tipoDocumento) ? (string)$tipoDocumento[0] : 'TD01';
                $this->invoiceData['numero_fattura'] = !empty($numeroFattura) ? (string)$numeroFattura[0] : '';
                $this->invoiceData['data_fattura'] = !empty($dataFattura) ? (string)$dataFattura[0] : Carbon::now()->format('Y-m-d');
                $this->invoiceData['importo_totale'] = !empty($importoTotale) ? floatval($importoTotale[0]) : 0;
            }
            
            // Causale
            $causale = $datiGenerali[0]->xpath('.//fe:Causale');
            if (!empty($causale)) {
                $this->invoiceData['causale'] = (string)$causale[0];
            }
        }
        
        // Dati beni/servizi (righe fattura)
        $datiBeni = $xml->xpath('//fe:DatiBeniServizi');
        if (!empty($datiBeni)) {
            foreach ($datiBeni as $index => $bene) {
                $descrizione = $bene->xpath('.//fe:Descrizione');
                $quantita = $bene->xpath('.//fe:Quantita');
                $prezzoUnitario = $bene->xpath('.//fe:PrezzoUnitario');
                $aliquotaIva = $bene->xpath('.//fe:AliquotaIVA');
                $sconto = $bene->xpath('.//fe:ScontoMaggiorazione');
                
                // Cerca la natura
                $natura = $bene->xpath('.//fe:Natura');
                
                $this->rows[] = [
                    'id' => null,
                    'description' => !empty($descrizione) ? (string)$descrizione[0] : '',
                    'quantity' => !empty($quantita) ? floatval($quantita[0]) : 1,
                    'unit_price' => !empty($prezzoUnitario) ? floatval($prezzoUnitario[0]) : 0,
                    'vat_rate' => !empty($aliquotaIva) ? floatval($aliquotaIva[0]) : 22,
                    'vat_rate_id' => $this->findVatRateId(!empty($aliquotaIva) ? floatval($aliquotaIva[0]) : 22),
                    'discount_percentage' => 0,
                    'natura' => !empty($natura) ? (string)$natura[0] : null,
                    'total' => 0,
                ];
                
                // Calcola totale riga
                $this->rows[$index]['total'] = ($this->rows[$index]['quantity'] * $this->rows[$index]['unit_price']) * (1 - $this->rows[$index]['discount_percentage'] / 100);
            }
        }
        
        // Cerca nell'azienda (ownership) corrispondente
        $this->findOwnership();
        $this->findEntity();
    }
    
    protected function findVatRateId($rate)
    {
        $vatRate = VatRate::where('rate', $rate)->first();
        return $vatRate ? $vatRate->id : null;
    }
    
    protected function findOwnership()
    {
        // Cerca la proprietà in base alla partita IVA o denominazione
        $partitaIva = $this->invoiceData['fornitore']['partita_iva'] ?? '';
        $denominazione = $this->invoiceData['fornitore']['denominazione'] ?? '';
        
        if ($partitaIva) {
            $ownership = Ownership::where('PartitaIvaProp', $partitaIva)->first();
            if ($ownership) {
                $this->invoiceData['id_ownership'] = $ownership->id_proprieta;
                return;
            }
        }
        
        if ($denominazione) {
            $ownership = Ownership::where('Rag_Soc_intest', 'like', "%{$denominazione}%")
                ->orWhere('RagAbbrev', 'like', "%{$denominazione}%")
                ->first();
            if ($ownership) {
                $this->invoiceData['id_ownership'] = $ownership->id_proprieta;
                return;
            }
        }
        
        $this->invoiceData['id_ownership'] = null;
    }
    
    protected function findEntity()
    {
        // Cerca il fornitore nella tabella entities
        $partitaIva = $this->invoiceData['fornitore']['partita_iva'] ?? '';
        $codiceFiscale = $this->invoiceData['fornitore']['codice_fiscale'] ?? '';
        $denominazione = $this->invoiceData['fornitore']['denominazione'] ?? '';
        
        if ($partitaIva) {
            $entity = Entity::where('partita_iva', $partitaIva)->first();
            if ($entity) {
                $this->invoiceData['id_entities'] = $entity->id_cliente;
                return;
            }
        }
        
        if ($codiceFiscale) {
            $entity = Entity::where('codice_fiscale', $codiceFiscale)->first();
            if ($entity) {
                $this->invoiceData['id_entities'] = $entity->id_cliente;
                return;
            }
        }
        
        if ($denominazione) {
            $entity = Entity::where('ragione_sociale', 'like', "%{$denominazione}%")->first();
            if ($entity) {
                $this->invoiceData['id_entities'] = $entity->id_cliente;
                return;
            }
        }
        
        $this->invoiceData['id_entities'] = null;
    }
    
    public function updateRowVat($index, $vatRateId)
    {
        if (isset($this->rows[$index])) {
            $this->rows[$index]['vat_rate_id'] = $vatRateId;
            $vatRate = VatRate::find($vatRateId);
            $this->rows[$index]['vat_rate'] = $vatRate ? $vatRate->rate : 22;
            $this->rows[$index]['total'] = ($this->rows[$index]['quantity'] * $this->rows[$index]['unit_price']) * (1 - $this->rows[$index]['discount_percentage'] / 100);
            $this->recalculateTotal();
        }
    }
    
    public function recalculateTotal()
    {
        $total = 0;
        foreach ($this->rows as $row) {
            $total += $row['total'];
        }
        $this->invoiceData['importo_totale'] = $total;
    }
    
    public function saveInvoice()
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_purchases')) {
            $this->dispatch('showError', message: 'Permessi insufficienti');
            return;
        }
        
        $this->validate([
            'invoiceData.id_ownership' => 'required|exists:ownership,id_proprieta',
            'invoiceData.id_entities' => 'required|exists:entities,id_cliente',
            'invoiceData.numero_fattura' => 'required|string',
            'invoiceData.data_fattura' => 'required|date',
            'invoiceData.importo_totale' => 'required|numeric|min:0',
            'rows' => 'required|array|min:1',
        ]);
        
        try {
            DB::beginTransaction();
            
            $ownership = Ownership::find($this->invoiceData['id_ownership']);
            $entity = Entity::find($this->invoiceData['id_entities']);
            
            $dataOwnership = [
                'Denominazione' => $ownership->RagAbbrev ?? $ownership->Rag_Soc_intest ?? '',
                'indirizzo' => $ownership->IndirizzoProp ?? '',
                'CAP' => $ownership->cap ?? '',
                'Comune' => $ownership->ComuneProp ?? '',
                'Provincia' => $ownership->ProvProp ?? '',
                'Nazione' => 'IT',
                'CodiceDestinatario' => $ownership->CodiceDestinatario ?? '',
                'PECDestinatario' => $ownership->PECDestinatario ?? '',
            ];
            
            $dataEntities = [
                'Denominazione' => $this->invoiceData['fornitore']['denominazione'] ?? $entity->ragione_sociale ?? ($entity->nome . ' ' . $entity->cognome),
                'indirizzo' => $this->invoiceData['fornitore']['indirizzo'] ?? $entity->indirizzo ?? '',
                'CAP' => $this->invoiceData['fornitore']['cap'] ?? $entity->cap ?? '',
                'Comune' => $this->invoiceData['fornitore']['comune'] ?? $entity->citta ?? '',
                'Provincia' => $this->invoiceData['fornitore']['provincia'] ?? $entity->provincia ?? '',
                'Nazione' => $this->invoiceData['fornitore']['nazione'] ?? 'IT',
                'CodiceFiscale' => $this->invoiceData['fornitore']['codice_fiscale'] ?? $entity->codice_fiscale ?? $entity->partita_iva ?? '',
                'RegimeFiscale' => 'RF01',
                'Telefono' => $entity->telefono ?? '',
                'Email' => $entity->email ?? '',
            ];
            
            // Crea la fattura
            $invoice = InvoiceReceived::create([
                'id_ownership' => $this->invoiceData['id_ownership'],
                'id_entities' => $this->invoiceData['id_entities'],
                'data_ownership' => json_encode($dataOwnership),
                'data_entities' => json_encode($dataEntities),
                'type_invoice' => $this->invoiceData['tipo_documento'] ?? 'TD01',
                'n_invoice' => $this->invoiceData['numero_fattura'],
                'data_invoice' => $this->invoiceData['data_fattura'],
                'importo_totale' => $this->invoiceData['importo_totale'],
                'causale' => $this->invoiceData['causale'] ?? null,
                'divisa' => 'EUR',
                'status' => 'draft',
                'sdi_id' => null,
            ]);
            
            // Crea le righe
            foreach ($this->rows as $row) {
                InvoiceRow::create([
                    'document_id' => $invoice->id,
                    'document_type' => 'invoice_received',
                    'id_cost_center' => null, // Da selezionare manualmente dopo
                    'description' => $row['description'],
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'vat_rate_id' => $row['vat_rate_id'],
                    'discount_percentage' => $row['discount_percentage'] ?? 0,
                    'total' => $row['total'],
                ]);
            }
            
            DB::commit();
            
            $this->resetForm();
            $this->dispatch('showSuccess', message: 'Fattura importata con successo!');
            
            return redirect()->route('admin.invoices-received.index')
                ->with('success', 'Fattura importata con successo!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore salvataggio fattura: ' . $e->getMessage());
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }
    
    public function resetForm()
    {
        $this->xmlFile = null;
        $this->parsedData = null;
        $this->hasData = false;
        $this->invoiceData = [];
        $this->rows = [];
    }
    
    public function getVatRatesProperty()
    {
        return VatRate::where('is_active', 1)->orderBy('rate')->get();
    }
    
    public function getOwnershipsProperty()
    {
        return Ownership::where('valid', 1)->orderBy('RagAbbrev')->get();
    }
    
    public function getEntitiesProperty()
    {
        return Entity::where('valid', 1)->orderBy('ragione_sociale')->orderBy('nome')->get();
    }
    
    public function getCostCentersProperty()
    {
        return CostCenter::where('valid', 1)->orderBy('Nome')->get();
    }
    
    public function render()
    {
        return view('livewire.admin.invoices-received.invoice-xml-upload', [
            'vatRates' => $this->vatRates,
            'ownerships' => $this->ownerships,
            'entities' => $this->entities,
            'costCenters' => $this->costCenters,
        ]);
    }
}