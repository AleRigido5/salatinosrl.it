<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\InvoiceReceived;
use App\Models\InvoiceRow;
use App\Models\Ownership;
use App\Models\Entity;
use App\Models\CostCenter;
use App\Models\Vehicles;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class InvoiceReceivedCreate extends Component
{
    // Dati fattura
    public $id_ownership = '';
    public $type_invoice = '';
    public $n_invoice = '';
    public $data_invoice = '';
    public $importo_totale = 0;
    public $causale = '';

    // Totali aggregati
    public $total_taxable = 0;
    public $total_vat = 0;
    public $total_discount = 0;
    public $vatSummary = [];

    // Fornitore
    public $supplierSearch = '';
    public $supplierResults = [];
    public $selectedSupplierId = '';
    public $selectedSupplierName = '';
    public $showSupplierDropdown = false;

    // Nuovo fornitore (modal)
    public $showSupplierModal = false;
    public $newSupplierName = '';
    public $newSupplierPiva = '';
    public $newSupplierCf = '';
    public $newSupplierEmail = '';
    public $newSupplierPhone = '';
    public $newSupplierAddress = '';
    public $newSupplierCap = '';
    public $newSupplierCity = '';
    public $newSupplierProvince = '';

    // Righe fattura
    public $rows = [];

    // Unità di Misura
    public $unitMeasureList = [];

    // Centri di costo per righe
    public $costCenterSearch = [];
    public $costCenterResults = [];
    public $showCostCenterDropdown = [];
    public $showCostCenterDropdownLocal = [];
    public $selectedCostCenterId = [];
    public $selectedCostCenterName = [];

    // Mezzi per righe
    public $vehicleSearch = [];
    public $vehicleResults = [];
    public $showVehicleDropdown = [];
    public $showVehicleDropdownLocal = [];
    public $selectedVehicleId = [];
    public $selectedVehicleName = [];

    // Scadenze pagamento
    public $payments = [];
    public $total_payments_amount = 0;
    public $paymentMethods = [];

    // Lista aliquote IVA
    public $vatRatesList = [];

    protected function rules()
    {
        return [
            'id_ownership' => 'required|exists:ownership,id_proprieta',
            'type_invoice' => 'required|string|max:10',
            'n_invoice' => 'required|string|max:100',
            'data_invoice' => 'required|date',
            'importo_totale' => 'nullable|numeric|min:0',
            'causale' => 'nullable|string',
            'selectedSupplierId' => 'required|exists:entities,id_cliente',
            'rows' => 'required|array|min:1',
            'rows.*.code' => 'nullable|string|max:50',
            'rows.*.description' => 'required|string',
            'rows.*.quantity' => 'required|numeric|min:0',
            'rows.*.unit_price' => 'required|numeric|min:0',
            'rows.*.unit_measure' => 'nullable|string|max:10',
            'rows.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            // Il select IVA usa "rate" per le aliquote > 0 e "rate|id" per le aliquote
            // esenti/non imponibili (es. "0|5" per Cod. N1). La regex accetta entrambi
            // i formati: il vecchio 'numeric' rifiutava "0|5" e faceva fallire il save
            // in silenzio, senza mostrare nessun errore in pagina.
            'rows.*.vat_rate' => ['nullable', 'regex:/^\d+(\.\d+)?(\|\d+)?$/'],
            'rows.*.id_cost_center' => 'nullable|exists:cost_centers,id',
            'rows.*.id_vehicle' => 'nullable|exists:vehicles,id',
            'payments' => 'nullable|array',
            'payments.*.due_date' => 'nullable|date',
            'payments.*.amount' => 'nullable|numeric|min:0',
            'payments.*.payment_method' => 'nullable|string|max:10',
            'payments.*.iban' => 'nullable|string|max:50',
            'payments.*.status' => 'nullable|in:issued,paid,overdue,cancelled',
        ];
    }

    protected $messages = [
        'rows.*.vat_rate.regex' => 'Aliquota IVA non valida per la riga selezionata.',
        'rows.*.description.required' => 'La descrizione è obbligatoria.',
        'rows.*.quantity.required' => 'La quantità è obbligatoria.',
        'rows.*.unit_price.required' => 'Il prezzo unitario è obbligatorio.',
        'selectedSupplierId.required' => 'Devi selezionare un fornitore.',
    ];

    public function mount()
    {
        $this->data_invoice = date('Y-m-d');
        $this->loadVatRates();
        $this->loadUnitMeasures();
        $this->loadPaymentMethods();
        $this->addRow();
        $this->calculateTotals();     // Prima calcola il totale
        $this->addPayment();          // Poi aggiungi la scadenza con l'importo corretto
        $this->calculatePaymentsTotal();
    }

    public function loadVatRates()
    {
        $this->vatRatesList = DB::table('vat_rates')
            ->where('is_active', 1)
            ->orderBy('rate', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'code' => trim($item->code),
                    'description' => $item->description,
                    'rate' => (float) $item->rate,
                    'rate_percent' => (float) $item->rate * 100,
                    'sdi_nature' => $item->sdi_nature,
                    'nature' => $item->nature,
                ];
            })
            ->toArray();
    }

    public function loadUnitMeasures()
    {
        $this->unitMeasureList = DB::table('unita_misura')
            ->where('valid', 1)
            ->orderBy('ordinamento')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id_um,
                    'nome' => $item->nome,
                    'codice' => $item->codice,
                    'categoria' => $item->categoria,
                ];
            })
            ->toArray();
    }

    public function loadPaymentMethods()
    {
        $this->paymentMethods = PaymentMethod::getSelectArray();
    }

    public function addRow()
    {
        $index = count($this->rows);
        $this->rows[] = [
            'code' => '',
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'unit_measure' => 'Pezzi',
            'discount_percentage' => 0,
            'vat_rate' => 0.22,
            'id_vat_rate' => null,
            'id_cost_center' => null,
            'cost_center_name' => '',
            'id_vehicle' => null,
            'vehicle_name' => '',
            'taxable_amount' => 0,
            'vat_amount' => 0,
        ];

        // Inizializza centri di costo
        $this->costCenterSearch[$index] = '';
        $this->costCenterResults[$index] = [];
        $this->showCostCenterDropdown[$index] = false;
        $this->selectedCostCenterId[$index] = '';
        $this->selectedCostCenterName[$index] = '';

        // Inizializza mezzi
        $this->vehicleSearch[$index] = '';
        $this->vehicleResults[$index] = [];
        $this->showVehicleDropdown[$index] = false;
        $this->selectedVehicleId[$index] = '';
        $this->selectedVehicleName[$index] = '';

        $this->calculateTotals();
    }

    public function addPayment()
    {
        $this->payments[] = [
            'due_date' => $this->data_invoice ?: date('Y-m-d'),
            'amount' => $this->importo_totale,
            'payment_method' => 'MP05',
            'iban' => '',
            'status' => 'issued',
        ];
        $this->calculatePaymentsTotal();
    }

    public function removePayment($index)
    {
        unset($this->payments[$index]);
        $this->payments = array_values($this->payments);
        $this->calculatePaymentsTotal();
    }

    public function removeRow($index)
    {
        if (count($this->rows) > 1) {
            unset($this->rows[$index]);
            $this->rows = array_values($this->rows);

            // Rimuovi centri di costo
            unset($this->costCenterSearch[$index]);
            unset($this->costCenterResults[$index]);
            unset($this->showCostCenterDropdown[$index]);
            unset($this->selectedCostCenterId[$index]);
            unset($this->selectedCostCenterName[$index]);

            // Rimuovi mezzi
            unset($this->vehicleSearch[$index]);
            unset($this->vehicleResults[$index]);
            unset($this->showVehicleDropdown[$index]);
            unset($this->selectedVehicleId[$index]);
            unset($this->selectedVehicleName[$index]);

            // Riallinea gli array
            $this->costCenterSearch = array_values($this->costCenterSearch);
            $this->costCenterResults = array_values($this->costCenterResults);
            $this->showCostCenterDropdown = array_values($this->showCostCenterDropdown);
            $this->selectedCostCenterId = array_values($this->selectedCostCenterId);
            $this->selectedCostCenterName = array_values($this->selectedCostCenterName);
            $this->vehicleSearch = array_values($this->vehicleSearch);
            $this->vehicleResults = array_values($this->vehicleResults);
            $this->showVehicleDropdown = array_values($this->showVehicleDropdown);
            $this->selectedVehicleId = array_values($this->selectedVehicleId);
            $this->selectedVehicleName = array_values($this->selectedVehicleName);

            $this->calculateTotals();
        } else {
            $this->dispatch('alert', type: 'warning', message: 'Devi avere almeno una riga');
        }
    }

    public function updatedRows()
    {
        $this->calculateTotals();
    }

    public function updatedRowsQuantity($value, $key)
    {
        $this->calculateTotals();
    }

    public function updatedRowsUnitPrice($value, $key)
    {
        $this->calculateTotals();
    }

    public function updatedRowsDiscountPercentage($value, $key)
    {
        $this->calculateTotals();
    }

    public function updatedRowsVatRate($value, $key)
    {
        $this->calculateTotals();
    }

    public function updatedPayments()
    {
        $this->calculatePaymentsTotal();
    }

    public function updatedPaymentsAmount($value, $key)
    {
        $this->calculatePaymentsTotal();
    }

    public function calculatePaymentsTotal()
    {
        $total = 0;
        foreach ($this->payments as $payment) {
            $total += floatval($payment['amount'] ?? 0);
        }
        $this->total_payments_amount = $total;
    }

    /**
     * Il value del <select> aliquota IVA può arrivare in due formati:
     *  - "0.22"   -> aliquota normale, il numero è il rate
     *  - "0|5"    -> aliquota esente/non imponibile (rate 0), "5" è l'id
     *                della riga vat_rates selezionata (serve per sapere
     *                quale natura SDI - N1, N2, N3... - è stata scelta)
     *
     * Ritorna sempre ['rate' => float, 'id' => int|null].
     */
    protected function parseVatRateValue($rawValue): array
    {
        $raw = trim((string) ($rawValue ?? '0'));

        if ($raw === '') {
            return ['rate' => 0.0, 'id' => null];
        }

        if (str_contains($raw, '|')) {
            [$ratePart, $idPart] = explode('|', $raw, 2);

            return [
                'rate' => floatval($ratePart),
                'id' => is_numeric($idPart) ? (int) $idPart : null,
            ];
        }

        return ['rate' => floatval($raw), 'id' => null];
    }

    public function calculateTotals()
    {
        $totalTaxable = 0;
        $totalVat = 0;
        $totalDiscount = 0;

        // Array per raggruppare per aliquota (+ id, per distinguere le nature diverse)
        $vatGroup = [];

        foreach ($this->rows as $index => &$row) {
            $quantity = floatval($row['quantity'] ?? 1);
            $unitPrice = floatval($row['unit_price'] ?? 0);
            $discountPercentage = floatval($row['discount_percentage'] ?? 0);

            $parsedVat = $this->parseVatRateValue($row['vat_rate'] ?? 0);
            $vatRate = $parsedVat['rate'];
            $vatRateId = $parsedVat['id'];

            // Salva l'id della riga vat_rates selezionata (utile in save())
            $row['id_vat_rate'] = $vatRateId;

            // Calcolo lordo = quantità * prezzo unitario
            $grossAmount = $quantity * $unitPrice;

            // Calcolo sconto
            $discountAmount = $grossAmount * ($discountPercentage / 100);
            $totalDiscount += $discountAmount;

            // Calcolo imponibile (lordo - sconto)
            $taxable = $grossAmount - $discountAmount;
            $totalTaxable += $taxable;

            // Calcolo IVA
            $vatAmount = $taxable * $vatRate;
            $totalVat += $vatAmount;

            // Chiave di raggruppamento: rate + id (se presente), così due
            // aliquote 0% con natura diversa (N1 vs N2) restano separate
            $key = $vatRateId ? $vatRate . '|' . $vatRateId : (string) $vatRate;

            if (!isset($vatGroup[$key])) {
                $vatInfo = $vatRateId
                    ? DB::table('vat_rates')->where('id', $vatRateId)->where('is_active', 1)->first()
                    : DB::table('vat_rates')->where('rate', $vatRate)->where('is_active', 1)->first();

                $vatGroup[$key] = [
                    'id_vat_rate' => $vatRateId,
                    'rate' => $vatRate,
                    'rate_percent' => number_format($vatRate * 100, 2),
                    'description' => $vatInfo ? $vatInfo->description : ($vatRate == 0 ? 'IVA Esente/Non imponibile' : 'IVA ' . number_format($vatRate * 100, 2) . '%'),
                    'nature_code' => $vatInfo ? $vatInfo->sdi_nature : null,
                    'taxable_amount' => 0,
                    'vat_amount' => 0,
                ];
            }

            $vatGroup[$key]['taxable_amount'] += $taxable;
            $vatGroup[$key]['vat_amount'] += $vatAmount;

            // Aggiorna i campi calcolati della riga
            $row['taxable_amount'] = $taxable;
            $row['vat_amount'] = $vatAmount;
        }
        unset($row);

        // Ordina per aliquota (discendente)
        uasort($vatGroup, function ($a, $b) {
            return $b['rate'] <=> $a['rate'];
        });

        $this->vatSummary = array_values($vatGroup);
        $this->total_taxable = $totalTaxable;
        $this->total_vat = $totalVat;
        $this->total_discount = $totalDiscount;
        $this->importo_totale = $totalTaxable + $totalVat;

        // Sincronizza la prima scadenza con il totale fattura
        $this->syncFirstPaymentWithTotal();
    }

    // Metodo per sincronizzare la prima scadenza con il totale fattura
    protected function syncFirstPaymentWithTotal()
    {
        if (count($this->payments) > 0) {
            $this->payments[0]['amount'] = $this->importo_totale;
            $this->calculatePaymentsTotal();
        }
    }

    // ==================== AUTOCOMPLETE FORNITORE ====================
    public function updatedSupplierSearch()
    {
        if (strlen($this->supplierSearch) < 2) {
            $this->supplierResults = [];
            $this->showSupplierDropdown = false;
            return;
        }

        $results = Entity::where('valid', 1)
            ->whereIn('entity_type', ['fornitore', 'entrambi'])
            ->where(function ($q) {
                $q->where('ragione_sociale', 'like', '%' . $this->supplierSearch . '%')
                  ->orWhere('nome', 'like', '%' . $this->supplierSearch . '%')
                  ->orWhere('cognome', 'like', '%' . $this->supplierSearch . '%')
                  ->orWhere('partita_iva', 'like', '%' . $this->supplierSearch . '%');
            })
            ->limit(10)
            ->get(['id_cliente as id', 'ragione_sociale as name', 'partita_iva as piva']);

        $this->supplierResults = $results->toArray();
        $this->showSupplierDropdown = count($this->supplierResults) > 0;
    }

    public function selectSupplier($id, $name)
    {
        $this->selectedSupplierId = $id;
        $this->selectedSupplierName = $name;
        $this->supplierSearch = $name;
        $this->showSupplierDropdown = false;
    }

    public function clearSupplier()
    {
        $this->selectedSupplierId = '';
        $this->selectedSupplierName = '';
        $this->supplierSearch = '';
    }

    // ==================== AUTOCOMPLETE CENTRO DI COSTO ====================
    public function updatedCostCenterSearch($value, $index)
    {
        if (isset($this->selectedCostCenterId[$index]) && $this->selectedCostCenterId[$index] &&
            $value === $this->selectedCostCenterName[$index]) {
            $this->showCostCenterDropdown[$index] = false;
            return;
        }

        if (isset($this->selectedCostCenterId[$index]) && $this->selectedCostCenterId[$index]) {
            $this->rows[$index]['id_cost_center'] = null;
            $this->rows[$index]['cost_center_name'] = '';
            $this->selectedCostCenterId[$index] = '';
            $this->selectedCostCenterName[$index] = '';
            $this->calculateTotals();
        }

        if (strlen($value) < 2) {
            $this->costCenterResults[$index] = [];
            $this->showCostCenterDropdown[$index] = false;
            return;
        }

        $results = CostCenter::where('valid', 1)
            ->where('Nome', 'like', '%' . $value . '%')
            ->limit(10)
            ->get(['id', 'Nome as name']);

        $this->costCenterResults[$index] = $results->toArray();
        $this->showCostCenterDropdown[$index] = count($this->costCenterResults[$index]) > 0;
    }

    public function selectCostCenter($id, $name, $index)
    {
        $this->rows[$index]['id_cost_center'] = $id;
        $this->rows[$index]['cost_center_name'] = $name;
        $this->selectedCostCenterId[$index] = $id;
        $this->selectedCostCenterName[$index] = $name;
        $this->costCenterSearch[$index] = $name;
        $this->showCostCenterDropdown[$index] = false;
        $this->calculateTotals();
    }

    public function clearCostCenter($index)
    {
        $this->rows[$index]['id_cost_center'] = null;
        $this->rows[$index]['cost_center_name'] = '';
        $this->costCenterSearch[$index] = '';
        $this->selectedCostCenterId[$index] = '';
        $this->selectedCostCenterName[$index] = '';
        $this->showCostCenterDropdown[$index] = false;
        $this->calculateTotals();
    }

    // ==================== AUTOCOMPLETE MEZZI ====================
    public function updatedVehicleSearch($value, $index)
    {
        if (isset($this->selectedVehicleId[$index]) && $this->selectedVehicleId[$index] &&
            $value === $this->selectedVehicleName[$index]) {
            $this->showVehicleDropdown[$index] = false;
            return;
        }

        if (isset($this->selectedVehicleId[$index]) && $this->selectedVehicleId[$index]) {
            $this->rows[$index]['id_vehicle'] = null;
            $this->rows[$index]['vehicle_name'] = '';
            $this->selectedVehicleId[$index] = '';
            $this->selectedVehicleName[$index] = '';
            $this->calculateTotals();
        }

        if (strlen($value) < 2) {
            $this->vehicleResults[$index] = [];
            $this->showVehicleDropdown[$index] = false;
            return;
        }

        $results = Vehicles::where('valid', 1)
            ->where(function ($q) use ($value) {
                $q->where('targa', 'like', '%' . $value . '%')
                  ->orWhere('modello', 'like', '%' . $value . '%')
                  ->orWhere('marca', 'like', '%' . $value . '%');
            })
            ->limit(10)
            ->get(['id', DB::raw("CONCAT(marca, ' ', modello, ' - ', targa) as name"), 'targa as plate']);

        $this->vehicleResults[$index] = $results->toArray();
        $this->showVehicleDropdown[$index] = count($this->vehicleResults[$index]) > 0;
    }

    public function selectVehicle($id, $name, $index)
    {
        $this->rows[$index]['id_vehicle'] = $id;
        $this->rows[$index]['vehicle_name'] = $name;
        $this->selectedVehicleId[$index] = $id;
        $this->selectedVehicleName[$index] = $name;
        $this->vehicleSearch[$index] = $name;
        $this->showVehicleDropdown[$index] = false;
        $this->calculateTotals();
    }

    public function clearVehicle($index)
    {
        $this->rows[$index]['id_vehicle'] = null;
        $this->rows[$index]['vehicle_name'] = '';
        $this->vehicleSearch[$index] = '';
        $this->selectedVehicleId[$index] = '';
        $this->selectedVehicleName[$index] = '';
        $this->showVehicleDropdown[$index] = false;
        $this->calculateTotals();
    }

    // ==================== MODALE NUOVO FORNITORE ====================
    public function openSupplierModal()
    {
        $this->showSupplierModal = true;
    }

    public function closeSupplierModal()
    {
        $this->showSupplierModal = false;
        $this->resetNewSupplierFields();
    }

    public function resetNewSupplierFields()
    {
        $this->newSupplierName = '';
        $this->newSupplierPiva = '';
        $this->newSupplierCf = '';
        $this->newSupplierEmail = '';
        $this->newSupplierPhone = '';
        $this->newSupplierAddress = '';
        $this->newSupplierCap = '';
        $this->newSupplierCity = '';
        $this->newSupplierProvince = '';
    }

    public function createSupplier()
    {
        $this->validate([
            'newSupplierName' => 'required|string|max:255',
            'newSupplierPiva' => 'nullable|string|max:20',
            'newSupplierCf' => 'nullable|string|max:20',
        ]);

        try {
            $entity = Entity::create([
                'entity_type' => 'fornitore',
                'ragione_sociale' => $this->newSupplierName,
                'nome' => $this->newSupplierName,
                'partita_iva' => $this->newSupplierPiva,
                'codice_fiscale' => $this->newSupplierCf,
                'email' => $this->newSupplierEmail,
                'telefono' => $this->newSupplierPhone,
                'indirizzo' => $this->newSupplierAddress,
                'cap' => $this->newSupplierCap,
                'comune' => $this->newSupplierCity,
                'provincia' => $this->newSupplierProvince,
                'valid' => 1,
                'created_by_system' => 0,
            ]);

            $this->selectedSupplierId = $entity->id_cliente;
            $this->selectedSupplierName = $this->newSupplierName;
            $this->supplierSearch = $this->newSupplierName;
            $this->showSupplierModal = false;
            $this->resetNewSupplierFields();

            $this->dispatch('alert', type: 'success', message: 'Fornitore creato con successo!');
        } catch (\Exception $e) {
            Log::error('Errore creazione fornitore: ' . $e->getMessage());
            $this->dispatch('alert', type: 'error', message: 'Errore: ' . $e->getMessage());
        }
    }

    public function save()
    {
        $this->validate();

        // Rifiuta esplicitamente se, nonostante la validazione, i totali non
        // combaciano con le scadenze inserite manualmente: evita di salvare
        // una fattura "sbilanciata" senza che l'utente se ne accorga.
        if ($this->total_payments_amount > 0 && abs($this->total_payments_amount - $this->importo_totale) > 0.01) {
            $this->dispatch('alert', type: 'error', message: 'Il totale delle scadenze (€ ' . number_format($this->total_payments_amount, 2, ',', '.') . ') non corrisponde al totale fattura (€ ' . number_format($this->importo_totale, 2, ',', '.') . ').');
            return null;
        }

        try {
            DB::beginTransaction();

            $defaultStatus = 'issued';

            $invoice = InvoiceReceived::create([
                'id_ownership' => $this->id_ownership,
                'id_entities' => $this->selectedSupplierId,
                'type_invoice' => $this->type_invoice,
                'n_invoice' => $this->n_invoice,
                'data_invoice' => $this->data_invoice,
                'importo_totale' => $this->importo_totale,
                'causale' => $this->causale,
                'divisa' => 'EUR',
                'status' => $defaultStatus,
                'is_manual' => true,
                'created_by' => Auth::guard('admin')->id(),
                'updated_by' => Auth::guard('admin')->id(),
                'imported_at' => now(),
            ]);

            // Salva le righe fattura
            foreach ($this->rows as $row) {
                $taxable = floatval($row['taxable_amount'] ?? 0);
                $discountPercentage = floatval($row['discount_percentage'] ?? 0);
                $parsedVat = $this->parseVatRateValue($row['vat_rate'] ?? 0);

                InvoiceRow::create([
                    'document_id' => $invoice->id,
                    'document_type' => 'invoice_received',
                    'id_cost_center' => $row['id_cost_center'] ?? null,
                    'id_vehicle' => $row['id_vehicle'] ?? null,
                    'description' => $row['description'],
                    'code' => $row['code'] ?? null,
                    'quantity' => floatval($row['quantity'] ?? 1),
                    'unit_price' => floatval($row['unit_price'] ?? 0),
                    'unit_measure' => $row['unit_measure'] ?? null,
                    'discount_percentage' => $discountPercentage,
                    'vat_rate' => $parsedVat['rate'],
                    'total' => $taxable,
                ]);
            }

            // Salva i riepiloghi IVA (già raggruppati correttamente per rate + natura)
            foreach ($this->vatSummary as $vat) {
                $vatInfo = null;

                if (!empty($vat['id_vat_rate'])) {
                    $vatInfo = DB::table('vat_rates')
                        ->where('id', $vat['id_vat_rate'])
                        ->where('is_active', 1)
                        ->first();
                } elseif ($vat['rate'] > 0) {
                    $vatInfo = DB::table('vat_rates')
                        ->where('rate', $vat['rate'])
                        ->where('is_active', 1)
                        ->first();
                }

                $sdiNature = $vat['nature_code'] ?? ($vatInfo->sdi_nature ?? null);
                $vatLawReference = $vat['description'] ?? ($vatInfo->description ?? null);

                \App\Models\InvoiceVatSummary::create([
                    'vatable_id' => $invoice->id,
                    'vatable_type' => InvoiceReceived::class,
                    'tax_rate' => $vat['rate'] * 100, // es: 0.22 -> 22.00
                    'sdi_nature' => $sdiNature,
                    'taxable_amount' => $vat['taxable_amount'],
                    'tax_amount' => $vat['vat_amount'],
                    'vat_law_reference' => $vatLawReference,
                    'esigibilita_iva' => 'I', // I = Immediata
                ]);
            }

            // Salva le scadenze pagamento
            foreach ($this->payments as $payment) {
                $invoice->payments()->create([
                    'due_date' => $payment['due_date'],
                    'amount' => floatval($payment['amount']),
                    'payment_method' => $payment['payment_method'] ?? null,
                    'iban' => $payment['iban'] ?? null,
                    'paid_at' => ($payment['status'] ?? 'issued') === 'paid' ? now() : null,
                    'status' => $payment['status'] ?? 'issued',
                ]);
            }

            DB::commit();

            session()->flash('success', 'Fattura creata con successo!');
            return redirect()->route('admin.invoices-received.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore salvataggio fattura: ' . $e->getMessage());
            $this->dispatch('alert', type: 'error', message: 'Errore: ' . $e->getMessage());

            return null;
        }
    }

    public function getOwnershipsProperty()
    {
        return Ownership::where('valid', 1)
            ->orderBy('RagAbbrev')
            ->get();
    }

    public function getTypeDocumentsProperty()
    {
        return config('gestionale.tipo_documento', []);
    }

    public function render()
    {
        return view('livewire.admin.invoice-received-create', [
            'ownerships' => $this->ownerships,
            'typeDocuments' => $this->typeDocuments,
            'vatRatesList' => $this->vatRatesList,
            'paymentMethods' => $this->paymentMethods,
            'unitMeasureList' => $this->unitMeasureList,
        ]);
    }
}