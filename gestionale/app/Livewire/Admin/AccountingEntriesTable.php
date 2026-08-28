<?php
// app/Livewire/Admin/AccountingEntriesTable.php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\AccountingEntry;
use App\Models\BankAccount;
use App\Models\PaymentMethod;
use App\Models\InvoiceReceived;
use App\Models\Entity;
use App\Models\Ownership;
use App\Models\CostCenter;
use App\Models\InvoicePayment;
use App\Models\InstallmentTransaction;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AccountingEntriesTable extends Component
{
    use WithPagination, WithFileUploads;
    
    public $search = '';
    public $type = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $bankAccountId = '';
    public $paymentMethodId = '';
    public $statusFilter = '';

    // Autocomplete Proprietà (FILTRO tabella)
    public string $ownershipSearch = '';
    public Collection $ownershipResults;
    public string $selectedOwnershipId = '';
    public string $selectedOwnershipName = '';
    public bool $showOwnershipDropdown = false;
    
    // Autocomplete Fornitore (FILTRO tabella)
    public string $entitySearch = '';
    public Collection $filteredEntities;
    public $entityFilter = null;
    public string $entityName = '';
    public bool $showEntityDropdown = false;
    
    public $sortField = 'entry_date';
    public $sortDirection = 'desc';
    public $perPage = 100;
    
    public $showModal = false;
    public $showDeleteModal = false;
    public $showViewModal = false;
    public $viewingEntry = null;
    public $entryToDelete = null;
    
    public $entryId = null;
    public $entry_date = '';
    public $description = '';
    public $type_value = '';
    public $status_value = 'COMPLETATO';
    public $id_payments_methods = '';
    public $bank_account_id = '';
    public $amount = '';
    public $invoice_id = '';
    public $isEditing = false;

    // Autocomplete Cliente/Fornitore (FORM Nuova/Modifica Scrittura)
    public string $formEntitySearch = '';
    public Collection $formEntityResults;
    public $formEntityId = '';
    public string $formEntityName = '';
    public bool $showFormEntityDropdown = false;

    // Autocomplete Centro di Costo (FORM Nuova/Modifica Scrittura)
    public string $costCenterSearch = '';
    public Collection $costCenterResults;
    public $costCenterId = '';
    public string $costCenterName = '';
    public bool $showCostCenterDropdown = false;
    
    // Variabili per il calcolo in tempo reale
    public $originalAmount = 0;
    public $displayAmount = '0,00';
    public $amountDifference = 0;
    public $isAmountChanged = false;
    
    // Cestino
    public bool $showTrashModal = false;
    public string $trashSearch = '';
    public string $trashSortField = 'deleted_at';
    public string $trashSortDirection = 'desc';

    // ==================== IMPORT CSV ====================
    public bool $showImportModal = false;
    public $importFile = null;
    public array $importPreview = [];
    public array $importRowErrors = [];
    public int $importValidRowsCount = 0;
    public bool $importDone = false;
    public int $importedCount = 0;

    // Cliente/Fornitore globale per l'import (opzionale)
    public string $importEntitySearch = '';
    public Collection $importEntityResults;
    public $importEntityId = '';
    public string $importEntityName = '';
    public bool $showImportEntityDropdown = false;

    // Centro di Costo globale per l'import (obbligatorio)
    public string $importCostCenterSearch = '';
    public Collection $importCostCenterResults;
    public $importCostCenterId = '';
    public string $importCostCenterName = '';
    public bool $showImportCostCenterDropdown = false;

    // Stato globale per l'import
    public string $importStatus = 'COMPLETATO';
    
    protected $rules = [
        'entry_date' => 'required|date',
        'description' => 'required|string|min:3',
        'type_value' => 'required|in:entrata,uscita',
        'status_value' => 'required|in:COMPLETATO,INSERITO,AUTOMATICO,DA INSERIRE',
        'amount' => 'required|numeric|min:0.01',
        'bank_account_id' => 'nullable|exists:bank_accounts,id',
        'id_payments_methods' => 'nullable|exists:payment_methods,id',
        'invoice_id' => 'nullable|exists:invoices_received,id',
        'formEntityId' => 'nullable|exists:entities,id_cliente',
        'costCenterId' => 'nullable|exists:cost_centers,id',
        'importFile' => 'nullable|file|mimes:csv,txt|max:5120',
    ];
    
    protected $listeners = [
        'refreshAccountingEntries' => '$refresh',
        'refreshPayments' => 'refreshTable',
        'refreshInvoices' => 'refreshTable',
        'dateRangeUpdated' => 'handleDateRangeUpdated',
    ];
    
    public function mount()
    {
        $this->entry_date = date('Y-m-d');
        $this->dateFrom = date('Y-m-01');
        $this->dateTo = date('Y-m-t');
        $this->filteredEntities = new Collection();
        $this->ownershipResults = new Collection();
        $this->formEntityResults = new Collection();
        $this->costCenterResults = new Collection();
        $this->importEntityResults = new Collection();
        $this->importCostCenterResults = new Collection();
    }
    
    public function refreshTable(): void
    {
        $this->resetPage();
    }
    
    public function handleDateRangeUpdated(array $data): void
    {
        $this->dateFrom = $data['date_from'] ?? '';
        $this->dateTo   = $data['date_to']   ?? '';
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }
    
    public function resetFilters()
    {
        $this->search = '';
        $this->entitySearch = '';
        $this->entityFilter = null;
        $this->entityName = '';
        $this->filteredEntities = new Collection();
        $this->showEntityDropdown = false;
        $this->selectedOwnershipId = '';
        $this->selectedOwnershipName = '';
        $this->ownershipSearch = '';
        $this->type = '';
        $this->statusFilter = '';
        $this->bankAccountId = '';
        $this->paymentMethodId = '';
        $this->dateFrom = date('Y-m-01');
        $this->dateTo = date('Y-m-t');
        $this->resetPage();
    }
    
    public function clearDates()
    {
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }
    
    public function openCreateModal()
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_accounting_entries')) {
            $this->dispatch('showError', message: 'Non hai i permessi per creare scritture contabili');
            return;
        }
        
        $this->resetForm();
        $this->isEditing = false;
        $this->entryId = null;
        $this->entry_date = date('Y-m-d');
        $this->originalAmount = 0;
        $this->isAmountChanged = false;
        $this->amountDifference = 0;
        $this->showModal = true;
    }
    
    public function openEditModal($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_accounting_entries')) {
            $this->dispatch('showError', message: 'Non hai i permessi per modificare scritture contabili');
            return;
        }
        
        $entry = AccountingEntry::with(['installmentTransactions.invoicePayment.payable'])->findOrFail($id);
        $this->entryId = $entry->id;
        $this->entry_date = $entry->entry_date->format('Y-m-d');
        $this->description = $entry->description;
        $this->type_value = $entry->type;
        $this->status_value = $entry->status ?? 'COMPLETATO';
        $this->id_payments_methods = $entry->id_payments_methods;
        $this->bank_account_id = $entry->bank_account_id;
        $this->amount = (string)$entry->amount;
        $this->displayAmount = number_format($entry->amount, 2, ',', '.');
        $this->originalAmount = $entry->amount;
        $this->invoice_id = $entry->invoice_id;

        // Precompila Cliente/Fornitore
        $this->formEntityId = $entry->id_entities ?: '';
        if ($entry->id_entities) {
            $entityModel = Entity::find($entry->id_entities);
            if ($entityModel) {
                $name = $entityModel->ragione_sociale ?: trim($entityModel->nome . ' ' . $entityModel->cognome);
                $this->formEntitySearch = $name;
                $this->formEntityName = $name;
            }
        } else {
            $this->formEntitySearch = '';
            $this->formEntityName = '';
        }

        // Precompila Centro di Costo
        $this->costCenterId = $entry->id_cost_centers ?: '';
        if ($entry->id_cost_centers) {
            $costCenterModel = CostCenter::find($entry->id_cost_centers);
            if ($costCenterModel) {
                $this->costCenterSearch = $costCenterModel->Nome;
                $this->costCenterName = $costCenterModel->Nome;
            }
        } else {
            $this->costCenterSearch = '';
            $this->costCenterName = '';
        }

        $this->isEditing = true;
        $this->isAmountChanged = false;
        $this->amountDifference = 0;
        $this->showModal = true;
    }

    // ==================== AUTOCOMPLETE PROPRIETÀ (FILTRO) ====================
    public function updatedOwnershipSearch(): void
    {
        if ($this->selectedOwnershipId && $this->ownershipSearch === $this->selectedOwnershipName) {
            $this->showOwnershipDropdown = false;
            return;
        }

        if ($this->selectedOwnershipId) {
            $this->selectedOwnershipId = '';
            $this->selectedOwnershipName = '';
            $this->resetPage();
        }

        if (strlen($this->ownershipSearch) < 2) {
            $this->ownershipResults = new Collection();
            $this->showOwnershipDropdown = false;
            return;
        }

        $this->ownershipResults = Ownership::where('valid', 1)
            ->where(function($q) {
                $q->where('RagAbbrev', 'like', '%' . $this->ownershipSearch . '%')
                  ->orWhere('Rag_Soc_intest', 'like', '%' . $this->ownershipSearch . '%')
                  ->orWhere('RagSocialePr', 'like', '%' . $this->ownershipSearch . '%');
            })
            ->limit(10)
            ->get(['id_proprieta as id', 'RagAbbrev as name']);
        
        $this->showOwnershipDropdown = $this->ownershipResults->isNotEmpty();
    }

    public function selectOwnership(int $id, string $name): void
    {
        $this->selectedOwnershipId = (string)$id;
        $this->selectedOwnershipName = $name;
        $this->ownershipSearch = $name;
        $this->showOwnershipDropdown = false;
        $this->resetPage();
    }

    public function clearOwnership(): void
    {
        $this->selectedOwnershipId = '';
        $this->selectedOwnershipName = '';
        $this->ownershipSearch = '';
        $this->resetPage();
        $this->dispatch('clearOwnershipInput');
    }

    // ==================== AUTOCOMPLETE FORNITORE (FILTRO) ====================
    public function updatedEntitySearch(): void
    {
        if ($this->entityFilter && $this->entitySearch === $this->entityName) {
            $this->showEntityDropdown = false;
            return;
        }

        if ($this->entityFilter) {
            $this->entityFilter = null;
            $this->entityName = '';
            $this->resetPage();
        }

        if (strlen($this->entitySearch) < 2) {
            $this->filteredEntities = new Collection();
            $this->showEntityDropdown = false;
            return;
        }

        $this->filteredEntities = Entity::where('valid', 1)
            ->where(function($q) {
                $q->where('ragione_sociale', 'like', '%' . $this->entitySearch . '%')
                ->orWhere('nome', 'like', '%' . $this->entitySearch . '%')
                ->orWhere('cognome', 'like', '%' . $this->entitySearch . '%');
            })
            ->limit(10)
            ->get(['id_cliente', 'ragione_sociale', 'nome', 'cognome', 'entity_type', 'partita_iva']);

        $this->showEntityDropdown = $this->filteredEntities->isNotEmpty();
    }

    public function clearEntity(): void
    {
        $this->entityFilter = null;
        $this->entityName = '';
        $this->entitySearch = '';
        $this->filteredEntities = new Collection();
        $this->showEntityDropdown = false;
        $this->resetPage();
    }

    // ==================== AUTOCOMPLETE CLIENTE/FORNITORE (FORM) ====================
    public function updatedFormEntitySearch(): void
    {
        if ($this->formEntityId && $this->formEntitySearch === $this->formEntityName) {
            $this->showFormEntityDropdown = false;
            return;
        }

        if ($this->formEntityId) {
            $this->formEntityId = '';
            $this->formEntityName = '';
        }

        if (strlen($this->formEntitySearch) < 2) {
            $this->formEntityResults = new Collection();
            $this->showFormEntityDropdown = false;
            return;
        }

        $this->formEntityResults = Entity::where('valid', 1)
            ->where(function($q) {
                $q->where('ragione_sociale', 'like', '%' . $this->formEntitySearch . '%')
                  ->orWhere('nome', 'like', '%' . $this->formEntitySearch . '%')
                  ->orWhere('cognome', 'like', '%' . $this->formEntitySearch . '%')
                  ->orWhere('partita_iva', 'like', '%' . $this->formEntitySearch . '%');
            })
            ->limit(10)
            ->get(['id_cliente', 'ragione_sociale', 'nome', 'cognome']);

        $this->showFormEntityDropdown = $this->formEntityResults->isNotEmpty();
    }

    public function selectFormEntity($id, $name): void
    {
        $this->formEntityId = $id;
        $this->formEntityName = $name;
        $this->formEntitySearch = $name;
        $this->showFormEntityDropdown = false;
    }

    public function clearFormEntity(): void
    {
        $this->formEntityId = '';
        $this->formEntityName = '';
        $this->formEntitySearch = '';
        $this->formEntityResults = new Collection();
        $this->showFormEntityDropdown = false;
    }

    // ==================== AUTOCOMPLETE CENTRO DI COSTO (FORM) ====================
    public function updatedCostCenterSearch(): void
    {
        if ($this->costCenterId && $this->costCenterSearch === $this->costCenterName) {
            $this->showCostCenterDropdown = false;
            return;
        }

        if ($this->costCenterId) {
            $this->costCenterId = '';
            $this->costCenterName = '';
        }

        if (strlen($this->costCenterSearch) < 2) {
            $this->costCenterResults = new Collection();
            $this->showCostCenterDropdown = false;
            return;
        }

        $this->costCenterResults = CostCenter::where('valid', 1)
            ->where('Nome', 'like', '%' . $this->costCenterSearch . '%')
            ->orderBy('Nome')
            ->limit(10)
            ->get(['id', 'Nome']);

        $this->showCostCenterDropdown = $this->costCenterResults->isNotEmpty();
    }

    public function selectCostCenter($id, $name): void
    {
        $this->costCenterId = $id;
        $this->costCenterName = $name;
        $this->costCenterSearch = $name;
        $this->showCostCenterDropdown = false;
    }

    public function clearCostCenter(): void
    {
        $this->costCenterId = '';
        $this->costCenterName = '';
        $this->costCenterSearch = '';
        $this->costCenterResults = new Collection();
        $this->showCostCenterDropdown = false;
    }
    
    /**
     * Aggiorna l'importo in tempo reale quando l'utente digita
     */
    public function updatedAmount($value)
    {
        $newAmount = (float)$value;
        $this->amountDifference = $newAmount - $this->originalAmount;
        $this->isAmountChanged = abs($this->amountDifference) > 0.01;
        $this->displayAmount = number_format($newAmount, 2, ',', '.');
    }
    
    /**
     * Gestisce l'aggiornamento in tempo reale del tipo di operazione
     */
    public function updatedTypeValue($value)
    {
        if ($value === 'entrata') {
            $this->dispatch('alert', [
                'type' => 'info',
                'message' => 'Hai selezionato ENTRATA - Operazione di incasso'
            ]);
        } elseif ($value === 'uscita') {
            $this->dispatch('alert', [
                'type' => 'info',
                'message' => 'Hai selezionato USCITA - Operazione di pagamento'
            ]);
        }
    }
    
    /**
     * Formatta l'importo quando l'utente esce dal campo
     */
    public function formatAmount()
    {
        $this->amount = number_format((float)$this->amount, 2, '.', '');
    }
    
    public function viewEntry($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_accounting_entries')) {
            $this->dispatch('showError', message: 'Non hai i permessi per visualizzare le scritture contabili');
            return;
        }
        
        $this->viewingEntry = AccountingEntry::with([
            'bankAccount', 
            'paymentMethod', 
            'invoice',
            'installmentTransactions.invoicePayment.payable'
        ])->findOrFail($id);
        $this->showViewModal = true;
    }
    
    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewingEntry = null;
    }
    
    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }
    
    public function resetForm()
    {
        $this->entryId = null;
        $this->entry_date = date('Y-m-d');
        $this->description = '';
        $this->type_value = '';
        $this->status_value = 'COMPLETATO';
        $this->id_payments_methods = '';
        $this->bank_account_id = '';
        $this->amount = '';
        $this->displayAmount = '0,00';
        $this->invoice_id = '';
        $this->isEditing = false;
        $this->originalAmount = 0;
        $this->isAmountChanged = false;
        $this->amountDifference = 0;

        $this->formEntityId = '';
        $this->formEntityName = '';
        $this->formEntitySearch = '';
        $this->formEntityResults = new Collection();
        $this->showFormEntityDropdown = false;

        $this->costCenterId = '';
        $this->costCenterName = '';
        $this->costCenterSearch = '';
        $this->costCenterResults = new Collection();
        $this->showCostCenterDropdown = false;
    }
    
    public function save()
    {
        if ($this->isEditing) {
            if (!Auth::guard('admin')->user()->hasPermission('edit_accounting_entries')) {
                $this->dispatch('showError', message: 'Non hai i permessi per modificare scritture contabili');
                return;
            }
        } else {
            if (!Auth::guard('admin')->user()->hasPermission('create_accounting_entries')) {
                $this->dispatch('showError', message: 'Non hai i permessi per creare scritture contabili');
                return;
            }
        }
        
        $this->validate();
        
        try {
            DB::beginTransaction();
            
            $data = [
                'entry_date' => $this->entry_date,
                'description' => $this->description,
                'type' => $this->type_value,
                'status' => $this->status_value,
                'id_payments_methods' => $this->id_payments_methods ?: null,
                'bank_account_id' => $this->bank_account_id ?: null,
                'amount' => $this->amount,
                'invoice_id' => $this->invoice_id ?: null,
                'id_entities' => $this->formEntityId ?: null,
                'id_cost_centers' => $this->costCenterId ?: null,
            ];
            
            if ($this->isEditing && $this->entryId) {
                $originalEntry = AccountingEntry::find($this->entryId);
                $originalAmount = $originalEntry->amount;
                $newAmount = (float)$this->amount;
                
                // Aggiorna la scrittura
                $originalEntry->update($data);
                
                // Se l'importo è cambiato, aggiorna i pagamenti collegati
                if (abs($newAmount - $originalAmount) > 0.01) {
                    $this->updateLinkedPayments($originalEntry, $originalAmount, $newAmount);
                }
                
                $message = 'Scrittura contabile aggiornata con successo!';
            } else {
                AccountingEntry::create($data);
                $message = 'Scrittura contabile creata con successo!';
            }
            
            DB::commit();
            $this->closeModal();
            $this->dispatch('showSuccess', message: $message);
            $this->dispatch('refreshPayments');
            $this->dispatch('refreshInvoices');
            $this->dispatch('refreshAccountingEntries');
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('AccountingEntriesTable::save error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }
    
    /**
     * Aggiorna i pagamenti collegati quando cambia l'importo della scrittura
     */
    private function updateLinkedPayments(AccountingEntry $entry, float $originalAmount, float $newAmount): void
    {
        $transactions = $entry->installmentTransactions()->with('invoicePayment')->get();
        
        if ($transactions->isEmpty()) {
            return;
        }
        
        // Calcola il fattore di proporzione
        $factor = $newAmount / $originalAmount;
        
        foreach ($transactions as $transaction) {
            $payment = $transaction->invoicePayment;
            if (!$payment) continue;
            
            $invoice = $payment->payable;
            if (!$invoice) continue;
            
            // Calcola il nuovo importo allocato
            $oldAllocatedAmount = $transaction->allocated_amount;
            $newAllocatedAmount = round($oldAllocatedAmount * $factor, 2);
            
            // Aggiorna la transazione
            $transaction->update(['allocated_amount' => $newAllocatedAmount]);
            
            // Ricalcola il totale pagato per questo payment
            $totalPaidForThisPayment = $payment->installmentTransactions()->sum('allocated_amount');
            
            // Aggiorna il pagamento
            $newResidual = $payment->amount - $totalPaidForThisPayment;
            
            $payment->update([
                'paid_amount' => $totalPaidForThisPayment,
                'residual_amount' => max(0, $newResidual),
                'status' => $newResidual <= 0.01 ? 'paid' : ($totalPaidForThisPayment > 0 ? 'partially_paid' : 'issued'),
                'paid_at' => $newResidual <= 0.01 ? now() : null,
            ]);
            
            // Aggiorna lo stato della fattura
            $this->updateInvoiceStatus($invoice);
        }
    }
    
    /**
     * Aggiorna lo stato della fattura in base ai pagamenti
     */
    private function updateInvoiceStatus(InvoiceReceived $invoice): void
    {
        $totalPaid = $invoice->payments()->sum('paid_amount');
        $residual = $invoice->importo_totale - $totalPaid;
        
        if ($residual <= 0.01) {
            $newStatus = 'paid';
        } elseif ($totalPaid > 0) {
            $newStatus = 'partially_paid';
        } else {
            $newStatus = 'issued';
        }
        
        if ($invoice->status !== $newStatus) {
            $invoice->update(['status' => $newStatus]);
            
            // Aggiorna anche il primo payment associato alla fattura
            $mainPayment = $invoice->payments()->first();
            if ($mainPayment && $mainPayment->status !== $newStatus) {
                $mainPayment->update([
                    'status' => $newStatus,
                    'residual_amount' => $residual,
                ]);
            }
        }
    }
    
    public function confirmDelete($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('delete_accounting_entries')) {
            $this->dispatch('showError', message: 'Non hai i permessi per eliminare scritture contabili');
            return;
        }
        
        $this->entryToDelete = AccountingEntry::find($id);
        $this->showDeleteModal = true;
    }
    
    public function deleteEntry()
    {
        try {
            DB::beginTransaction();
            
            $entry = $this->entryToDelete;
            
            // Se la scrittura ha transazioni collegate, gestiscile
            $transactions = $entry->installmentTransactions()->get();
            foreach ($transactions as $transaction) {
                $payment = $transaction->invoicePayment;
                if ($payment) {
                    $invoice = $payment->payable;
                    
                    // Rimuovi l'importo allocato
                    $newPaidAmount = max(0, $payment->paid_amount - $transaction->allocated_amount);
                    $newResidual = $payment->amount - $newPaidAmount;
                    
                    $payment->update([
                        'paid_amount' => $newPaidAmount,
                        'residual_amount' => $newResidual,
                        'status' => $newResidual <= 0.01 ? 'paid' : ($newPaidAmount > 0 ? 'partially_paid' : 'issued'),
                    ]);
                    
                    if ($invoice) {
                        $this->updateInvoiceStatus($invoice);
                    }
                }
                $transaction->delete();
            }
            
            $entry->delete();
            
            DB::commit();
            $this->showDeleteModal = false;
            $this->entryToDelete = null;
            $this->dispatch('showSuccess', message: 'Scrittura contabile eliminata con successo!');
            $this->dispatch('refreshAccountingEntries');
            $this->dispatch('refreshPayments');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }
    
    public function cancelDelete()
    {
        $this->showDeleteModal = false;
        $this->entryToDelete = null;
    }
    
    // ==================== CESTINO ====================
    
    public function openTrashModal(): void
    {
        $this->trashSearch = '';
        $this->showTrashModal = true;
    }
    
    public function closeTrashModal(): void
    {
        $this->showTrashModal = false;
        $this->trashSearch = '';
    }
    
    public function trashSortBy(string $field): void
    {
        if ($this->trashSortField === $field) {
            $this->trashSortDirection = $this->trashSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->trashSortField = $field;
            $this->trashSortDirection = 'asc';
        }
    }
    
    public function getTrashedEntriesProperty()
    {
        $query = AccountingEntry::onlyTrashed()->with(['bankAccount', 'paymentMethod']);
        
        if ($this->trashSearch) {
            $searchTerm = '%' . $this->trashSearch . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('description', 'like', $searchTerm)
                  ->orWhere('amount', 'like', $searchTerm);
            });
        }
        
        return $query->orderBy($this->trashSortField, $this->trashSortDirection)
                     ->paginate(10);
    }
    
    public function getTrashCountProperty(): int
    {
        return AccountingEntry::onlyTrashed()->count();
    }
    
    public function restoreFromTrash(int $id): void
    {
        try {
            DB::beginTransaction();
            
            $entry = AccountingEntry::onlyTrashed()->find($id);
            if ($entry) {
                $description = $entry->description;
                
                // Ripristina la scrittura
                $entry->restore();
                
                // Ripristina anche le transazioni collegate
                $transactions = InstallmentTransaction::onlyTrashed()
                    ->where('id_accounting_entries', $entry->id)
                    ->get();
                    
                foreach ($transactions as $transaction) {
                    $transaction->restore();
                    
                    // Aggiorna il pagamento collegato
                    $payment = $transaction->invoicePayment;
                    if ($payment) {
                        $invoice = $payment->payable;
                        if ($invoice) {
                            $this->updateInvoiceStatus($invoice);
                        }
                    }
                }
                
                DB::commit();
                
                $this->dispatch('showSuccess', message: "Scrittura '{$description}' ripristinata con successo!");
                $this->dispatch('refreshAccountingEntries');
                $this->dispatch('refreshPayments');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }
    
    public function forceDeleteFromTrash(int $id): void
    {
        try {
            DB::beginTransaction();
            
            $entry = AccountingEntry::onlyTrashed()->find($id);
            if ($entry) {
                $entryName = 'Scrittura del ' . $entry->entry_date->format('d/m/Y') . ' - ' . number_format($entry->amount, 2, ',', '.') . ' €';
                
                // Elimina le installment_transactions collegate
                if (method_exists(InstallmentTransaction::class, 'onlyTrashed')) {
                    $entry->installmentTransactions()->forceDelete();
                } else {
                    $entry->installmentTransactions()->delete();
                }
                
                // Elimina definitivamente la scrittura contabile
                $entry->forceDelete();
                
                DB::commit();
                
                $this->dispatch('showSuccess', message: "{$entryName} eliminata definitivamente.");
                $this->dispatch('refreshAccountingEntries');
                $this->closeTrashModal();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore force delete: ' . $e->getMessage());
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }

    // ==================== IMPORT CSV ====================

    public function openImportModal(): void
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_accounting_entries')) {
            $this->dispatch('showError', message: 'Non hai i permessi per creare scritture contabili');
            return;
        }

        $this->resetImportForm();
        $this->showImportModal = true;
    }

    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->resetImportForm();
    }

    private function resetImportForm(): void
    {
        $this->importFile = null;
        $this->importPreview = [];
        $this->importRowErrors = [];
        $this->importValidRowsCount = 0;
        $this->importDone = false;
        $this->importedCount = 0;

        $this->importEntitySearch = '';
        $this->importEntityId = '';
        $this->importEntityName = '';
        $this->importEntityResults = new Collection();
        $this->showImportEntityDropdown = false;

        $this->importCostCenterSearch = '';
        $this->importCostCenterId = '';
        $this->importCostCenterName = '';
        $this->importCostCenterResults = new Collection();
        $this->showImportCostCenterDropdown = false;

        $this->importStatus = 'COMPLETATO';
    }

    // --- Autocomplete Cliente/Fornitore (import) ---
    public function updatedImportEntitySearch(): void
    {
        if ($this->importEntityId && $this->importEntitySearch === $this->importEntityName) {
            $this->showImportEntityDropdown = false;
            return;
        }
        if ($this->importEntityId) {
            $this->importEntityId = '';
            $this->importEntityName = '';
        }
        if (strlen($this->importEntitySearch) < 2) {
            $this->importEntityResults = new Collection();
            $this->showImportEntityDropdown = false;
            return;
        }
        $this->importEntityResults = Entity::where('valid', 1)
            ->where(function($q) {
                $q->where('ragione_sociale', 'like', '%' . $this->importEntitySearch . '%')
                  ->orWhere('nome', 'like', '%' . $this->importEntitySearch . '%')
                  ->orWhere('cognome', 'like', '%' . $this->importEntitySearch . '%');
            })
            ->limit(10)
            ->get(['id_cliente', 'ragione_sociale', 'nome', 'cognome']);
        $this->showImportEntityDropdown = $this->importEntityResults->isNotEmpty();
    }

    public function selectImportEntity($id, $name): void
    {
        $this->importEntityId = $id;
        $this->importEntityName = $name;
        $this->importEntitySearch = $name;
        $this->showImportEntityDropdown = false;
    }

    public function clearImportEntity(): void
    {
        $this->importEntityId = '';
        $this->importEntityName = '';
        $this->importEntitySearch = '';
        $this->importEntityResults = new Collection();
        $this->showImportEntityDropdown = false;
    }

    // --- Autocomplete Centro di Costo (import) ---
    public function updatedImportCostCenterSearch(): void
    {
        if ($this->importCostCenterId && $this->importCostCenterSearch === $this->importCostCenterName) {
            $this->showImportCostCenterDropdown = false;
            return;
        }
        if ($this->importCostCenterId) {
            $this->importCostCenterId = '';
            $this->importCostCenterName = '';
        }
        if (strlen($this->importCostCenterSearch) < 2) {
            $this->importCostCenterResults = new Collection();
            $this->showImportCostCenterDropdown = false;
            return;
        }
        $this->importCostCenterResults = CostCenter::where('valid', 1)
            ->where('Nome', 'like', '%' . $this->importCostCenterSearch . '%')
            ->orderBy('Nome')
            ->limit(10)
            ->get(['id', 'Nome']);
        $this->showImportCostCenterDropdown = $this->importCostCenterResults->isNotEmpty();
    }

    public function selectImportCostCenter($id, $name): void
    {
        $this->importCostCenterId = $id;
        $this->importCostCenterName = $name;
        $this->importCostCenterSearch = $name;
        $this->showImportCostCenterDropdown = false;
    }

    public function clearImportCostCenter(): void
    {
        $this->importCostCenterId = '';
        $this->importCostCenterName = '';
        $this->importCostCenterSearch = '';
        $this->importCostCenterResults = new Collection();
        $this->showImportCostCenterDropdown = false;
    }

    /**
     * Viene chiamato automaticamente da Livewire quando l'utente sceglie il file
     * (wire:model="importFile"). Legge e valida il CSV, popola l'anteprima.
     */
    public function updatedImportFile(): void
    {
        $this->importPreview = [];
        $this->importRowErrors = [];
        $this->importValidRowsCount = 0;
        $this->importDone = false;

        if (!$this->importFile) {
            return;
        }

        $this->validate([
            'importFile' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $path = $this->importFile->getRealPath();
        $handle = fopen($path, 'r');
        if ($handle === false) {
            $this->dispatch('showError', message: 'Impossibile leggere il file CSV');
            return;
        }

        // FIX: gestisce sia delimitatore , sia ; (LibreOffice/Excel IT usano spesso ;)
        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $header = fgetcsv($handle, 0, $delimiter);
        if (!$header) {
            fclose($handle);
            $this->dispatch('showError', message: 'File CSV vuoto o non leggibile');
            return;
        }

        // Normalizza intestazioni (case-insensitive, trim)
        $header = array_map(fn($h) => strtolower(trim($h)), $header);
        $required = ['entry_date', 'description', 'type', 'amount'];
        foreach ($required as $col) {
            if (!in_array($col, $header)) {
                fclose($handle);
                $this->dispatch('showError', message: "Colonna obbligatoria mancante nel CSV: {$col}");
                return;
            }
        }

        $rowNumber = 1; // header = riga 1
        $preview = [];
        $errorsCount = 0;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNumber++;
            if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) {
                continue; // riga vuota
            }

            $data = array_combine($header, array_pad($row, count($header), null));

            $rowErrors = [];

            // entry_date: accetta Y-m-d o d/m/Y
            $rawDate = trim($data['entry_date'] ?? '');
            $entryDate = null;
            foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $fmt) {
                try {
                    $parsed = \Carbon\Carbon::createFromFormat($fmt, $rawDate);
                    if ($parsed && $parsed->format($fmt) === $rawDate) {
                        $entryDate = $parsed->format('Y-m-d');
                        break;
                    }
                } catch (\Exception $e) {
                    // prova formato successivo
                }
            }
            if (!$entryDate) {
                $rowErrors[] = 'data non valida';
            }

            $description = trim($data['description'] ?? '');
            if ($description === '') {
                $rowErrors[] = 'descrizione mancante';
            }

            $type = strtolower(trim($data['type'] ?? ''));
            if (!in_array($type, ['entrata', 'uscita'])) {
                $rowErrors[] = 'tipo non valido (deve essere entrata/uscita)';
            }

            // amount: gestisce sia virgola che punto decimale
            $rawAmount = trim((string)($data['amount'] ?? ''));
            $normalizedAmount = str_replace(',', '.', $rawAmount);
            $amount = is_numeric($normalizedAmount) ? (float)$normalizedAmount : null;
            if ($amount === null || $amount <= 0) {
                $rowErrors[] = 'importo non valido';
            }

            // id_payments_methods: opzionale, verifica esistenza se presente
            $paymentMethodId = trim((string)($data['id_payments_methods'] ?? $data['id_payments_method'] ?? ''));
            $paymentMethodId = $paymentMethodId !== '' ? (int)$paymentMethodId : null;
            if ($paymentMethodId && !PaymentMethod::where('id', $paymentMethodId)->exists()) {
                $rowErrors[] = "metodo pagamento id {$paymentMethodId} inesistente";
            }

            // bank_account_id: opzionale, verifica esistenza se presente
            $bankAccountId = trim((string)($data['bank_account_id'] ?? ''));
            $bankAccountId = $bankAccountId !== '' ? (int)$bankAccountId : null;
            if ($bankAccountId && !BankAccount::where('id', $bankAccountId)->exists()) {
                $rowErrors[] = "conto bancario id {$bankAccountId} inesistente";
            }

            $isValid = empty($rowErrors);
            if ($isValid) {
                $this->importValidRowsCount++;
            } else {
                $errorsCount++;
            }

            $preview[] = [
                'row_number' => $rowNumber,
                'entry_date' => $entryDate ?? $rawDate,
                'description' => $description,
                'type' => $type,
                'id_payments_methods' => $paymentMethodId,
                'bank_account_id' => $bankAccountId,
                'amount' => $amount,
                'is_valid' => $isValid,
                'errors' => $rowErrors,
            ];
        }

        fclose($handle);

        // Limite di sicurezza per l'anteprima/import in un'unica richiesta
        if (count($preview) > 500) {
            $this->dispatch('showError', message: 'Il file contiene troppe righe (max 500 per importazione). Dividilo in più file.');
            $this->importFile = null;
            return;
        }

        $this->importPreview = $preview;

        if ($errorsCount > 0) {
            $this->dispatch('showError', message: "{$errorsCount} righe contengono errori e non verranno importate (vedi anteprima).");
        }
    }

    /**
     * Crea le scritture contabili per tutte le righe valide dell'anteprima,
     * applicando id_entities / id_cost_centers / status scelti una sola volta
     * per l'intero import.
     */
    public function confirmImport(): void
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_accounting_entries')) {
            $this->dispatch('showError', message: 'Non hai i permessi per creare scritture contabili');
            return;
        }

        if (empty($this->importPreview)) {
            $this->dispatch('showError', message: 'Nessuna riga da importare');
            return;
        }

        $adminId = Auth::guard('admin')->id();
        $count = 0;

        try {
            DB::beginTransaction();

            foreach ($this->importPreview as $row) {
                if (!$row['is_valid']) {
                    continue;
                }

                AccountingEntry::create([
                    'entry_date' => $row['entry_date'],
                    'description' => $row['description'],
                    'type' => $row['type'],
                    'id_payments_methods' => $row['id_payments_methods'],
                    'bank_account_id' => $row['bank_account_id'],
                    'amount' => $row['amount'],
                    'status' => $this->importStatus,
                    'id_entities' => $this->importEntityId ?: null,
                    'id_cost_centers' => $this->importCostCenterId,
                ]);

                $count++;
            }

            DB::commit();

            $this->importedCount = $count;
            $this->importDone = true;

            $this->dispatch('showSuccess', message: "{$count} scritture contabili importate con successo!");
            $this->dispatch('refreshAccountingEntries');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AccountingEntriesTable::confirmImport error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->dispatch('showError', message: 'Errore durante l\'importazione: ' . $e->getMessage());
        }
    }
    
    // ==================== PROPRIETÀ ====================
    
    public function getEntriesProperty()
    {
        // Inizia con TUTTE le scritture contabili
        $query = AccountingEntry::with([
            'bankAccount', 
            'paymentMethod', 
            'invoice',
            'invoicePayment.payable',
            'installmentTransactions.invoicePayment.payable.ownership',
            'installmentTransactions.invoicePayment.payable.entity'
        ]);

        // ==================== FILTRO RICERCA ====================
        if ($this->search) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('description', 'like', $searchTerm)
                  ->orWhereHas('bankAccount', fn($sq) => $sq->where('name', 'like', $searchTerm))
                  ->orWhereHas('paymentMethod', fn($sq) => $sq->where('name', 'like', $searchTerm))
                  ->orWhereHas('invoice', fn($sq) => $sq->where('n_invoice', 'like', $searchTerm))
                  ->orWhereHas('invoicePayment.payable', function($sq) use ($searchTerm) {
                      $sq->where('n_invoice', 'like', $searchTerm);
                  })
                  ->orWhereHas('installmentTransactions', function($sq) use ($searchTerm) {
                      $sq->whereHas('invoicePayment', function($sub) use ($searchTerm) {
                          $sub->whereHas('payable', function($inner) use ($searchTerm) {
                              $inner->where('n_invoice', 'like', $searchTerm);
                          });
                      });
                  });
            });
        }

        // ==================== FILTRO TIPO ====================
        if ($this->type) {
            $query->where('type', $this->type);
        }

        // ==================== FILTRO STATO ====================
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        // ==================== FILTRO DATA ====================
        if ($this->dateFrom) {
            $query->whereDate('entry_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('entry_date', '<=', $this->dateTo);
        }

        // ==================== FILTRO CONTO BANCARIO ====================
        if ($this->bankAccountId) {
            $query->where('bank_account_id', $this->bankAccountId);
        }

        // ==================== FILTRO METODO PAGAMENTO ====================
        if ($this->paymentMethodId) {
            $query->where('id_payments_methods', $this->paymentMethodId);
        }

        // ==================== FILTRO PROPRIETÀ ====================
        if ($this->selectedOwnershipId) {
            $query->where(function($q) {
                $q->whereHas('bankAccount', fn($sq) => $sq->where('id_ownership', $this->selectedOwnershipId))
                  ->orWhereHas('invoice', fn($sq) => $sq->where('id_ownership', $this->selectedOwnershipId))
                  ->orWhereHas('invoicePayment.payable', function($sq) {
                      $sq->where('id_ownership', $this->selectedOwnershipId);
                  })
                  ->orWhereHas('installmentTransactions', function($sq) {
                      $sq->whereHas('invoicePayment', function($sub) {
                          $sub->whereHas('payable', function($inner) {
                              $inner->where('id_ownership', $this->selectedOwnershipId);
                          });
                      });
                  });
            });
        }

        // ==================== FILTRO ENTITÀ ====================
        if ($this->entityFilter) {
            $query->where(function($q) {
                $q->where('id_entities', $this->entityFilter)
                  ->orWhereHas('invoice', fn($sq) => $sq->where('id_entities', $this->entityFilter))
                  ->orWhereHas('invoicePayment.payable', function($sq) {
                      $sq->where('id_entities', $this->entityFilter);
                  })
                  ->orWhereHas('installmentTransactions', function($sq) {
                      $sq->whereHas('invoicePayment', function($sub) {
                          $sub->whereHas('payable', function($inner) {
                              $inner->where('id_entities', $this->entityFilter);
                          });
                      });
                  });
            });
        }

        // ==================== ESECUZIONE QUERY ====================
        if ($this->perPage == 100000) {
            $results = $query->orderBy($this->sortField, $this->sortDirection)->get();
            $page = \Illuminate\Pagination\Paginator::resolveCurrentPage();
            $perPage = $results->count() ?: 1;
            return new \Illuminate\Pagination\LengthAwarePaginator(
                $results->forPage($page, $perPage),
                $results->count(),
                $perPage,
                $page,
                ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
            );
        }

        return $query->orderBy($this->sortField, $this->sortDirection)
                    ->paginate($this->perPage);
    }
    
    public function getBankAccountsProperty()
    {
        return BankAccount::with('ownership')
            ->where('valid', 1)
            ->get()
            ->sortBy(function($account) {
                return $account->ownership->RagAbbrev ?? $account->ownership->Rag_Soc_intest ?? '';
            })
            ->map(function($account) {
                $ownershipAbbrev = $account->ownership->RagAbbrev ?? $account->ownership->Rag_Soc_intest ?? 'N/A';
                $displayName = trim($ownershipAbbrev . ' - ' . $account->name);
                
                if (!empty($account->n_conto)) {
                    $displayName .= ' - ' . $account->n_conto;
                }
                
                $result = new \stdClass();
                $result->id = $account->id;
                $result->name = $displayName;
                $result->ownership_name = $ownershipAbbrev;
                $result->bank_name = $account->name;
                $result->n_conto = $account->n_conto;
                $result->iban = $account->iban;
                
                return $result;
            });
    }
    
    public function getPaymentMethodsProperty()
    {
        return PaymentMethod::where('is_active', true)->orderBy('sort_order')->get();
    }

    public function getPaymentStatusesProperty()
    {
        return Setting::where('tabella_riferimento', 'accounting_entries_status')
            ->where('valid', 1)
            ->orderBy('ordinamento')
            ->get();
    }
    
    public function getTotalEntrateProperty()
    {
        $query = AccountingEntry::query();
        if ($this->dateFrom) $query->whereDate('entry_date', '>=', $this->dateFrom);
        if ($this->dateTo) $query->whereDate('entry_date', '<=', $this->dateTo);
        return $query->where('type', 'entrata')->sum('amount');
    }
    
    public function getTotalUsciteProperty()
    {
        $query = AccountingEntry::query();
        if ($this->dateFrom) $query->whereDate('entry_date', '>=', $this->dateFrom);
        if ($this->dateTo) $query->whereDate('entry_date', '<=', $this->dateTo);
        return $query->where('type', 'uscita')->sum('amount');
    }
    
    public function getSaldoProperty()
    {
        return $this->totalEntrate - $this->totalUscite;
    }

    public function formatDate($date)
    {
        return Carbon::parse($date)->format('d/m/Y');
    }
    
    public function render()
    {
        $currentUser = Auth::guard('admin')->user();
        
        return view('livewire.admin.accounting-entries-table', [
            'entries' => $this->entries,
            'bankAccounts' => $this->bankAccounts,
            'paymentMethods' => $this->paymentMethods,
            'paymentStatuses' => $this->paymentStatuses,
            'totalEntrate' => $this->totalEntrate,
            'totalUscite' => $this->totalUscite,
            'saldo' => $this->saldo,
            'trashCount' => $this->trashCount,
            'trashedEntries' => $this->trashedEntries,
            'canView' => $currentUser->hasPermission('view_accounting_entries'),
            'canCreate' => $currentUser->hasPermission('create_accounting_entries'),
            'canEdit' => $currentUser->hasPermission('edit_accounting_entries'),
            'canDelete' => $currentUser->hasPermission('delete_accounting_entries'),
        ]);
    }
}