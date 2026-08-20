{{-- @extends('admin.layouts.app')

@section('title', 'Nuova Fattura di Vendita')

@section('content')
    <div>
        @livewire('admin.invoice-sent-create')
    </div>
@endsection --}}


{{-- resources/views/admin/invoice-sent/create.blade.php --}}

@extends('admin.layouts.app')

@section('title', 'Nuova Fattura di Vendita')

@section('content')
<div class="container-fluid">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-plus-circle text-lime-500 mr-2"></i> Nuova Fattura di Vendita
        </h1>
        <a href="{{ route('admin.invoices-sent.index') }}" class="bg-gray-600 hover:bg-gray-900 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
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

    <form id="invoiceForm" action="{{ route('admin.invoices-sent.store') }}" method="POST" class="bg-white rounded-lg shadow p-6">
        @csrf
        
        <!-- Layout a 2 colonne -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-4">
            <!-- Colonna SINISTRA -->
            <div class="lg:col-span-2">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Proprietà <span class="text-red-500">*</span></label>
                        <select id="id_ownership" name="id_ownership" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500 @error('id_ownership') border-red-500 @enderror">
                            <option value="">Seleziona proprietà</option>
                            @foreach($ownerships as $o)
                                <option value="{{ $o->id_proprieta }}" {{ old('id_ownership') == $o->id_proprieta ? 'selected' : '' }}>
                                    [{{ $o->id_proprieta }}] {{ $o->RagAbbrev ?? $o->Rag_Soc_intest }}
                                </option>
                            @endforeach
                        </select>
                        @error('id_ownership') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sezionale <span class="text-red-500">*</span></label>
                        <select id="selected_series_id" name="selected_series_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500 @error('selected_series_id') border-red-500 @enderror">
                            <option value="">Seleziona prima la proprietà</option>
                        </select>
                        @error('selected_series_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        <div id="seriesInfo" class="mt-1 text-xs hidden"></div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo Documento <span class="text-red-500">*</span></label>
                        <select id="type_invoice" name="type_invoice" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500 @error('type_invoice') border-red-500 @enderror">
                            <option value="">Seleziona tipo</option>
                            @foreach($typeDocuments as $code => $label)
                                <option value="{{ $code }}" {{ old('type_invoice', 'TD01') == $code ? 'selected' : '' }}>
                                    {{ $code }} - {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('type_invoice') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">N. Fattura <span class="text-red-500">*</span></label>
                        <input type="text" id="n_invoice" name="n_invoice" value="{{ old('n_invoice') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100" readonly>
                        @error('n_invoice') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">N. Fattura Esterno <span class="text-gray-400 text-xs font-normal">(opzionale)</span></label>
                        <div class="relative">
                            <i class="fas fa-file-invoice absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                            <input type="text" id="n_invoice_ext" name="n_invoice_ext" value="{{ old('n_invoice_ext') }}" placeholder="Numero fattura del fornitore/cliente..." class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500">
                        </div>
                        @error('n_invoice_ext') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
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
                                        class="w-full pl-9 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 @error('selected_customer_id') border-red-500 @enderror"
                                        autocomplete="off">
                                    <input type="hidden" id="selected_customer_id" name="selected_customer_id" value="{{ old('selected_customer_id') }}">
                                    <button type="button" id="clearCustomerBtn" class="absolute right-2 top-2 text-gray-400 hover:text-red-500 hidden">
                                        <i class="fas fa-times-circle text-sm"></i>
                                    </button>
                                </div>
                                <div id="customerDropdown" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto hidden"></div>
                            </div>
                            <button type="button" id="openCustomerModalBtn" class="px-3 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition-colors" title="Nuovo Cliente">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div id="selectedCustomerInfo" class="mt-1 text-xs text-green-600 hidden">
                            <i class="fas fa-check-circle"></i> Cliente selezionato: <span id="selectedCustomerName"></span>
                        </div>
                        @error('selected_customer_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data Fattura <span class="text-red-500">*</span></label>
                        <input type="date" id="data_invoice" name="data_invoice" value="{{ old('data_invoice', date('Y-m-d')) }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500 @error('data_invoice') border-red-500 @enderror">
                        @error('data_invoice') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Causale / Note</label>
                    <textarea id="causale" name="causale" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500" placeholder="Note aggiuntive...">{{ old('causale') }}</textarea>
                </div>
            </div>
            
            <!-- Colonna DESTRA: Card Totali -->
            <div class="lg:col-span-1">
                <div class="totals-card bg-white rounded-lg p-4 shadow border border-gray-200">
                    <div class="grid grid-cols-2 gap-4 mb-3">
                        <div>
                            <div class="text-xs uppercase tracking-wider text-gray-500 mb-1">TOTALE IMPONIBILE</div>
                            <div class="text-xl font-bold text-gray-800" id="totalTaxable">€ 0,00</div>
                        </div>
                        <div>
                            <div class="text-xs uppercase tracking-wider text-gray-500 mb-1">TOTALE SCONTI</div>
                            <div class="text-xl font-bold text-red-500" id="totalDiscount">- € 0,00</div>
                        </div>
                    </div>
                    
                    <div class="border-b border-gray-200 mb-3"></div>
                    
                    <div id="vatSummaryContainer" class="mb-3">
                        <div class="text-xs uppercase tracking-wider text-gray-500 text-center mb-2 pb-1 border-b border-gray-200">DETTAGLIO IVA PER ALIQUOTA</div>
                        <div id="vatSummaryItems" class="space-y-1"></div>
                    </div>
                    
                    <div class="border-b border-gray-200 mb-3"></div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-xs uppercase tracking-wider text-gray-500 mb-1">TOTALE IVA</div>
                            <div class="text-xl font-bold text-gray-800" id="totalVat">€ 0,00</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                            <div class="text-xs uppercase tracking-wider text-gray-500 mb-1">TOTALE FATTURA</div>
                            <div class="text-2xl font-bold text-green-600" id="totalInvoice">€ 0,00</div>
                        </div>
                    </div>

                    <input type="hidden" id="importo_totale" name="importo_totale" value="0">
                    <input type="hidden" id="vat_summary_input" name="vat_summary" value="">
                </div>
            </div>
        </div>
        
        <!-- RIGHE FATTURA -->
        <div class="mt-6">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-list text-blue-500 mr-2"></i> Righe Fattura
                </h3>
                <button type="button" id="addRowBtn" class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-3 py-1 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                    <i class="fas fa-plus"></i> Aggiungi riga
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full border rounded-lg" id="invoiceRowsTable">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-2 py-2 text-left text-xs font-medium" style="width:80px;">Codice</th>
                            <th class="px-2 py-2 text-left text-xs font-medium" style="width:350px;">Descrizione <span class="text-red-500">*</span></th>
                            <th class="px-2 py-2 text-right text-xs font-medium" style="width:90px;">Qtà <span class="text-red-500">*</span></th>
                            <th class="px-2 py-2 text-right text-xs font-medium" style="width:100px;">Prezzo Unit.</th>
                            <th class="px-2 py-2 text-center text-xs font-medium" style="width:70px;">UM</th>
                            <th class="px-2 py-2 text-right text-xs font-medium" style="width:80px;">Sconto%</th>
                            <th class="px-2 py-2 text-left text-xs font-medium" style="width:120px;">Aliquota IVA</th>
                            <th class="px-2 py-2 text-right text-xs font-medium" style="width:110px;">Imponibile</th>
                            <th class="px-2 py-2 text-left text-xs font-medium" style="width:160px;">Centro Costo</th>
                            <th class="px-2 py-2 text-left text-xs font-medium" style="width:160px;">Servizio</th>
                            <th class="px-2 py-2 text-center text-xs font-medium" style="width:50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="rowsContainer">
                        <!-- Le righe verranno aggiunte qui via JavaScript -->
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
                <button type="button" id="addPaymentBtn" class="bg-gradient-to-r from-purple-500 to-purple-600 text-white px-3 py-1 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                    <i class="fas fa-plus"></i> Aggiungi scadenza
                </button>
            </div>

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
                        <!-- Le scadenze verranno aggiunte qui via JavaScript -->
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td class="px-3 py-2 text-right font-bold" colspan="1">Totale Scadenze:</td>
                            <td class="px-3 py-2 text-right font-bold" colspan="1" id="totalPayments">€ 0,00</td>
                            <td colspan="3"></td>
                        </tr>
                        <tr id="paymentDifferenceRow" class="hidden">
                            <td class="px-3 py-2 text-right text-orange-600" colspan="1">⚠️ Differenza:</td>
                            <td class="px-3 py-2 text-right text-orange-600 font-bold" colspan="1" id="paymentDifference">€ 0,00</td>
                            <td colspan="3" class="text-xs text-orange-500">L'importo totale delle scadenze non corrisponde al totale fattura</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        
        <!-- Bottoni -->
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.invoices-sent.index') }}" class="px-4 py-2 rounded-lg shadow-md transition-all duration-200 bg-gray-200 text-gray-700 hover:bg-gray-300">
                Annulla
            </a>
            <button type="submit" class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-4 py-2 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                <i class="fas fa-save mr-2"></i> Salva Fattura
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // =============================================
    // DATI DA PHP
    // =============================================
    const vatRates = @json($vatRates);
    const unitMeasures = @json($unitMeasures);
    const paymentMethods = @json($paymentMethods);
    const today = '{{ date('Y-m-d') }}';
    
    // Debug - verifica dati caricati
    console.log('Aliquote IVA caricate:', vatRates);
    console.log('Unitá di misura caricate:', unitMeasures);
    
    // =============================================
    // STATO
    // =============================================
    let rowIndex = 0;
    let paymentIndex = 0;
    let selectedCustomerId = '{{ old('selected_customer_id') }}';
    let selectedCustomerName = '';
    let companyIban = '';
    let companyBankName = '';

    // =============================================
    // DOM ELEMENTI
    // =============================================
    const rowsContainer = document.getElementById('rowsContainer');
    const paymentsContainer = document.getElementById('paymentsContainer');
    const addRowBtn = document.getElementById('addRowBtn');
    const addPaymentBtn = document.getElementById('addPaymentBtn');
    const ownershipSelect = document.getElementById('id_ownership');
    const seriesSelect = document.getElementById('selected_series_id');
    const seriesInfo = document.getElementById('seriesInfo');
    const nInvoiceInput = document.getElementById('n_invoice');

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
        // Cerca IVA al 22%
        for (let v of rates) {
            if (v && v.rate === 0.22 && !v.sdi_nature) {
                return v.id;
            }
        }
        // Cerca IVA al 22% anche con sdi_nature
        for (let v of rates) {
            if (v && v.rate === 0.22) {
                return v.id;
            }
        }
        // Fallback: primo IVA attiva
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
        
        // Genera opzioni IVA
        let vatOptionsHtml = '';
        if (rates.length === 0) {
            vatOptionsHtml = '<option value="">Nessuna IVA disponibile</option>';
        } else {
            vatOptionsHtml = rates.map(v => {
                if (!v) return '';
                const ratePercent = v.rate_percent !== undefined ? v.rate_percent : (v.rate ? v.rate * 100 : 0);
                let display = ratePercent + '% - ' + (v.description || 'IVA');
                if (v.sdi_nature) {
                    display += ' (Cod. ' + v.sdi_nature + ')';
                }
                const selected = (data && data.vat_rate_id == v.id) || (!data && defaultVatId == v.id) ? 'selected' : '';
                return `<option value="${v.id || ''}" ${selected}>${display}</option>`;
            }).filter(opt => opt !== '').join('');
        }

        // Genera opzioni unità di misura
        const umOptions = unitMeasures.map(um => {
            const selected = (data && data.unit_measure == um.codice) || (!data && um.codice == 'pz') ? 'selected' : '';
            return `<option value="${um.codice}" ${selected}>${um.nome} (${um.codice})</option>`;
        }).join('');

        const row = data || {
            code: '',
            description: '',
            quantity: 1,
            unit_price: 0,
            unit_measure: 'pz',
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
                <td class="px-2 py-1 align-top" style="width:80px;">
                    <input type="text" name="rows[${index}][code]" value="${row.code || ''}" class="w-full px-1 py-1 text-sm border rounded-md row-code">
                </td>
                <td class="px-2 py-1 align-top" style="width:350px;">
                    <textarea name="rows[${index}][description]" rows="2" class="w-full px-1 py-1 text-sm border rounded-md resize-y row-description">${row.description || ''}</textarea>
                </td>
                <td class="px-2 py-1 align-top" style="width:90px;">
                    <input type="text" inputmode="decimal" name="rows[${index}][quantity]" value="${formatNumber(quantity, 2)}" class="w-full px-1 py-1 text-sm border rounded-md text-right row-quantity">
                </td>
                <td class="px-2 py-1 align-top" style="width:100px;">
                    <input type="text" inputmode="decimal" name="rows[${index}][unit_price]" value="${formatNumber(unitPrice, 3)}" class="w-full px-1 py-1 text-sm border rounded-md text-right row-unit-price">
                </td>
                <td class="px-2 py-1 align-top" style="width:70px;">
                    <select name="rows[${index}][unit_measure]" class="w-full px-1 py-1 text-sm border rounded-md text-center row-unit-measure">${umOptions}</select>
                </td>
                <td class="px-2 py-1 align-top" style="width:80px;">
                    <input type="number" step="0.01" name="rows[${index}][discount_percentage]" value="${row.discount_percentage || 0}" class="w-full px-1 py-1 text-sm border rounded-md text-right row-discount">
                </td>
                <td class="px-2 py-1 align-top" style="width:120px;">
                    <select name="rows[${index}][vat_rate_id]" class="w-full px-1 py-1 text-sm border rounded-md row-vat">${vatOptionsHtml}</select>
                </td>
                <td class="px-2 py-1 align-top" style="width:110px;">
                    <input type="text" readonly value="${formatEuro(taxable)}" class="w-full px-1 py-1 text-sm border rounded-md text-right bg-gray-100 font-semibold row-taxable">
                    <input type="hidden" name="rows[${index}][taxable_amount]" value="${taxable}">
                </td>
                <td class="px-2 py-1 align-top" style="width:160px;">
                    <input type="text" placeholder="Cerca centro..." class="w-full px-1 py-1 text-sm border rounded-md cost-center-search" data-index="${index}" autocomplete="off">
                    <input type="hidden" name="rows[${index}][id_cost_center]" value="${row.id_cost_center || ''}" class="cost-center-id">
                    <div class="cost-center-dropdown hidden absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto"></div>
                </td>
                <td class="px-2 py-1 align-top" style="width:160px;">
                    <input type="text" placeholder="Cerca servizio..." class="w-full px-1 py-1 text-sm border rounded-md service-search" data-index="${index}" autocomplete="off">
                    <input type="hidden" name="rows[${index}][id_service]" value="${row.id_service || ''}" class="service-id">
                    <div class="service-dropdown hidden absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto"></div>
                </td>
                <td class="px-2 py-1 text-center align-top" style="width:50px;">
                    ${index > 0 ? `<button type="button" class="remove-row-btn text-red-500 hover:text-red-700 transition-colors" data-index="${index}"><i class="fas fa-trash-alt"></i></button>` : ''}
                </td>
            </tr>
        `;
    }

    // =============================================
    // GESTIONE RIGHE
    // =============================================
    function addRow(data = null) {
        const html = generateRowHtml(rowIndex, data);
        const temp = document.createElement('tbody');
        temp.innerHTML = html;
        const rowElement = temp.firstElementChild;
        rowsContainer.appendChild(rowElement);
        
        // Inizializza autocomplete
        initCostCenterAutocomplete(rowIndex);
        initServiceAutocomplete(rowIndex);
        
        // Aggiungi event listener per il select IVA (IMPORTANTE!)
        const vatSelect = rowElement.querySelector('.row-vat');
        if (vatSelect) {
            vatSelect.addEventListener('change', function() {
                const row = this.closest('tr');
                if (row) {
                    const index = parseInt(row.dataset.index);
                    calculateRowTotal(index);
                }
            });
        }
        
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

        const quantityInput = row.querySelector('.row-quantity');
        const unitPriceInput = row.querySelector('.row-unit-price');
        const discountInput = row.querySelector('.row-discount');
        
        if (!quantityInput || !unitPriceInput || !discountInput) return;

        const quantity = parseNumber(quantityInput.value);
        const unitPrice = parseNumber(unitPriceInput.value);
        const discount = parseNumber(discountInput.value);
        
        const grossAmount = quantity * unitPrice;
        const discountAmount = grossAmount * (discount / 100);
        const taxable = grossAmount - discountAmount;
        
        // Aggiorna il campo imponibile
        const taxableInput = row.querySelector('.row-taxable');
        if (taxableInput) {
            taxableInput.value = formatEuro(taxable);
        }
        
        const hiddenInput = row.querySelector('input[name="rows['+index+'][taxable_amount]"]');
        if (hiddenInput) {
            hiddenInput.value = taxable;
        }
        
        // Ricalcola TUTTI i totali
        calculateAllTotals();
    }

    function calculateAllTotals() {
        let totalTaxable = 0;
        let totalDiscount = 0;
        let totalVat = 0;
        const vatSummary = {};

        const allRows = rowsContainer.querySelectorAll('tr');
        
        if (allRows.length === 0) {
            // Nessuna riga, reset totali
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
            
            // Trova l'aliquota IVA selezionata
            const vatRate = vatRates.find(v => v.id == vatId);
            
            // ASSICURATI CHE rate E rate_percent SIANO SEMPRE NUMERICI
            let rate = 0;
            let ratePercent = 0;
            let sdiNature = '';
            let description = 'IVA 0%';
            
            if (vatRate) {
                rate = typeof vatRate.rate === 'number' ? vatRate.rate : parseFloat(vatRate.rate) || 0;
                ratePercent = typeof vatRate.rate_percent === 'number' ? vatRate.rate_percent : (rate * 100);
                sdiNature = vatRate.sdi_nature || '';
                description = vatRate.description || 'IVA ' + ratePercent + '%';
            } else {
                // Se non trova l'IVA, prova a cercare per rate
                const fallbackRate = vatRates.find(v => v.id == vatId);
                if (fallbackRate) {
                    rate = typeof fallbackRate.rate === 'number' ? fallbackRate.rate : parseFloat(fallbackRate.rate) || 0;
                    ratePercent = rate * 100;
                    description = fallbackRate.description || 'IVA ' + ratePercent + '%';
                }
            }
            
            const grossAmount = quantity * unitPrice;
            const discountAmount = grossAmount * (discount / 100);
            const taxable = grossAmount - discountAmount;
            const vatAmount = taxable * rate;
            
            totalTaxable += taxable;
            totalDiscount += discountAmount;
            totalVat += vatAmount;
            
            // Riepilogo IVA per aliquota - CHIAVE UNIVOCA
            const key = rate + '|' + (sdiNature || 'default');
            if (!vatSummary[key]) {
                vatSummary[key] = {
                    rate: rate,
                    rate_percent: ratePercent,
                    taxable_amount: 0,
                    vat_amount: 0,
                    description: description,
                    nature_code: sdiNature || null,
                };
            }
            vatSummary[key].taxable_amount += taxable;
            vatSummary[key].vat_amount += vatAmount;
        });

        const totalInvoice = totalTaxable + totalVat;

        // Aggiorna i totali
        document.getElementById('totalTaxable').textContent = formatEuro(totalTaxable);
        document.getElementById('totalDiscount').textContent = '- ' + formatEuro(totalDiscount);
        document.getElementById('totalVat').textContent = formatEuro(totalVat);
        document.getElementById('totalInvoice').textContent = formatEuro(totalInvoice);
        document.getElementById('importo_totale').value = totalInvoice;

        // =============================================
        // RIEpilogo IVA - VERSIONE CORRETTA
        // =============================================
        const vatSummaryContainer = document.getElementById('vatSummaryItems');
        vatSummaryContainer.innerHTML = '';
        
        // Ordina per aliquota decrescente
        const sortedVats = Object.values(vatSummary).sort((a, b) => b.rate - a.rate);
        
        if (sortedVats.length === 0) {
            vatSummaryContainer.innerHTML = '<div class="text-center text-gray-400 text-xs py-2">Nessuna aliquota IVA</div>';
        } else {
            sortedVats.forEach(v => {
                const div = document.createElement('div');
                div.className = 'flex justify-between items-center py-1 text-sm border-b border-gray-100 last:border-0';
                
                // CALCOLA LA PERCENTUALE IN MODO SICURO
                let ratePercentDisplay = 0;
                if (typeof v.rate_percent === 'number' && !isNaN(v.rate_percent)) {
                    ratePercentDisplay = v.rate_percent;
                } else if (typeof v.rate === 'number' && !isNaN(v.rate)) {
                    ratePercentDisplay = v.rate * 100;
                } else {
                    ratePercentDisplay = 0;
                }
                
                // Mostra anche il codice natura per le esenzioni
                let rateLabel = ratePercentDisplay + '%';
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
        }

        // Aggiorna l'input nascosto per il riepilogo IVA
        document.getElementById('vat_summary_input').value = JSON.stringify(Object.values(vatSummary));
        
        // Aggiorna totali scadenze
        updatePaymentsTotal();
    }

    // =============================================
    // EVENTI RIGHE (DELEGATI) - VERSIONE COMPLETA
    // =============================================
    
    // Event listener per input (cattura tutti i cambiamenti in tempo reale)
    rowsContainer.addEventListener('input', function(e) {
        const target = e.target;
        if (target.classList.contains('row-quantity') || 
            target.classList.contains('row-unit-price') || 
            target.classList.contains('row-discount')) {
            const row = target.closest('tr');
            if (row) {
                const index = parseInt(row.dataset.index);
                calculateRowTotal(index);
            }
        }
    });

    // Event listener per change (cattura il cambio di select IVA)
    rowsContainer.addEventListener('change', function(e) {
        const target = e.target;
        if (target.classList.contains('row-vat')) {
            const row = target.closest('tr');
            if (row) {
                const index = parseInt(row.dataset.index);
                calculateRowTotal(index);
            }
        }
    });

    // Event listener per blur (formatta i numeri)
    rowsContainer.addEventListener('blur', function(e) {
        const target = e.target;
        if (target.classList.contains('row-quantity') || target.classList.contains('row-unit-price')) {
            const value = parseNumber(target.value);
            const decimals = target.classList.contains('row-unit-price') ? 3 : 2;
            target.value = value.toFixed(decimals);
            const row = target.closest('tr');
            if (row) {
                const index = parseInt(row.dataset.index);
                calculateRowTotal(index);
            }
        }
    }, true);

    // Event listener per click (rimozione righe)
    rowsContainer.addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.remove-row-btn');
        if (removeBtn) {
            const index = parseInt(removeBtn.dataset.index);
            removeRow(index);
        }
    });

    addRowBtn.addEventListener('click', function() {
        addRow();
    });

    // =============================================
    // GESTIONE SCADENZE
    // =============================================
    function addPayment(data = null) {
        const html = `
            <tr class="border-b hover:bg-gray-50" data-payment-index="${paymentIndex}">
                <td class="px-3 py-2">
                    <input type="date" name="payments[${paymentIndex}][due_date]" value="${data ? data.due_date : today}" class="w-full px-2 py-1 text-sm border rounded-md payment-date">
                </td>
                <td class="px-3 py-2">
                    <input type="number" step="0.01" name="payments[${paymentIndex}][amount]" value="${data ? data.amount : 0}" class="w-full px-2 py-1 text-sm border rounded-md text-right payment-amount">
                </td>
                <td class="px-3 py-2">
                    <select name="payments[${paymentIndex}][payment_method]" class="w-full px-2 py-1 text-sm border rounded-md payment-method">
                        <option value="">Seleziona modalità di pagamento</option>
                        ${Object.entries(paymentMethods).map(([code, label]) => {
                            const selected = (data && data.payment_method == code) || (!data && code == 'MP05') ? 'selected' : '';
                            return `<option value="${code}" ${selected}>${code} - ${label}</option>`;
                        }).join('')}
                    </select>
                </td>
                <td class="px-3 py-2">
                    <input type="text" name="payments[${paymentIndex}][iban]" value="${data ? data.iban : companyIban}" placeholder="IT00 XXXX XXXX XXXX XXXX XXXX XXX" class="w-full px-2 py-1 text-sm border rounded-md payment-iban">
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
            diffRow.classList.remove('hidden');
            diffSpan.textContent = formatEuro(diff);
        } else {
            diffRow.classList.add('hidden');
        }
    }

    // Eventi scadenze
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

    addPaymentBtn.addEventListener('click', function() {
        addPayment();
    });

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
    // AUTOCOMPLETE SERVIZIO
    // =============================================
    function initServiceAutocomplete(index) {
        const row = rowsContainer.querySelector(`tr[data-index="${index}"]`);
        if (!row) return;
        
        const searchInput = row.querySelector('.service-search');
        const dropdown = row.querySelector('.service-dropdown');
        const hiddenInput = row.querySelector('.service-id');
        const descTextarea = row.querySelector('.row-description');
        const unitPriceInput = row.querySelector('.row-unit-price');
        const vatSelect = row.querySelector('.row-vat');
        
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
                fetch('/admin/invoices-sent/api/search-services?q=' + encodeURIComponent(query))
                    .then(r => r.json())
                    .then(data => {
                        dropdown.innerHTML = '';
                        if (!data || data.length === 0) {
                            dropdown.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500 text-center">Nessun servizio trovato</div>';
                        } else {
                            data.forEach(item => {
                                const div = document.createElement('div');
                                div.className = 'px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0';
                                div.innerHTML = `
                                    <div class="font-medium text-gray-800">${item.name}</div>
                                    ${item.descr_fattura ? `<div class="text-xs text-gray-500 truncate">${item.descr_fattura}</div>` : ''}
                                    ${item.prezzo_un > 0 ? `<div class="text-xs text-lime-600 font-semibold">€ ${parseFloat(item.prezzo_un).toFixed(3)}</div>` : ''}
                                `;
                                div.addEventListener('click', function() {
                                    searchInput.value = item.name;
                                    hiddenInput.value = item.id;
                                    if (item.descr_fattura) {
                                        descTextarea.value = item.descr_fattura;
                                    }
                                    if (item.prezzo_un > 0 && (parseNumber(unitPriceInput.value) === 0)) {
                                        unitPriceInput.value = parseFloat(item.prezzo_un).toFixed(3);
                                    }
                                    if (item.vat_rate_id) {
                                        vatSelect.value = item.vat_rate_id;
                                    }
                                    hideDropdownPortal(dropdown);
                                    calculateAllTotals();
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
    ownershipSelect.addEventListener('change', function() {
        const idOwnership = this.value;
        seriesSelect.innerHTML = '<option value="">Caricamento sezionali...</option>';
        seriesInfo.classList.add('hidden');
        nInvoiceInput.value = '';

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
                loadBankAccount(idOwnership);
            })
            .catch(() => {
                seriesSelect.innerHTML = '<option value="">Errore nel caricamento</option>';
            });
    });

    seriesSelect.addEventListener('change', function() {
        const seriesId = this.value;
        if (!seriesId) {
            seriesInfo.classList.add('hidden');
            nInvoiceInput.value = '';
            return;
        }

        fetch('/admin/invoices-sent/api/series?id_ownership=' + ownershipSelect.value)
            .then(r => r.json())
            .then(data => {
                const series = data.find(s => s.id == seriesId);
                if (series) {
                    const nextNumber = series.last_number + 1;
                    nInvoiceInput.value = nextNumber + '/' + series.code + '-' + series.year;
                    
                    if (!series.active) {
                        seriesInfo.innerHTML = `
                            <div class="text-red-600 bg-red-50 border border-red-200 rounded-md px-3 py-1.5 flex items-center">
                                <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                                <span>ATTENZIONE: Questo sezionale è <strong>disattivato</strong>.</span>
                            </div>
                        `;
                        seriesInfo.classList.remove('hidden');
                    } else if (series.year != new Date().getFullYear()) {
                        const isPast = series.year < new Date().getFullYear();
                        seriesInfo.innerHTML = `
                            <div class="${isPast ? 'text-orange-600 bg-orange-50 border border-orange-200' : 'text-blue-600 bg-blue-50 border border-blue-200'} rounded-md px-3 py-1.5 flex items-center">
                                <i class="fas ${isPast ? 'fa-history' : 'fa-clock'} mr-2"></i>
                                <span>Sezionale dell'anno <strong>${series.year}</strong> ${isPast ? '(anno passato)' : '(anno futuro)'}</span>
                            </div>
                        `;
                        seriesInfo.classList.remove('hidden');
                    } else {
                        seriesInfo.classList.add('hidden');
                    }
                }
            });
    });

    function loadBankAccount(idOwnership) {
        fetch('/admin/invoices-sent/api/bank-account?id_ownership=' + idOwnership)
            .then(r => r.json())
            .then(data => {
                if (data) {
                    companyIban = data.iban || '';
                    companyBankName = data.bank_name || '';
                }
            });
    }

    // =============================================
    // GESTIONE CLIENTI
    // =============================================
    const customerSearch = document.getElementById('customer_search');
    const customerDropdown = document.getElementById('customerDropdown');
    const selectedCustomerIdInput = document.getElementById('selected_customer_id');
    const selectedCustomerInfo = document.getElementById('selectedCustomerInfo');
    const selectedCustomerNameSpan = document.getElementById('selectedCustomerName');
    const clearCustomerBtn = document.getElementById('clearCustomerBtn');
    let customerSearchTimeout = null;

    function selectCustomer(id, name) {
        selectedCustomerId = id;
        selectedCustomerName = name;
        selectedCustomerIdInput.value = id;
        customerSearch.value = name;
        selectedCustomerNameSpan.textContent = name;
        selectedCustomerInfo.classList.remove('hidden');
        clearCustomerBtn.classList.remove('hidden');
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

    clearCustomerBtn.addEventListener('click', function() {
        selectedCustomerId = '';
        selectedCustomerName = '';
        selectedCustomerIdInput.value = '';
        customerSearch.value = '';
        selectedCustomerInfo.classList.add('hidden');
        this.classList.add('hidden');
    });

    // Cliente pre-selezionato
    if (selectedCustomerIdInput.value) {
        const customerId = selectedCustomerIdInput.value;
        fetch('/admin/invoices-sent/api/customer/' + customerId)
            .then(r => r.json())
            .then(data => {
                if (data) {
                    selectCustomer(data.id, data.name);
                }
            });
    }

    // =============================================
    // MODALE CREAZIONE CLIENTE
    // =============================================
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

    openCustomerModalBtn.addEventListener('click', openModal);
    closeCustomerModalBtns.forEach(btn => btn.addEventListener('click', closeModal));
    customerModalOverlay.addEventListener('click', closeModal);

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
        
        if (!selectedCustomerIdInput.value) {
            e.preventDefault();
            alert('Seleziona un cliente.');
            customerSearch.focus();
            return;
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
    // Aggiungi la prima riga
    addRow();
    
    // Aggiungi la prima scadenza
    addPayment();
    
    // Se c'è una proprietà pre-selezionata, carica i sezionali
    if (ownershipSelect.value) {
        ownershipSelect.dispatchEvent(new Event('change'));
    }
    
    // Calcola i totali iniziali
    setTimeout(function() {
        calculateAllTotals();
    }, 100);
    
    console.log('✅ InvoiceSentCreate inizializzato correttamente');
});
</script>
@endpush

@push('styles')
<style>
    .totals-card {
        background: white;
        border-radius: 0.5rem;
        padding: 1rem;
        height: 100%;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
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
</style>
@endpush