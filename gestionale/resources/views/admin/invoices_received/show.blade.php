@extends('admin.layouts.app')

@section('title', 'Dettaglio Fattura di Acquisto')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-file-invoice text-blue-500 mr-2"></i>
            Dettaglio Fattura
        </h1>
        <div class="flex gap-3">
            <a href="{{ route('admin.invoices-received.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition">
                <i class="fas fa-arrow-left mr-2"></i> Torna indietro
            </a>
            @if(auth()->guard('admin')->user()->hasPermission('edit_purchases'))
            <a href="{{ route('admin.invoices-received.edit', $invoice->id) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg transition">
                <i class="fas fa-edit mr-2"></i> Modifica
            </a>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <!-- Header Fattura -->
        <div class="bg-gradient-to-r from-gray-50 to-gray-100 p-6 border-b">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-gray-500">Numero Fattura</p>
                    <p class="text-xl font-bold text-gray-800">{{ $invoice->n_invoice }}</p>
                    @if($invoice->sdi_id)
                        <p class="text-xs text-gray-400 mt-1">SDI ID: {{ $invoice->sdi_id }}</p>
                    @endif
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500">Data</p>
                    <p class="text-xl font-bold text-gray-800">{{ \Carbon\Carbon::parse($invoice->data_invoice)->format('d/m/Y') }}</p>
                </div>
            </div>
        </div>
        
        <div class="p-6">
            <!-- Due colonne: Cedente e Cessionario -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                        <i class="fas fa-building text-lime-500 mr-2"></i> Cedente / Prestatore (Fornitore)
                    </h3>
                    @php $cedente = $invoice->data_entities; @endphp
                    <div class="space-y-2 text-sm">
                        <p><span class="text-gray-500">Denominazione:</span> {{ $cedente['Denominazione'] ?? '-' }}</p>
                        <p><span class="text-gray-500">Indirizzo:</span> {{ $cedente['indirizzo'] ?? '-' }}</p>
                        <p><span class="text-gray-500">CAP:</span> {{ $cedente['CAP'] ?? '-' }}</p>
                        <p><span class="text-gray-500">Comune:</span> {{ $cedente['Comune'] ?? '-' }} ({{ $cedente['Provincia'] ?? '-' }})</p>
                        <p><span class="text-gray-500">Nazione:</span> {{ $cedente['Nazione'] ?? 'IT' }}</p>
                        <p><span class="text-gray-500">Codice Fiscale:</span> {{ $cedente['CodiceFiscale'] ?? '-' }}</p>
                        <p><span class="text-gray-500">Telefono:</span> {{ $cedente['Telefono'] ?? '-' }}</p>
                        <p><span class="text-gray-500">Email:</span> {{ $cedente['Email'] ?? '-' }}</p>
                    </div>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                        <i class="fas fa-building text-lime-500 mr-2"></i> Cessionario / Committente (Proprietà)
                    </h3>
                    @php $cessionario = $invoice->data_ownership; @endphp
                    <div class="space-y-2 text-sm">
                        <p><span class="text-gray-500">Denominazione:</span> {{ $cessionario['Denominazione'] ?? '-' }}</p>
                        <p><span class="text-gray-500">Indirizzo:</span> {{ $cessionario['indirizzo'] ?? '-' }}</p>
                        <p><span class="text-gray-500">CAP:</span> {{ $cessionario['CAP'] ?? '-' }}</p>
                        <p><span class="text-gray-500">Comune:</span> {{ $cessionario['Comune'] ?? '-' }} ({{ $cessionario['Provincia'] ?? '-' }})</p>
                        <p><span class="text-gray-500">Nazione:</span> {{ $cessionario['Nazione'] ?? 'IT' }}</p>
                        <p><span class="text-gray-500">Codice Destinatario:</span> {{ $cessionario['CodiceDestinatario'] ?? '-' }}</p>
                        <p><span class="text-gray-500">PEC:</span> {{ $cessionario['PECDestinatario'] ?? '-' }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Riga Causale -->
            @if($invoice->causale)
            <div class="mb-6 p-4 bg-yellow-50 rounded-lg border-l-4 border-yellow-400">
                <p class="text-sm font-medium text-yellow-800 mb-1">Causale</p>
                <p class="text-sm text-yellow-700">{{ $invoice->causale }}</p>
            </div>
            @endif
            
            <!-- Tabella Righe -->
            <h3 class="text-md font-semibold text-gray-800 mb-3">Righe Fattura</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Descrizione</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">Q.tà</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Prezzo Unit.</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">Sconto %</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">IVA %</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Totale</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($invoice->rows as $row)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ $row->description }}
                                @if($row->costCenter)
                                    <span class="text-xs text-gray-400 block">{{ $row->costCenter->Nome }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-center">{{ number_format($row->quantity, 3) }}</td>
                            <td class="px-4 py-3 text-sm text-right">€ {{ number_format($row->unit_price, 4) }}</td>
                            <td class="px-4 py-3 text-sm text-center">{{ number_format($row->discount_percentage, 2) }}%</td>
                            <td class="px-4 py-3 text-sm text-right">{{ number_format($row->vatRate->rate ?? 0, 2) }}%</td>
                            <td class="px-4 py-3 text-sm text-right font-semibold">€ {{ number_format($row->total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="5" class="px-4 py-3 text-right font-bold">Totale Fattura:</td>
                            <td class="px-4 py-3 text-right font-bold text-lg">€ {{ number_format($invoice->importo_totale, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <!-- Badge Stato -->
            <div class="mt-6 pt-4 border-t text-right">
                <span class="px-3 py-1 rounded-full text-sm {{ $invoice->status_badge_class }}">
                    <i class="fas {{ $invoice->status_icon }} mr-1"></i>
                    {{ $invoice->status_label }}
                </span>
            </div>
        </div>
    </div>
</div>
@endsection