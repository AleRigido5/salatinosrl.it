@extends('admin.layouts.app')

@section('title', 'Dettaglio Fattura')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Dettaglio Fattura</h1>
            <div class="flex gap-2">
                <a href="{{ route('admin.invoices-received.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded">← Torna</a>
            </div>
        </div>

        <div class="bg-white rounded shadow overflow-hidden">
            <div class="p-6 border-b">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm text-gray-500">Numero Fattura</p>
                        <p class="text-xl font-bold">{{ $invoice->n_invoice }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500">Data</p>
                        <p class="text-lg">{{ $invoice->data_invoice->format('d/m/Y') }}</p>
                    </div>
                </div>
            </div>

            <div class="p-6 border-b">
                <h2 class="text-lg font-bold mb-4">Dettagli</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Tipo Documento</p>
                        <p>{{ $invoice->type_invoice_label }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Stato</p>
                        <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">{{ $invoice->status_label }}</span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Divisa</p>
                        <p>{{ $invoice->divisa }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">SDI ID</p>
                        <p class="font-mono text-sm">{{ $invoice->sdi_id ?: '-' }}</p>
                    </div>
                </div>
                @if($invoice->causale)
                <div class="mt-4">
                    <p class="text-sm text-gray-500">Causale / Note</p>
                    <p class="mt-1">{{ $invoice->causale }}</p>
                </div>
                @endif
            </div>

            <div class="p-6 border-b">
                <h2 class="text-lg font-bold mb-4">Soggetti</h2>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm font-bold mb-2">📦 Proprietà (Cessionario)</p>
                        <div class="bg-gray-50 rounded p-3">
                            @if($invoice->data_ownership)
                                <p>{{ $invoice->data_ownership['Denominazione'] ?? 'N/D' }}</p>
                                <p class="text-sm text-gray-600">{{ $invoice->data_ownership['Indirizzo'] ?? '' }}, {{ $invoice->data_ownership['CAP'] ?? '' }} {{ $invoice->data_ownership['Comune'] ?? '' }}</p>
                                <p class="text-sm text-gray-600">P.IVA: {{ $invoice->data_ownership['PartitaIVA'] ?? '-' }}</p>
                                <p class="text-sm text-gray-600">Codice Destinatario: {{ $invoice->data_ownership['CodiceDestinatario'] ?? '-' }}</p>
                            @else
                                <p class="text-gray-500">Nessun dato</p>
                            @endif
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-bold mb-2">🚚 Fornitore (Cedente)</p>
                        <div class="bg-gray-50 rounded p-3">
                            @if($invoice->data_entities)
                                <p>{{ $invoice->data_entities['Denominazione'] ?? $invoice->data_entities['Nome'] . ' ' . ($invoice->data_entities['Cognome'] ?? '') }}</p>
                                <p class="text-sm text-gray-600">P.IVA: {{ $invoice->data_entities['PartitaIVA'] ?? '-' }}</p>
                                <p class="text-sm text-gray-600">CF: {{ $invoice->data_entities['CodiceFiscale'] ?? '-' }}</p>
                            @else
                                <p class="text-gray-500">Nessun dato</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <h2 class="text-lg font-bold mb-4">Righe Fattura</h2>
                <table class="min-w-full border">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left">Descrizione</th>
                            <th class="px-3 py-2 text-right">Quantità</th>
                            <th class="px-3 py-2 text-right">Prezzo Unit.</th>
                            <th class="px-3 py-2 text-right">Sconto</th>
                            <th class="px-3 py-2 text-right">Totale</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->rows as $row)
                        <tr>
                            <td class="px-3 py-2">{{ $row->description }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format($row->quantity, 2, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format($row->unit_price, 4, ',', '.') }} €</td>
                            <td class="px-3 py-2 text-right">{{ $row->discount_percentage > 0 ? number_format($row->discount_percentage, 2, ',', '.') . '%' : '-' }}</td>
                            <td class="px-3 py-2 text-right font-bold">{{ number_format($row->total, 2, ',', '.') }} €</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="4" class="px-3 py-2 text-right font-bold">TOTALE</td>
                            <td class="px-3 py-2 text-right font-bold text-lg">{{ number_format($invoice->importo_totale, 2, ',', '.') }} €</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection