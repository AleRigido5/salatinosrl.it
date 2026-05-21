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

    <!-- Tabella Scadenze -->
    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 cursor-pointer hover:bg-gray-100" wire:click="sortBy('due_date')">
                            Data Scadenza
                            @if($sortField === 'due_date')<i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>@endif
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">N. Fattura</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Fornitore</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Proprietà</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 cursor-pointer hover:bg-gray-100" wire:click="sortBy('amount')">
                            Importo
                            @if($sortField === 'amount')<i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>@endif
                        </th>
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
                    @endphp
                    <tr class="hover:bg-gray-50 {{ $rowClass }}" wire:key="payment-{{ $payment->id }}">
                        <td class="px-4 py-3 text-sm whitespace-nowrap {{ $isOverdue ? 'text-red-600 font-bold' : '' }}">
                            {{ $payment->due_date ? $payment->due_date->format('d/m/Y') : '-' }}
                            @if($isOverdue)
                                <i class="fas fa-exclamation-triangle text-red-500 ml-1" title="Scaduto!"></i>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if($invoice)
                            <p>
                                {{ $invoice->n_invoice ?? '-' }}
                            </p>
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">{{ $invoice->entity->ragione_sociale ?? $invoice->supplier_name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm">{{ $invoice->ownership->RagAbbrev ?? $invoice->ownership_name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-right font-medium">{{ number_format($payment->amount, 2, ',', '.') }} €</td>
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
                        <td colspan="8" class="text-center py-8 text-gray-500">Nessuna scadenza trovata</td>
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

    <!-- MODAL DETTAGLI SCADENZA -->
    @if($showModal && $selectedPayment)
    @php
        $invoiceModal = $selectedPayment->payable;
    @endphp
    <div x-data="{ open: true }" x-show="open" x-on:click.away="open = false; $wire.closeModal()" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Dettaglio Scadenza</h3>
                    <button x-on:click="open = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="space-y-3">
                    <div class="border-b pb-2">
                        <label class="text-xs text-gray-500 uppercase font-semibold">Fattura</label>
                        <p class="font-medium text-gray-900 mt-1">{{ $invoiceModal->n_invoice ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 uppercase font-semibold">Fornitore</label>
                        <p class="text-gray-800">{{ $invoiceModal->entity->ragione_sociale ?? $invoiceModal->supplier_name ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 uppercase font-semibold">Proprietà</label>
                        <p class="text-gray-800">{{ $invoiceModal->ownership->RagAbbrev ?? $invoiceModal->ownership_name ?? '-' }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs text-gray-500 uppercase font-semibold">Data Scadenza</label>
                            <p class="font-medium">{{ $selectedPayment->due_date ? $selectedPayment->due_date->format('d/m/Y') : '-' }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 uppercase font-semibold">Importo</label>
                            <p class="font-bold text-lg text-green-600">{{ number_format($selectedPayment->amount, 2, ',', '.') }} €</p>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 uppercase font-semibold">Modalità Pagamento</label>
                        <p>{{ $selectedPayment->payment_method_label ?? $selectedPayment->payment_method ?? '-' }}</p>
                    </div>
                    @if($selectedPayment->iban)
                    <div>
                        <label class="text-xs text-gray-500 uppercase font-semibold">IBAN</label>
                        <p class="font-mono text-sm">{{ $selectedPayment->iban }}</p>
                    </div>
                    @endif
                    <div>
                        <label class="text-xs text-gray-500 uppercase font-semibold">Stato</label>
                        @php
                            $statusConfigModal = $statuses[$selectedPayment->status] ?? ['label' => $selectedPayment->status, 'badge_class' => 'bg-gray-100'];
                        @endphp
                        <p><span class="inline-flex px-2 py-1 rounded-full text-xs font-medium {{ $statusConfigModal['badge_class'] }}">{{ $statusConfigModal['label'] }}</span></p>
                    </div>
                    @if($selectedPayment->paid_at)
                    <div>
                        <label class="text-xs text-gray-500 uppercase font-semibold">Data Pagamento</label>
                        <p>{{ $selectedPayment->paid_at->format('d/m/Y') }}</p>
                    </div>
                    @endif
                </div>
                <div class="mt-6 flex justify-end">
                    <button x-on:click="open = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-md transition-colors">
                        Chiudi
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>