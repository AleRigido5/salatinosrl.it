<div>
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">
            <i class="fa-solid fa-book mr-3 text-lime-600"></i>
            Prima Nota - Scritture Contabili
        </h1>
        @if($canCreate)
        <button wire:click="openCreateModal" 
            class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-5 py-2.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
            <i class="fas fa-plus mr-2"></i> Nuova Scrittura
        </button>
        @endif
    </div>

    <!-- Card Filtri -->
    <div class="bg-white rounded-lg shadow mb-4 border border-gray-200">
        
        <!-- RIGA SUPERIORE: Date Range Filter -->
        <div class="p-4 border-b border-gray-200">
            @livewire('components.date-range-filter', ['dateFrom' => $dateFrom, 'dateTo' => $dateTo])
        </div>
        
        <!-- RIGA INFERIORE: Filtri -->
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                <!-- Ricerca -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Ricerca</label>
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                        <input type="text" wire:model.live.debounce.300ms="search" 
                            placeholder="Cerca in descrizione, conti..." 
                            class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                    </div>
                </div>

                <!-- Tipo -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Tipo</label>
                    <select wire:model.live="type" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md">
                        <option value="">Tutti</option>
                        <option value="entrata">Entrata</option>
                        <option value="uscita">Uscita</option>
                    </select>
                </div>

                <!-- Conto Dare -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Conto Dare</label>
                    <select wire:model.live="debitAccountId" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md">
                        <option value="">Tutti</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Conto Avere -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Conto Avere</label>
                    <select wire:model.live="creditAccountId" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md">
                        <option value="">Tutti</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Metodo Pagamento -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Metodo Pag.</label>
                    <select wire:model.live="paymentMethodId" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md">
                        <option value="">Tutti</option>
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method->id }}">{{ $method->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Per Page -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Per pagina</label>
                    <select wire:model.live="perPage" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md">
                        <option value="10000">Tutti</option>
                        <option value="200">200</option>
                        <option value="100">100</option>
                        <option value="50">50</option>
                        <option value="25">25</option>
                    </select>
                </div>
            </div>

            <!-- Active Filters Tags -->
            @if($search || $type || $debitAccountId || $creditAccountId || $paymentMethodId || $dateFrom || $dateTo)
            <div class="mt-4 pt-3 border-t border-gray-200 flex flex-wrap items-center gap-2">
                <span class="text-xs text-gray-500">Filtri attivi:</span>
                
                @if($dateFrom || $dateTo)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    <i class="fas fa-calendar mr-1 text-xs"></i>
                    {{ $dateFrom ?: '...' }} → {{ $dateTo ?: '...' }}
                    <button wire:click="clearDates" class="ml-1 hover:text-lime-900">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </span>
                @endif
                
                @if($search)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    Ricerca: "{{ $search }}"
                    <button wire:click="$set('search', '')" class="ml-1 hover:text-lime-900">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </span>
                @endif
                
                @if($type)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    Tipo: {{ $type === 'entrata' ? 'Entrata' : 'Uscita' }}
                    <button wire:click="$set('type', '')" class="ml-1 hover:text-lime-900">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </span>
                @endif
                
                @if($debitAccountId)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    Conto Dare
                    <button wire:click="$set('debitAccountId', '')" class="ml-1 hover:text-lime-900">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </span>
                @endif
                
                @if($creditAccountId)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    Conto Avere
                    <button wire:click="$set('creditAccountId', '')" class="ml-1 hover:text-lime-900">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </span>
                @endif
                
                @if($paymentMethodId)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    Metodo Pag.
                    <button wire:click="$set('paymentMethodId', '')" class="ml-1 hover:text-lime-900">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </span>
                @endif
                
                <button wire:click="resetFilters" class="text-xs text-red-500 hover:text-red-700">
                    <i class="fas fa-trash-alt mr-1"></i> Rimuovi tutti
                </button>
            </div>
            @endif
        </div>
    </div>

    <!-- Card Totali -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <div class="bg-green-50 rounded-lg p-4 border border-green-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-green-600 uppercase font-semibold">Totale Entrate</p>
                    <p class="text-2xl font-bold text-green-700">{{ number_format($totalEntrate, 2, ',', '.') }} €</p>
                </div>
                <i class="fas fa-arrow-down text-green-500 text-3xl"></i>
            </div>
        </div>
        <div class="bg-red-50 rounded-lg p-4 border border-red-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-red-600 uppercase font-semibold">Totale Uscite</p>
                    <p class="text-2xl font-bold text-red-700">{{ number_format($totalUscite, 2, ',', '.') }} €</p>
                </div>
                <i class="fas fa-arrow-up text-red-500 text-3xl"></i>
            </div>
        </div>
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-blue-600 uppercase font-semibold">Saldo</p>
                    <p class="text-2xl font-bold {{ $saldo >= 0 ? 'text-blue-700' : 'text-red-700' }}">
                        {{ number_format($saldo, 2, ',', '.') }} €
                    </p>
                </div>
                <i class="fas fa-balance-scale text-blue-500 text-3xl"></i>
            </div>
        </div>
    </div>

    <!-- Tabella -->
    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 cursor-pointer" wire:click="sortBy('entry_date')">
                            Data @if($sortField === 'entry_date')<i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>@endif
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Descrizione</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Conto Dare</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Conto Avere</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 cursor-pointer" wire:click="sortBy('amount')">
                            Importo @if($sortField === 'amount')<i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>@endif
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Metodo Pag.</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($entries as $entry)
                    <tr class="hover:bg-gray-50" wire:key="entry-{{ $entry->id }}">
                        <td class="px-4 py-3 text-sm whitespace-nowrap">{{ $entry->entry_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm max-w-xs truncate" title="{{ $entry->description }}">{{ Str::limit($entry->description, 50) }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $entry->type_badge_class }}">
                                {{ $entry->type_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm">{{ $entry->debitAccount->code ?? '' }} - {{ $entry->debitAccount->name ?? '' }}</td>
                        <td class="px-4 py-3 text-sm">{{ $entry->creditAccount->code ?? '' }} - {{ $entry->creditAccount->name ?? '' }}</td>
                        <td class="px-4 py-3 text-sm text-right font-medium">{{ number_format($entry->amount, 2, ',', '.') }} €</td>
                        <td class="px-4 py-3 text-sm">{{ $entry->paymentMethod->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center space-x-2">
                                @if($canView)
                                <button wire:click="viewEntry({{ $entry->id }})" class="text-blue-600 hover:text-blue-900" title="Dettagli">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                                @endif
                                @if($canEdit)
                                <button wire:click="openEditModal({{ $entry->id }})" class="text-yellow-600 hover:text-yellow-900" title="Modifica">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                @endif
                                @if($canDelete)
                                <button wire:click="confirmDelete({{ $entry->id }})" class="text-red-600 hover:text-red-900" title="Elimina">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-8 text-gray-500">Nessuna scrittura contabile trovata</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginazione -->
    @if($perPage != 10000 && $entries instanceof \Illuminate\Pagination\AbstractPaginator && $entries->hasPages())
    <div class="mt-4">
        <div class="text-sm text-gray-500 mb-2">
            Mostrando {{ $entries->firstItem() ?? 0 }} - {{ $entries->lastItem() ?? 0 }} di {{ $entries->total() }} risultati
        </div>
        <div class="flex justify-center">
            {{ $entries->links() }}
        </div>
    </div>
    @endif

    <!-- MODAL CREAZIONE/MODIFICA -->
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ open: true }" x-show="open" x-on:keydown.escape.window="open = false; $wire.closeModal()">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" x-on:click="open = false; $wire.closeModal()"></div>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-6 pt-5 pb-4 border-b">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">
                            {{ $isEditing ? 'Modifica Scrittura Contabile' : 'Nuova Scrittura Contabile' }}
                        </h3>
                        <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <form wire:submit.prevent="save">
                    <div class="px-6 py-4 space-y-4">
                        <!-- Data -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data <span class="text-red-500">*</span></label>
                            <input type="date" wire:model="entry_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            @error('entry_date') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Descrizione -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descrizione <span class="text-red-500">*</span></label>
                            <textarea wire:model="description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500" placeholder="Inserisci una descrizione..."></textarea>
                            @error('description') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Tipo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo <span class="text-red-500">*</span></label>
                            <select wire:model="type_value" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                                <option value="">Seleziona tipo</option>
                                <option value="entrata">Entrata</option>
                                <option value="uscita">Uscita</option>
                            </select>
                            @error('type_value') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Conto Dare -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Conto Dare <span class="text-red-500">*</span></label>
                            <select wire:model="debit_account_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                                <option value="">Seleziona conto</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                @endforeach
                            </select>
                            @error('debit_account_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Conto Avere -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Conto Avere <span class="text-red-500">*</span></label>
                            <select wire:model="credit_account_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                                <option value="">Seleziona conto</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                @endforeach
                            </select>
                            @error('credit_account_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Importo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Importo (€) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" wire:model="amount" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500" placeholder="0.00">
                            @error('amount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Metodo Pagamento -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Metodo Pagamento</label>
                            <select wire:model="id_payments_methods" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                                <option value="">Seleziona metodo</option>
                                @foreach($paymentMethods as $method)
                                    <option value="{{ $method->id }}">{{ $method->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 px-6 py-3 flex justify-end gap-3">
                        <button type="button" wire:click="closeModal" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                            Annulla
                        </button>
                        <button type="submit" class="px-4 py-2 bg-lime-500 hover:bg-lime-600 text-white rounded-md transition-colors">
                            <i class="fas fa-save mr-2"></i> {{ $isEditing ? 'Aggiorna' : 'Salva' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL VISUALIZZAZIONE DETTAGLIO -->
    @if($showViewModal && $viewingEntry)
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ open: true }" x-show="open" x-on:keydown.escape.window="open = false; $wire.closeViewModal()">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" x-on:click="open = false; $wire.closeViewModal()"></div>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="bg-white px-6 pt-5 pb-4 border-b">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">Dettaglio Scrittura Contabile</h3>
                        <button type="button" wire:click="closeViewModal" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="px-6 py-4 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="block text-xs text-gray-500 uppercase">Data</label>
                            <p class="font-medium">{{ $viewingEntry->entry_date->format('d/m/Y') }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="block text-xs text-gray-500 uppercase">Tipo</label>
                            <p><span class="px-2 py-1 rounded-full text-xs font-medium {{ $viewingEntry->type_badge_class }}">{{ $viewingEntry->type_label }}</span></p>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <label class="block text-xs text-gray-500 uppercase">Descrizione</label>
                        <p class="whitespace-pre-wrap">{{ $viewingEntry->description }}</p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="block text-xs text-gray-500 uppercase">Conto Dare</label>
                            <p class="font-medium">{{ $viewingEntry->debitAccount->code ?? '' }} - {{ $viewingEntry->debitAccount->name ?? '' }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="block text-xs text-gray-500 uppercase">Conto Avere</label>
                            <p class="font-medium">{{ $viewingEntry->creditAccount->code ?? '' }} - {{ $viewingEntry->creditAccount->name ?? '' }}</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="block text-xs text-gray-500 uppercase">Importo</label>
                            <p class="text-xl font-bold text-lime-600">{{ number_format($viewingEntry->amount, 2, ',', '.') }} €</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="block text-xs text-gray-500 uppercase">Metodo Pagamento</label>
                            <p>{{ $viewingEntry->paymentMethod->name ?? '-' }}</p>
                        </div>
                    </div>
                    
                    @if($viewingEntry->invoice)
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <label class="block text-xs text-gray-500 uppercase">Fattura Associata</label>
                        <p>N. {{ $viewingEntry->invoice->n_invoice }} del {{ $viewingEntry->invoice->data_invoice->format('d/m/Y') }}</p>
                    </div>
                    @endif
                    
                    @if($viewingEntry->invoicePayment)
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <label class="block text-xs text-gray-500 uppercase">Pagamento Associato</label>
                        <p>Scadenza: {{ $viewingEntry->invoicePayment->due_date->format('d/m/Y') }} - Importo: {{ number_format($viewingEntry->invoicePayment->amount, 2, ',', '.') }} €</p>
                    </div>
                    @endif
                </div>
                
                <div class="bg-gray-50 px-6 py-3 flex justify-end gap-3">
                    <button type="button" wire:click="closeViewModal" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                        Chiudi
                    </button>
                    @if($canEdit)
                    <button type="button" wire:click="openEditModal({{ $viewingEntry->id }})" class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-md transition-colors">
                        <i class="fas fa-edit mr-2"></i> Modifica
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL CONFERMA ELIMINAZIONE -->
    @if($showDeleteModal && $entryToDelete)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" x-data="{ show: true }" x-show="show" x-transition.opacity.duration.200ms>
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6" x-on:click.away="show = false; $wire.cancelDelete()" x-transition.scale.origin.top>
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Conferma eliminazione</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Sei sicuro di voler eliminare questa scrittura contabile?<br>
                    <span class="text-xs text-gray-400">L'operazione è irreversibile.</span>
                </p>
                <div class="flex justify-center space-x-3">
                    <button wire:click="cancelDelete" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                        Annulla
                    </button>
                    <button wire:click="deleteEntry" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md transition-colors">
                        Elimina
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <style scoped>
        nav[role="navigation"] div.flex-1 { display: none !important; }
        nav[role="navigation"] .relative.z-0 { justify-content: center !important; display: flex !important; }
        nav[role="navigation"] span[aria-current="page"] span,
        nav[role="navigation"] .relative.inline-flex.items-center {
            background-color: white !important;
            border-color: #e5e7eb !important;
            color: #374151 !important;
        }
        nav[role="navigation"] span[aria-current="page"] span {
            background-color: #84cc16 !important;
            border-color: #84cc16 !important;
            color: white !important;
        }
        nav[role="navigation"] .relative.inline-flex.items-center:hover {
            background-color: #f9fafb !important;
            border-color: #d1d5db !important;
        }
        nav[role="navigation"] p.text-sm { display: none !important; }
        nav[role="navigation"] > div:first-child { justify-content: center !important; }
        nav[role="navigation"] > div:first-child > div:first-child { display: none !important; }
    </style>
</div>