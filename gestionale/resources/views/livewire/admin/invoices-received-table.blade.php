<div>
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Fatture di Acquisto</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.invoices-received.xml-update') }}" class="px-4 py-2 bg-indigo-600 text-white rounded">Importa XML</a>
            <a href="{{ route('admin.invoices-received.create') }}" class="px-4 py-2 bg-green-600 text-white rounded">Nuova Fattura</a>
        </div>
    </div>

    <div class="bg-white rounded shadow p-4 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cerca..." class="border rounded px-3 py-2">
            <select wire:model.live="status" class="border rounded px-3 py-2">
                <option value="">Tutti gli stati</option>
                @foreach($statuses as $s)
                    <option value="{{ $s['value'] }}">{{ $s['label'] }}</option>
                @endforeach
            </select>
            <select wire:model.live="id_ownership" class="border rounded px-3 py-2">
                <option value="">Tutte le proprietà</option>
                @foreach($ownerships as $o)
                    <option value="{{ $o->id }}">{{ $o->label }}</option>
                @endforeach
            </select>
            <select wire:model.live="id_entities" class="border rounded px-3 py-2">
                <option value="">Tutti i fornitori</option>
                @foreach($suppliers as $s)
                    <option value="{{ $s->id }}">{{ $s->label }}</option>
                @endforeach
            </select>
        </div>
        <button wire:click="resetFilters" class="mt-3 text-sm text-indigo-600">Reset filtri</button>
    </div>

    <div class="bg-white rounded shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer" wire:click="sortBy('n_invoice')">Numero</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer" wire:click="sortBy('data_invoice')">Data</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fornitore</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Proprietà</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Totale</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stato</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Azioni</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($invoices as $invoice)
                <tr>
                    <td class="px-6 py-4">{{ $invoice->n_invoice }}</td>
                    <td class="px-6 py-4">{{ $invoice->data_invoice->format('d/m/Y') }}</td>
                    <td class="px-6 py-4">{{ $invoice->supplier_name }}</td>
                    <td class="px-6 py-4">{{ $invoice->ownership_name }}</td>
                    <td class="px-6 py-4 text-right">{{ number_format($invoice->importo_totale, 2, ',', '.') }} €</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                            {{ $invoice->status_label }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('admin.invoices-received.show', $invoice) }}" class="text-indigo-600 mr-2">Vedi</a>
                        <a href="{{ route('admin.invoices-received.edit', $invoice) }}" class="text-blue-600 mr-2">Modifica</a>
                        <button wire:click="deleteInvoice({{ $invoice->id }})" onclick="return confirm('Sei sicuro?')" class="text-red-600">Elimina</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">Nessuna fattura trovata</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t">
            {{ $invoices->links() }}
        </div>
    </div>
</div>