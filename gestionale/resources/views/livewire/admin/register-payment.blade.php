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
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
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
                                    <span class="ml-2 text-sm {{ $currentStep >= 2 ? 'text-lime-600 font-medium' : 'text-gray-400' }}">Fatture</span>
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
                
                <div class="px-6 py-4 space-y-4 max-h-[60vh] overflow-y-auto">
                    
                    <!-- STEP 1: Selezione Cliente/Fornitore -->
                    @if($currentStep == 1)
                    <div>
                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cliente / Fornitore <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <i class="fas fa-user absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                                <input type="text" 
                                    wire:model.live.debounce.300ms="entitySearch"
                                    class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500"
                                    placeholder="Cerca per nome, ragione sociale o P.IVA...">
                            </div>
                            
                            @if($showEntityDropdown && count($entityResults) > 0)
                            <div class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                @foreach($entityResults as $result)
                                <div wire:click="selectEntity({{ $result['id'] }}, '{{ addslashes($result['name']) }}', '{{ $result['type'] }}')"
                                    class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                                    <div class="font-medium text-gray-800">{{ $result['name'] }}</div>
                                    <div class="text-xs text-gray-500">
                                        {{ $result['type'] === 'fornitore' ? 'Fornitore' : 'Cliente' }}
                                        @if($result['piva']) - P.IVA: {{ $result['piva'] }} @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endif
                            
                            @if($selectedEntityId)
                            <div class="mt-2 p-2 bg-green-50 rounded-md">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span class="text-sm text-green-700 ml-1">{{ $selectedEntityName }}</span>
                            </div>
                            @endif
                            @error('selectedEntityId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    @endif
                    
                    <!-- STEP 2: Selezione Fatture -->
                    @if($currentStep == 2)
                    <div>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data Pagamento</label>
                                <input type="date" wire:model="paymentDate" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                @error('paymentDate') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Metodo Pagamento</label>
                                <select wire:model="paymentMethod" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                    <option value="">Seleziona...</option>
                                    @foreach($paymentMethods as $method)
                                        <option value="{{ $method->code }}">{{ $method->code }} - {{ $method->name }}</option>
                                    @endforeach
                                </select>
                                @error('paymentMethod') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fatture da pagare</label>
                            <div class="border rounded-lg overflow-hidden">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium">N. Fattura</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium">Data Scadenza</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium">Totale</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium">Residuo</th>
                                            <th class="px-3 py-2 text-center text-xs font-medium">Importo da pagare</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @forelse($availableInvoices as $index => $invoice)
                                        <tr class="{{ $invoice['selected'] ? 'bg-lime-50' : '' }}">
                                            <td class="px-3 py-2 text-sm">{{ $invoice['invoice_number'] }}</td>
                                            <td class="px-3 py-2 text-sm">{{ $invoice['due_date'] }}</td>
                                            <td class="px-3 py-2 text-sm text-right">{{ number_format($invoice['total_amount'], 2, ',', '.') }} €</td>
                                            <td class="px-3 py-2 text-sm text-right font-medium">{{ number_format($invoice['residual_amount'], 2, ',', '.') }} €</td>
                                            <td class="px-3 py-2 text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    <input type="checkbox" 
                                                        wire:click="toggleInvoice({{ $index }})"
                                                        {{ $invoice['selected'] ? 'checked' : '' }}
                                                        class="rounded border-gray-300">
                                                    <input type="number" 
                                                        step="0.01"
                                                        wire:change="updateSelectedAmount({{ $index }}, $event.target.value)"
                                                        value="{{ $invoice['selected_amount'] > 0 ? number_format($invoice['selected_amount'], 2, '.', '') : '' }}"
                                                        class="w-28 px-2 py-1 text-right text-sm border rounded-md"
                                                        placeholder="0,00"
                                                        {{ !$invoice['selected'] ? 'disabled' : '' }}>
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="px-3 py-8 text-center text-gray-500">
                                                Nessuna fattura aperta per questa controparte
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Spese Bancarie</label>
                                <input type="number" step="0.01" wire:model="bankFees" class="w-full px-3 py-2 border border-gray-300 rounded-md text-right">
                            </div>
                            <div class="bg-gray-100 p-3 rounded-md text-right">
                                <p class="text-sm text-gray-600">Totale da pagare</p>
                                <p class="text-xl font-bold text-lime-600">{{ number_format($totalAmount, 2, ',', '.') }} €</p>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                            <textarea wire:model="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Note aggiuntive..."></textarea>
                        </div>
                    </div>
                    @endif
                    
                    <!-- STEP 3: Riepilogo e Conferma -->
                    @if($currentStep == 3)
                    <div>
                        <div class="space-y-3">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-gray-50 p-3 rounded-md">
                                    <p class="text-xs text-gray-500 uppercase">Controparte</p>
                                    <p class="font-medium">{{ $selectedEntityName }}</p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-md">
                                    <p class="text-xs text-gray-500 uppercase">Data Pagamento</p>
                                    <p class="font-medium">{{ \Carbon\Carbon::parse($paymentDate)->format('d/m/Y') }}</p>
                                </div>
                            </div>
                            
                            <div class="bg-gray-50 p-3 rounded-md">
                                <p class="text-xs text-gray-500 uppercase">Metodo Pagamento</p>
                                <p class="font-medium">{{ $paymentMethod }}</p>
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
                                        @if($bankFees > 0)
                                        <tr class="bg-yellow-50">
                                            <td class="px-3 py-2 text-sm">Spese bancarie</td>
                                            <td class="px-3 py-2 text-sm text-right">{{ number_format($bankFees, 2, ',', '.') }} €</td>
                                        </tr>
                                        @endif
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
                        <button type="button" wire:click="goToStep(2)" class="px-4 py-2 bg-lime-500 text-white rounded-md" {{ !$selectedEntityId ? 'disabled' : '' }}>
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