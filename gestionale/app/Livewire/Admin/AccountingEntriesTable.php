<?php
// app/Livewire/Admin/AccountingEntriesTable.php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\AccountingEntry;
use App\Models\BankAccount;
use App\Models\PaymentMethod;
use App\Models\InvoiceReceived;
use App\Models\InvoicePayment;
use App\Models\InstallmentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AccountingEntriesTable extends Component
{
    use WithPagination;
    
    public $search = '';
    public $type = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $bankAccountId = '';
    public $paymentMethodId = '';
    
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
    public $id_payments_methods = '';
    public $bank_account_id = '';
    public $amount = '';
    public $invoice_id = '';
    public $isEditing = false;
    
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
    
    protected $rules = [
        'entry_date' => 'required|date',
        'description' => 'required|string|min:3',
        'type_value' => 'required|in:entrata,uscita',
        'amount' => 'required|numeric|min:0.01',
        'bank_account_id' => 'nullable|exists:bank_accounts,id',
        'id_payments_methods' => 'nullable|exists:payment_methods,id',
        'invoice_id' => 'nullable|exists:invoices_received,id',
    ];
    
    protected $listeners = [
        'refreshAccountingEntries' => '$refresh',
        'refreshPayments' => 'refreshTable',
        'refreshInvoices' => 'refreshTable',
    ];
    
    public function mount()
    {
        $this->entry_date = date('Y-m-d');
        $this->dateFrom = date('Y-m-01');
        $this->dateTo = date('Y-m-t');
    }
    
    public function refreshTable(): void
    {
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
        $this->type = '';
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
        $this->id_payments_methods = $entry->id_payments_methods;
        $this->bank_account_id = $entry->bank_account_id;
        $this->amount = (string)$entry->amount;
        $this->displayAmount = number_format($entry->amount, 2, ',', '.');
        $this->originalAmount = $entry->amount;
        $this->invoice_id = $entry->invoice_id;
        $this->isEditing = true;
        $this->isAmountChanged = false;
        $this->amountDifference = 0;
        $this->showModal = true;
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
        $this->id_payments_methods = '';
        $this->bank_account_id = '';
        $this->amount = '';
        $this->displayAmount = '0,00';
        $this->invoice_id = '';
        $this->isEditing = false;
        $this->originalAmount = 0;
        $this->isAmountChanged = false;
        $this->amountDifference = 0;
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
                'id_payments_methods' => $this->id_payments_methods ?: null,
                'bank_account_id' => $this->bank_account_id ?: null,
                'amount' => $this->amount,
                'invoice_id' => $this->invoice_id ?: null,
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
                $description = $entry->description;
                
                // Verifica se questa scrittura è collegata a pagamenti di fatture
                $installmentTransactions = InstallmentTransaction::onlyTrashed()
                    ->where('id_accounting_entries', $entry->id)
                    ->get();
                
                foreach ($installmentTransactions as $transaction) {
                    $payment = $transaction->invoicePayment;
                    if ($payment) {
                        $invoice = $payment->payable;
                        
                        // Rimuovi l'importo allocato dal pagamento
                        $newPaidAmount = max(0, $payment->paid_amount - $transaction->allocated_amount);
                        $newResidual = $payment->amount - $newPaidAmount;
                        
                        $payment->update([
                            'paid_amount' => $newPaidAmount,
                            'residual_amount' => $newResidual,
                            'status' => $newResidual <= 0.01 ? 'paid' : ($newPaidAmount > 0 ? 'partially_paid' : 'issued'),
                            'paid_at' => $newResidual <= 0.01 ? now() : null,
                        ]);
                        
                        // Aggiorna lo stato della fattura
                        if ($invoice) {
                            $this->updateInvoiceStatus($invoice);
                        }
                    }
                    
                    // Elimina definitivamente la transazione
                    $transaction->forceDelete();
                }
                
                // Elimina definitivamente la scrittura contabile
                $entry->forceDelete();
                
                DB::commit();
                
                $this->dispatch('showSuccess', message: "Scrittura '{$description}' eliminata definitivamente.");
                $this->dispatch('refreshAccountingEntries');
                $this->dispatch('refreshPayments');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }
    
    // ==================== PROPRIETÀ ====================
    
    public function getEntriesProperty()
    {
        $query = AccountingEntry::with(['bankAccount', 'paymentMethod', 'invoice']);
        
        if ($this->search) {
            $query->where(function($q) {
                $q->where('description', 'like', '%' . $this->search . '%')
                  ->orWhereHas('bankAccount', fn($sq) => $sq->where('name', 'like', '%' . $this->search . '%'))
                  ->orWhereHas('paymentMethod', fn($sq) => $sq->where('name', 'like', '%' . $this->search . '%'));
            });
        }
        
        if ($this->type) {
            $query->where('type', $this->type);
        }
        
        if ($this->dateFrom) {
            $query->whereDate('entry_date', '>=', $this->dateFrom);
        }
        
        if ($this->dateTo) {
            $query->whereDate('entry_date', '<=', $this->dateTo);
        }
        
        if ($this->bankAccountId) {
            $query->where('bank_account_id', $this->bankAccountId);
        }
        
        if ($this->paymentMethodId) {
            $query->where('id_payments_methods', $this->paymentMethodId);
        }
        
        if ($this->perPage == 10000) {
            $results = $query->orderBy($this->sortField, $this->sortDirection)->get();
            $page = \Illuminate\Pagination\Paginator::resolveCurrentPage();
            $perPage = $results->count();
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
                
                // Crea un oggetto stdClass con TUTTE le proprietà necessarie
                $result = new \stdClass();
                $result->id = $account->id;
                $result->name = $displayName;
                $result->ownership_name = $ownershipAbbrev;
                $result->bank_name = $account->name;
                $result->n_conto = $account->n_conto;  // Aggiungi questa proprietà
                $result->iban = $account->iban;
                
                return $result;
            });
    }
    
    public function getPaymentMethodsProperty()
    {
        return PaymentMethod::where('is_active', true)->orderBy('sort_order')->get();
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