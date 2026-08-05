<div>
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fa-solid fa-dolly mr-2 text-orange-600"></i>
                Fatture di Acquisto collegate
            </h1>
            <p class="text-gray-600 mt-1">
                <i class="fas fa-truck mr-1"></i> {{ $vehicleName }}
            </p>
        </div>
        <button wire:click="backToVehicles" 
                class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
            <i class="fas fa-arrow-left"></i>
        </button>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">N. Fattura</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fornitore</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proprietà</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Righe collegate al mezzo</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Totale Fattura</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($invoices as $invoice)
                    <tr wire:key="invoice-{{ $invoice->id }}" class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                            {{ $invoice->data_invoice->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 whitespace-nowrap">
                            {{ $invoice->n_invoice }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ $invoice->supplier_name }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ $invoice->ownership->RagAbbrev ?? $invoice->ownership_name }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            @if($invoice->rows->count() > 0)
                                <div class="space-y-1">
                                    @foreach($invoice->rows as $row)
                                    <div class="text-xs">
                                        <span class="text-gray-800">{{ $row->description }}</span>
                                        <span class="text-gray-400"> — {{ number_format($row->quantity, 2, ',', '.') }} x {{ number_format($row->unit_price, 2, ',', '.') }} € = </span>
                                        <span class="font-medium text-gray-700">{{ number_format($row->total, 2, ',', '.') }} €</span>
                                    </div>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-gray-400 italic">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-medium text-gray-900 whitespace-nowrap">
                            {{ number_format($invoice->importo_totale, 2, ',', '.') }} €
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('admin.invoices-received.edit', $invoice->id) }}"
                               class="text-yellow-600 hover:text-yellow-900"
                               title="Apri fattura">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="text-gray-500">
                                <i class="fa-solid fa-dolly text-gray-300 text-4xl block mb-2"></i>
                                <p class="text-sm">Nessuna fattura di acquisto collegata a questo mezzo</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($invoices->count() > 0)
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="5" class="px-4 py-3 text-right font-bold text-gray-700">TOTALE COMPLESSIVO</td>
                        <td class="px-4 py-3 text-right font-bold text-lg text-gray-900">
                            {{ number_format($invoices->sum('importo_totale'), 2, ',', '.') }} €
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>