<div>
    <!-- Header con titolo -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">
            <i class="fas fa-calendar-alt mr-3 text-lime-600"></i>
            Scadenze Pagamenti
        </h1>
        
        <!-- Aggiungi il componente per il nuovo pagamento -->
        @livewire('admin.register-payment')
    </div>

    <!-- Card filtri -->
    <div class="bg-white rounded-lg shadow p-4 mb-4 border border-gray-200">
        <!-- Date Range Filter -->
        @livewire('components.date-range-filter', ['dateFrom' => $dateFrom, 'dateTo' => $dateTo])
        
        <div class="border-t border-gray-200 my-4"></div>
        
        <!-- Filtri -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Proprietà Autocomplete -->
            <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                <label class="block text-xs font-medium text-gray-500 mb-1">Proprietà</label>
                <div class="relative">
                    <i class="fas fa-building absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                    <input type="text"
                        id="ownership_input"
                        wire:model.live.debounce.300ms="ownershipSearch"
                        x-on:focus="open = true"
                        x-on:input="open = true; @this.set('ownershipSearch', $event.target.value)"
                        placeholder="Cerca proprietà..."
                        class="w-full pl-9 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                        autocomplete="off">
                    @if($selectedOwnershipId)
                        <button type="button"
                            wire:click="clearOwnership"
                            class="absolute right-2 top-2 text-gray-400 hover:text-red-500">
                            <i class="fas fa-times-circle text-sm"></i>
                        </button>
                    @endif
                </div>
                <div x-show="open && @entangle('showOwnershipDropdown')" class="absolute z-50 w-full mt-1 bg-white border rounded-md shadow-lg max-h-60 overflow-y-auto">
                    @foreach($ownershipResults as $item)
                        <div x-on:click="open = false; $wire.selectOwnership({{ $item->id }}, '{{ addslashes($item->name) }}')" 
                             class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm">{{ $item->name }}</div>
                    @endforeach
                </div>
            </div>
            
            <!-- Fornitore Autocomplete -->
            <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                <label class="block text-xs font-medium text-gray-500 mb-1">Fornitore</label>
                <div class="relative">
                    <i class="fas fa-user absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                    <input type="text"
                        id="supplier_input"
                        wire:model.live.debounce.300ms="supplierSearch"
                        x-on:focus="open = true"
                        x-on:input="open = true; @this.set('supplierSearch', $event.target.value)"
                        placeholder="Cerca fornitore..."
                        class="w-full pl-9 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                        autocomplete="off">
                    @if($selectedSupplierId)
                        <button type="button"
                            wire:click="clearSupplier"
                            class="absolute right-2 top-2 text-gray-400 hover:text-red-500">
                            <i class="fas fa-times-circle text-sm"></i>
                        </button>
                    @endif
                </div>
                <div x-show="open && @entangle('showSupplierDropdown')" class="absolute z-50 w-full mt-1 bg-white border rounded-md shadow-lg max-h-60 overflow-y-auto">
                    @foreach($supplierResults as $item)
                        <div x-on:click="open = false; $wire.selectSupplier({{ $item->id }}, '{{ addslashes($item->name) }}')" 
                             class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm">{{ $item->name }}</div>
                    @endforeach
                </div>
            </div>
            
            <!-- Stato -->
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Stato Pagamento</label>
                <div class="relative">
                    <i class="fas fa-tag absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                    <select wire:model.live="status" class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-md">
                        <option value="">Tutti gli stati</option>
                        @foreach($statuses as $value => $statusData)
                            <option value="{{ $value }}">{{ $statusData['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            
            <!-- Per pagina -->
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Per pagina</label>
                <select wire:model.live="perPage" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md">
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="200">200</option>
                    <option value="500">500</option>
                </select>
            </div>
        </div>
        
        <!-- Active Filters Tags -->
        @if($selectedOwnershipId || $selectedSupplierId || $status || $dateFrom || $dateTo)
        <div class="mt-4 pt-3 border-t border-gray-200">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs text-gray-500">Filtri attivi:</span>
                @if($selectedOwnershipId)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    <i class="fas fa-building mr-1"></i> {{ $selectedOwnershipName }}
                    <button wire:click="clearOwnership" class="ml-1 hover:text-lime-900"><i class="fas fa-times"></i></button>
                </span>
                @endif
                @if($selectedSupplierId)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    <i class="fas fa-user mr-1"></i> {{ $selectedSupplierName }}
                    <button wire:click="clearSupplier" class="ml-1 hover:text-lime-900"><i class="fas fa-times"></i></button>
                </span>
                @endif
                @if($status)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    <i class="fas fa-tag mr-1"></i> {{ $statuses[$status]['label'] ?? $status }}
                    <button wire:click="clearStatus" class="ml-1 hover:text-lime-900"><i class="fas fa-times"></i></button>
                </span>
                @endif
                @if($dateFrom || $dateTo)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    <i class="fas fa-calendar mr-1"></i> {{ $dateFrom ?: '...' }} → {{ $dateTo ?: '...' }}
                    <button wire:click="$set('dateFrom', ''); $set('dateTo', '')" class="ml-1 hover:text-lime-900"><i class="fas fa-times"></i></button>
                </span>
                @endif
                <span class="text-xs text-gray-400 ml-2">
                    <button wire:click="resetFilters" class="hover:text-red-500"><i class="fas fa-trash-alt mr-1"></i> Rimuovi tutti</button>
                </span>
            </div>
        </div>
        @endif
    </div>

    <!-- Tabella Scadenze con le colonne nell'ordine richiesto -->
    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 cursor-pointer hover:bg-gray-100">Proprietà</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 cursor-pointer hover:bg-gray-100">Fornitore</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 cursor-pointer hover:bg-gray-100" wire:click="sortBy('due_date')">
                            Data Scadenza
                            @if($sortField === 'due_date')<i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>@endif
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">N. Fattura</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 cursor-pointer hover:bg-gray-100" wire:click="sortBy('amount')">
                            Importo
                            @if($sortField === 'amount')<i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>@endif
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Residuo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Modalità Pagamento</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">Stato</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($payments as $payment)
                    @php
                        $invoice = $payment->payable;
                        $isOverdue = $payment->due_date && $payment->due_date->isPast() && $payment->status !== 'paid';
                        $rowClass = $isOverdue ? 'bg-red-50' : '';
                        $residual = $payment->residual_amount > 0 ? $payment->residual_amount : $payment->amount;
                    @endphp
                    <tr class="hover:bg-gray-50 {{ $rowClass }}" wire:key="payment-{{ $payment->id }}">
                        <td class="px-4 py-3 text-sm">{{ $invoice->ownership->RagAbbrev ?? $invoice->ownership_name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm">{{ $invoice->entity->ragione_sociale ?? $invoice->supplier_name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap {{ $isOverdue ? 'text-red-600 font-bold' : '' }}">
                            {{ $payment->due_date ? $payment->due_date->format('d/m/Y') : '-' }}
                            @if($isOverdue)
                                <i class="fas fa-exclamation-triangle text-red-500 ml-1" title="Scaduto!"></i>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">{{ $invoice->n_invoice ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-right font-medium">{{ number_format($payment->amount, 2, ',', '.') }} €</td>
                        <td class="px-4 py-3 text-sm text-right font-medium text-orange-600">{{ number_format($residual, 2, ',', '.') }} €</td>
                        <td class="px-4 py-3 text-sm">{{ $payment->payment_method_label ?? $payment->payment_method ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            @php
                                $statusConfig = $statuses[$payment->status] ?? ['label' => $payment->status, 'badge_class' => 'bg-gray-100 text-gray-800'];
                            @endphp
                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium {{ $statusConfig['badge_class'] }}">
                                {{ $statusConfig['label'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button wire:click="showDetails({{ $payment->id }})" class="text-blue-600 hover:text-blue-900" title="Dettagli">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-8 text-gray-500">Nessuna scadenza trovata</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginazione -->
    @if($payments->hasPages())
    <div class="mt-4">
        {{ $payments->links() }}
    </div>
    @endif

    <!-- MODAL DETTAGLI SCADENZA  -->
    @if($showModal && $selectedPayment)
    @php
        $invoiceModal = $selectedPayment->payable;
        $residualModal = $selectedPayment->residual_amount > 0 ? $selectedPayment->residual_amount : $selectedPayment->amount;
        
        // Recupera tutti i pagamenti associati a questa fattura (tramite installment_transactions)
        $paymentHistory = [];
        if ($invoiceModal) {
            $paymentHistory = \App\Models\InstallmentTransaction::whereHas('invoicePayment', function($q) use ($invoiceModal) {
                $q->where('payable_id', $invoiceModal->id)->where('payable_type', App\Models\InvoiceReceived::class);
            })->with(['accountingEntry', 'invoicePayment'])->orderBy('created_at', 'desc')->get();
        }
    @endphp
    <div x-data="{ open: true }" x-show="open" x-on:click.away="open = false; $wire.closeModal()" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="bg-white px-6 pt-5 pb-4 border-b sticky top-0 bg-white rounded-t-lg">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-bold text-gray-900">Dettaglio Scadenza</h3>
                        <button x-on:click="open = false" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>
                
                <div class="px-6 py-4 space-y-4">
                    <!-- Prima riga: Proprietà e Fornitore affiancati -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="text-xs text-gray-500 uppercase font-semibold">PROPRIETÀ</label>
                            <p class="font-medium text-gray-900 mt-1">{{ $invoiceModal->ownership->RagAbbrev ?? $invoiceModal->ownership_name ?? '-' }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="text-xs text-gray-500 uppercase font-semibold">FORNITORE</label>
                            <p class="font-medium text-gray-900 mt-1">{{ $invoiceModal->entity->ragione_sociale ?? $invoiceModal->supplier_name ?? '-' }}</p>
                        </div>
                    </div>
                    
                    <!-- Seconda riga: Data Scadenza e N. Fattura affiancati -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="text-xs text-gray-500 uppercase font-semibold">DATA SCADENZA</label>
                            <p class="font-medium text-gray-900 mt-1">{{ $selectedPayment->due_date ? $selectedPayment->due_date->format('d/m/Y') : '-' }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="text-xs text-gray-500 uppercase font-semibold">N. FATTURA</label>
                            <p class="font-medium text-gray-900 mt-1">{{ $invoiceModal->n_invoice ?? '-' }}</p>
                        </div>
                    </div>
                    
                    <!-- Terza riga: Importo e Residuo affiancati -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="text-xs text-gray-500 uppercase font-semibold">IMPORTO</label>
                            <p class="font-bold text-lg text-lime-600 mt-1">{{ number_format($selectedPayment->amount, 2, ',', '.') }} €</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="text-xs text-gray-500 uppercase font-semibold">RESIDUO</label>
                            <p class="font-bold text-lg text-orange-600 mt-1">{{ number_format($residualModal, 2, ',', '.') }} €</p>
                        </div>
                    </div>
                    
                    <!-- Quarta riga: Modalità Pagamento e Stato affiancati -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="text-xs text-gray-500 uppercase font-semibold">MODALITÀ PAGAMENTO</label>
                            <p class="font-medium text-gray-900 mt-1">{{ $selectedPayment->payment_method_label ?? $selectedPayment->payment_method ?? 'Non specificato' }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="text-xs text-gray-500 uppercase font-semibold">STATO</label>
                            @php
                                $statusConfigModal = $statuses[$selectedPayment->status] ?? ['label' => $selectedPayment->status, 'badge_class' => 'bg-gray-100'];
                            @endphp
                            <p class="mt-1"><span class="inline-flex px-2 py-1 rounded-full text-xs font-medium {{ $statusConfigModal['badge_class'] }}">{{ $statusConfigModal['label'] }}</span></p>
                        </div>
                    </div>
                    
                    <!-- CRONOLOGIA PAGAMENTI EFFETTUATI -->
                    @if($paymentHistory->count() > 0)
                    <div class="mt-4">
                        <label class="text-xs text-gray-500 uppercase font-semibold mb-2 block">CRONOLOGIA PAGAMENTI</label>
                        <div class="border rounded-lg overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium">Data Pagamento</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium">Importo</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium">Metodo</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium">Operatore</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($paymentHistory as $transaction)
                                    @php
                                        $accountingEntry = $transaction->accountingEntry;
                                        $payment = $transaction->invoicePayment;
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-2 text-sm">{{ $accountingEntry ? $accountingEntry->entry_date->format('d/m/Y') : '-' }}</td>
                                        <td class="px-3 py-2 text-sm text-right font-medium text-green-600">{{ number_format($transaction->allocated_amount, 2, ',', '.') }} €</td>
                                        <td class="px-3 py-2 text-sm">{{ $payment->payment_method ?? '-' }}</td>
                                        <td class="px-3 py-2 text-sm">
                                            @if($accountingEntry && $accountingEntry->created_by)
                                                {{\App\Models\Administrator::find($accountingEntry->created_by)->name ?? 'Sistema'}}
                                            @else
                                                Sistema
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-green-50">
                                    <tr>
                                        <td class="px-3 py-2 font-bold text-sm">TOTALE PAGATO</td>
                                        <td class="px-3 py-2 text-right font-bold text-green-600">{{ number_format($selectedPayment->paid_amount, 2, ',', '.') }} €</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    @elseif($selectedPayment->paid_amount > 0)
                    <div class="bg-green-50 p-3 rounded-lg">
                        <label class="text-xs text-gray-500 uppercase font-semibold">PAGAMENTI EFFETTUATI</label>
                        <p class="font-medium text-green-600 mt-1">{{ number_format($selectedPayment->paid_amount, 2, ',', '.') }} €</p>
                        @if($selectedPayment->paid_at)
                        <p class="text-xs text-gray-500">Data pagamento: {{ $selectedPayment->paid_at->format('d/m/Y') }}</p>
                        @endif
                    </div>
                    @endif
                    
                    <!-- RIFERIMENTI OPERAZIONE (creazione/modifica) -->
                    <div class="bg-gray-50 p-3 rounded-lg mt-2">
                        <label class="text-xs text-gray-500 uppercase font-semibold">RIFERIMENTI OPERAZIONE</label>
                        <div class="mt-2 grid grid-cols-2 gap-3 text-xs">
                            <div>
                                <p class="text-gray-600">
                                    <i class="fas fa-plus-circle text-green-500 mr-1"></i>
                                    Creata il: {{ $selectedPayment->created_at ? $selectedPayment->created_at->format('d/m/Y H:i:s') : '-' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-600">
                                    <i class="fas fa-edit text-blue-500 mr-1"></i>
                                    Ultima modifica: {{ $selectedPayment->updated_at ? $selectedPayment->updated_at->format('d/m/Y H:i:s') : '-' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-6 py-3 rounded-b-lg flex justify-end">
                    <button x-on:click="open = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-md transition-colors">
                        Chiudi
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
</div>