@extends('admin.layouts.app')

@section('title', 'Modifica Attività')

@section('content')
<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-edit text-yellow-500 mr-2"></i> Modifica Attività
        </h1>
        <div class="relative group">
            <a href="{{ route('admin.activities.index', session('activities_filters', [])) }}" 
               class="bg-gray-700 hover:bg-gray-900 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>
    </div>

    <form action="{{ route('admin.activities.update', $activity->id) }}" method="POST" class="bg-white rounded-lg shadow p-6">
        @csrf
        @method('PUT')
        
        <!-- Campi hidden per mantenere i filtri -->
        @foreach(session('activities_filters', []) as $key => $value)
            @if(is_string($value) && !empty($value))
                <input type="hidden" name="filters[{{ $key }}]" value="{{ $value }}">
            @endif
        @endforeach
        
        <!-- Tutti i campi su una riga - 3 colonne -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- COLONNA 1: Data -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data <span class="text-red-500">*</span></label>
                <input type="date" name="data_activities" value="{{ old('data_activities', $activity->data_activities instanceof \Carbon\Carbon ? $activity->data_activities->format('Y-m-d') : ($activity->data_activities ? date('Y-m-d', strtotime($activity->data_activities)) : '')) }}" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500">
                @error('data_activities') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            
            <!-- COLONNA 2: Centro di Costo + ID -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Centro di Costo <span class="text-red-500">*</span></label>
                <div class="flex gap-2">
                    <div class="flex-1 relative" x-data="costCenterAutocomplete()" 
                         x-init="initSelected('{{ $activity->id_cost_centers }}', '{{ addslashes($activity->costCenter?->Nome ?? '') }}')">
                        <input type="hidden" name="id_cost_centers" x-model="selectedId" :value="selectedId">
                        <input type="text" 
                               x-model="search"
                               x-on:input.debounce.300ms="searchItems()"
                               x-on:focus="open = true"
                               x-on:keydown.escape="open = false"
                               x-on:keydown.down.prevent="moveDown()"
                               x-on:keydown.up.prevent="moveUp()"
                               x-on:keydown.enter.prevent="selectHighlighted()"
                               x-on:click.away="open = false"
                               placeholder="Digita almeno 2 caratteri..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 text-sm"
                               autocomplete="off">
                        
                        <div x-show="loading" class="absolute right-3 top-2.5">
                            <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        
                        <div x-show="open && results.length > 0" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                            <template x-for="(item, index) in results" :key="item.id">
                                <div @click="selectItem(item)"
                                     :class="{'bg-lime-50': highlightIndex === index}"
                                     class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                                    <div class="font-medium text-gray-800" x-text="item.Nome"></div>
                                    <div class="text-xs text-gray-500" x-show="item.Localita" x-text="item.Localita"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                    
                    <div class="w-16">
                        <input type="text" 
                               x-data="{ costCenterId: '{{ $activity->id_cost_centers }}' }" 
                               x-init="$watch('$store.costCenter.selectedId', val => costCenterId = val)"
                               x-model="costCenterId"
                               readonly
                               class="w-full px-2 py-2 bg-gray-100 border border-gray-300 rounded-md text-gray-600 text-sm text-center cursor-not-allowed"
                               placeholder="ID">
                    </div>
                </div>
            </div>
            
            <!-- COLONNA 3: Cliente + ID (opzionale) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Cliente <span class="text-gray-400 text-xs">(opzionale)</span>
                </label>
                <div class="flex gap-2">
                    <div class="flex-1 relative" x-data="clientAutocomplete()" 
                        x-init="initSelected('{{ $activity->id_entities }}', '{{ addslashes($activity->entity ? ($activity->entity->ragione_sociale ?: ($activity->entity->nome . ' ' . $activity->entity->cognome)) : '') }}')">
                        <input type="hidden" name="id_entities" x-model="selectedId" :value="selectedId">
                        <input type="text" 
                            x-model="search"
                            x-on:input.debounce.300ms="searchItems()"
                            x-on:focus="open = true"
                            x-on:keydown.escape="open = false"
                            x-on:keydown.down.prevent="moveDown()"
                            x-on:keydown.up.prevent="moveUp()"
                            x-on:keydown.enter.prevent="selectHighlighted()"
                            x-on:click.away="open = false"
                            placeholder="Digita almeno 2 caratteri... (lascia vuoto per nessun cliente)"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 text-sm"
                            autocomplete="off">
                        
                        <div x-show="loading" class="absolute right-3 top-2.5">
                            <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        
                        <div x-show="open && results.length > 0" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                            <template x-for="(item, index) in results" :key="item.id_cliente">
                                <div @click="selectItem(item)"
                                    :class="{'bg-lime-50': highlightIndex === index}"
                                    class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                                    <div class="font-medium text-gray-800" x-text="item.ragione_sociale || item.nome + ' ' + (item.cognome || '')"></div>
                                    <div class="text-xs text-gray-500" x-show="item.partita_iva" x-text="'P.IVA: ' + item.partita_iva"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                    
                    <div class="w-16">
                        <input type="text" 
                            x-data="{ clientId: '{{ $activity->id_entities }}' }" 
                            x-init="$watch('$store.client.selectedId', val => clientId = val)"
                            x-model="clientId"
                            readonly
                            class="w-full px-2 py-2 bg-gray-100 border border-gray-300 rounded-md text-gray-600 text-sm text-center cursor-not-allowed"
                            placeholder="ID">
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-1">Lascia vuoto per nessun cliente</p>
            </div>
        </div>

        <!-- Riga: Servizio e Rif. Fattura -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Servizio <span class="text-red-500">*</span></label>
                <div class="relative" x-data="serviceAutocomplete()" 
                        x-init="initSelected('{{ $activity->id_services }}', '{{ addslashes($activity->service?->Titolo ?? '') }}')">
                    <input type="hidden" name="id_services" x-model="selectedId" :value="selectedId">
                    <input type="text" 
                            x-model="search"
                            x-on:input.debounce.300ms="searchItems()"
                            x-on:focus="open = true"
                            x-on:keydown.escape="open = false"
                            x-on:keydown.down.prevent="moveDown()"
                            x-on:keydown.up.prevent="moveUp()"
                            x-on:keydown.enter.prevent="selectHighlighted()"
                            x-on:click.away="open = false"
                            placeholder="Digita almeno 2 caratteri..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 text-sm"
                            autocomplete="off">
                    
                    <div x-show="loading" class="absolute right-3 top-2.5">
                        <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    
                    <div x-show="open && results.length > 0" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                        <template x-for="(item, index) in results" :key="item.id">
                            <div @click="selectItem(item)"
                                    :class="{'bg-lime-50': highlightIndex === index}"
                                    class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                                <div class="font-medium text-gray-800" x-text="item.Titolo"></div>
                                <div class="text-xs text-gray-500 truncate" x-show="item.Descrizione" x-text="item.Descrizione"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Rif. Fattura</label>
                <input type="text" name="invoice_references" value="{{ old('invoice_references', $activity->invoice_references) }}" 
                       placeholder="es. FV-2024-001"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500">
            </div>
        </div>
        
        <!-- Note -->
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
            <textarea name="note" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500">{{ old('note', $activity->note) }}</textarea>
        </div>
        
        <!-- Personale Associato -->
        <div class="mt-6">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-users text-blue-500 mr-2"></i> Personale Associato
                </h3>
                <button type="button" id="addStaffBtn" class="px-3 py-1 bg-lime-500 text-white rounded-md hover:bg-lime-600 text-sm transition-colors">
                    <i class="fas fa-plus"></i> Aggiungi personale
                </button>
            </div>
            
            <div id="staffContainer" class="space-y-3">
                <!-- Righe personale esistenti -->
                @php $staffIndex = 0; @endphp
                @forelse($activity->staffDetails as $staffDetail)
                <div class="staff-row bg-gray-50 p-4 rounded-lg border" data-index="{{ $staffIndex }}">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                        <div class="md:col-span-4">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Personale <span class="text-red-500">*</span></label>
                            <div class="relative" x-data="staffAutocomplete({{ $staffIndex }})" 
                                x-init="initSelected('{{ $staffDetail->id_staff }}', '{{ addslashes($staffDetail->staff?->CognomePers . ' ' . $staffDetail->staff?->NomePers ?? '') }}')">
                                <input type="hidden" name="staff[{{ $staffIndex }}][id_staff]" x-model="selectedId">
                                <input type="text" 
                                    x-model="search"
                                    x-on:input.debounce.300ms="searchItems()"
                                    x-on:focus="open = true"
                                    x-on:keydown.escape="open = false"
                                    x-on:keydown.down.prevent="moveDown()"
                                    x-on:keydown.up.prevent="moveUp()"
                                    x-on:keydown.enter.prevent="selectHighlighted()"
                                    x-on:click.away="open = false"
                                    placeholder="Digita nome o cognome..."
                                    class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                                    autocomplete="off">
                                
                                <div x-show="open && results.length > 0" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-40 overflow-y-auto">
                                    <template x-for="(item, idx) in results" :key="item.id_personale">
                                        <div @click="selectItem(item)"
                                            :class="{'bg-lime-50': highlightIndex === idx}"
                                            class="px-3 py-1.5 hover:bg-lime-50 cursor-pointer text-xs border-b border-gray-100 last:border-0">
                                            <div class="font-medium" x-text="item.full_name"></div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                        
                        <div class="md:col-span-1">
                            <label class="block text-xs font-medium text-gray-600 mb-1">N. Ore <span class="text-red-500">*</span></label>
                            <input type="number" step="0.5" name="staff[{{ $staffIndex }}][n_ore]" value="{{ $staffDetail->n_ore }}" 
                                class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                                min="0.5" step="0.5">
                        </div>
                        
                        <div class="md:col-span-1">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Spese (€)</label>
                            <input type="number" step="0.01" name="staff[{{ $staffIndex }}][spese]" value="{{ $staffDetail->spese ?? 0 }}" 
                                class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500"
                                min="0" step="0.01">
                        </div>
                        
                        <div class="md:col-span-6">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Note</label>
                            <div class="flex gap-2">
                                <input type="text" name="staff[{{ $staffIndex }}][note]" value="{{ $staffDetail->note ?? '' }}" placeholder="Note aggiuntive" 
                                    class="flex-1 px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                                <button type="button" class="remove-staff-btn text-red-500 hover:text-red-700 px-2 transition-colors" {{ $loop->count <= 1 ? 'style="display: none;"' : '' }}>
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @php $staffIndex++; @endphp
                @empty
                <!-- Nessun personale associato - verrà aggiunto via JavaScript -->
                <div id="noStaffPlaceholder"></div>
                @endforelse
            </div>
        </div>
        
        <!-- Bottoni -->
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.activities.index', session('activities_filters', [])) }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                Annulla
            </a>
            <button type="submit" class="px-4 py-2 bg-yellow-500 text-white rounded-md hover:bg-yellow-600 transition-colors">
                <i class="fas fa-save mr-2"></i> Aggiorna
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    // Store condiviso
    if (!Alpine.store('costCenter')) Alpine.store('costCenter', { selectedId: '' });
    if (!Alpine.store('client')) Alpine.store('client', { selectedId: '' });
    
    // Centro di Costo Autocomplete
    Alpine.data('costCenterAutocomplete', () => ({
        search: '',
        selectedId: '',
        results: [],
        open: false,
        loading: false,
        highlightIndex: -1,
        
        initSelected(id, name) {
            if (id && id !== '') {
                this.selectedId = id;
                this.search = name;
                Alpine.store('costCenter').selectedId = id;
            }
        },
        
        init() {
            this.$watch('selectedId', val => {
                Alpine.store('costCenter').selectedId = val;
            });
        },
        
        async searchItems() {
            if (this.search.length < 2) {
                this.results = [];
                this.open = false;
                return;
            }
            
            this.loading = true;
            this.open = true;
            
            try {
                const response = await fetch(`{{ route('admin.api.search-cost-centers') }}?q=${encodeURIComponent(this.search)}`);
                this.results = await response.json();
                this.highlightIndex = -1;
            } catch (error) {
                console.error('Errore:', error);
                this.results = [];
            } finally {
                this.loading = false;
            }
        },
        
        selectItem(item) {
            this.search = item.Nome;
            this.selectedId = item.id;
            this.open = false;
            this.results = [];
            Alpine.store('costCenter').selectedId = item.id;
        },
        
        moveDown() { if (this.highlightIndex < this.results.length - 1) this.highlightIndex++; },
        moveUp() { if (this.highlightIndex > 0) this.highlightIndex--; },
        selectHighlighted() { if (this.highlightIndex >= 0 && this.results[this.highlightIndex]) this.selectItem(this.results[this.highlightIndex]); }
    }));
    
    // Cliente Autocomplete
    Alpine.data('clientAutocomplete', () => ({
        search: '',
        selectedId: '',
        results: [],
        open: false,
        loading: false,
        highlightIndex: -1,
        
        initSelected(id, name) {
            if (id && id !== '') {
                this.selectedId = id;
                this.search = name;
                Alpine.store('client').selectedId = id;
            }
        },
        
        init() {
            this.$watch('selectedId', val => {
                Alpine.store('client').selectedId = val;
            });
        },
        
        async searchItems() {
            if (this.search.length < 2) {
                this.results = [];
                this.open = false;
                return;
            }
            
            this.loading = true;
            this.open = true;
            
            try {
                const response = await fetch(`{{ route('admin.api.search-clients') }}?q=${encodeURIComponent(this.search)}`);
                this.results = await response.json();
                this.highlightIndex = -1;
            } catch (error) {
                console.error('Errore:', error);
                this.results = [];
            } finally {
                this.loading = false;
            }
        },
        
        selectItem(item) {
            this.search = item.ragione_sociale || (item.nome + ' ' + (item.cognome || ''));
            this.selectedId = item.id_cliente;
            this.open = false;
            this.results = [];
            Alpine.store('client').selectedId = item.id_cliente;
        },
        
        moveDown() { if (this.highlightIndex < this.results.length - 1) this.highlightIndex++; },
        moveUp() { if (this.highlightIndex > 0) this.highlightIndex--; },
        selectHighlighted() { if (this.highlightIndex >= 0 && this.results[this.highlightIndex]) this.selectItem(this.results[this.highlightIndex]); }
    }));
    
    // Servizio Autocomplete
    Alpine.data('serviceAutocomplete', () => ({
        search: '',
        selectedId: '',
        results: [],
        open: false,
        loading: false,
        highlightIndex: -1,
        
        initSelected(id, name) {
            if (id && id !== '') {
                this.selectedId = id;
                this.search = name;
            }
        },
        
        async searchItems() {
            if (this.search.length < 2) {
                this.results = [];
                this.open = false;
                return;
            }
            
            this.loading = true;
            this.open = true;
            
            try {
                const response = await fetch(`{{ route('admin.api.search-services') }}?q=${encodeURIComponent(this.search)}`);
                this.results = await response.json();
                this.highlightIndex = -1;
            } catch (error) {
                console.error('Errore:', error);
                this.results = [];
            } finally {
                this.loading = false;
            }
        },
        
        selectItem(item) {
            this.search = item.Titolo;
            this.selectedId = item.id;
            this.open = false;
            this.results = [];
        },
        
        moveDown() { if (this.highlightIndex < this.results.length - 1) this.highlightIndex++; },
        moveUp() { if (this.highlightIndex > 0) this.highlightIndex--; },
        selectHighlighted() { if (this.highlightIndex >= 0 && this.results[this.highlightIndex]) this.selectItem(this.results[this.highlightIndex]); }
    }));
    
    // Staff Autocomplete
    Alpine.data('staffAutocomplete', (rowIndex) => ({
        index: rowIndex,
        search: '',
        selectedId: '',
        results: [],
        open: false,
        loading: false,
        highlightIndex: -1,
        
        initSelected(id, name) {
            if (id && id !== '') {
                this.selectedId = id;
                this.search = name;
            }
        },
        
        async searchItems() {
            if (this.search.length < 2) {
                this.results = [];
                this.open = false;
                return;
            }
            
            this.loading = true;
            this.open = true;
            
            try {
                const response = await fetch(`{{ route('admin.api.search-staff') }}?q=${encodeURIComponent(this.search)}`);
                this.results = await response.json();
                this.highlightIndex = -1;
            } catch (error) {
                console.error('Errore:', error);
                this.results = [];
            } finally {
                this.loading = false;
            }
        },
        
        selectItem(item) {
            this.search = item.full_name;
            this.selectedId = item.id_personale;
            this.open = false;
            this.results = [];
        },
        
        moveDown() { if (this.highlightIndex < this.results.length - 1) this.highlightIndex++; },
        moveUp() { if (this.highlightIndex > 0) this.highlightIndex--; },
        selectHighlighted() { if (this.highlightIndex >= 0 && this.results[this.highlightIndex]) this.selectItem(this.results[this.highlightIndex]); }
    }));
});

