<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\AccountingEntry;
use App\Models\Account;
use App\Models\PaymentMethod;
use App\Models\InvoiceReceived;
use App\Models\InvoicePayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AccountingEntriesTable extends Component
{
    use WithPagination;
    
    // Filtri
    public $search = '';
    public $type = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $debitAccountId = '';
    public $creditAccountId = '';
    public $paymentMethodId = '';
    
    // Ordinamento
    public $sortField = 'entry_date';
    public $sortDirection = 'desc';
    public $perPage = 100;
    
    // Modals
    public $showModal = false;
    public $showDeleteModal = false;
    public $showViewModal = false;
    public $selectedEntry = null;
    public $viewingEntry = null;
    public $entryToDelete = null;
    
    // Form per creazione/modifica
    public $entryId = null;
    public $entry_date = '';
    public $description = '';
    public $type_value = '';
    public $id_payments_methods = '';
    public $debit_account_id = '';
    public $credit_account_id = '';
    public $amount = '';
    public $invoice_id = '';
    public $invoice_payment_id = '';
    public $isEditing = false;
    
    // Autocomplete
    public $accountSearch = '';
    public $showAccountDropdown = false;
    public $accountResults = [];
    
    protected $rules = [
        'entry_date' => 'required|date',
        'description' => 'required|string|min:3',
        'type_value' => 'required|in:entrata,uscita',
        'debit_account_id' => 'required|exists:accounts,id',
        'credit_account_id' => 'required|exists:accounts,id',
        'amount' => 'required|numeric|min:0.01',
        'id_payments_methods' => 'nullable|exists:payment_methods,id',
        'invoice_id' => 'nullable|exists:invoices_received,id',
        'invoice_payment_id' => 'nullable|exists:invoice_payments,id',
    ];
    
    protected $messages = [
        'entry_date.required' => 'La data è obbligatoria',
        'entry_date.date' => 'Inserire una data valida',
        'description.required' => 'La descrizione è obbligatoria',
        'description.min' => 'La descrizione deve contenere almeno 3 caratteri',
        'type_value.required' => 'Selezionare il tipo (Entrata/Uscita)',
        'type_value.in' => 'Tipo non valido',
        'debit_account_id.required' => 'Selezionare il conto Dare',
        'debit_account_id.exists' => 'Conto Dare non valido',
        'credit_account_id.required' => 'Selezionare il conto Avere',
        'credit_account_id.exists' => 'Conto Avere non valido',
        'amount.required' => 'L\'importo è obbligatorio',
        'amount.numeric' => 'Inserire un importo valido',
        'amount.min' => 'L\'importo deve essere almeno 0.01',
    ];
    
    protected $listeners = [
        'dateRangeUpdated' => 'updateDateRange',
        'resetDates' => 'resetDateRange',
        'refreshTable' => '$refresh',
    ];
    
    public function mount()
    {
        $now = Carbon::now();
        $this->entry_date = $now->format('Y-m-d');
        $this->dateFrom = $now->copy()->startOfMonth()->format('Y-m-d');
        $this->dateTo = $now->copy()->endOfMonth()->format('Y-m-d');
    }
    
    /**
     * Aggiorna il range di date quando arriva l'evento dal componente DateRangeFilter
     */
    public function updateDateRange(array $data): void
    {
        $this->dateFrom = $data['date_from'] ?? '';
        $this->dateTo = $data['date_to'] ?? '';
        $this->resetPage();
    }
    
    /**
     * Resetta il range date
     */
    public function resetDateRange(): void
    {
        $now = Carbon::now();
        $this->dateFrom = $now->copy()->startOfMonth()->format('Y-m-d');
        $this->dateTo = $now->copy()->endOfMonth()->format('Y-m-d');
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
        $this->debitAccountId = '';
        $this->creditAccountId = '';
        $this->paymentMethodId = '';
        
        $now = Carbon::now();
        $this->dateFrom = $now->copy()->startOfMonth()->format('Y-m-d');
        $this->dateTo = $now->copy()->endOfMonth()->format('Y-m-d');
        
        $this->resetPage();
        
        // Resetta anche il componente date-range-filter
        $this->dispatch('resetDates')->to('components.date-range-filter');
    }
    
    public function clearDates()
    {
        $now = Carbon::now();
        $this->dateFrom = $now->copy()->startOfMonth()->format('Y-m-d');
        $this->dateTo = $now->copy()->endOfMonth()->format('Y-m-d');
        $this->resetPage();
        $this->dispatch('resetDates')->to('components.date-range-filter');
    }
    
    // ... resto dei metodi esistenti (openCreateModal, openEditModal, viewEntry, closeModal, closeViewModal, resetForm, save, confirmDelete, deleteEntry, cancelDelete) ...
    
    public function getEntriesProperty()
    {
        $query = AccountingEntry::with(['debitAccount', 'creditAccount', 'paymentMethod', 'invoice']);
        
        // Filtro ricerca
        if ($this->search) {
            $query->where(function($q) {
                $q->where('description', 'like', '%' . $this->search . '%')
                  ->orWhereHas('debitAccount', fn($sq) => $sq->where('name', 'like', '%' . $this->search . '%'))
                  ->orWhereHas('creditAccount', fn($sq) => $sq->where('name', 'like', '%' . $this->search . '%'))
                  ->orWhereHas('paymentMethod', fn($sq) => $sq->where('name', 'like', '%' . $this->search . '%'));
            });
        }
        
        // Filtro tipo
        if ($this->type) {
            $query->where('type', $this->type);
        }
        
        // ============================================
        // FILTRO PER DATA - CON DATE RANGE
        // ============================================
        if ($this->dateFrom && $this->dateTo) {
            $query->whereBetween('entry_date', [$this->dateFrom, $this->dateTo]);
        } elseif ($this->dateFrom) {
            $query->whereDate('entry_date', '>=', $this->dateFrom);
        } elseif ($this->dateTo) {
            $query->whereDate('entry_date', '<=', $this->dateTo);
        }
        
        // Filtro conto Dare
        if ($this->debitAccountId) {
            $query->where('debit_account_id', $this->debitAccountId);
        }
        
        // Filtro conto Avere
        if ($this->creditAccountId) {
            $query->where('credit_account_id', $this->creditAccountId);
        }
        
        // Filtro metodo pagamento
        if ($this->paymentMethodId) {
            $query->where('id_payments_methods', $this->paymentMethodId);
        }
        
        // Gestione paginazione "Tutti"
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
    
    public function getAccountsProperty()
    {
        return Account::where('is_active', true)->orderBy('code')->get();
    }
    
    public function getPaymentMethodsProperty()
    {
        return PaymentMethod::where('is_active', true)->orderBy('sort_order')->get();
    }
    
    public function getTotalEntrateProperty()
    {
        $query = AccountingEntry::query();
        
        if ($this->dateFrom && $this->dateTo) {
            $query->whereBetween('entry_date', [$this->dateFrom, $this->dateTo]);
        } elseif ($this->dateFrom) {
            $query->whereDate('entry_date', '>=', $this->dateFrom);
        } elseif ($this->dateTo) {
            $query->whereDate('entry_date', '<=', $this->dateTo);
        }
        
        return $query->where('type', 'entrata')->sum('amount');
    }
    
    public function getTotalUsciteProperty()
    {
        $query = AccountingEntry::query();
        
        if ($this->dateFrom && $this->dateTo) {
            $query->whereBetween('entry_date', [$this->dateFrom, $this->dateTo]);
        } elseif ($this->dateFrom) {
            $query->whereDate('entry_date', '>=', $this->dateFrom);
        } elseif ($this->dateTo) {
            $query->whereDate('entry_date', '<=', $this->dateTo);
        }
        
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
    
    public function formatCurrency($amount)
    {
        return number_format($amount, 2, ',', '.') . ' €';
    }
    
    public function render()
    {
        $currentUser = Auth::guard('admin')->user();
        
        return view('livewire.admin.accounting-entries-table', [
            'entries' => $this->entries,
            'accounts' => $this->accounts,
            'paymentMethods' => $this->paymentMethods,
            'totalEntrate' => $this->totalEntrate,
            'totalUscite' => $this->totalUscite,
            'saldo' => $this->saldo,
            'canView' => $currentUser->hasPermission('view_accounting_entries'),
            'canCreate' => $currentUser->hasPermission('create_accounting_entries'),
            'canEdit' => $currentUser->hasPermission('edit_accounting_entries'),
            'canDelete' => $currentUser->hasPermission('delete_accounting_entries'),
        ]);
    }
}