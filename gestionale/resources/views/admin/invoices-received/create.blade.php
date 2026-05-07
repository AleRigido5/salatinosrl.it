@extends('admin.layouts.app')

@section('title', 'Nuova Fattura di Acquisto')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-5xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Nuova Fattura di Acquisto</h1>
            <a href="{{ route('admin.invoices-received.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded">← Torna all'elenco</a>
        </div>

        <form action="{{ route('admin.invoices-received.store') }}" method="POST" id="invoiceForm">
            @csrf

            <!-- Dati Fattura -->
            <div class="bg-white rounded shadow p-6 mb-6">
                <h2 class="text-lg font-bold mb-4">Dati Fattura</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Numero Fattura *</label>
                        <input type="text" name="n_invoice" value="{{ old('n_invoice') }}" class="w-full border rounded px-3 py-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Data Fattura *</label>
                        <input type="date" name="data_invoice" value="{{ old('data_invoice', date('Y-m-d')) }}" class="w-full border rounded px-3 py-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Tipo Documento</label>
                        <select name="type_invoice" class="w-full border rounded px-3 py-2">
                            <option value="TD01">TD01 - Fattura</option>
                            <option value="TD04">TD04 - Nota di Credito</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Stato</label>
                        <select name="status" class="w-full border rounded px-3 py-2">
                            <option value="bozza">Bozza</option>
                            <option value="inviata">Inviata</option>
                            <option value="consegnata">Consegnata</option>
                            <option value="scartata">Scartata</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Divisa</label>
                        <select name="divisa" class="w-full border rounded px-3 py-2">
                            <option value="EUR">EUR</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">SDI ID</label>
                        <input type="text" name="sdi_id" value="{{ old('sdi_id') }}" class="w-full border rounded px-3 py-2">
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium mb-1">Causale / Note</label>
                    <textarea name="causale" rows="2" class="w-full border rounded px-3 py-2">{{ old('causale') }}</textarea>
                </div>
            </div>

            <!-- Soggetti -->
            <div class="bg-white rounded shadow p-6 mb-6">
                <h2 class="text-lg font-bold mb-4">Soggetti</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Proprietà</label>
                        <select name="id_ownership" class="w-full border rounded px-3 py-2">
                            <option value="">-- Seleziona --</option>
                            @foreach($ownerships as $ownership)
                                <option value="{{ $ownership->id_proprieta }}">{{ $ownership->Rag_Soc_intest ?: $ownership->RagSocialePr }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Fornitore *</label>
                        <select name="id_entities" class="w-full border rounded px-3 py-2" required>
                            <option value="">-- Seleziona --</option>
                            @foreach($entities as $entity)
                                <option value="{{ $entity->id_cliente }}">{{ $entity->ragione_sociale ?: ($entity->nome . ' ' . $entity->cognome) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Righe -->
            <div class="bg-white rounded shadow p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-bold">Righe Fattura</h2>
                    <button type="button" id="addRowBtn" class="px-3 py-1 bg-green-600 text-white rounded text-sm">+ Aggiungi Riga</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full border">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-sm">Centro Costo</th>
                                <th class="px-3 py-2 text-left text-sm">Descrizione</th>
                                <th class="px-3 py-2 text-right text-sm">Qtà</th>
                                <th class="px-3 py-2 text-right text-sm">Prezzo Unit.</th>
                                <th class="px-3 py-2 text-right text-sm">Sconto %</th>
                                <th class="px-3 py-2 text-right text-sm">Totale</th>
                                <th class="px-3 py-2 text-center text-sm"></th>
                            </tr>
                        </thead>
                        <tbody id="rowsContainer"></tbody>
                        <tfoot>
                            <tr class="bg-gray-50">
                                <td colspan="5" class="px-3 py-2 text-right font-bold">TOTALE</td>
                                <td class="px-3 py-2 text-right font-bold text-lg" id="grandTotal">0,00 €</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <input type="hidden" name="importo_totale" id="importo_totale" value="0">

            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.invoices-received.index') }}" class="px-4 py-2 bg-gray-300 rounded">Annulla</a>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">Salva Fattura</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let rowIndex = 0;
    const rowsContainer = document.getElementById('rowsContainer');
    
    function calculateGrandTotal() {
        let total = 0;
        document.querySelectorAll('.invoice-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.qty')?.value) || 0;
            const price = parseFloat(row.querySelector('.price')?.value) || 0;
            const discount = parseFloat(row.querySelector('.discount')?.value) || 0;
            const rowTotal = qty * price * (1 - discount / 100);
            total += rowTotal;
            const totalCell = row.querySelector('.row-total');
            if (totalCell) totalCell.textContent = rowTotal.toFixed(2).replace('.', ',') + ' €';
        });
        document.getElementById('grandTotal').textContent = total.toFixed(2).replace('.', ',') + ' €';
        document.getElementById('importo_totale').value = total.toFixed(6);
    }
    
    function addRow() {
        const row = document.createElement('tr');
        row.className = 'invoice-row';
        row.innerHTML = `
            <td class="px-3 py-2">
                <select name="rows[${rowIndex}][id_cost_center]" class="w-full border rounded px-2 py-1 text-sm">
                    <option value="">-- Seleziona --</option>
                    @foreach($costCenters as $cc)
                        <option value="{{ $cc->id }}">{{ $cc->Nome }}</option>
                    @endforeach
                </select>
            </td>
            <td class="px-3 py-2">
                <input type="text" name="rows[${rowIndex}][description]" class="w-full border rounded px-2 py-1 text-sm" required>
            </td>
            <td class="px-3 py-2">
                <input type="number" step="0.000001" name="rows[${rowIndex}][quantity]" value="1" class="qty w-24 text-right border rounded px-2 py-1 text-sm">
            </td>
            <td class="px-3 py-2">
                <input type="number" step="0.000001" name="rows[${rowIndex}][unit_price]" value="0" class="price w-28 text-right border rounded px-2 py-1 text-sm">
            </td>
            <td class="px-3 py-2">
                <input type="number" step="0.01" name="rows[${rowIndex}][discount_percentage]" value="0" class="discount w-20 text-right border rounded px-2 py-1 text-sm">
            </td>
            <td class="px-3 py-2 text-right row-total text-sm">0,00 €</td>
            <td class="px-3 py-2 text-center">
                <button type="button" class="remove-row text-red-600">🗑</button>
            </td>
        `;
        
        row.querySelectorAll('.qty, .price, .discount').forEach(input => {
            input.addEventListener('input', () => calculateGrandTotal());
        });
        row.querySelector('.remove-row').addEventListener('click', () => {
            row.remove();
            calculateGrandTotal();
        });
        
        rowsContainer.appendChild(row);
        rowIndex++;
        calculateGrandTotal();
    }
    
    document.getElementById('addRowBtn').addEventListener('click', addRow);
    addRow();
});
</script>
@endsection