// Gestione righe personale
let staffIndex = {{ $activity->staffDetails->count() }};

function initAlpineInRow(row) {
    if (window.Alpine && row) {
        window.Alpine.initTree(row);
    }
}

function createStaffRow(index) {
    const div = document.createElement('div');
    div.className = 'staff-row bg-gray-50 p-4 rounded-lg border';
    div.setAttribute('data-index', index);
    
    div.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
            <div class="md:col-span-4">
                <label class="block text-xs font-medium text-gray-600 mb-1">Personale <span class="text-red-500">*</span></label>
                <div class="relative" x-data="staffAutocomplete(${index})">
                    <input type="hidden" name="staff[${index}][id_staff]" x-model="selectedId">
                    <input type="text" 
                        x-model="search"
                        x-on:input.debounce.300ms="searchItems()"
                        x-on:focus="open = true"
                        x-on:keydown.escape="open = false"
                        x-on:keydown.down.prevent="moveDown()"
                        x-on:keydown.up.prevent="moveUp()"
                        x-on:keydown.enter.prevent="selectHighlighted()"
                        x-on:click.away="open = false"
                        placeholder="Digita nome o cognome..."
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                        autocomplete="off">
                    
                    <div x-show="open && results.length > 0" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-40 overflow-y-auto">
                        <template x-for="(item, idx) in results" :key="item.id_personale">
                            <div @click="selectItem(item)"
                                :class="{'bg-lime-50': highlightIndex === idx}"
                                class="px-3 py-1.5 hover:bg-lime-50 cursor-pointer text-xs border-b border-gray-100 last:border-0">
                                <div class="font-medium" x-text="item.full_name"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            
            <div class="md:col-span-1">
                <label class="block text-xs font-medium text-gray-600 mb-1">N. Ore <span class="text-red-500">*</span></label>
                <input type="number" step="0.5" name="staff[${index}][n_ore]" value="1" 
                       class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                       min="0.5" step="0.5">
            </div>
            
            <div class="md:col-span-1">
                <label class="block text-xs font-medium text-gray-600 mb-1">Spese (€)</label>
                <input type="number" step="0.01" name="staff[${index}][spese]" value="0" 
                       class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500"
                       min="0" step="0.01">
            </div>
            
            <div class="md:col-span-6">
                <label class="block text-xs font-medium text-gray-600 mb-1">Note</label>
                <div class="flex gap-2">
                    <input type="text" name="staff[${index}][note]" placeholder="Note aggiuntive" 
                        class="flex-1 px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                    <button type="button" class="remove-staff-btn text-red-500 hover:text-red-700 px-2 transition-colors">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    return div;
}

// Se non ci sono staff, aggiungi una riga vuota
const existingRows = document.querySelectorAll('.staff-row');
if (existingRows.length === 0) {
    const container = document.getElementById('staffContainer');
    const noStaffPlaceholder = document.getElementById('noStaffPlaceholder');
    if (noStaffPlaceholder) {
        noStaffPlaceholder.remove();
    }
    const newRow = createStaffRow(0);
    container.appendChild(newRow);
    initAlpineInRow(newRow);
    staffIndex = 1;
} else {
    // Aggiorna gli indici delle righe esistenti
    existingRows.forEach((row, idx) => {
        row.setAttribute('data-index', idx);
        // Aggiorna i name degli input
        const inputs = row.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (input.name) {
                input.name = input.name.replace(/staff\[\d+\]/g, `staff[${idx}]`);
            }
        });
    });
    staffIndex = existingRows.length;
}

// Aggiungi nuova riga
document.getElementById('addStaffBtn')?.addEventListener('click', function(e) {
    e.preventDefault();
    const newRow = createStaffRow(staffIndex);
    document.getElementById('staffContainer').appendChild(newRow);
    initAlpineInRow(newRow);
    staffIndex++;
});

// Rimuovi riga
document.getElementById('staffContainer')?.addEventListener('click', function(e) {
    const removeBtn = e.target.closest('.remove-staff-btn');
    if (removeBtn) {
        e.preventDefault();
        const row = removeBtn.closest('.staff-row');
        if (row && document.querySelectorAll('.staff-row').length > 1) {
            row.remove();
            // Rinumera gli indici dopo la rimozione
            const remainingRows = document.querySelectorAll('.staff-row');
            remainingRows.forEach((remainingRow, newIdx) => {
                remainingRow.setAttribute('data-index', newIdx);
                const inputs = remainingRow.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    if (input.name) {
                        input.name = input.name.replace(/staff\[\d+\]/g, `staff[${newIdx}]`);
                    }
                });
                // Aggiorna anche l'indice in x-data
                const alpineDiv = remainingRow.querySelector('[x-data^="staffAutocomplete"]');
                if (alpineDiv && alpineDiv.__x) {
                    // Ricrea Alpine per questo elemento
                    const newAlpineDiv = alpineDiv.cloneNode(true);
                    alpineDiv.parentNode.replaceChild(newAlpineDiv, alpineDiv);
                    if (window.Alpine) {
                        window.Alpine.initTree(newAlpineDiv);
                    }
                }
            });
            staffIndex = remainingRows.length;
        }
    }
});
</script>
@endpush
@endsection