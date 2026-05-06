<div>
    <!-- Filtri -->
    <div class="bg-white rounded-lg shadow mb-6 p-4 border border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Stato</label>
                <select wire:model.live="status" class="w-full px-3 py-2 border rounded-md text-sm">
                    <option value="">Tutti</option>
                    @foreach($statuses as $value => $status)
                        <option value="{{ $value }}">{{ $status['label'] }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Data da</label>
                <input type="date" wire:model.live="dateFrom" class="w-full px-3 py-2 border rounded-md text-sm">
            </div>
            
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Data a</label>
                <input type="date" wire:model.live="dateTo" class="w-full px-3 py-2 border rounded-md text-sm">
            </div>
            
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Cerca</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Numero fattura, causale..." class="w-full px-3 py-2 border rounded-md text-sm">
            </div>
            
            <div class="flex items-end gap-2">
                <select wire:model.live="perPage" class="text-sm px-3 py-2 border border-gray-300 rounded-md">
                    <option value="0">Tutti</option>
                    <option value="200">200 per pagina</option>
                    <option value="100">100 per pagina</option>
                    <option value="50">50 per pagina</option>
                    <option value="25">25 per pagina</option>
                    <option value="15">15 per pagina</option>
                </select>
                
                @if($search || $status || $dateFrom || $dateTo)
                <button wire:click="$set('search', ''); $set('status', ''); $set('dateFrom', ''); $set('dateTo', '')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times-circle"></i> Reset
                </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Tabella -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100" wire:click="sortBy('n_invoice')">
                            <div class="flex items-center gap-1">
                                <span>Numero</span>
                                @if($sortField === 'n_invoice')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600 text-xs"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100" wire:click="sortBy('data_invoice')">
                            <div class="flex items-center gap-1">
                                <span>Data</span>
                                @if($sortField === 'data_invoice')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600 text-xs"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fornitore</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100" wire:click="sortBy('importo_totale')">
                            <div class="flex items-center justify-end gap-1">
                                <span>Importo</span>
                                @if($sortField === 'importo_totale')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600 text-xs"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100" wire:click="sortBy('status')">
                            <div class="flex items-center justify-center gap-1">
                                <span>Stato</span>
                                @if($sortField === 'status')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600 text-xs"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($invoices as $invoice)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">
                            {{ $invoice->n_invoice }}
                            @if($invoice->sdi_id)
                                <span class="text-xs text-gray-400 block">SDI: {{ $invoice->sdi_id }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $this->formatDate($invoice->data_invoice) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $invoice->entity?->ragione_sociale ?: ($invoice->entity?->nome . ' ' . $invoice->entity?->cognome) ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-center">
                            <span class="px-2 py-1 rounded text-xs bg-blue-100 text-blue-800">
                                {{ $invoice->type_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-right font-semibold">
                            {{ number_format($invoice->importo_totale, 2) }} €
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-2 py-1 rounded-full text-xs {{ $invoice->status_badge_class }}">
                                <i class="fas {{ $invoice->status_icon }} mr-1"></i>
                                {{ $invoice->status_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex justify-center gap-2">
                                <a href="{{ route('admin.invoices-received.show', $invoice->id) }}" 
                                   class="text-blue-500 hover:text-blue-700 transition" title="Visualizza">
                                    <i class="fa-regular fa-eye"></i>
                                </a>
                                @if(auth()->guard('admin')->user()->hasPermission('edit_purchases'))
                                <a href="{{ route('admin.invoices-received.edit', $invoice->id) }}" 
                                   class="text-yellow-500 hover:text-yellow-700 transition" title="Modifica">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                @endif
                                @if(auth()->guard('admin')->user()->hasPermission('delete_purchases'))
                                <button wire:click="deleteInvoice({{ $invoice->id }})" 
                                        wire:confirm="Sei sicuro di voler eliminare questa fattura?"
                                        class="text-red-500 hover:text-red-700 transition" title="Elimina">
                                    <i class="fa-regular fa-trash-alt"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-file-invoice text-4xl mb-2 text-gray-300"></i>
                            <p>Nessuna fattura trovata</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Paginazione -->
        @if($perPage != 0 && method_exists($invoices, 'links'))
        <div class="px-6 py-4 border-t">
            {{ $invoices->links() }}
        </div>
        @elseif($perPage == 0 && $invoices->count() > 0)
        <div class="px-6 py-4 border-t bg-green-50 text-center">
            <i class="fas fa-database text-green-500 mr-1"></i>
            Mostrati tutti i <strong>{{ $invoices->count() }}</strong> risultati
        </div>
        @endif
    </div>
</div>