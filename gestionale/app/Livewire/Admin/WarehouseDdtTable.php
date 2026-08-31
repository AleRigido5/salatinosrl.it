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
use App\Models\Setting;
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
    public string $termini_consegna = '';
    public string $aspetto_esteriore_beni = '';
    public string $numero_colli = '';
    public string $trasporto_a_mezzo = '';
    public string $inizio_trasporto_at = '';

    // Vettore (campi liberi)
    public string $vettore_nome = '';
    public string $vettore_indirizzo = '';
    public string $vettore_telefono = '';
    public string $vettore_email = '';

    // Destinatario — override opzionale rispetto all'anagrafica Entity
    public bool $overrideDestinatario = false;
    public string $dest_ragione_sociale = '';
    public string $dest_indirizzo = '';
    public string $dest_cap = '';
    public string $dest_citta = '';
    public string $dest_provincia = '';
    public string $dest_piva = '';
    public string $dest_cf = '';

    // Luogo di destinazione — override opzionale (default = destinatario)
    public bool $overrideLuogo = false;
    public string $luogo_ragione_sociale = '';
    public string $luogo_indirizzo = '';
    public string $luogo_cap = '';
    public string $luogo_citta = '';
    public string $luogo_provincia = '';

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
    // (id, id_product, product_name, codice, description, quantity, unit_of_measure, note)
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

    // Conferma emissione / annullamento emissione (modal Tailwind, non alert nativo)
    public ?int $issuingId = null;
    public ?int $cancellingIssueId = null;

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

    public function getDdtCausaliProperty()
    {
        return Setting::where('tabella_riferimento', 'ddt_causale')
            ->where('valid', 1)
            ->orderBy('ordinamento')
            ->get();
    }

    public function getDdtAspettoBeniProperty()
    {
        return Setting::where('tabella_riferimento', 'ddt_aspetto_beni')
            ->where('valid', 1)
            ->orderBy('ordinamento')
            ->get();
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

        // Precompila destinatario/luogo dall'anagrafica (Entity + indirizzo
        // principale su Address), solo se l'utente non ha già attivato un
        // override manuale.
        if (!$this->overrideDestinatario) {
            $entity = Entity::with('addresses')->where('id_cliente', $id)->first();
            if ($entity) {
                $address = $entity->primary_address;

                $this->dest_ragione_sociale = $entity->ragione_sociale ?: trim($entity->nome . ' ' . $entity->cognome);
                $this->dest_indirizzo = $address->indirizzo ?? '';
                $this->dest_cap = $address->cap ?? '';
                $this->dest_citta = $address->citta ?? '';
                $this->dest_provincia = $address->provincia ?? '';
                $this->dest_piva = $entity->partita_iva ?? '';
                $this->dest_cf = $entity->codice_fiscale ?? '';
            }
        }
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

    // ==================== TOGGLE OVERRIDE DESTINATARIO/LUOGO ====================
    public function toggleOverrideDestinatario(): void
    {
        $this->overrideDestinatario = !$this->overrideDestinatario;
    }

    public function toggleOverrideLuogo(): void
    {
        $this->overrideLuogo = !$this->overrideLuogo;

        // Se attivo l'override del luogo per la prima volta, precompilo
        // dal destinatario corrente come punto di partenza comodo da editare.
        if ($this->overrideLuogo && $this->luogo_ragione_sociale === '') {
            $this->luogo_ragione_sociale = $this->dest_ragione_sociale;
            $this->luogo_indirizzo = $this->dest_indirizzo;
            $this->luogo_cap = $this->dest_cap;
            $this->luogo_citta = $this->dest_citta;
            $this->luogo_provincia = $this->dest_provincia;
        }
    }

    // ==================== RIGHE ====================
    public function addRow(): void
    {
        $this->rows[] = [
            'id' => null,
            'id_product' => '',
            'product_name' => '',
            'codice' => '',
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
        $this->rows[$index]['codice'] = $product->sku ?? '';
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

        $this->editingId = $ddt->id;
        $this->ddt_number = $ddt->ddt_number;
        $this->ddt_date = optional($ddt->ddt_date)->format('Y-m-d') ?? '';
        $this->causale = $ddt->causale ?? '';
        $this->termini_consegna = $ddt->termini_consegna ?? '';
        $this->aspetto_esteriore_beni = $ddt->aspetto_esteriore_beni ?? '';
        $this->numero_colli = $ddt->numero_colli !== null ? (string) $ddt->numero_colli : '';
        $this->trasporto_a_mezzo = $ddt->trasporto_a_mezzo ?? '';
        $this->inizio_trasporto_at = $ddt->inizio_trasporto_at ? $ddt->inizio_trasporto_at->format('Y-m-d\TH:i') : '';

        $this->vettore_nome = $ddt->vettore_nome ?? '';
        $this->vettore_indirizzo = $ddt->vettore_indirizzo ?? '';
        $this->vettore_telefono = $ddt->vettore_telefono ?? '';
        $this->vettore_email = $ddt->vettore_email ?? '';

        $this->overrideDestinatario = !empty($ddt->dest_ragione_sociale);
        $this->dest_ragione_sociale = $ddt->dest_ragione_sociale ?? '';
        $this->dest_indirizzo = $ddt->dest_indirizzo ?? '';
        $this->dest_cap = $ddt->dest_cap ?? '';
        $this->dest_citta = $ddt->dest_citta ?? '';
        $this->dest_provincia = $ddt->dest_provincia ?? '';
        $this->dest_piva = $ddt->dest_piva ?? '';
        $this->dest_cf = $ddt->dest_cf ?? '';

        $this->overrideLuogo = !empty($ddt->luogo_ragione_sociale);
        $this->luogo_ragione_sociale = $ddt->luogo_ragione_sociale ?? '';
        $this->luogo_indirizzo = $ddt->luogo_indirizzo ?? '';
        $this->luogo_cap = $ddt->luogo_cap ?? '';
        $this->luogo_citta = $ddt->luogo_citta ?? '';
        $this->luogo_provincia = $ddt->luogo_provincia ?? '';

        $this->selectedEntityId = (string) $ddt->id_entities;
        $this->selectedEntityName = $ddt->entity ? ($ddt->entity->ragione_sociale ?? trim($ddt->entity->nome . ' ' . $ddt->entity->cognome)) : '';
        $this->entitySearch = $this->selectedEntityName;

        // Se non c'è override salvato, precompila comunque i campi
        // destinatario dall'anagrafica (Entity + indirizzo principale su
        // Address) per mostrarli nel form.
        if (!$this->overrideDestinatario && $ddt->entity) {
            $entityWithAddress = Entity::with('addresses')->find($ddt->entity->id_cliente);
            $address = $entityWithAddress ? $entityWithAddress->primary_address : null;

            $this->dest_ragione_sociale = $ddt->entity->ragione_sociale ?? trim($ddt->entity->nome . ' ' . $ddt->entity->cognome);
            $this->dest_indirizzo = $address->indirizzo ?? '';
            $this->dest_cap = $address->cap ?? '';
            $this->dest_citta = $address->citta ?? '';
            $this->dest_provincia = $address->provincia ?? '';
            $this->dest_piva = $ddt->entity->partita_iva ?? '';
            $this->dest_cf = $ddt->entity->codice_fiscale ?? '';
        }

        $this->selectedOwnershipId = (string) ($ddt->id_ownership ?? '');
        $this->selectedOwnershipName = $ddt->ownership ? ($ddt->ownership->RagAbbrev ?? $ddt->ownership->Rag_Soc_intest) : '';
        $this->ownershipSearch = $this->selectedOwnershipName;

        $this->rows = [];
        foreach ($ddt->rows as $row) {
            $this->rows[] = [
                'id' => $row->id,
                'id_product' => (string) ($row->id_product ?? ''),
                'product_name' => $row->product->name ?? '',
                'codice' => $row->codice ?? '',
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
        $this->termini_consegna = '';
        $this->aspetto_esteriore_beni = '';
        $this->numero_colli = '';
        $this->trasporto_a_mezzo = '';
        $this->inizio_trasporto_at = '';

        $this->vettore_nome = '';
        $this->vettore_indirizzo = '';
        $this->vettore_telefono = '';
        $this->vettore_email = '';

        $this->overrideDestinatario = false;
        $this->dest_ragione_sociale = '';
        $this->dest_indirizzo = '';
        $this->dest_cap = '';
        $this->dest_citta = '';
        $this->dest_provincia = '';
        $this->dest_piva = '';
        $this->dest_cf = '';

        $this->overrideLuogo = false;
        $this->luogo_ragione_sociale = '';
        $this->luogo_indirizzo = '';
        $this->luogo_cap = '';
        $this->luogo_citta = '';
        $this->luogo_provincia = '';

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
            'termini_consegna' => 'nullable|string|max:255',
            'aspetto_esteriore_beni' => 'nullable|string|max:100',
            'numero_colli' => 'nullable|integer|min:0',
            'trasporto_a_mezzo' => 'nullable|in:mittente,destinatario,vettore',
            'inizio_trasporto_at' => 'nullable|date',
            'vettore_nome' => 'nullable|string|max:255',
            'vettore_indirizzo' => 'nullable|string|max:255',
            'vettore_telefono' => 'nullable|string|max:50',
            'vettore_email' => 'nullable|email|max:150',
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
            'termini_consegna' => $this->termini_consegna ?: null,
            'aspetto_esteriore_beni' => $this->aspetto_esteriore_beni ?: null,
            'numero_colli' => $this->numero_colli !== '' ? (int) $this->numero_colli : null,
            'trasporto_a_mezzo' => $this->trasporto_a_mezzo ?: null,
            'inizio_trasporto_at' => $this->inizio_trasporto_at ?: null,
            'vettore_nome' => $this->vettore_nome ?: null,
            'vettore_indirizzo' => $this->vettore_indirizzo ?: null,
            'vettore_telefono' => $this->vettore_telefono ?: null,
            'vettore_email' => $this->vettore_email ?: null,
            'updated_by' => $adminId,

            'dest_ragione_sociale' => $this->overrideDestinatario ? ($this->dest_ragione_sociale ?: null) : null,
            'dest_indirizzo' => $this->overrideDestinatario ? ($this->dest_indirizzo ?: null) : null,
            'dest_cap' => $this->overrideDestinatario ? ($this->dest_cap ?: null) : null,
            'dest_citta' => $this->overrideDestinatario ? ($this->dest_citta ?: null) : null,
            'dest_provincia' => $this->overrideDestinatario ? ($this->dest_provincia ?: null) : null,
            'dest_piva' => $this->overrideDestinatario ? ($this->dest_piva ?: null) : null,
            'dest_cf' => $this->overrideDestinatario ? ($this->dest_cf ?: null) : null,

            'luogo_ragione_sociale' => $this->overrideLuogo ? ($this->luogo_ragione_sociale ?: null) : null,
            'luogo_indirizzo' => $this->overrideLuogo ? ($this->luogo_indirizzo ?: null) : null,
            'luogo_cap' => $this->overrideLuogo ? ($this->luogo_cap ?: null) : null,
            'luogo_citta' => $this->overrideLuogo ? ($this->luogo_citta ?: null) : null,
            'luogo_provincia' => $this->overrideLuogo ? ($this->luogo_provincia ?: null) : null,
        ];

        DB::beginTransaction();
        try {
            $wasIssued = false;

            if ($this->editingId) {
                $ddt = WarehouseDdt::findOrFail($this->editingId);
                $wasIssued = $ddt->isIssued();

                // Se il DDT era emesso, annulla prima i vecchi movimenti/giacenze
                // (stessa logica di cancelIssue), così le nuove righe partono
                // da uno stato pulito e verranno ri-emesse sotto.
                if ($wasIssued) {
                    $this->reverseIssuedMovements($ddt, $adminId);
                }

                $ddt->update($data);
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
                    'codice' => $row['codice'] ?: null,
                    'description' => $row['description'],
                    'quantity' => (float) str_replace(',', '.', $row['quantity']),
                    'unit_of_measure' => $row['unit_of_measure'] ?: null,
                    'note' => $row['note'] ?: null,
                ]);
            }

            $message = "DDT '{$ddt->ddt_number}' " . ($this->editingId ? 'aggiornato' : 'creato') . ' con successo';

            // Se era emesso, ri-emettilo subito con le nuove righe: rigenera i
            // movimenti di magazzino in modo coerente con la modifica appena fatta.
            if ($wasIssued) {
                $this->reissueDdt($ddt->fresh('rows.product'), $adminId);
                $message .= ' e ri-emesso (giacenze aggiornate).';
            } else {
                $message .= ' (bozza).';
            }

            DB::commit();

            $this->dispatch('showSuccess', message: $message);
            $this->closeFormModal();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }

    /**
     * Annulla i movimenti di magazzino di un DDT emesso e ripristina le
     * giacenze precedenti, SENZA cambiare lo status del DDT (che verrà
     * aggiornato subito dopo da chi chiama questo metodo). Estratta da
     * cancelIssue() per essere riusata anche dentro save() quando si
     * modifica un DDT già emesso.
     */
    private function reverseIssuedMovements(WarehouseDdt $ddt, $adminId): void
    {
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
    }

    /**
     * Rigenera i movimenti di magazzino per un DDT che era già emesso e
     * viene ri-emesso dopo una modifica (righe cambiate). Stessa logica
     * di issueDdt(), ma senza i controlli "già emesso" (qui lo status è
     * già 'emesso' o sta per esserlo) e senza aprire una nuova transazione
     * (siamo già dentro quella di save()).
     */
    private function reissueDdt(WarehouseDdt $ddt, $adminId): void
    {
        $movementType = $ddt->movementType();
        $referenceType = $ddt->referenceType();

        foreach ($ddt->rows as $row) {
            if (!$row->id_product || !$row->product) {
                continue;
            }

            $qty = (float) $row->quantity;

            WarehouseMovement::create([
                'id_product' => $row->id_product,
                'type' => $movementType,
                'quantity' => $qty,
                'movement_date' => $ddt->ddt_date,
                'reference_type' => $referenceType,
                'reference_id' => $ddt->id,
                'note' => 'DDT n. ' . $ddt->ddt_number . ' (rettifica dopo modifica)',
                'created_by' => $adminId,
            ]);

            $delta = $ddt->isPurchase() ? $qty : -$qty;
            $row->product->increment('quantity', $delta);
            $row->product->update(['updated_by' => $adminId]);
        }

        $ddt->update([
            'status' => WarehouseDdt::STATUS_ISSUED,
            'issued_at' => $ddt->issued_at ?? now(),
            'updated_by' => $adminId,
        ]);
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

    // ==================== CONFERMA EMISSIONE (modal Tailwind) ====================
    public function confirmIssue(int $id): void
    {
        $this->issuingId = $id;
    }

    public function cancelConfirmIssue(): void
    {
        $this->issuingId = null;
    }

    public function issueDdtConfirmed(): void
    {
        if (!$this->issuingId) {
            return;
        }
        $this->issueDdt($this->issuingId);
        $this->issuingId = null;
    }

    // ==================== CONFERMA ANNULLAMENTO EMISSIONE (modal Tailwind) ====================
    public function confirmCancelIssue(int $id): void
    {
        $this->cancellingIssueId = $id;
    }

    public function cancelConfirmCancelIssue(): void
    {
        $this->cancellingIssueId = null;
    }

    public function cancelIssueConfirmed(): void
    {
        if (!$this->cancellingIssueId) {
            return;
        }
        $this->cancelIssue($this->cancellingIssueId);
        $this->cancellingIssueId = null;
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
            'ddtCausali' => $this->ddtCausali,
            'ddtAspettoBeni' => $this->ddtAspettoBeni,
        ]);
    }
}