<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\WarehouseDdt;
use App\Models\WarehouseDdtRow;
use App\Models\WarehouseMovement;
use App\Models\WarehouseProduct;
use App\Models\Entity;
use App\Models\Ownership;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WarehouseDdtTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // 'acquisto' | 'vendita' — passato dalla shell di pagina
    public string $type = 'acquisto';

    // Filtri
    public string $search = '';
    public string $statusFilter = '';
    public int $perPage = 50;

    // ==================== FORM TESTATA ====================
    public bool $showFormModal = false;
    public ?int $editingId = null;
    public string $ddt_number = '';
    public string $ddt_date = '';
    public string $causale = '';

    // Autocomplete Cliente/Fornitore
    public string $entitySearch = '';
    public Collection $entityResults;
    public string $selectedEntityId = '';
    public string $selectedEntityName = '';
    public bool $showEntityDropdown = false;

    // Autocomplete Proprietà
    public string $ownershipSearch = '';
    public Collection $ownershipResults;
    public string $selectedOwnershipId = '';
    public string $selectedOwnershipName = '';
    public bool $showOwnershipDropdown = false;

    // Righe DDT — ogni riga è un array associativo semplice
    // (id, id_product, product_name, description, quantity, unit_of_measure, note)
    public array $rows = [];

    // FIX (bug segnalato: autocomplete riga non funzionava e bloccava anche
    // "Salva Bozza"): invece di tenere ricerca/risultati/dropdown-aperto come
    // ARRAY indicizzati per riga (pattern fragile con wire:model annidato +
    // @js statico, che poteva lasciare il menu a tendina "bloccato" visibile
    // sopra il resto del modal, intercettando i click anche sul pulsante
    // Salva), gestiamo UNA sola riga "attiva" alla volta per la ricerca
    // prodotto, con proprietà semplici — esattamente lo stesso pattern,
    // già collaudato, di entitySearch/ownershipSearch qui sopra.
    public string $activeRowIndex = ''; // '' = nessuna riga in ricerca
    public string $rowProductQuery = '';
    public Collection $rowProductQueryResults;

    // Dettaglio / eliminazione
    public bool $showDetailModal = false;
    public ?WarehouseDdt $viewingDdt = null;
    public ?int $deletingId = null;

    public function mount(string $type = 'acquisto'): void
    {
        $this->type = in_array($type, ['acquisto', 'vendita']) ? $type : 'acquisto';
        $this->entityResults = new Collection();
        $this->ownershipResults = new Collection();
        $this->rowProductQueryResults = new Collection();
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->resetPage();
    }

    // ==================== ELENCO ====================
    public function getDdtsProperty()
    {
        $query = WarehouseDdt::where('type', $this->type)
            ->with(['entity', 'ownership'])
            ->withCount('rows');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('ddt_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('entity', fn ($eq) => $eq->where('ragione_sociale', 'like', '%' . $this->search . '%'));
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return $query->orderByDesc('ddt_date')->orderByDesc('id')->paginate($this->perPage);
    }

    // ==================== AUTOCOMPLETE CLIENTE/FORNITORE ====================
    public function updatedEntitySearch(): void
    {
        if (!empty($this->selectedEntityId) && $this->entitySearch === $this->selectedEntityName) {
            $this->showEntityDropdown = false;
            return;
        }
        if (!empty($this->selectedEntityId) && $this->entitySearch !== $this->selectedEntityName) {
            $this->selectedEntityId = '';
            $this->selectedEntityName = '';
        }
        if (strlen(trim($this->entitySearch)) < 2) {
            $this->entityResults = new Collection();
            $this->showEntityDropdown = false;
            return;
        }

        $entityTypes = $this->type === 'acquisto'
            ? ['fornitore', 'entrambi']
            : ['cliente', 'entrambi'];

        $this->entityResults = Entity::where('valid', 1)
            ->whereIn('entity_type', $entityTypes)
            ->where(function ($q) {
                $q->where('ragione_sociale', 'like', '%' . $this->entitySearch . '%')
                  ->orWhere('nome', 'like', '%' . $this->entitySearch . '%')
                  ->orWhere('cognome', 'like', '%' . $this->entitySearch . '%');
            })
            ->limit(10)
            ->get(['id_cliente as id', 'ragione_sociale as name']);

        $this->showEntityDropdown = $this->entityResults->isNotEmpty();
    }

    public function selectEntity($id, string $name): void
    {
        $this->selectedEntityId = (string) $id;
        $this->selectedEntityName = $name;
        $this->entitySearch = $name;
        $this->showEntityDropdown = false;
    }

    public function clearEntity(): void
    {
        $this->selectedEntityId = '';
        $this->selectedEntityName = '';
        $this->entitySearch = '';
        $this->showEntityDropdown = false;
    }

    // ==================== AUTOCOMPLETE PROPRIETÀ ====================
    public function updatedOwnershipSearch(): void
    {
        if (!empty($this->selectedOwnershipId) && $this->ownershipSearch === $this->selectedOwnershipName) {
            $this->showOwnershipDropdown = false;
            return;
        }
        if (!empty($this->selectedOwnershipId) && $this->ownershipSearch !== $this->selectedOwnershipName) {
            $this->selectedOwnershipId = '';
            $this->selectedOwnershipName = '';
        }
        if (strlen(trim($this->ownershipSearch)) < 2) {
            $this->ownershipResults = new Collection();
            $this->showOwnershipDropdown = false;
            return;
        }

        $this->ownershipResults = Ownership::where('valid', 1)
            ->where(function ($q) {
                $q->where('RagAbbrev', 'like', '%' . $this->ownershipSearch . '%')
                  ->orWhere('Rag_Soc_intest', 'like', '%' . $this->ownershipSearch . '%');
            })
            ->limit(10)
            ->get(['id_proprieta as id', 'RagAbbrev as name']);

        $this->showOwnershipDropdown = $this->ownershipResults->isNotEmpty();
    }

    public function selectOwnership($id, string $name): void
    {
        $this->selectedOwnershipId = (string) $id;
        $this->selectedOwnershipName = $name;
        $this->ownershipSearch = $name;
        $this->showOwnershipDropdown = false;
    }

    public function clearOwnership(): void
    {
        $this->selectedOwnershipId = '';
        $this->selectedOwnershipName = '';
        $this->ownershipSearch = '';
        $this->showOwnershipDropdown = false;
    }

    // ==================== RIGHE ====================
    public function addRow(): void
    {
        $this->rows[] = [
            'id' => null,
            'id_product' => '',
            'product_name' => '',
            'description' => '',
            'quantity' => '',
            'unit_of_measure' => '',
            'note' => '',
        ];
    }

    public function removeRow(int $index): void
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);

        if ($this->activeRowIndex === (string) $index) {
            $this->deactivateRowSearch();
        }
    }

    /**
     * Attiva la ricerca prodotto per UNA riga specifica (solo una alla
     * volta può essere "in ricerca"). Precompila il campo con il nome del
     * prodotto già selezionato su quella riga, se presente.
     */
    public function activateRowProductSearch(int $index): void
    {
        if (!isset($this->rows[$index])) {
            return;
        }
        $this->activeRowIndex = (string) $index;
        $this->rowProductQuery = $this->rows[$index]['product_name'] ?? '';
        $this->rowProductQueryResults = new Collection();
    }

    public function deactivateRowSearch(): void
    {
        $this->activeRowIndex = '';
        $this->rowProductQuery = '';
        $this->rowProductQueryResults = new Collection();
    }

    public function updatedRowProductQuery(): void
    {
        if (strlen(trim($this->rowProductQuery)) < 2) {
            $this->rowProductQueryResults = new Collection();
            return;
        }

        $this->rowProductQueryResults = WarehouseProduct::where('valid', 1)
            ->where(function ($q) {
                $q->where('name', 'like', '%' . $this->rowProductQuery . '%')
                  ->orWhere('sku', 'like', '%' . $this->rowProductQuery . '%');
            })
            ->limit(8)
            ->get(['id', 'name', 'sku', 'unit_of_measure', 'quantity']);
    }

    public function selectRowProduct($productId): void
    {
        if ($this->activeRowIndex === '' || !isset($this->rows[(int) $this->activeRowIndex])) {
            return;
        }

        $product = WarehouseProduct::find($productId);
        if (!$product) {
            return;
        }

        $index = (int) $this->activeRowIndex;
        $this->rows[$index]['id_product'] = (string) $product->id;
        $this->rows[$index]['product_name'] = $product->name;
        $this->rows[$index]['description'] = $product->name;
        $this->rows[$index]['unit_of_measure'] = $product->unit_of_measure ?? '';

        $this->deactivateRowSearch();
    }

    public function clearRowProduct(int $index): void
    {
        if (!isset($this->rows[$index])) {
            return;
        }
        $this->rows[$index]['id_product'] = '';
        $this->rows[$index]['product_name'] = '';

        if ($this->activeRowIndex === (string) $index) {
            $this->deactivateRowSearch();
        }
    }

    // ==================== CREA / MODIFICA TESTATA ====================
    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->addRow();
        $this->showFormModal = true;
    }

    public function openEditModal(int $id): void
    {
        $ddt = WarehouseDdt::with('rows.product')->findOrFail($id);

        if ($ddt->isIssued()) {
            $this->dispatch('showError', message: 'Questo DDT è già emesso: usa "Annulla Emissione" prima di modificarlo.');
            return;
        }

        $this->editingId = $ddt->id;
        $this->ddt_number = $ddt->ddt_number;
        $this->ddt_date = optional($ddt->ddt_date)->format('Y-m-d') ?? '';
        $this->causale = $ddt->causale ?? '';

        $this->selectedEntityId = (string) $ddt->id_entities;
        $this->selectedEntityName = $ddt->entity ? ($ddt->entity->ragione_sociale ?? trim($ddt->entity->nome . ' ' . $ddt->entity->cognome)) : '';
        $this->entitySearch = $this->selectedEntityName;

        $this->selectedOwnershipId = (string) ($ddt->id_ownership ?? '');
        $this->selectedOwnershipName = $ddt->ownership ? ($ddt->ownership->RagAbbrev ?? $ddt->ownership->Rag_Soc_intest) : '';
        $this->ownershipSearch = $this->selectedOwnershipName;

        $this->rows = [];
        foreach ($ddt->rows as $row) {
            $this->rows[] = [
                'id' => $row->id,
                'id_product' => (string) ($row->id_product ?? ''),
                'product_name' => $row->product->name ?? '',
                'description' => $row->description,
                'quantity' => (string) $row->quantity,
                'unit_of_measure' => $row->unit_of_measure ?? '',
                'note' => $row->note ?? '',
            ];
        }

        if (empty($this->rows)) {
            $this->addRow();
        }

        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->ddt_number = '';
        $this->ddt_date = now()->format('Y-m-d');
        $this->causale = '';
        $this->clearEntity();
        $this->clearOwnership();
        $this->rows = [];
        $this->deactivateRowSearch();
        $this->resetErrorBag();
    }

    protected function rules(): array
    {
        return [
            'ddt_number' => 'required|string|max:50',
            'ddt_date' => 'required|date',
            'selectedEntityId' => 'required|exists:entities,id_cliente',
            'causale' => 'nullable|string|max:255',
            'rows' => 'required|array|min:1',
            'rows.*.description' => 'required|string|max:255',
            'rows.*.quantity' => 'required|numeric|min:0.01',
        ];
    }

    protected $messages = [
        'selectedEntityId.required' => 'Seleziona un cliente/fornitore.',
        'rows.required' => 'Aggiungi almeno una riga.',
        'rows.min' => 'Aggiungi almeno una riga.',
    ];

    public function save(): void
    {
        $this->validate();

        $adminId = Auth::guard('admin')->id();

        $data = [
            'type' => $this->type,
            'ddt_number' => $this->ddt_number,
            'ddt_date' => $this->ddt_date,
            'id_entities' => $this->selectedEntityId,
            'id_ownership' => $this->selectedOwnershipId ?: null,
            'causale' => $this->causale ?: null,
            'updated_by' => $adminId,
        ];

        DB::beginTransaction();
        try {
            if ($this->editingId) {
                $ddt = WarehouseDdt::findOrFail($this->editingId);
                if ($ddt->isIssued()) {
                    throw new \Exception('DDT già emesso, non modificabile.');
                }
                $ddt->update($data);
                // Righe: elimina tutte e ricrea (semplice e sicuro finché il DDT è in bozza)
                $ddt->rows()->delete();
            } else {
                $data['status'] = WarehouseDdt::STATUS_DRAFT;
                $data['created_by'] = $adminId;
                $ddt = WarehouseDdt::create($data);
            }

            foreach ($this->rows as $row) {
                if (trim($row['description']) === '' || (float) $row['quantity'] <= 0) {
                    continue;
                }
                WarehouseDdtRow::create([
                    'id_ddt' => $ddt->id,
                    'id_product' => $row['id_product'] ?: null,
                    'description' => $row['description'],
                    'quantity' => (float) str_replace(',', '.', $row['quantity']),
                    'unit_of_measure' => $row['unit_of_measure'] ?: null,
                    'note' => $row['note'] ?: null,
                ]);
            }

            DB::commit();

            $this->dispatch('showSuccess', message: "DDT '{$ddt->ddt_number}' " . ($this->editingId ? 'aggiornato' : 'creato') . ' con successo (bozza).');
            $this->closeFormModal();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }

    // ==================== EMISSIONE / ANNULLAMENTO ====================

    /**
     * Emette il DDT: per ogni riga collegata a un prodotto, crea un
     * movimento di magazzino (entrata per acquisto, uscita per vendita)
     * e aggiorna la giacenza cache del prodotto. Da questo momento il
     * DDT non è più modificabile finché non viene annullata l'emissione.
     */
    public function issueDdt(int $id): void
    {
        DB::beginTransaction();
        try {
            $ddt = WarehouseDdt::with('rows.product')->findOrFail($id);

            if ($ddt->isIssued()) {
                throw new \Exception('DDT già emesso.');
            }
            if ($ddt->rows->isEmpty()) {
                throw new \Exception('Il DDT non ha righe.');
            }

            $adminId = Auth::guard('admin')->id();
            $movementType = $ddt->movementType();
            $referenceType = $ddt->referenceType();
            $movedCount = 0;
            $lowStockWarnings = [];

            foreach ($ddt->rows as $row) {
                if (!$row->id_product || !$row->product) {
                    continue; // riga libera, non tocca il magazzino
                }

                $qty = (float) $row->quantity;

                if (!$ddt->isPurchase()) {
                    $resultingQty = (float) $row->product->quantity - $qty;
                    if ($resultingQty < 0) {
                        $lowStockWarnings[] = "{$row->product->name} (giacenza risultante: " . number_format($resultingQty, 2, ',', '.') . ")";
                    }
                }

                WarehouseMovement::create([
                    'id_product' => $row->id_product,
                    'type' => $movementType,
                    'quantity' => $qty,
                    'movement_date' => $ddt->ddt_date,
                    'reference_type' => $referenceType,
                    'reference_id' => $ddt->id,
                    'note' => 'DDT n. ' . $ddt->ddt_number,
                    'created_by' => $adminId,
                ]);

                $delta = $ddt->isPurchase() ? $qty : -$qty;
                $row->product->increment('quantity', $delta);
                $row->product->update(['updated_by' => $adminId]);
                $movedCount++;
            }

            $ddt->update([
                'status' => WarehouseDdt::STATUS_ISSUED,
                'issued_at' => now(),
                'updated_by' => $adminId,
            ]);

            DB::commit();

            $message = "DDT '{$ddt->ddt_number}' emesso — {$movedCount} movimento/i di magazzino generati.";
            if (!empty($lowStockWarnings)) {
                $message .= ' Attenzione, giacenza negativa per: ' . implode('; ', $lowStockWarnings);
                $this->dispatch('showWarning', message: $message);
            } else {
                $this->dispatch('showSuccess', message: $message);
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }

    /**
     * Annulla l'emissione: elimina i movimenti generati da questo DDT,
     * riporta la giacenza dei prodotti coinvolti al valore precedente, e
     * riporta il DDT in stato "bozza" (di nuovo modificabile).
     */
    public function cancelIssue(int $id): void
    {
        DB::beginTransaction();
        try {
            $ddt = WarehouseDdt::findOrFail($id);

            if (!$ddt->isIssued()) {
                throw new \Exception('Il DDT non è emesso.');
            }

            $adminId = Auth::guard('admin')->id();
            $movements = WarehouseMovement::where('reference_type', $ddt->referenceType())
                ->where('reference_id', $ddt->id)
                ->get();

            foreach ($movements as $movement) {
                $product = WarehouseProduct::find($movement->id_product);
                if ($product) {
                    $delta = $movement->type === WarehouseMovement::TYPE_IN
                        ? -(float) $movement->quantity
                        : (float) $movement->quantity;
                    $product->increment('quantity', $delta);
                    $product->update(['updated_by' => $adminId]);
                }
                $movement->delete();
            }

            $ddt->update([
                'status' => WarehouseDdt::STATUS_DRAFT,
                'issued_at' => null,
                'updated_by' => $adminId,
            ]);

            DB::commit();

            $this->dispatch('showSuccess', message: "Emissione del DDT '{$ddt->ddt_number}' annullata: {$movements->count()} movimento/i rimossi, giacenze ripristinate.");
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }

    // ==================== ELIMINAZIONE ====================
    public function confirmDelete(int $id): void
    {
        $ddt = WarehouseDdt::find($id);
        if ($ddt && $ddt->isIssued()) {
            $this->dispatch('showError', message: 'Non puoi eliminare un DDT emesso: annulla prima l\'emissione.');
            return;
        }
        $this->deletingId = $id;
    }

    public function cancelDelete(): void
    {
        $this->deletingId = null;
    }

    public function delete(): void
    {
        if (!$this->deletingId) {
            return;
        }

        $ddt = WarehouseDdt::find($this->deletingId);
        if ($ddt) {
            if ($ddt->isIssued()) {
                $this->dispatch('showError', message: 'Non puoi eliminare un DDT emesso: annulla prima l\'emissione.');
                $this->deletingId = null;
                return;
            }
            $number = $ddt->ddt_number;
            $ddt->delete();
            $this->dispatch('showSuccess', message: "DDT '{$number}' eliminato.");
        }

        $this->deletingId = null;
    }

    // ==================== DETTAGLIO ====================
    public function openDetailModal(int $id): void
    {
        $this->viewingDdt = WarehouseDdt::with(['entity', 'ownership', 'rows.product', 'creator'])->findOrFail($id);
        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->viewingDdt = null;
    }

    public function render()
    {
        return view('livewire.admin.warehouse.warehouse-ddt-table', [
            'ddts' => $this->ddts,
        ]);
    }
}