<?php
use App\Models\InstallmentTransaction;
?>

<div>
    <!-- Header con titolo -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">
            <i class="fas fa-calendar-alt mr-3 text-lime-600"></i>
            Scadenze Vendite
        </h1>

        <!-- Componente per il nuovo pagamento -->
        @livewire('admin.register-payment', ['invoiceType' => 'vendita'])
    </div>

    <!-- Card filtri -->
    <div class="bg-white rounded-lg shadow p-4 mb-4 border border-gray-200">
        <!-- Date Range -->
        @livewire('components.date-range-filter', ['dateFrom' => $dateFrom, 'dateTo' => $dateTo])

        <div class="border-t border-gray-200 my-4"></div>
        
        <!-- Filtri -->
        <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3">
            <!-- Autocomplete Proprietà -->
            <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                <label class="block text-xs font-medium text-gray-500 mb-1">Proprietà</label>
                <div class="relative">
                    <i class="fas fa-building absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                    <input type="text"
                        id="ownership_input"
                        wire:model.live.debounce.300ms="ownershipSearch"
                        x-on:focus="open = true"
                        x-on:input="open = true"
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

                <div x-show="open && $wire.showOwnershipDropdown"
                    x-cloak
                    class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                    @if($ownershipResults && $ownershipResults->count() > 0)
                        @foreach($ownershipResults as $item)
                            <div
                                x-on:click="
                                    open = false;
                                    $wire.selectOwnership({{ $item->id }}, '{{ addslashes($item->name) }}');
                                "
                                class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                                <div class="font-medium text-gray-800">{{ $item->name }}</div>
                                @if($item->ragione_sociale ?? false)
                                    <div class="text-xs text-gray-500">{{ $item->ragione_sociale }}</div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="px-3 py-2 text-sm text-gray-500 text-center">Nessun risultato trovato</div>
                    @endif
                </div>
            </div>

            <!-- Autocomplete Cliente -->
            <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                <label class="block text-xs font-medium text-gray-500 mb-1">Cliente</label>
                <div class="relative">
                    <i class="fas fa-user absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                    <input type="text"
                        id="client_input"
                        wire:model.live.debounce.300ms="clientSearch"
                        x-on:focus="open = true"
                        x-on:input="open = true"
                        placeholder="Cerca cliente..."
                        class="w-full pl-9 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                        autocomplete="off">
                    @if($selectedClientId)
                        <button type="button"
                            wire:click="clearClient"
                            class="absolute right-2 top-2 text-gray-400 hover:text-red-500">
                            <i class="fas fa-times-circle text-sm"></i>
                        </button>
                    @endif
                </div>

                <div x-show="open && $wire.showClientDropdown"
                    x-cloak
                    class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                    @if($clientResults && $clientResults->count() > 0)
                        @foreach($clientResults as $item)
                            <div
                                x-on:click="
                                    open = false;
                                    $wire.selectClient({{ $item->id }}, '{{ addslashes($item->name) }}');
                                "
                                class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                                <div class="font-medium text-gray-800">{{ $item->name }}</div>
                                @if($item->piva ?? false)
                                    <div class="text-xs text-gray-500">P.IVA: {{ $item->piva }}</div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="px-3 py-2 text-sm text-gray-500 text-center">Nessun risultato trovato</div>
                    @endif
                </div>
            </div>

            <!-- N. Fattura -->
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">N. Fattura</label>
                <div class="relative">
                    <i class="fas fa-file-invoice absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                    <input type="text" wire:model.live.debounce.300ms="invoiceNumberFilter"
                        placeholder="Cerca n. fattura..."
                        class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                    @if($invoiceNumberFilter)
                        <button wire:click="$set('invoiceNumberFilter', '')" class="absolute right-2 top-2 text-gray-400 hover:text-red-500">
                            <i class="fas fa-times-circle text-sm"></i>
                        </button>
                    @endif
                </div>
            </div>
            
            <!-- Stato -->
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Stato Pagamento</label>
                <div class="relative">
                    <i class="fas fa-tag absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                    <select wire:model.live="statusFilter" class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-md">
                        <option value="">Tutti gli stati</option>
                        @foreach($statuses as $value => $statusData)
                            @if($value !== 'paid')
                                <option value="{{ $value }}">{{ $statusData['label'] }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>
            
            <!-- Per pagina -->
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Per pagina</label>
                <select wire:model.live="perPage" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md">
                    <option value="100000">Tutti</option>
                    <option value="200">200</option>
                    <option value="100">100</option>
                    <option value="50">50</option>
                    <option value="25">25</option>
                </select>
            </div>
        </div>
        
        <!-- Active Filters Tags -->
        @if($selectedOwnershipId || $selectedClientId || $statusFilter || $dateFrom || $dateTo || $invoiceNumberFilter)
        <div class="mt-4 pt-3 border-t border-gray-200">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs text-gray-500">Filtri attivi:</span>
                @if($selectedOwnershipId)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    <i class="fas fa-building mr-1"></i> {{ $selectedOwnershipName }}
                    <button wire:click="clearOwnership" class="ml-1 hover:text-lime-900"><i class="fas fa-times"></i></button>
                </span>
                @endif
                @if($selectedClientId)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    <i class="fas fa-user mr-1"></i> {{ $selectedClientName }}
                    <button wire:click="clearClient" class="ml-1 hover:text-lime-900"><i class="fas fa-times"></i></button>
                </span>
                @endif
                @if($statusFilter)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    <i class="fas fa-tag mr-1"></i> {{ $statuses[$statusFilter]['label'] ?? $statusFilter }}
                    <button wire:click="clearStatus" class="ml-1 hover:text-lime-900"><i class="fas fa-times"></i></button>
                </span>
                @endif
                @if($dateFrom || $dateTo)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    <i class="fas fa-calendar mr-1"></i> {{ $dateFrom ?: '...' }} → {{ $dateTo ?: '...' }}
                    <button wire:click="$set('dateFrom', ''); $set('dateTo', '')" class="ml-1 hover:text-lime-900"><i class="fas fa-times"></i></button>
                </span>
                @endif
                @if($invoiceNumberFilter)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    <i class="fas fa-file-invoice mr-1"></i> Fattura: {{ $invoiceNumberFilter }}
                    <button wire:click="$set('invoiceNumberFilter', '')" class="ml-1 hover:text-lime-900"><i class="fas fa-times"></i></button>
                </span>
                @endif
                <span class="text-xs text-gray-400 ml-2">
                    <button wire:click="resetFilters" class="hover:text-red-500"><i class="fas fa-trash-alt mr-1"></i> Rimuovi tutti</button>
                </span>
            </div>
        </div>
        @endif
    </div>

    <!-- Tabella Scadenze Vendite -->
    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 cursor-pointer hover:bg-gray-100" wire:click="sortBy('ownership_name')">
                            Proprietà
                            @if($sortField === 'ownership_name')<i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>@endif
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 cursor-pointer hover:bg-gray-100" wire:click="sortBy('client_name')">
                            Cliente
                            @if($sortField === 'client_name')<i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>@endif
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 cursor-pointer hover:bg-gray-100" wire:click="sortBy('due_date')">
                            Data Scadenza
                            @if($sortField === 'due_date')<i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>@endif
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 cursor-pointer hover:bg-gray-100" wire:click="sortBy('invoice_number')">
                            N. Fattura
                            @if($sortField === 'invoice_number')<i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>@endif
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 cursor-pointer hover:bg-gray-100" wire:click="sortBy('amount')">
                            Importo
                            @if($sortField === 'amount')<i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>@endif
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 cursor-pointer hover:bg-gray-100" wire:click="sortBy('residual_amount')">
                            Residuo
                            @if($sortField === 'residual_amount')<i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>@endif
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
                        $isCreditNote = $invoice && method_exists($invoice, 'isCreditNote') && $invoice->isCreditNote();
                        $isOverdue = $payment->due_date && $payment->due_date->isPast() && $payment->residual_amount > 0 && $payment->status !== 'closed_credit_note';
                        $rowClass = $isOverdue ? 'bg-red-50' : ($isCreditNote ? 'bg-purple-50' : '');
                        $residual = $payment->residual_amount > 0 ? $payment->residual_amount : ($payment->amount - $payment->paid_amount);
                        $displayAmount = $isCreditNote ? -$payment->amount : $payment->amount;
                        $displayResidual = $isCreditNote ? -max(0, $residual) : max(0, $residual);
                    @endphp
                    <tr class="hover:bg-gray-50 {{ $rowClass }}" wire:key="payment-{{ $payment->id }}">
                        <td class="px-4 py-3 text-sm">{{ $invoice->ownership->RagAbbrev ?? $invoice->ownership->Rag_Soc_intest ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm">{{ $invoice->entity->ragione_sociale ?? ($invoice->entity->nome . ' ' . ($invoice->entity->cognome ?? '')) ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap {{ $isOverdue ? 'text-red-600 font-bold' : '' }}">
                            {{ $payment->due_date ? $payment->due_date->format('d/m/Y') : '-' }}
                            @if($isOverdue)
                                <i class="fas fa-exclamation-triangle text-red-500 ml-1" title="Scaduto!"></i>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            {{ $invoice->n_invoice ?? '-' }}
                            @if($isCreditNote)
                                <span class="ml-1 inline-flex px-1.5 py-0.5 rounded text-xs font-semibold bg-purple-100 text-purple-800">NC</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-medium {{ $isCreditNote ? 'text-purple-700' : '' }}">
                            {{ number_format($displayAmount, 2, ',', '.') }} €
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-medium {{ $isCreditNote ? 'text-purple-700' : 'text-orange-600' }}">
                            {{ number_format($displayResidual, 2, ',', '.') }} €
                        </td>
                        <td class="px-4 py-3 text-sm">{{ $payment->payment_method ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            @php
                                $currentStatus = $payment->status;
                                if ($currentStatus !== 'closed_credit_note') {
                                    if ($residual <= 0.01) {
                                        $currentStatus = 'paid';
                                    } elseif ($payment->paid_amount > 0) {
                                        $currentStatus = 'partially_paid';
                                    }
                                }
                                $statusConfig = $statuses[$currentStatus] ?? ['label' => $currentStatus, 'badge_class' => 'bg-gray-100 text-gray-800'];
                            @endphp
                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium {{ $statusConfig['badge_class'] }}">
                                {{ $statusConfig['label'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button wire:click="showDetails({{ $payment->id }})" class="text-blue-600 hover:text-blue-900" title="Dettagli">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                            @if(!$isCreditNote && $residual > 0.01 && $invoice instanceof \App\Models\InvoiceSent)
                                <button wire:click="openCloseModal({{ $invoice->id }})" class="text-purple-600 hover:text-purple-900 ml-2" title="Chiudi con nota di credito">
                                    <i class="fas fa-link"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-8 text-gray-500">
                            <i class="fas fa-calendar-check text-4xl mb-2 text-gray-300 block"></i>
                            Nessuna scadenza trovata
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginazione -->
    @if($payments->hasPages())
    <div class="mt-4 flex flex-col items-center gap-3">
        <div class="flex items-center gap-1">
            {{-- Freccia Precedente --}}
            @if ($payments->onFirstPage())
                <span class="px-3 py-2 rounded-md text-sm text-gray-300 cursor-not-allowed border border-gray-200 bg-white">
                    <i class="fas fa-chevron-left"></i>
                </span>
            @else
                <button type="button" wire:click="previousPage" wire:loading.attr="disabled"
                        class="px-3 py-2 rounded-md text-sm text-gray-600 bg-white border border-gray-300 hover:bg-gray-50 transition-colors">
                    <i class="fas fa-chevron-left"></i>
                </button>
            @endif

            {{-- Numeri di pagina --}}
            @php
                $current = $payments->currentPage();
                $last = $payments->lastPage();
                $onEachSide = 2;
                $start = max($current - $onEachSide, 1);
                $end = min($current + $onEachSide, $last);
            @endphp

            @if ($start > 1)
                <button type="button" wire:click="gotoPage(1)" class="px-3 py-2 rounded-md text-sm text-gray-600 bg-white border border-gray-300 hover:bg-gray-50 transition-colors">1</button>
                @if ($start > 2)
                    <span class="px-3 py-2 text-sm text-gray-400">...</span>
                @endif
            @endif

            @for ($page = $start; $page <= $end; $page++)
                @if ($page == $current)
                    <span class="px-3 py-2 rounded-md text-sm font-semibold text-white bg-lime-600 border border-lime-600">{{ $page }}</span>
                @else
                    <button type="button" wire:click="gotoPage({{ $page }})" class="px-3 py-2 rounded-md text-sm text-gray-600 bg-white border border-gray-300 hover:bg-gray-50 transition-colors">{{ $page }}</button>
                @endif
            @endfor

            @if ($end < $last)
                @if ($end < $last - 1)
                    <span class="px-3 py-2 text-sm text-gray-400">...</span>
                @endif
                <button type="button" wire:click="gotoPage({{ $last }})" class="px-3 py-2 rounded-md text-sm text-gray-600 bg-white border border-gray-300 hover:bg-gray-50 transition-colors">{{ $last }}</button>
            @endif

            {{-- Freccia Successiva --}}
            @if ($payments->hasMorePages())
                <button type="button" wire:click="nextPage" wire:loading.attr="disabled"
                        class="px-3 py-2 rounded-md text-sm text-gray-600 bg-white border border-gray-300 hover:bg-gray-50 transition-colors">
                    <i class="fas fa-chevron-right"></i>
                </button>
            @else
                <span class="px-3 py-2 rounded-md text-sm text-gray-300 cursor-not-allowed border border-gray-200 bg-white">
                    <i class="fas fa-chevron-right"></i>
                </span>
            @endif
        </div>

        <p class="text-sm text-gray-500">
            Da <span class="font-medium text-gray-700">{{ $payments->firstItem() }}</span>
            a <span class="font-medium text-gray-700">{{ $payments->lastItem() }}</span>
            di <span class="font-medium text-gray-700">{{ $payments->total() }}</span> risultati
        </p>
    </div>
    @endif

    <!-- MODAL CHIUSURA FATTURA CON NOTA DI CREDITO -->
    @if($closingInvoiceId)
    <div x-data="{}" x-on:click.self="$wire.closeCloseModal()" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500 bg-opacity-75">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-3">
                <h3 class="font-bold text-gray-900">Chiudi fattura con nota di credito</h3>
                <button wire:click="closeCloseModal" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <p class="text-xs text-gray-500 mb-3">
                Seleziona la nota di credito da collegare. Entrambe le scadenze verranno chiuse come saldate.
            </p>
            <input type="text"
                wire:model.live.debounce.300ms="closeInvoiceSearch"
                placeholder="Cerca n. nota di credito..."
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                autocomplete="off">
            <div class="mt-2 max-h-56 overflow-y-auto border rounded-md">
                @forelse($creditNoteResults as $cn)
                    <div wire:click="closeInvoiceWithCreditNote({{ $cn->id }})"
                         class="px-3 py-2 hover:bg-purple-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                        <div class="font-medium text-gray-800">NC {{ $cn->n_invoice }}</div>
                        <div class="text-xs text-gray-500">{{ number_format($cn->importo_totale, 2, ',', '.') }} €</div>
                    </div>
                @empty
                    <div class="px-3 py-2 text-sm text-gray-400 text-center">
                        @if(strlen($closeInvoiceSearch) >= 2)
                            Nessuna nota di credito trovata
                        @else
                            Digita almeno 2 caratteri
                        @endif
                    </div>
                @endforelse
            </div>
            <div class="mt-4 flex justify-end">
                <button wire:click="closeCloseModal" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-md text-sm transition-colors">
                    Annulla
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL DETTAGLI SCADENZA VENDITA -->
    @if($showModal && $selectedPayment)
    @php
        $invoiceModal = $selectedPayment->payable;
        $isCreditNoteModal = $invoiceModal && method_exists($invoiceModal, 'isCreditNote') && $invoiceModal->isCreditNote();
        $residualModal = $selectedPayment->residual_amount > 0 ? $selectedPayment->residual_amount : ($selectedPayment->amount - $selectedPayment->paid_amount);
        
        // Recupera tutti i pagamenti associati a questa scadenza
        $paymentHistory = InstallmentTransaction::whereHas('invoicePayment', function($q) use ($invoiceModal) {
            $q->where('payable_id', $invoiceModal->id)->where('payable_type', \App\Models\InvoiceSent::class);
        })->with(['accountingEntry', 'invoicePayment'])->orderBy('created_at', 'desc')->get();
        
        if (!($paymentHistory instanceof \Illuminate\Support\Collection)) {
            $paymentHistory = collect($paymentHistory);
        }
    @endphp
    <div x-data="{}" x-show="$wire.showModal" x-on:click.away="$wire.closeModal()" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="bg-white px-6 pt-5 pb-4 border-b sticky top-0 bg-white rounded-t-lg">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-bold text-gray-900">Dettaglio Scadenza Incasso</h3>
                        <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>
                
                <div class="px-6 py-4 space-y-4">
                    <!-- Prima riga: Proprietà e Cliente -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="text-xs text-gray-500 uppercase font-semibold">PROPRIETÀ</label>
                            <p class="font-medium text-gray-900 mt-1">{{ $invoiceModal->ownership->RagAbbrev ?? $invoiceModal->ownership->Rag_Soc_intest ?? '-' }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="text-xs text-gray-500 uppercase font-semibold">CLIENTE</label>
                            <p class="font-medium text-gray-900 mt-1">{{ $invoiceModal->entity->ragione_sociale ?? ($invoiceModal->entity->nome . ' ' . ($invoiceModal->entity->cognome ?? '')) ?? '-' }}</p>
                        </div>
                    </div>
                    
                    <!-- Seconda riga: Data Scadenza e N. Fattura -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="text-xs text-gray-500 uppercase font-semibold">DATA SCADENZA</label>
                            <p class="font-medium text-gray-900 mt-1">{{ $selectedPayment->due_date ? $selectedPayment->due_date->format('d/m/Y') : '-' }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="text-xs text-gray-500 uppercase font-semibold">N. FATTURA</label>
                            <p class="font-medium text-gray-900 mt-1">
                                {{ $invoiceModal->n_invoice ?? '-' }}
                                @if($isCreditNoteModal)
                                    <span class="ml-1 inline-flex px-1.5 py-0.5 rounded text-xs font-semibold bg-purple-100 text-purple-800">NC</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    
                    <!-- Terza riga: Importo e Residuo -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="text-xs text-gray-500 uppercase font-semibold">IMPORTO</label>
                            <p class="font-bold text-lg text-lime-600 mt-1">{{ number_format($selectedPayment->amount, 2, ',', '.') }} €</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="text-xs text-gray-500 uppercase font-semibold">RESIDUO</label>
                            <p class="font-bold text-lg text-orange-600 mt-1">{{ number_format(max(0, $residualModal), 2, ',', '.') }} €</p>
                        </div>
                    </div>
                    
                    <!-- Quarta riga: Modalità Pagamento e Stato -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="text-xs text-gray-500 uppercase font-semibold">MODALITÀ PAGAMENTO</label>
                            <p class="font-medium text-gray-900 mt-1">{{ $selectedPayment->payment_method ?? 'Non specificato' }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="text-xs text-gray-500 uppercase font-semibold">STATO</label>
                            @php
                                $currentStatusModal = $selectedPayment->status;
                                if ($currentStatusModal !== 'closed_credit_note') {
                                    if ($residualModal <= 0.01) {
                                        $currentStatusModal = 'paid';
                                    } elseif ($selectedPayment->paid_amount > 0) {
                                        $currentStatusModal = 'partially_paid';
                                    }
                                }
                                $statusConfigModal = $statuses[$currentStatusModal] ?? ['label' => $currentStatusModal, 'badge_class' => 'bg-gray-100'];
                            @endphp
                            <p class="mt-1"><span class="inline-flex px-2 py-1 rounded-full text-xs font-medium {{ $statusConfigModal['badge_class'] }}">{{ $statusConfigModal['label'] }}</span></p>
                        </div>
                    </div>

                    <!-- FATTURA CHIUSA DA NOTA DI CREDITO / NOTA DI CREDITO CHE CHIUDE -->
                    @if($invoiceModal && method_exists($invoiceModal, 'closingCreditNote') && $invoiceModal->closingCreditNote)
                        <div class="bg-purple-50 p-3 rounded-lg border border-purple-200">
                            <label class="text-xs text-purple-500 uppercase font-semibold">CHIUSA DA NOTA DI CREDITO</label>
                            <p class="font-medium text-purple-800 mt-1">NC {{ $invoiceModal->closingCreditNote->n_invoice }}</p>
                        </div>
                    @endif
                    @if($invoiceModal && isset($invoiceModal->closes_invoice_id) && $invoiceModal->closes_invoice_id && method_exists($invoiceModal, 'closedInvoice') && $invoiceModal->closedInvoice)
                        <div class="bg-purple-50 p-3 rounded-lg border border-purple-200">
                            <label class="text-xs text-purple-500 uppercase font-semibold">CHIUDE LA FATTURA</label>
                            <p class="font-medium text-purple-800 mt-1">{{ $invoiceModal->closedInvoice->n_invoice }}</p>
                        </div>
                    @endif
                    
                    <!-- CRONOLOGIA PAGAMENTI EFFETTUATI -->
                    @if($paymentHistory->count() > 0)
                        <div class="mt-4">
                            <label class="text-xs text-gray-500 uppercase font-semibold mb-2 block">CRONOLOGIA PAGAMENTI EFFETTUATI</label>
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
                                            $paymentTx = $transaction->invoicePayment;
                                        @endphp
                                        <tr>
                                            <td class="px-3 py-2 text-sm">{{ $accountingEntry ? $accountingEntry->entry_date->format('d/m/Y') : '-' }}</td>
                                            <td class="px-3 py-2 text-sm text-right font-medium text-green-600">{{ number_format($transaction->allocated_amount, 2, ',', '.') }} €</td>
                                            <td class="px-3 py-2 text-sm">{{ $paymentTx->payment_method ?? '-' }}</td>
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
                </div>
                
                <div class="bg-gray-50 px-6 py-3 rounded-b-lg flex justify-end">
                    <button wire:click="closeModal" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-md transition-colors">
                        Chiudi
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>