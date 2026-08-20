{{-- resources/views/admin/invoice-sent/edit.blade.php --}}

@extends('admin.layouts.app')

@section('title', 'Modifica Fattura di Vendita')

@section('content')
<div class="container-fluid">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-edit text-lime-500 mr-2"></i> Modifica Fattura di Vendita
        </h1>
        <a href="{{ route('admin.invoices-sent.index') }}" 
           class="bg-gray-600 hover:bg-gray-900 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
            <i class="fas fa-arrow-left"></i>
        </a>
    </div>

    @if(session('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
            <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
        </div>
    @endif

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
        </div>
    @endif

    @if(!$is_manual)
        <div class="mb-4 p-4 bg-yellow-50 border border-yellow-300 text-yellow-700 rounded-lg">
            <i class="fas fa-info-circle mr-2"></i>
            <strong>Nota:</strong> Questa fattura è stata importata da XML. Puoi modificare solo la causale, i centri di costo e i servizi associati alle righe.
        </div>
    @endif

    <form id="invoiceForm" action="{{ route('admin.invoices-sent.update', $invoice->id) }}" method="POST" class="bg-white rounded-lg shadow p-6">
        @csrf
        @method('PUT')
        
        <!-- Layout a 2 colonne -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-4">
            <!-- Colonna SINISTRA -->
            <div class="lg:col-span-2">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Proprietà <span class="text-red-500">*</span></label>
                        <select id="id_ownership" name="id_ownership" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500 @error('id_ownership') border-red-500 @enderror"
                                {{ !$is_manual ? 'disabled' : '' }}>
                            <option value="">Seleziona proprietà</option>
                            @foreach($ownerships as $o)
                                <option value="{{ $o->id_proprieta }}" {{ $invoice->id_ownership == $o->id_proprieta ? 'selected' : '' }}>
                                    {{ $o->RagAbbrev ?? $o->Rag_Soc_intest }}
                                </option>
                            @endforeach
                        </select>
                        @error('id_ownership') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sezionale <span class="text-red-500">*</span></label>
                        <select id="selected_series_id" name="selected_series_id" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500 @error('selected_series_id') border-red-500 @enderror"
                                {{ !$is_manual ? 'disabled' : '' }}>
                            <option value="">Seleziona sezionale</option>
                            @php
                                $currentYear = date('Y');
                            @endphp
                            @foreach($availableSeries as $series)
                                @php
                                    $isActive = $series['active'] ?? true;
                                @endphp
                                <option value="{{ $series['id'] }}" 
                                        {{ $invoice->id_invoice_series == $series['id'] ? 'selected' : '' }}
                                        {{ !$isActive ? 'disabled' : '' }}
                                        style="{{ !$isActive ? 'color: #999; background: #f5f5f5;' : '' }}">
                                    {{ $series['code'] }} - {{ $series['name'] }} ({{ $series['year'] }})
                                    @if(!$isActive) 🔒 DISATTIVATO @endif
                                </option>
                            @endforeach
                        </select>
                        @error('selected_series_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo Documento <span class="text-red-500">*</span></label>
                        <select id="type_invoice" name="type_invoice" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500 @error('type_invoice') border-red-500 @enderror"
                                {{ !$is_manual ? 'disabled' : '' }}>
                            <option value="">Seleziona tipo</option>
                            @foreach($typeDocuments as $code => $label)
                                <option value="{{ $code }}" {{ $invoice->type_invoice == $code ? 'selected' : '' }}>
                                    {{ $code }} - {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('type_invoice') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">N. Fattura <span class="text-red-500">*</span></label>
                        <input type="text" id="n_invoice" name="n_invoice" value="{{ $invoice->n_invoice }}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100" readonly>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">N. Fattura Esterno <span class="text-gray-400 text-xs font-normal">(opzionale)</span></label>
                        <div class="relative">
                            <i class="fas fa-file-invoice absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                            <input type="text" id="n_invoice_ext" name="n_invoice_ext" 
                                   value="{{ old('n_invoice_ext', $invoice->n_invoice_ext) }}" 
                                   placeholder="Numero fattura del cliente..."
                                   class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500"
                                   {{ !$is_manual ? 'readonly' : '' }}>
                        </div>
                        @error('n_invoice_ext') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data Fattura <span class="text-red-500">*</span></label>
                        <input type="date" id="data_invoice" name="data_invoice" 
                               value="{{ old('data_invoice', date('Y-m-d', strtotime($invoice->data_invoice))) }}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500 @error('data_invoice') border-red-500 @enderror"
                               {{ !$is_manual ? 'readonly' : '' }}>
                        @error('data_invoice') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cliente <span class="text-red-500">*</span></label>
                        <div class="flex gap-2">
                            <div class="flex-1 relative" id="customerAutocomplete">
                                <div class="relative">
                                    <i class="fas fa-user absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                                    <input type="text"
                                        id="customer_search"
                                        placeholder="Cerca cliente..."
                                        value="{{ $invoice->entity ? ($invoice->entity->ragione_sociale ?? $invoice->entity->nome . ' ' . $invoice->entity->cognome) : '' }}"
                                        class="w-full pl-9 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 @error('selected_customer_id') border-red-500 @enderror"
                                        autocomplete="off"
                                        {{ !$is_manual ? 'readonly' : '' }}>
                                    <input type="hidden" id="selected_customer_id" name="selected_customer_id" value="{{ $invoice->id_entities }}">
                                    @if($is_manual)
                                    <button type="button" id="clearCustomerBtn" class="absolute right-2 top-2 text-gray-400 hover:text-red-500 {{ $invoice->id_entities ? '' : 'hidden' }}">
                                        <i class="fas fa-times-circle text-sm"></i>
                                    </button>
                                    @endif
                                </div>
                                @if($is_manual)
                                <div id="customerDropdown" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto hidden"></div>
                                @endif
                            </div>
                            
                            @if($is_manual)
                            <button type="button" id="openCustomerModalBtn" class="px-3 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition-colors" title="Nuovo Cliente">
                                <i class="fas fa-plus"></i>
                            </button>
                            @endif
                        </div>
                        <div id="selectedCustomerInfo" class="mt-1 text-xs text-green-600 {{ $invoice->id_entities ? '' : 'hidden' }}">
                            <i class="fas fa-check-circle"></i> Cliente selezionato: <span id="selectedCustomerName">{{ $invoice->entity ? ($invoice->entity->ragione_sociale ?? $invoice->entity->nome . ' ' . $invoice->entity->cognome) : '' }}</span>
                        </div>
                        @error('selected_customer_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Causale / Note</label>
                        <textarea id="causale" name="causale" rows="2" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500" 
                                  placeholder="Note aggiuntive...">{{ old('causale', $invoice->causale) }}</textarea>
                    </div>
                </div>
            </div>
            
            <!-- Colonna DESTRA: Totali -->
            <div class="lg:col-span-1">
                <div class="totals-card bg-white rounded-lg p-4 shadow border border-gray-200">
                    <div class="grid grid-cols-2 gap-4 mb-3">
                        <div>
                            <div class="text-xs uppercase tracking-wider text-gray-500 mb-1">TOTALE IMPONIBILE</div>
                            <div class="text-xl font-bold text-gray-800" id="totalTaxable">€ {{ number_format($totalTaxable, 2, ',', '.') }}</div>
                        </div>
                        <div>
                            <div class="text-xs uppercase tracking-wider text-gray-500 mb-1">TOTALE SCONTI</div>
                            <div class="text-xl font-bold text-red-500" id="totalDiscount">- € {{ number_format($totalDiscount, 2, ',', '.') }}</div>
                        </div>
                    </div>
                    
                    <div class="border-b border-gray-200 mb-3"></div>
                    
                    <div id="vatSummaryContainer" class="mb-3">
                        <div class="text-xs uppercase tracking-wider text-gray-500 text-center mb-2 pb-1 border-b border-gray-200">DETTAGLIO IVA PER ALIQUOTA</div>
                        <div id="vatSummaryItems" class="space-y-1">
                            @foreach($vatSummary as $vat)
                                <div class="flex justify-between items-center py-1 text-sm border-b border-gray-100 last:border-0">
                                    <div class="font-medium text-gray-700">
                                        @php
                                            $rateLabel = $vat['rate_percent'] . '%';
                                            if ($vat['rate'] == 0 && $vat['nature_code']) {
                                                $rateLabel = '0% (Cod. ' . $vat['nature_code'] . ')';
                                            }
                                        @endphp
                                        <span class="text-xs bg-blue-100 text-blue-800 px-1 py-0.5 rounded">{{ $rateLabel }}</span>
                                        <span class="text-gray-600 text-xs">IVA</span>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-semibold text-sm">€ {{ number_format($vat['vat_amount'], 2, ',', '.') }}</div>
                                        <div class="text-xs text-gray-500">su € {{ number_format($vat['taxable_amount'], 2, ',', '.') }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <div class="border-b border-gray-200 mb-3"></div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-xs uppercase tracking-wider text-gray-500 mb-1">TOTALE IVA</div>
                            <div class="text-xl font-bold text-gray-800" id="totalVat">€ {{ number_format($totalVat, 2, ',', '.') }}</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                            <div class="text-xs uppercase tracking-wider text-gray-500 mb-1">TOTALE FATTURA</div>
                            <div class="text-2xl font-bold text-green-600" id="totalInvoice">€ {{ number_format($importoTotale, 2, ',', '.') }}</div>
                        </div>
                    </div>

                    <input type="hidden" id="importo_totale" name="importo_totale" value="{{ $importoTotale }}">
                    <input type="hidden" id="vat_summary_input" name="vat_summary" value='@json($vatSummary)'>
                </div>
            </div>
        </div>

        <!-- RIGHE FATTURA -->
        <div class="mt-6">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-list text-blue-500 mr-2"></i> Righe Fattura
                </h3>
                @if($is_manual)
                <button type="button" id="addRowBtn" class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-3 py-1 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                    <i class="fas fa-plus"></i> Aggiungi riga
                </button>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full border rounded-lg" id="invoiceRowsTable">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-2 py-2 text-left text-xs font-medium" style="width:80px;">Codice</th>
                            <th class="px-2 py-2 text-left text-xs font-medium" style="width:250px;">Descrizione <span class="text-red-500">*</span></th>
                            <th class="px-2 py-2 text-right text-xs font-medium" style="width:90px;">Qtà</th>
                            <th class="px-2 py-2 text-right text-xs font-medium" style="width:100px;">Prezzo Unit.</th>
                            <th class="px-2 py-2 text-right text-xs font-medium" style="width:80px;">Sconto%</th>
                            <th class="px-2 py-2 text-left text-xs font-medium" style="width:120px;">Aliquota IVA</th>
                            <th class="px-2 py-2 text-right text-xs font-medium" style="width:110px;">Imponibile</th>
                            <th class="px-2 py-2 text-left text-xs font-medium" style="width:160px;">Centro Costo</th>
                            <th class="px-2 py-2 text-left text-xs font-medium" style="width:160px;">Servizio</th>
                            <th class="px-2 py-2 text-center text-xs font-medium" style="width:50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="rowsContainer">
                        @foreach($rows as $index => $row)
                        <tr class="border-b hover:bg-gray-50" data-index="{{ $index }}">
                            <td class="px-2 py-1" style="width:80px;">
                                <input type="text" name="rows[{{ $index }}][code]" 
                                    value="{{ old("rows.{$index}.code", $row['code'] ?? '') }}"
                                    class="w-full px-1 py-1 text-sm border rounded-md row-code"
                                    {{ !$is_manual ? 'readonly' : '' }}>
                                <input type="hidden" name="rows[{{ $index }}][id]" value="{{ $row['id'] ?? '' }}">
                            </td>
                            <td class="px-2 py-1" style="width:250px;">
                                <textarea name="rows[{{ $index }}][description]" rows="2" 
                                        class="w-full px-1 py-1 text-sm border rounded-md resize-y row-description"
                                        {{ !$is_manual ? 'readonly' : '' }}>{{ old("rows.{$index}.description", $row['description']) }}</textarea>
                                @error("rows.{$index}.description") <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                            </td>
                            <td class="px-2 py-1" style="width:90px;">
                                <input type="text" inputmode="decimal" name="rows[{{ $index }}][quantity]" 
                                    value="{{ old("rows.{$index}.quantity", number_format($row['quantity'], 2, '.', '')) }}"
                                    class="w-full px-1 py-1 text-sm border rounded-md text-right row-quantity"
                                    {{ !$is_manual ? 'readonly' : '' }}>
                                @error("rows.{$index}.quantity") <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                            </td>
                            <td class="px-2 py-1" style="width:100px;">
                                <input type="text" inputmode="decimal" name="rows[{{ $index }}][unit_price]" 
                                    value="{{ old("rows.{$index}.unit_price", number_format($row['unit_price'], 3, '.', '')) }}"
                                    class="w-full px-1 py-1 text-sm border rounded-md text-right row-unit-price"
                                    {{ !$is_manual ? 'readonly' : '' }}>
                                @error("rows.{$index}.unit_price") <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                            </td>
                            <td class="px-2 py-1" style="width:80px;">
                                <input type="number" step="0.01" name="rows[{{ $index }}][discount_percentage]" 
                                    value="{{ old("rows.{$index}.discount_percentage", $row['discount_percentage']) }}"
                                    class="w-full px-1 py-1 text-sm border rounded-md text-right row-discount"
                                    {{ !$is_manual ? 'readonly' : '' }}>
                            </td>
                            <td class="px-2 py-1" style="width:120px;">
                                <select name="rows[{{ $index }}][vat_rate_id]" 
                                        class="w-full px-1 py-1 text-sm border rounded-md row-vat"
                                        {{ !$is_manual ? 'disabled' : '' }}>
                                    <option value="">Seleziona IVA</option>
                                    @foreach($vatRates as $vat)
                                        @php
                                            $vatId = $vat['id'] ?? '';
                                            $vatRate = $vat['rate'] ?? 0;
                                            $vatDescription = $vat['description'] ?? '';
                                            $vatSdiNature = $vat['sdi_nature'] ?? '';
                                            $selected = ($row['vat_rate_id'] ?? '') == $vatId ? 'selected' : '';
                                            $displayText = ($vatRate * 100) . '% - ' . $vatDescription;
                                            if (!empty($vatSdiNature)) {
                                                $displayText .= ' (Cod. ' . $vatSdiNature . ')';
                                            }
                                        @endphp
                                        <option value="{{ $vatId }}" {{ $selected }}>
                                            {{ $displayText }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="rows[{{ $index }}][vat_rate]" value="{{ $row['vat_rate'] ?? 0 }}">
                            </td>
                            <td class="px-2 py-1" style="width:110px;">
                                <input type="text" readonly value="{{ number_format($row['taxable_amount'], 2, ',', '.') }}"
                                    class="w-full px-1 py-1 text-sm border rounded-md text-right bg-gray-100 font-semibold row-taxable">
                                <input type="hidden" name="rows[{{ $index }}][taxable_amount]" value="{{ $row['taxable_amount'] }}">
                            </td>
                            <td class="px-2 py-1" style="width:160px;">
                                <input type="text" name="rows[{{ $index }}][cost_center_search]" 
                                    placeholder="Cerca centro..." 
                                    value="{{ old("rows.{$index}.cost_center_search", isset($costCenters[$row['id_cost_center']]) ? $costCenters[$row['id_cost_center']] : '') }}"
                                    class="w-full px-1 py-1 text-sm border rounded-md cost-center-search" 
                                    data-index="{{ $index }}" autocomplete="off"
                                    {{ !$is_manual ? 'readonly' : '' }}>
                                <input type="hidden" name="rows[{{ $index }}][id_cost_center]" value="{{ $row['id_cost_center'] ?? '' }}" class="cost-center-id">
                                <div class="cost-center-dropdown hidden absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto"></div>
                            </td>
                            <td class="px-2 py-1" style="width:160px;">
                                <input type="text" name="rows[{{ $index }}][service_search]" 
                                    placeholder="Cerca servizio..." 
                                    value="{{ old("rows.{$index}.service_search", isset($services[$row['id_service']]) ? $services[$row['id_service']] : '') }}"
                                    class="w-full px-1 py-1 text-sm border rounded-md service-search" 
                                    data-index="{{ $index }}" autocomplete="off"
                                    {{ !$is_manual ? 'readonly' : '' }}>
                                <input type="hidden" name="rows[{{ $index }}][id_service]" value="{{ $row['id_service'] ?? '' }}" class="service-id">
                                <div class="service-dropdown hidden absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto"></div>
                            </td>
                            <td class="px-2 py-1 text-center" style="width:50px;">
                                @if($is_manual)
                                <button type="button" class="remove-row-btn text-red-500 hover:text-red-700 transition-colors" data-index="{{ $index }}">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SCADENZE PAGAMENTO -->
        <div class="mt-6">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-calendar-alt text-purple-500 mr-2"></i> Scadenze Pagamento
                </h3>
                @if($is_manual)
                <button type="button" id="addPaymentBtn" class="bg-gradient-to-r from-purple-500 to-purple-600 text-white px-3 py-1 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                    <i class="fas fa-plus"></i> Aggiungi scadenza
                </button>
                @endif
            </div>

            @if(count($payments) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full border rounded-lg" id="paymentsTable">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium w-32">Data Scadenza</th>
                                <th class="px-3 py-2 text-right text-xs font-medium w-32">Importo (€)</th>
                                <th class="px-3 py-2 text-left text-xs font-medium w-40">Metodo</th>
                                <th class="px-3 py-2 text-left text-xs font-medium w-48">IBAN</th>
                                <th class="px-3 py-2 text-center text-xs font-medium w-16"></th>
                            </tr>
                        </thead>
                        <tbody id="paymentsContainer">
                            @foreach($payments as $pIndex => $payment)
                            <tr class="border-b hover:bg-gray-50" data-payment-index="{{ $pIndex }}">
                                <td class="px-3 py-2">
                                    <input type="date" name="payments[{{ $pIndex }}][due_date]" 
                                           value="{{ old("payments.{$pIndex}.due_date", $payment['due_date']) }}"
                                           class="w-full px-2 py-1 text-sm border rounded-md payment-date"
                                           {{ !$is_manual ? 'readonly' : '' }}>
                                    <input type="hidden" name="payments[{{ $pIndex }}][id]" value="{{ $payment['id'] ?? '' }}">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" step="0.01" name="payments[{{ $pIndex }}][amount]" 
                                           value="{{ old("payments.{$pIndex}.amount", $payment['amount']) }}"
                                           class="w-full px-2 py-1 text-sm border rounded-md text-right payment-amount"
                                           {{ !$is_manual ? 'readonly' : '' }}>
                                </td>
                                <td class="px-3 py-2">
                                    <select name="payments[{{ $pIndex }}][payment_method]" 
                                            class="w-full px-2 py-1 text-sm border rounded-md payment-method"
                                            {{ !$is_manual ? 'disabled' : '' }}>
                                        <option value="">— nessuna —</option>
                                        @foreach($paymentMethods as $code => $label)
                                            <option value="{{ $code }}" {{ ($payment['payment_method'] ?? '') == $code ? 'selected' : '' }}>
                                                {{ $code }} — {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-3 py-2">
                                    <input type="text" name="payments[{{ $pIndex }}][iban]" 
                                           value="{{ old("payments.{$pIndex}.iban", $payment['iban'] ?? '') }}"
                                           placeholder="IT00 XXXX..."
                                           class="w-full px-2 py-1 text-sm border rounded-md payment-iban"
                                           {{ !$is_manual ? 'readonly' : '' }}>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    @if($is_manual && count($payments) > 1)
                                    <button type="button" class="remove-payment-btn text-red-500 hover:text-red-700 transition-colors" data-payment-index="{{ $pIndex }}">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td class="px-3 py-2 text-right font-bold" colspan="1">Totale Scadenze:</td>
                                <td class="px-3 py-2 text-right font-bold" colspan="1" id="totalPayments">
                                    € {{ number_format(array_sum(array_column($payments, 'amount')), 2, ',', '.') }}
                                </td>
                                <td colspan="4"></td>
                            </tr>
                            @php
                                $totalPayments = array_sum(array_column($payments, 'amount'));
                                $diff = $importoTotale - $totalPayments;
                            @endphp
                            @if(abs($diff) > 0.01 && $importoTotale > 0)
                            <tr id="paymentDifferenceRow">
                                <td class="px-3 py-2 text-right text-orange-600" colspan="1">⚠️ Differenza:</td>
                                <td class="px-3 py-2 text-right text-orange-600 font-bold" colspan="1" id="paymentDifference">
                                    € {{ number_format($diff, 2, ',', '.') }}
                                </td>
                                <td colspan="4" class="text-xs text-orange-500 px-3">L'importo totale delle scadenze non corrisponde al totale fattura</td>
                            </tr>
                            @endif
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="text-center text-gray-500 py-4 bg-gray-50 rounded-lg border border-dashed">
                    <i class="fas fa-calendar-alt mr-2"></i> Nessuna scadenza inserita
                    @if($is_manual)
                    <button type="button" id="addPaymentBtn" class="ml-3 text-purple-600 hover:text-purple-700 underline">
                        Aggiungi scadenza
                    </button>
                    @endif
                </div>
            @endif
        </div>
        
        <!-- Bottoni -->
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.invoices-sent.index') }}" 
               class="px-4 py-2 rounded-lg shadow-md transition-all duration-200 bg-gray-200 text-gray-700 hover:bg-gray-300">
                Annulla
            </a>
            <button type="submit" 
                    class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-4 py-2 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                <i class="fas fa-save mr-2"></i> Aggiorna Fattura
            </button>
        </div>
    </form>
    
    <!-- MODALE CREAZIONE NUOVO CLIENTE -->
    <div id="customerModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" id="customerModalOverlay"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-6 pt-5 pb-4 border-b">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">Nuovo Cliente</h3>
                        <button type="button" id="closeCustomerModalBtn" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                
                <div class="px-6 py-4">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ragione Sociale / Nome <span class="text-red-500">*</span></label>
                            <input type="text" id="newCustomerName" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            <p id="newCustomerNameError" class="text-xs text-red-500 mt-1 hidden"></p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Partita IVA</label>
                                <input type="text" id="newCustomerPiva" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Codice Fiscale</label>
                                <input type="text" id="newCustomerCf" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" id="newCustomerEmail" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Telefono</label>
                                <input type="text" id="newCustomerPhone" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Indirizzo</label>
                            <input type="text" id="newCustomerAddress" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">CAP</label>
                                <input type="text" id="newCustomerCap" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Città</label>
                                <input type="text" id="newCustomerCity" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Provincia</label>
                                <input type="text" id="newCustomerProvince" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-6 py-3 flex justify-end gap-3">
                    <button type="button" id="closeCustomerModalBtn2" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                        Annulla
                    </button>
                    <button type="button" id="saveCustomerBtn" class="px-4 py-2 bg-lime-500 text-white rounded-md hover:bg-lime-600 transition-colors">
                        <i class="fas fa-save mr-2"></i> Crea Cliente
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .totals-card {
        background: white;
        border-radius: 0.5rem;
        padding: 1rem;
        height: 100%;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
    }

    /* Nota: non più strettamente necessaria dopo il fix "portal" (i
       dropdown vengono spostati in <body> con position:fixed), ma
       innocua da mantenere. */
    #invoiceRowsTable td {
        position: relative;
    }
    
    .cost-center-dropdown, .service-dropdown {
        position: absolute;
        z-index: 9999;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        max-height: 250px;
        overflow-y: auto;
        width: 100%;
        margin-top: 2px;
    }
    
    .cost-center-dropdown div, .service-dropdown div {
        padding: 0.5rem 0.75rem;
        cursor: pointer;
        transition: background 0.15s;
        font-size: 0.875rem;
    }
    
    .cost-center-dropdown div:hover, .service-dropdown div:hover {
        background-color: #f3f4f6;
    }
    
    .relative {
        position: relative;
    }
    
    select:disabled, input:readonly {
        background-color: #f9fafb;
        color: #6b7280;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // =============================================
    // DATI DA PHP
    // =============================================
    const vatRates = @json($vatRates);
    const isManual = @json($is_manual);
    const invoiceId = @json($invoice->id);
    
    // =============================================
    // DOM ELEMENTI
    // =============================================
    const rowsContainer = document.getElementById('rowsContainer');
    const paymentsContainer = document.getElementById('paymentsContainer');
    const addRowBtn = document.getElementById('addRowBtn');
    const addPaymentBtn = document.getElementById('addPaymentBtn');
    const ownershipSelect = document.getElementById('id_ownership');
    const seriesSelect = document.getElementById('selected_series_id');
    
    // =============================================
    // FUNZIONI UTILITY
    // =============================================
    function formatEuro(value) {
        return '€ ' + parseFloat(value || 0).toFixed(2).replace('.', ',');
    }

    function formatNumber(value, decimals = 2) {
        return parseFloat(value || 0).toFixed(decimals).replace('.', ',');
    }

    function parseNumber(value) {
        return parseFloat(String(value).replace(',', '.')) || 0;
    }

    function findDefaultVatId() {
        const rates = Array.isArray(vatRates) ? vatRates : [];
        for (let v of rates) {
            if (v && v.rate === 0.22 && !v.sdi_nature) {
                return v.id;
            }
        }
        return rates.length > 0 && rates[0] ? rates[0].id : null;
    }

    // =============================================
    // POSIZIONAMENTO DROPDOWN (pattern "portal")
    // =============================================
    // FIX: il contenitore della tabella righe ha overflow-x-auto, che per
    // specifica CSS forza anche overflow-y:auto sullo stesso elemento.
    // Questo crea un contesto di clipping che rompe il posizionamento
    // "position: absolute" dei dropdown dentro le celle, facendoli comparire
    // in fondo alla pagina invece che sotto il campo di ricerca.
    // Soluzione: spostiamo il dropdown dentro <body> (portal) e lo
    // posizioniamo con position:fixed calcolando le coordinate reali
    // dell'input tramite getBoundingClientRect(), così non dipende più da
    // nessun antenato con overflow.
    function positionDropdown(input, dropdown) {
        const rect = input.getBoundingClientRect();
        dropdown.style.position = 'fixed';
        dropdown.style.top = (rect.bottom + 2) + 'px';
        dropdown.style.left = rect.left + 'px';
        dropdown.style.width = rect.width + 'px';
        dropdown.style.margin = '0';
    }

    function showDropdownPortal(input, dropdown) {
        if (dropdown.parentNode !== document.body) {
            document.body.appendChild(dropdown);
        }
        positionDropdown(input, dropdown);
        dropdown.classList.remove('hidden');
        dropdown.style.display = 'block';
    }

    function hideDropdownPortal(dropdown) {
        dropdown.classList.add('hidden');
        dropdown.style.display = 'none';
    }

    // =============================================
    // GENERAZIONE HTML RIGHE
    // =============================================
    function generateRowHtml(index, data = null) {
        const defaultVatId = findDefaultVatId();
        const rates = Array.isArray(vatRates) ? vatRates : [];
        
        const vatOptions = rates.map(v => {
            if (!v) return '';
            const ratePercent = v.rate_percent !== undefined ? v.rate_percent : (v.rate ? v.rate * 100 : 0);
            const display = ratePercent + '% - ' + (v.description || 'IVA') + (v.sdi_nature ? ' (Cod. ' + v.sdi_nature + ')' : '');
            const selected = (data && data.vat_rate_id == v.id) || (!data && defaultVatId == v.id) ? 'selected' : '';
            return `<option value="${v.id || ''}" ${selected}>${display}</option>`;
        }).filter(opt => opt !== '').join('');

        const vatOptionsHtml = vatOptions || '<option value="">Nessuna IVA disponibile</option>';

        const row = data || {
            code: '',
            description: '',
            quantity: 1,
            unit_price: 0,
            discount_percentage: 0,
            vat_rate_id: defaultVatId,
            id_cost_center: '',
            id_service: '',
        };

        const quantity = parseNumber(row.quantity);
        const unitPrice = parseNumber(row.unit_price);
        const discount = parseNumber(row.discount_percentage);
        const taxable = quantity * unitPrice * (1 - discount / 100);

        return `
            <tr class="border-b hover:bg-gray-50" data-index="${index}">
                <td class="px-2 py-1" style="width:80px;">
                    <input type="text" name="rows[${index}][code]" value="${row.code || ''}" class="w-full px-1 py-1 text-sm border rounded-md row-code">
                </td>
                <td class="px-2 py-1" style="width:250px;">
                    <textarea name="rows[${index}][description]" rows="2" class="w-full px-1 py-1 text-sm border rounded-md resize-y row-description">${row.description || ''}</textarea>
                </td>
                <td class="px-2 py-1" style="width:90px;">
                    <input type="text" inputmode="decimal" name="rows[${index}][quantity]" value="${formatNumber(quantity, 2)}" class="w-full px-1 py-1 text-sm border rounded-md text-right row-quantity">
                </td>
                <td class="px-2 py-1" style="width:100px;">
                    <input type="text" inputmode="decimal" name="rows[${index}][unit_price]" value="${formatNumber(unitPrice, 3)}" class="w-full px-1 py-1 text-sm border rounded-md text-right row-unit-price">
                </td>
                <td class="px-2 py-1" style="width:80px;">
                    <input type="number" step="0.01" name="rows[${index}][discount_percentage]" value="${row.discount_percentage || 0}" class="w-full px-1 py-1 text-sm border rounded-md text-right row-discount">
                </td>
                <td class="px-2 py-1" style="width:120px;">
                    <select name="rows[${index}][vat_rate_id]" class="w-full px-1 py-1 text-sm border rounded-md row-vat">${vatOptionsHtml}</select>
                    <input type="hidden" name="rows[${index}][vat_rate]" value="${(vatRates.find(v => v.id == defaultVatId)?.rate || 0.22)}">
                </td>
                <td class="px-2 py-1" style="width:110px;">
                    <input type="text" readonly value="${formatEuro(taxable)}" class="w-full px-1 py-1 text-sm border rounded-md text-right bg-gray-100 font-semibold row-taxable">
                    <input type="hidden" name="rows[${index}][taxable_amount]" value="${taxable}">
                </td>
                <td class="px-2 py-1" style="width:160px;">
                    <input type="text" placeholder="Cerca centro..." class="w-full px-1 py-1 text-sm border rounded-md cost-center-search" data-index="${index}" autocomplete="off">
                    <input type="hidden" name="rows[${index}][id_cost_center]" value="${row.id_cost_center || ''}" class="cost-center-id">
                    <div class="cost-center-dropdown hidden absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto"></div>
                </td>
                <td class="px-2 py-1" style="width:160px;">
                    <input type="text" placeholder="Cerca servizio..." class="w-full px-1 py-1 text-sm border rounded-md service-search" data-index="${index}" autocomplete="off">
                    <input type="hidden" name="rows[${index}][id_service]" value="${row.id_service || ''}" class="service-id">
                    <div class="service-dropdown hidden absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto"></div>
                </td>
                <td class="px-2 py-1 text-center" style="width:50px;">
                    <button type="button" class="remove-row-btn text-red-500 hover:text-red-700 transition-colors" data-index="${index}"><i class="fas fa-trash-alt"></i></button>
                </td>
            </tr>
        `;
    }

    // =============================================
    // GESTIONE RIGHE
    // =============================================
    let rowIndex = document.querySelectorAll('#rowsContainer tr').length;

    function addRow(data = null) {
        const html = generateRowHtml(rowIndex, data);
        const temp = document.createElement('tbody');
        temp.innerHTML = html;
        const rowElement = temp.firstElementChild;
        rowsContainer.appendChild(rowElement);
        
        initCostCenterAutocomplete(rowIndex);
        initServiceAutocomplete(rowIndex);
        
        rowIndex++;
        calculateAllTotals();
    }

    function removeRow(index) {
        const row = rowsContainer.querySelector(`tr[data-index="${index}"]`);
        if (row) {
            // Rimuove eventuali dropdown "portati" in body prima di eliminare la riga
            const ccDropdown = row.querySelector('.cost-center-dropdown');
            const svDropdown = row.querySelector('.service-dropdown');
            if (ccDropdown && ccDropdown.parentNode === document.body) ccDropdown.remove();
            if (svDropdown && svDropdown.parentNode === document.body) svDropdown.remove();
            row.remove();
            calculateAllTotals();
        }
    }

    function calculateRowTotal(index) {
        const row = rowsContainer.querySelector(`tr[data-index="${index}"]`);
        if (!row) return;

        const quantity = parseNumber(row.querySelector('.row-quantity').value);
        const unitPrice = parseNumber(row.querySelector('.row-unit-price').value);
        const discount = parseNumber(row.querySelector('.row-discount').value);

        // 🆕 FIX: sincronizza il campo nascosto vat_rate con la select corrente
        const vatSelect = row.querySelector('.row-vat');
        const vatId = vatSelect ? vatSelect.value : '';
        const vatInfo = vatRates.find(v => v.id == vatId);
        const vatRateDecimal = vatInfo ? vatInfo.rate : 0;
        const vatHidden = row.querySelector('input[name="rows[' + index + '][vat_rate]"]');
        if (vatHidden) vatHidden.value = vatRateDecimal;

        const grossAmount = quantity * unitPrice;
        const discountAmount = grossAmount * (discount / 100);
        const taxable = grossAmount - discountAmount;

        row.querySelector('.row-taxable').value = formatEuro(taxable);
        const hidden = row.querySelector('input[name="rows[' + index + '][taxable_amount]"]');
        if (hidden) hidden.value = taxable;

        calculateAllTotals();
    }

    function calculateAllTotals() {
        let totalTaxable = 0;
        let totalDiscount = 0;
        let totalVat = 0;
        const vatSummary = {};

        const allRows = rowsContainer.querySelectorAll('tr');
        
        if (allRows.length === 0) {
            document.getElementById('totalTaxable').textContent = formatEuro(0);
            document.getElementById('totalDiscount').textContent = '- ' + formatEuro(0);
            document.getElementById('totalVat').textContent = formatEuro(0);
            document.getElementById('totalInvoice').textContent = formatEuro(0);
            document.getElementById('importo_totale').value = 0;
            document.getElementById('vatSummaryItems').innerHTML = '<div class="text-center text-gray-400 text-xs py-2">Nessuna riga inserita</div>';
            document.getElementById('vat_summary_input').value = '[]';
            updatePaymentsTotal();
            return;
        }

        allRows.forEach(row => {
            const quantity = parseNumber(row.querySelector('.row-quantity').value);
            const unitPrice = parseNumber(row.querySelector('.row-unit-price').value);
            const discount = parseNumber(row.querySelector('.row-discount').value);
            const vatId = row.querySelector('.row-vat').value;
            
            const vatRate = vatRates.find(v => v.id == vatId);
            const rate = vatRate ? vatRate.rate : 0;
            const ratePercent = vatRate ? vatRate.rate_percent : 0;
            const sdiNature = vatRate ? vatRate.sdi_nature : '';
            
            const grossAmount = quantity * unitPrice;
            const discountAmount = grossAmount * (discount / 100);
            const taxable = grossAmount - discountAmount;
            const vatAmount = taxable * rate;
            
            totalTaxable += taxable;
            totalDiscount += discountAmount;
            totalVat += vatAmount;
            
            const key = rate + '|' + (sdiNature || 'default');
            if (!vatSummary[key]) {
                vatSummary[key] = {
                    rate: rate,
                    rate_percent: ratePercent,
                    taxable_amount: 0,
                    vat_amount: 0,
                    description: vatRate ? vatRate.description : 'IVA ' + ratePercent + '%',
                    nature_code: sdiNature || null,
                };
            }
            vatSummary[key].taxable_amount += taxable;
            vatSummary[key].vat_amount += vatAmount;
        });

        const totalInvoice = totalTaxable + totalVat;

        document.getElementById('totalTaxable').textContent = formatEuro(totalTaxable);
        document.getElementById('totalDiscount').textContent = '- ' + formatEuro(totalDiscount);
        document.getElementById('totalVat').textContent = formatEuro(totalVat);
        document.getElementById('totalInvoice').textContent = formatEuro(totalInvoice);
        document.getElementById('importo_totale').value = totalInvoice;

        // Riepilogo IVA
        const vatSummaryContainer = document.getElementById('vatSummaryItems');
        vatSummaryContainer.innerHTML = '';
        const sortedVats = Object.values(vatSummary).sort((a, b) => b.rate - a.rate);
        sortedVats.forEach(v => {
            const div = document.createElement('div');
            div.className = 'flex justify-between items-center py-1 text-sm border-b border-gray-100 last:border-0';
            let rateLabel = v.rate_percent + '%';
            if (v.rate === 0 && v.nature_code) {
                rateLabel = '0% (Cod. ' + v.nature_code + ')';
            }
            div.innerHTML = `
                <div class="font-medium text-gray-700">
                    <span class="text-xs bg-blue-100 text-blue-800 px-1 py-0.5 rounded">${rateLabel}</span>
                    <span class="text-gray-600 text-xs">IVA</span>
                </div>
                <div class="text-right">
                    <div class="font-semibold text-sm">${formatEuro(v.vat_amount)}</div>
                    <div class="text-xs text-gray-500">su ${formatEuro(v.taxable_amount)}</div>
                </div>
            `;
            vatSummaryContainer.appendChild(div);
        });

        document.getElementById('vat_summary_input').value = JSON.stringify(Object.values(vatSummary));
        updatePaymentsTotal();
    }

    // =============================================
    // EVENTI RIGHE (DELEGATI)
    // =============================================
    if (isManual) {
        rowsContainer.addEventListener('input', function(e) {
            const target = e.target;
            if (target.classList.contains('row-quantity') || 
                target.classList.contains('row-unit-price') || 
                target.classList.contains('row-discount') || 
                target.classList.contains('row-vat')) {
                const row = target.closest('tr');
                if (row) {
                    const index = parseInt(row.dataset.index);
                    calculateRowTotal(index);
                }
            }
        });

        rowsContainer.addEventListener('change', function(e) {
            if (e.target.classList.contains('row-vat')) {
                const row = e.target.closest('tr');
                if (row) {
                    const index = parseInt(row.dataset.index);
                    calculateRowTotal(index);
                }
            }
        });

        rowsContainer.addEventListener('click', function(e) {
            const removeBtn = e.target.closest('.remove-row-btn');
            if (removeBtn) {
                const index = parseInt(removeBtn.dataset.index);
                removeRow(index);
            }
        });

        if (addRowBtn) {
            addRowBtn.addEventListener('click', function() {
                addRow();
            });
        }
    }

    // =============================================
    // GESTIONE SCADENZE
    // =============================================
    let paymentIndex = document.querySelectorAll('#paymentsContainer tr').length;

    function addPayment(data = null) {
        const today = '{{ date('Y-m-d') }}';
        const html = `
            <tr class="border-b hover:bg-gray-50" data-payment-index="${paymentIndex}">
                <td class="px-3 py-2">
                    <input type="date" name="payments[${paymentIndex}][due_date]" value="${data ? data.due_date : today}" class="w-full px-2 py-1 text-sm border rounded-md payment-date">
                </td>
                <td class="px-3 py-2">
                    <input type="text" inputmode="decimal" name="payments[${paymentIndex}][amount]" value="${data ? data.amount : 0}" class="w-full px-2 py-1 text-sm border rounded-md text-right payment-amount">
                </td>
                <td class="px-3 py-2">
                    <select name="payments[${paymentIndex}][payment_method]" class="w-full px-2 py-1 text-sm border rounded-md payment-method">
                        <option value="">— nessuna —</option>
                        @foreach($paymentMethods as $code => $label)
                            <option value="{{ $code }}">{{ $code }} — {{ $label }}</option>
                        @endforeach
                    </select>
                </td>
                <td class="px-3 py-2">
                    <input type="text" name="payments[${paymentIndex}][iban]" placeholder="IT00 XXXX..." class="w-full px-2 py-1 text-sm border rounded-md payment-iban">
                </td>
                <td class="px-3 py-2 text-center">
                    <button type="button" class="remove-payment-btn text-red-500 hover:text-red-700 transition-colors" data-payment-index="${paymentIndex}">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            </tr>
        `;
        const temp = document.createElement('tbody');
        temp.innerHTML = html;
        paymentsContainer.appendChild(temp.firstElementChild);
        paymentIndex++;
        updatePaymentsTotal();
    }

    function removePayment(index) {
        const row = paymentsContainer.querySelector(`tr[data-payment-index="${index}"]`);
        if (row) row.remove();
        updatePaymentsTotal();
    }

    function updatePaymentsTotal() {
        const amountInputs = paymentsContainer.querySelectorAll('.payment-amount');
        let total = 0;
        amountInputs.forEach(input => {
            total += parseNumber(input.value);
        });
        
        document.getElementById('totalPayments').textContent = formatEuro(total);
        
        const invoiceTotal = parseNumber(document.getElementById('importo_totale').value);
        const diff = invoiceTotal - total;
        
        const diffRow = document.getElementById('paymentDifferenceRow');
        const diffSpan = document.getElementById('paymentDifference');
        if (Math.abs(diff) > 0.01) {
            if (diffRow) diffRow.style.display = '';
            if (diffSpan) diffSpan.textContent = formatEuro(diff);
        } else {
            if (diffRow) diffRow.style.display = 'none';
        }
    }

    if (isManual) {
        paymentsContainer.addEventListener('input', function(e) {
            if (e.target.classList.contains('payment-amount')) {
                updatePaymentsTotal();
            }
        });

        paymentsContainer.addEventListener('click', function(e) {
            const removeBtn = e.target.closest('.remove-payment-btn');
            if (removeBtn) {
                const index = parseInt(removeBtn.dataset.paymentIndex);
                removePayment(index);
            }
        });

        if (addPaymentBtn) {
            addPaymentBtn.addEventListener('click', function() {
                addPayment();
            });
        }
    }

    // =============================================
    // AUTOCOMPLETE CENTRO COSTO
    // =============================================
    function initCostCenterAutocomplete(index) {
        const row = rowsContainer.querySelector(`tr[data-index="${index}"]`);
        if (!row) return;
        
        const searchInput = row.querySelector('.cost-center-search');
        const dropdown = row.querySelector('.cost-center-dropdown');
        const hiddenInput = row.querySelector('.cost-center-id');
        
        if (!searchInput || !dropdown) return;
        
        let timeout = null;
        
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            if (query.length < 2) {
                hideDropdownPortal(dropdown);
                return;
            }
            
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                fetch('/admin/invoices-sent/api/search-cost-centers?q=' + encodeURIComponent(query))
                    .then(r => r.json())
                    .then(data => {
                        dropdown.innerHTML = '';
                        if (data.length === 0) {
                            dropdown.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500 text-center">Nessun centro trovato</div>';
                        } else {
                            data.forEach(item => {
                                const div = document.createElement('div');
                                div.className = 'px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0';
                                div.textContent = item.name;
                                div.addEventListener('click', function() {
                                    searchInput.value = item.name;
                                    hiddenInput.value = item.id;
                                    hideDropdownPortal(dropdown);
                                });
                                dropdown.appendChild(div);
                            });
                        }
                        showDropdownPortal(searchInput, dropdown);
                    });
            }, 300);
        });
        
        searchInput.addEventListener('blur', function() {
            setTimeout(() => hideDropdownPortal(dropdown), 200);
        });
        
        searchInput.addEventListener('focus', function() {
            if (this.value.trim().length >= 2) {
                showDropdownPortal(searchInput, dropdown);
            }
        });

        // Riposiziona il dropdown se l'utente scrolla la pagina/tabella
        // (con overflow-x-auto) mentre il dropdown è aperto.
        window.addEventListener('scroll', function() {
            if (!dropdown.classList.contains('hidden')) {
                positionDropdown(searchInput, dropdown);
            }
        }, true);
        window.addEventListener('resize', function() {
            if (!dropdown.classList.contains('hidden')) {
                positionDropdown(searchInput, dropdown);
            }
        });
    }

    // =============================================
    // AUTOCOMPLETE SERVIZI
    // =============================================
    function initServiceAutocomplete(index) {
        const row = rowsContainer.querySelector(`tr[data-index="${index}"]`);
        if (!row) {
            console.log('❌ Row not found for index:', index);
            return;
        }
        
        const searchInput = row.querySelector('.service-search');
        const dropdown = row.querySelector('.service-dropdown');
        const hiddenInput = row.querySelector('.service-id');
        
        if (!searchInput || !dropdown) {
            console.log('❌ Service search input not found for index:', index);
            return;
        }
        
        console.log('✅ Initializing service autocomplete for index:', index);
        
        let timeout = null;
        
        searchInput.addEventListener('input', function(e) {
            const query = this.value.trim();
            console.log('🔍 Service search input:', query);
            
            if (query.length < 2) {
                hideDropdownPortal(dropdown);
                return;
            }
            
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                // URL CORRETTO per la ricerca servizi
                const url = '/admin/invoices-sent/api/search-services?q=' + encodeURIComponent(query);
                console.log('📡 Fetching services from:', url);
                
                fetch(url)
                    .then(response => {
                        console.log('📡 Response status:', response.status);
                        if (!response.ok) {
                            throw new Error('HTTP error! status: ' + response.status);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('📦 Services data received:', data);
                        dropdown.innerHTML = '';
                        
                        if (!data || data.length === 0) {
                            dropdown.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500 text-center">Nessun servizio trovato</div>';
                        } else {
                            data.forEach(item => {
                                const div = document.createElement('div');
                                div.className = 'px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0';
                                
                                let html = `<div class="font-medium text-gray-800">${item.name}</div>`;
                                if (item.descr_fattura) {
                                    html += `<div class="text-xs text-gray-500">${item.descr_fattura}</div>`;
                                }
                                if (item.prezzo_un && parseFloat(item.prezzo_un) > 0) {
                                    html += `<div class="text-xs text-green-600 font-semibold">€ ${parseFloat(item.prezzo_un).toFixed(3)}</div>`;
                                }
                                div.innerHTML = html;
                                
                                div.addEventListener('click', function() {
                                    console.log('✅ Selected service:', item);
                                    searchInput.value = item.name;
                                    hiddenInput.value = item.id;
                                    hideDropdownPortal(dropdown);
                                    
                                    // Aggiorna il prezzo unitario se presente
                                    if (item.prezzo_un && parseFloat(item.prezzo_un) > 0) {
                                        const unitPriceInput = row.querySelector('.row-unit-price');
                                        if (unitPriceInput) {
                                            unitPriceInput.value = parseFloat(item.prezzo_un).toFixed(3);
                                            // Ricalcola il totale
                                            const rowIndex = parseInt(row.dataset.index);
                                            calculateRowTotal(rowIndex);
                                        }
                                    }
                                    
                                    // Aggiorna l'IVA se presente
                                    if (item.vat_rate_id) {
                                        const vatSelect = row.querySelector('.row-vat');
                                        if (vatSelect) {
                                            vatSelect.value = item.vat_rate_id;
                                            // Ricalcola il totale
                                            const rowIndex = parseInt(row.dataset.index);
                                            calculateRowTotal(rowIndex);
                                        }
                                    }
                                    
                                    // Aggiorna la descrizione se presente
                                    if (item.descr_fattura) {
                                        const descTextarea = row.querySelector('.row-description');
                                        if (descTextarea && !descTextarea.value.trim()) {
                                            descTextarea.value = item.descr_fattura;
                                        }
                                    }
                                });
                                
                                dropdown.appendChild(div);
                            });
                        }
                        
                        showDropdownPortal(searchInput, dropdown);
                    })
                    .catch(error => {
                        console.error('❌ Error fetching services:', error);
                        dropdown.innerHTML = '<div class="px-3 py-2 text-sm text-red-500 text-center">Errore nel caricamento dei servizi</div>';
                        showDropdownPortal(searchInput, dropdown);
                    });
            }, 300);
        });
        
        searchInput.addEventListener('blur', function() {
            setTimeout(() => hideDropdownPortal(dropdown), 200);
        });
        
        searchInput.addEventListener('focus', function() {
            const query = this.value.trim();
            if (query.length >= 2) {
                // Trigger una ricerca quando l'input riceve focus
                this.dispatchEvent(new Event('input'));
            }
        });
        
        // Gestione del tasto ESC per chiudere il dropdown
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                hideDropdownPortal(dropdown);
            }
        });

        window.addEventListener('scroll', function() {
            if (!dropdown.classList.contains('hidden')) {
                positionDropdown(searchInput, dropdown);
            }
        }, true);
        window.addEventListener('resize', function() {
            if (!dropdown.classList.contains('hidden')) {
                positionDropdown(searchInput, dropdown);
            }
        });
    }

    // =============================================
    // GESTIONE PROPRIETÀ E SEZIONALI
    // =============================================
    if (isManual && ownershipSelect) {
        ownershipSelect.addEventListener('change', function() {
            const idOwnership = this.value;
            seriesSelect.innerHTML = '<option value="">Caricamento sezionali...</option>';

            if (!idOwnership) {
                seriesSelect.innerHTML = '<option value="">Seleziona prima la proprietà</option>';
                return;
            }

            fetch('/admin/invoices-sent/api/series?id_ownership=' + idOwnership)
                .then(r => r.json())
                .then(data => {
                    seriesSelect.innerHTML = '<option value="">Seleziona sezionale</option>';
                    data.forEach(s => {
                        const option = document.createElement('option');
                        option.value = s.id;
                        option.textContent = s.code + ' - ' + s.name + ' (' + s.year + ')';
                        if (!s.active) {
                            option.disabled = true;
                            option.textContent += ' ❌ DISATTIVATO';
                        }
                        seriesSelect.appendChild(option);
                    });
                    if (data.length === 0) {
                        seriesSelect.innerHTML = '<option value="">Nessun sezionale disponibile</option>';
                    }
                })
                .catch(() => {
                    seriesSelect.innerHTML = '<option value="">Errore nel caricamento</option>';
                });
        });
    }

    // =============================================
    // GESTIONE CLIENTI
    // =============================================
    if (isManual) {
        const customerSearch = document.getElementById('customer_search');
        const customerDropdown = document.getElementById('customerDropdown');
        const selectedCustomerIdInput = document.getElementById('selected_customer_id');
        const selectedCustomerInfo = document.getElementById('selectedCustomerInfo');
        const selectedCustomerNameSpan = document.getElementById('selectedCustomerName');
        const clearCustomerBtn = document.getElementById('clearCustomerBtn');
        let customerSearchTimeout = null;

        function selectCustomer(id, name) {
            selectedCustomerIdInput.value = id;
            customerSearch.value = name;
            selectedCustomerNameSpan.textContent = name;
            selectedCustomerInfo.classList.remove('hidden');
            if (clearCustomerBtn) clearCustomerBtn.classList.remove('hidden');
            customerDropdown.classList.add('hidden');
        }

        customerSearch.addEventListener('input', function() {
            const query = this.value.trim();
            if (query.length < 2) {
                customerDropdown.classList.add('hidden');
                return;
            }

            clearTimeout(customerSearchTimeout);
            customerSearchTimeout = setTimeout(() => {
                fetch('/admin/invoices-sent/api/search-customers?q=' + encodeURIComponent(query))
                    .then(r => r.json())
                    .then(data => {
                        customerDropdown.innerHTML = '';
                        if (data.length === 0) {
                            customerDropdown.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500 text-center">Nessun risultato trovato</div>';
                        } else {
                            data.forEach(item => {
                                const div = document.createElement('div');
                                div.className = 'px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0';
                                div.innerHTML = `
                                    <div class="font-medium text-gray-800">${item.name}</div>
                                    ${item.piva ? `<div class="text-xs text-gray-500">P.IVA: ${item.piva}</div>` : ''}
                                `;
                                div.addEventListener('click', function() {
                                    selectCustomer(item.id, item.name);
                                });
                                customerDropdown.appendChild(div);
                            });
                        }
                        customerDropdown.classList.remove('hidden');
                    });
            }, 300);
        });

        customerSearch.addEventListener('blur', function() {
            setTimeout(() => customerDropdown.classList.add('hidden'), 200);
        });

        customerSearch.addEventListener('focus', function() {
            if (this.value.trim().length >= 2) {
                customerDropdown.classList.remove('hidden');
            }
        });

        if (clearCustomerBtn) {
            clearCustomerBtn.addEventListener('click', function() {
                selectedCustomerIdInput.value = '';
                customerSearch.value = '';
                selectedCustomerInfo.classList.add('hidden');
                this.classList.add('hidden');
            });
        }
    }

    // =============================================
    // MODALE CREAZIONE CLIENTE
    // =============================================
    if (isManual) {
        const customerModal = document.getElementById('customerModal');
        const openCustomerModalBtn = document.getElementById('openCustomerModalBtn');
        const closeCustomerModalBtns = document.querySelectorAll('#closeCustomerModalBtn, #closeCustomerModalBtn2');
        const customerModalOverlay = document.getElementById('customerModalOverlay');
        const saveCustomerBtn = document.getElementById('saveCustomerBtn');

        function openModal() {
            customerModal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function closeModal() {
            customerModal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            document.getElementById('newCustomerName').value = '';
            document.getElementById('newCustomerPiva').value = '';
            document.getElementById('newCustomerCf').value = '';
            document.getElementById('newCustomerEmail').value = '';
            document.getElementById('newCustomerPhone').value = '';
            document.getElementById('newCustomerAddress').value = '';
            document.getElementById('newCustomerCap').value = '';
            document.getElementById('newCustomerCity').value = '';
            document.getElementById('newCustomerProvince').value = '';
            document.getElementById('newCustomerNameError').classList.add('hidden');
        }

        if (openCustomerModalBtn) openCustomerModalBtn.addEventListener('click', openModal);
        closeCustomerModalBtns.forEach(btn => btn.addEventListener('click', closeModal));
        if (customerModalOverlay) customerModalOverlay.addEventListener('click', closeModal);

        if (saveCustomerBtn) {
            saveCustomerBtn.addEventListener('click', function() {
                const name = document.getElementById('newCustomerName').value.trim();
                if (!name) {
                    document.getElementById('newCustomerNameError').textContent = 'Il nome è obbligatorio';
                    document.getElementById('newCustomerNameError').classList.remove('hidden');
                    return;
                }
                document.getElementById('newCustomerNameError').classList.add('hidden');

                const data = {
                    name: name,
                    piva: document.getElementById('newCustomerPiva').value.trim(),
                    cf: document.getElementById('newCustomerCf').value.trim(),
                    email: document.getElementById('newCustomerEmail').value.trim(),
                    telefono: document.getElementById('newCustomerPhone').value.trim(),
                    indirizzo: document.getElementById('newCustomerAddress').value.trim(),
                    cap: document.getElementById('newCustomerCap').value.trim(),
                    comune: document.getElementById('newCustomerCity').value.trim(),
                    provincia: document.getElementById('newCustomerProvince').value.trim(),
                };

                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Creazione...';

                fetch('/admin/invoices-sent/api/store-customer', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(data)
                })
                .then(r => r.json())
                .then(response => {
                    if (response.success) {
                        selectCustomer(response.id, response.name);
                        closeModal();
                    } else {
                        document.getElementById('newCustomerNameError').textContent = response.error || 'Errore nella creazione';
                        document.getElementById('newCustomerNameError').classList.remove('hidden');
                    }
                })
                .catch(error => {
                    document.getElementById('newCustomerNameError').textContent = 'Errore di connessione';
                    document.getElementById('newCustomerNameError').classList.remove('hidden');
                })
                .finally(() => {
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-save mr-2"></i> Crea Cliente';
                });
            });
        }
    }

    // =============================================
    // VALIDAZIONE FORM
    // =============================================
    document.getElementById('invoiceForm').addEventListener('submit', function(e) {
        const rows = rowsContainer.querySelectorAll('tr');
        if (rows.length === 0) {
            e.preventDefault();
            alert('Aggiungi almeno una riga alla fattura.');
            return;
        }
        
        if (isManual) {
            const customerId = document.getElementById('selected_customer_id');
            if (!customerId || !customerId.value) {
                e.preventDefault();
                alert('Seleziona un cliente.');
                return;
            }
        }
        
        let hasEmptyDescription = false;
        rows.forEach(row => {
            const desc = row.querySelector('.row-description');
            if (desc && !desc.value.trim()) {
                hasEmptyDescription = true;
                desc.classList.add('border-red-500');
            } else if (desc) {
                desc.classList.remove('border-red-500');
            }
        });
        
        if (hasEmptyDescription) {
            e.preventDefault();
            alert('Compila la descrizione per tutte le righe.');
            return;
        }
    });

    // =============================================
    // INIZIALIZZAZIONE
    // =============================================
    // Inizializza autocomplete per le righe esistenti
    document.querySelectorAll('#rowsContainer tr').forEach((row, index) => {
        initCostCenterAutocomplete(index);
        initServiceAutocomplete(index);
    });
    
    // Calcola totali iniziali
    setTimeout(function() {
        calculateAllTotals();
    }, 100);
    
    console.log('✅ InvoiceSentEdit inizializzato correttamente');
});
</script>
@endpush