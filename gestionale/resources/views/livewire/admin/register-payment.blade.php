{{-- resources/views/livewire/admin/register-payment.blade.php --}}
<div>
    <!-- Pulsante per aprire il modale -->
    <button type="button" wire:click="openModal" 
        class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-5 py-2.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
        <i class="fas fa-euro-sign mr-2"></i> Nuovo Pagamento
    </button>

    <!-- MODALE -->
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ open: true }" x-init="() => { $watch('open', value => { if (!value) $wire.closeModal() }) }" x-show="open" x-on:keydown.escape.window="open = false; $wire.closeModal()">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-on:click="open = false; $wire.closeModal()"></div>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full">
                <div class="bg-white px-6 pt-5 pb-4 border-b">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">Registra Pagamento</h3>
                        <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Step Indicator -->
                    <div class="flex items-center justify-between mt-4">
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="rounded-full h-8 w-8 flex items-center justify-center {{ $currentStep >= 1 ? 'bg-lime-500 text-white' : 'bg-gray-200 text-gray-600' }}">
                                        1
                                    </div>
                                    <span class="ml-2 text-sm {{ $currentStep >= 1 ? 'text-lime-600 font-medium' : 'text-gray-400' }}">Controparte</span>
                                </div>
                                <div class="flex-1 h-px mx-4 {{ $currentStep >= 2 ? 'bg-lime-500' : 'bg-gray-300' }}"></div>
                                <div class="flex items-center">
                                    <div class="rounded-full h-8 w-8 flex items-center justify-center {{ $currentStep >= 2 ? 'bg-lime-500 text-white' : 'bg-gray-200 text-gray-600' }}">
                                        2
                                    </div>
                                    <span class="ml-2 text-sm {{ $currentStep >= 2 ? 'text-lime-600 font-medium' : 'text-gray-400' }}">Dettagli</span>
                                </div>
                                <div class="flex-1 h-px mx-4 {{ $currentStep >= 3 ? 'bg-lime-500' : 'bg-gray-300' }}"></div>
                                <div class="flex items-center">
                                    <div class="rounded-full h-8 w-8 flex items-center justify-center {{ $currentStep >= 3 ? 'bg-lime-500 text-white' : 'bg-gray-200 text-gray-600' }}">
                                        3
                                    </div>
                                    <span class="ml-2 text-sm {{ $currentStep >= 3 ? 'text-lime-600 font-medium' : 'text-gray-400' }}">Conferma</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                    
                    <!-- STEP 1: Selezione Proprietà e Cliente/Fornitore -->
                    @if($currentStep == 1)
                    <div class="grid grid-cols-2 gap-6">
                        <!-- Proprietà (Autocomplete) -->
                        <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Proprietà <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <i class="fas fa-building absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                                <input type="text" 
                                    id="ownership_input"
                                    value="{{ $ownershipSearch }}"
                                    wire:model.live.debounce.300ms="ownershipSearch"
                                    x-on:focus="open = true"
                                    x-on:input="open = true"
                                    placeholder="Cerca proprietà..."
                                    class="w-full pl-9 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                                    autocomplete="off">
                                @if($selectedOwnershipId)
                                <button type="button" 
                                    wire:click="clearOwnership"
                                    x-on:click="document.getElementById('ownership_input').value = ''; open = false"
                                    class="absolute right-2 top-2 text-gray-400 hover:text-red-500">
                                    <i class="fas fa-times-circle text-sm"></i>
                                </button>
                                @endif
                            </div>

                            @if($showOwnershipDropdown && $ownershipResults && $ownershipResults->count() > 0)
                            <div class="relative w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                @foreach($ownershipResults as $item)
                                <div 
                                    wire:click="selectOwnership({{ $item->id }}, '{{ addslashes($item->name) }}')"
                                    x-on:click="document.getElementById('ownership_input').value = '{{ addslashes($item->name) }}'; open = false"
                                    class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                                    <div class="font-medium text-gray-800">{{ $item->name }}</div>
                                </div>
                                @endforeach
                            </div>
                            @endif
                            
                            @error('selectedOwnershipId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        
                        <!-- Cliente/Fornitore (Autocomplete) -->
                        <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cliente / Fornitore <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <i class="fas fa-user absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                                <input type="text" 
                                    id="entity_input"
                                    value="{{ $entitySearch }}"
                                    wire:model.live.debounce.300ms="entitySearch"
                                    x-on:focus="open = true"
                                    x-on:input="open = true"
                                    placeholder="Cerca per nome, ragione sociale o P.IVA..."
                                    class="w-full pl-9 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                                    autocomplete="off">
                                @if($selectedEntityId)
                                <button type="button" 
                                    wire:click="clearEntity"
                                    x-on:click="document.getElementById('entity_input').value = ''; open = false"
                                    class="absolute right-2 top-2 text-gray-400 hover:text-red-500">
                                    <i class="fas fa-times-circle text-sm"></i>
                                </button>
                                @endif
                            </div>

                            @if($showEntityDropdown && $entityResults && $entityResults->count() > 0)
                            <div class="relative w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                @foreach($entityResults as $item)
                                <div 
                                    wire:click="selectEntity({{ $item->id }}, '{{ addslashes($item->name) }}', '{{ $item->type }}')"
                                    x-on:click="document.getElementById('entity_input').value = '{{ addslashes($item->name) }}'; open = false"
                                    class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                                    <div class="font-medium text-gray-800">{{ $item->name }}</div>
                                    @if($item->type === 'fornitore')
                                    <div class="text-xs text-gray-500">Fornitore</div>
                                    @endif                                
                                </div>
                                @endforeach
                            </div>
                            @endif
                            
                            @error('selectedEntityId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    @endif
                    
                    <!-- STEP 2: Dettagli Pagamento e Selezione Fatture -->
                    @if($currentStep == 2)
                    <div>
                        <!-- Info Proprietà e Controparte -->
                        <div class="grid grid-cols-2 gap-4 mb-6 p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Proprietà selezionata</p>
                                <p class="font-medium text-gray-800">{{ $selectedOwnershipName }}</p>
                            </div>
                            <div>
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Controparte selezionata</p>
                                        <p class="font-medium text-gray-800">{{ $selectedEntityName }}</p>
                                        <p class="text-xs text-gray-500">{{ $selectedEntityType === 'fornitore' ? 'Fornitore' : 'Cliente' }}</p>
                                    </div>
                                    <button type="button" wire:click="goToStep(1)" class="text-xs text-lime-500 hover:text-lime-600">
                                        <i class="fas fa-edit mr-1"></i> Modifica
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data Pagamento <span class="text-red-500">*</span></label>
                                <input type="date" wire:model="paymentDate" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                @error('paymentDate') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Metodo Pagamento <span class="text-red-500">*</span></label>
                                <select wire:model="paymentMethod" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                    <option value="">Seleziona...</option>
                                    @foreach($paymentMethods as $method)
                                        <option value="{{ $method->code }}">{{ $method->code }} - {{ $method->name }}</option>
                                    @endforeach
                                </select>
                                @error('paymentMethod') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Conto Bancario</label>
                                <select wire:model="bankAccountId" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                    <option value="">Seleziona conto...</option>
                                    @foreach($bankAccounts as $account)
                                        @php
                                            // Mappa degli ID proprietà ai nomi (cache in memory per evitare query multiple)
                                            static $ownershipNames = [];
                                            $ownershipId = $account['id_ownership'] ?? null;
                                            
                                            if ($ownershipId && !isset($ownershipNames[$ownershipId])) {
                                                $ownership = \App\Models\Ownership::find($ownershipId);
                                                $ownershipNames[$ownershipId] = $ownership->RagAbbrev ?? $ownership->Rag_Soc_intest ?? 'N/A';
                                            }
                                            
                                            $ownershipName = $ownershipNames[$ownershipId] ?? 'N/A';
                                            $displayName = $ownershipName . ' - ' . ($account['name'] ?? '');
                                            
                                            if (!empty($account['n_conto'])) {
                                                $displayName .= ' - ' . $account['n_conto'];
                                            }
                                        @endphp
                                        <option value="{{ $account['id'] }}">
                                            {{ $displayName }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{--!!!!! SPESE BANCARIE !!!!!--}}
                        {{-- <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Spese Bancarie</label>
                                <input type="number" step="0.01" wire:model="bankFees" class="w-full px-3 py-2 border border-gray-300 rounded-md text-right">
                            </div>
                        </div> --}}
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Scadenze da pagare</label>
                            <div class="border rounded-lg overflow-hidden">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium">N. Fattura</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium">Data Scadenza</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium">Totale Scadenza</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium">Residuo</th>
                                            <th class="px-3 py-2 text-center text-xs font-medium">Importo da pagare</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @forelse($availableInvoices as $index => $item)
                                        <tr class="{{ $item['selected'] ? 'bg-lime-50' : '' }}">
                                            <td class="px-3 py-2 text-sm">{{ $item['invoice_number'] }}</td>
                                            <td class="px-3 py-2 text-sm {{ \Carbon\Carbon::parse($item['due_date'])->isPast() && $item['residual_amount'] > 0 ? 'text-red-600 font-bold' : '' }}">
                                                {{ $item['due_date'] }}
                                                @if(\Carbon\Carbon::parse($item['due_date'])->isPast() && $item['residual_amount'] > 0)
                                                    <i class="fas fa-exclamation-triangle text-red-500 ml-1" title="Scaduto!"></i>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-sm text-right">{{ number_format($item['total_amount'], 2, ',', '.') }} €</td>
                                            <td class="px-3 py-2 text-sm text-right font-medium">{{ number_format($item['residual_amount'], 2, ',', '.') }} €</td>
                                            <td class="px-3 py-2 text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    <input type="checkbox" 
                                                        wire:click="toggleInvoice({{ $index }})"
                                                        {{ $item['selected'] ? 'checked' : '' }}
                                                        class="rounded border-gray-300">
                                                    <input type="text" 
                                                        wire:change="updateSelectedAmount({{ $index }}, $event.target.value)"
                                                        value="{{ $item['selected_amount'] > 0 ? number_format($item['selected_amount'], 2, ',', '') : '' }}"
                                                        class="w-28 px-2 py-1 text-right text-sm border rounded-md"
                                                        placeholder="0,00"
                                                        {{ !$item['selected'] ? 'disabled' : '' }}>
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="px-3 py-8 text-center text-gray-500">
                                                Nessuna scadenza aperta per questa controparte
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Note (col-8) e Totale (col-4) -->
                        <div class="grid grid-cols-12 gap-4 mt-4">
                            <div class="col-span-8">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                                <input type="text" wire:model="notes" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Note aggiuntive...">
                            </div>
                            <div class="col-span-4 bg-gray-100 p-3 rounded-md text-right">
                                <p class="text-sm text-gray-600">Totale da pagare</p>
                                <p class="text-xl font-bold text-lime-600">{{ number_format($totalAmount, 2, ',', '.') }} €</p>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    <!-- STEP 3: Riepilogo e Conferma -->
                    @if($currentStep == 3)
                    <div>
                        <div class="space-y-3">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-gray-50 p-3 rounded-md">
                                    <p class="text-xs text-gray-500 uppercase">Proprietà</p>
                                    <p class="font-medium">{{ $selectedOwnershipName }}</p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-md">
                                    <p class="text-xs text-gray-500 uppercase">Controparte</p>
                                    <p class="font-medium">{{ $selectedEntityName }}</p>
                                    <p class="text-xs text-gray-500">{{ $selectedEntityType === 'fornitore' ? 'Fornitore' : 'Cliente' }}</p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-gray-50 p-3 rounded-md">
                                    <p class="text-xs text-gray-500 uppercase">Data Pagamento</p>
                                    <p class="font-medium">{{ \Carbon\Carbon::parse($paymentDate)->format('d/m/Y') }}</p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-md">
                                    <p class="text-xs text-gray-500 uppercase">Metodo Pagamento</p>
                                    <p class="font-medium">{{ $paymentMethod }}</p>
                                </div>
                            </div>
                            
                            <div class="border rounded-md overflow-hidden">
                                <table class="min-w-full">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium">Fattura</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium">Importo pagato</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($availableInvoices as $invoice)
                                            @if($invoice['selected'] && $invoice['selected_amount'] > 0)
                                            <tr>
                                                <td class="px-3 py-2 text-sm">{{ $invoice['invoice_number'] }}</td>
                                                <td class="px-3 py-2 text-sm text-right">{{ number_format($invoice['selected_amount'], 2, ',', '.') }} €</td>
                                            </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-lime-50">
                                        <tr>
                                            <td class="px-3 py-2 font-bold">TOTALE</td>
                                            <td class="px-3 py-2 text-right font-bold text-lime-600">{{ number_format($totalAmount, 2, ',', '.') }} €</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            
                            @if($notes)
                            <div class="bg-gray-50 p-3 rounded-md">
                                <p class="text-xs text-gray-500 uppercase">Note</p>
                                <p class="text-sm">{{ $notes }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                    
                </div>
                
                <!-- Footer con pulsanti -->
                <div class="bg-gray-50 px-6 py-3 flex justify-between">
                    @if($currentStep == 1)
                    <div></div>
                    <div>
                        <button type="button" wire:click="closeModal" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md mr-2">
                            Annulla
                        </button>
                        <button type="button" wire:click="goToStep(2)" class="px-4 py-2 bg-lime-500 text-white rounded-md" {{ !$selectedOwnershipId || !$selectedEntityId ? 'disabled' : '' }}>
                            Continua <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                    @endif
                    
                    @if($currentStep == 2)
                    <div>
                        <button type="button" wire:click="goToStep(1)" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md">
                            <i class="fas fa-arrow-left mr-2"></i> Indietro
                        </button>
                    </div>
                    <div>
                        <button type="button" wire:click="closeModal" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md mr-2">
                            Annulla
                        </button>
                        <button type="button" wire:click="goToStep(3)" class="px-4 py-2 bg-lime-500 text-white rounded-md" {{ $totalSelectedAmount <= 0 ? 'disabled' : '' }}>
                            Continua <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                    @endif
                    
                    @if($currentStep == 3)
                    <div>
                        <button type="button" wire:click="goToStep(2)" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md">
                            <i class="fas fa-arrow-left mr-2"></i> Indietro
                        </button>
                    </div>
                    <div>
                        <button type="button" wire:click="closeModal" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md mr-2">
                            Annulla
                        </button>
                        <button type="button" wire:click="register" class="px-4 py-2 bg-lime-500 text-white rounded-md">
                            <i class="fas fa-check mr-2"></i> Conferma Pagamento
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
