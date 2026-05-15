@extends('admin.layouts.app')

@section('title', 'Nuova Attività')

@section('content')
<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-plus-circle text-lime-500 mr-2"></i> Nuova Attività
        </h1>
        <div class="relative group">
            <a href="{{ route('admin.activities.index') }}" 
               class="bg-gray-600 hover:bg-gray-900 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>
    </div>

    <form action="{{ route('admin.activities.store') }}" method="POST" class="bg-white rounded-lg shadow p-6" id="activityForm" novalidate>
        @csrf
        
        <!-- Tutti i campi su una riga - 4 colonne -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- COLONNA 1: Data -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data</label>
                <input type="date" name="data_activities" value="{{ old('data_activities', date('Y-m-d')) }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500">
                @error('data_activities') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            
            <!-- COLONNA 2: Centro di Costo + ID (affiancato) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Centro di Costo <span class="text-red-500">*</span></label>
                <div class="flex gap-2">
                    <div class="flex-1 relative" x-data="costCenterAutocomplete()">
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
                               :class="{
                                   'border-red-500 focus:ring-red-500': errorCostCenter,
                                   'border-gray-300 focus:ring-lime-500 focus:border-lime-500': !errorCostCenter
                               }"
                               class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 text-sm"
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
                        
                        <div x-show="open && search.length >= 2 && results.length === 0 && !loading" 
                             class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg p-3 text-center text-gray-500 text-sm">
                            Nessun centro di costo trovato
                        </div>
                    </div>
                    
                    <!-- ID Centro di Costo (non editabile) -->
                    <div class="w-16">
                        <input type="text" 
                               x-data="{ costCenterId: '' }" 
                               x-init="$watch('$store.costCenter.selectedId', val => costCenterId = val)"
                               x-model="costCenterId"
                               readonly
                               class="w-full px-2 py-2 bg-gray-100 border border-gray-300 rounded-md text-gray-600 text-sm text-center cursor-not-allowed"
                               placeholder="ID">
                    </div>
                </div>
                <p x-show="errorCostCenter" class="text-xs text-red-500 mt-1" x-text="errorCostCenterMessage"></p>
            </div>
            
            <!-- COLONNA 3: Cliente + ID (affiancato) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cliente <span class="text-red-500">*</span></label>
                <div class="flex gap-2">
                    <div class="flex-1 relative" x-data="clientAutocomplete()">
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
                               placeholder="Digita almeno 2 caratteri..."
                               :class="{
                                   'border-red-500 focus:ring-red-500': errorClient,
                                   'border-gray-300 focus:ring-lime-500 focus:border-lime-500': !errorClient
                               }"
                               class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 text-sm"
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
                        
                        <div x-show="open && search.length >= 2 && results.length === 0 && !loading" 
                             class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg p-3 text-center text-gray-500 text-sm">
                            Nessun cliente trovato
                        </div>
                    </div>
                    
                    <!-- ID Cliente (non editabile) -->
                    <div class="w-16">
                        <input type="text" 
                               x-data="{ clientId: '' }" 
                               x-init="$watch('$store.client.selectedId', val => clientId = val)"
                               x-model="clientId"
                               readonly
                               class="w-full px-2 py-2 bg-gray-100 border border-gray-300 rounded-md text-gray-600 text-sm text-center cursor-not-allowed"
                               placeholder="ID">
                    </div>
                </div>
                <p x-show="errorClient" class="text-xs text-red-500 mt-1" x-text="errorClientMessage"></p>
            </div>
            
            <!-- COLONNA 4: Servizio -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Servizio <span class="text-red-500">*</span></label>
                <div class="relative" x-data="serviceAutocomplete()">
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
                           :class="{
                               'border-red-500 focus:ring-red-500': errorService,
                               'border-gray-300 focus:ring-lime-500 focus:border-lime-500': !errorService
                           }"
                           class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 text-sm"
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
                    
                    <div x-show="open && search.length >= 2 && results.length === 0 && !loading" 
                         class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg p-3 text-center text-gray-500 text-sm">
                        Nessun servizio trovato
                    </div>
                </div>
                <p x-show="errorService" class="text-xs text-red-500 mt-1" x-text="errorServiceMessage"></p>
            </div>
        </div>
        
        <!-- Note -->
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
            <textarea name="note" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500">{{ old('note') }}</textarea>
            @error('note') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>
        
        <!-- Personale Associato -->
        <div class="mt-6">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-users text-blue-500 mr-2"></i> Personale Associato
                </h3>
                <button type="button" id="addStaffBtn" class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-3 py-1 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                    <i class="fas fa-plus"></i> Aggiungi personale
                </button>
            </div>
            
            <div id="staffContainer" class="space-y-3">
                <!-- Template riga personale -->
                <div class="staff-row-template hidden bg-gray-50 p-4 rounded-lg border">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                        <div class="md:col-span-4">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Personale <span class="text-red-500">*</span></label>
                            <div class="relative" x-data="staffAutocomplete(__INDEX__)">
                                <input type="hidden" :name="'staff[' + index + '][id_staff]'" x-model="selectedId">
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
                                    :class="{
                                        'border-red-500 focus:ring-red-500': errorStaff,
                                        'border-gray-300 focus:ring-lime-500': !errorStaff
                                    }"
                                    class="w-full px-2 py-1.5 text-sm border rounded-md focus:outline-none focus:ring-2"
                                    autocomplete="off">
                                
                                <div x-show="loading" class="absolute right-2 top-1.5">
                                    <svg class="animate-spin h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                                
                                <div x-show="open && results.length > 0" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-40 overflow-y-auto">
                                    <template x-for="(item, idx) in results" :key="item.id_personale">
                                        <div @click="selectItem(item)"
                                            :class="{'bg-lime-50': highlightIndex === idx}"
                                            class="px-3 py-1.5 hover:bg-lime-50 cursor-pointer text-xs border-b border-gray-100 last:border-0">
                                            <div class="font-medium" x-text="item.full_name"></div>
                                        </div>
                                    </template>
                                </div>
                                
                                <div x-show="open && search.length >= 2 && results.length === 0 && !loading" 
                                    class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg p-2 text-center text-gray-500 text-xs">
                                    Nessun personale trovato
                                </div>
                            </div>
                            <p x-show="errorStaff" class="text-xs text-red-500 mt-1" x-text="errorStaffMessage"></p>
                        </div>
                        
                        <div class="md:col-span-1">
                            <label class="block text-xs font-medium text-gray-600 mb-1">N. Ore <span class="text-red-500">*</span></label>
                            <input type="number" step="0.5" name="" value="0" 
                                class="hours-input w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                                min="0"
                                step="0.5">
                        </div>
                        
                        <div class="md:col-span-1">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Spese (€)</label>
                            <input type="number" step="0.01" name="" value="0" 
                                class="expenses-input w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                                min="0"
                                step="0.01">
                        </div>
                        
                        <div class="md:col-span-6">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Note</label>
                            <div class="flex gap-2">
                                <input type="text" name="" placeholder="Note aggiuntive" 
                                    class="note-input flex-1 px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500">
                                <button type="button" class="remove-staff-btn text-red-500 hover:text-red-700 px-2 transition-colors">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Prima riga personale predefinita -->
                <div class="staff-row bg-gray-50 p-4 rounded-lg border">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                        <div class="md:col-span-4">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Personale <span class="text-red-500">*</span></label>
                            <div class="relative" x-data="staffAutocomplete(0)">
                                <input type="hidden" name="staff[0][id_staff]" x-model="selectedId">
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
                                    :class="{
                                        'border-red-500 focus:ring-red-500': errorStaff,
                                        'border-gray-300 focus:ring-lime-500': !errorStaff
                                    }"
                                    class="w-full px-2 py-1.5 text-sm border rounded-md focus:outline-none focus:ring-2"
                                    autocomplete="off">
                                
                                <div x-show="loading" class="absolute right-2 top-1.5">
                                    <svg class="animate-spin h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                                
                                <div x-show="open && results.length > 0" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-40 overflow-y-auto">
                                    <template x-for="(item, idx) in results" :key="item.id_personale">
                                        <div @click="selectItem(item)"
                                            :class="{'bg-lime-50': highlightIndex === idx}"
                                            class="px-3 py-1.5 hover:bg-lime-50 cursor-pointer text-xs border-b border-gray-100 last:border-0">
                                            <div class="font-medium" x-text="item.full_name"></div>
                                        </div>
                                    </template>
                                </div>
                                
                                <div x-show="open && search.length >= 2 && results.length === 0 && !loading" 
                                    class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg p-2 text-center text-gray-500 text-xs">
                                    Nessun personale trovato
                                </div>
                            </div>
                            <p x-show="errorStaff" class="text-xs text-red-500 mt-1" x-text="errorStaffMessage"></p>
                        </div>
                        
                        <div class="md:col-span-1">
                            <label class="block text-xs font-medium text-gray-600 mb-1">N. Ore <span class="text-red-500">*</span></label>
                            <input type="number" step="0.5" name="staff[0][n_ore]" value="0" 
                                   :class="{
                                       'border-red-500 focus:ring-red-500': errorHours,
                                       'border-gray-300 focus:ring-lime-500': !errorHours
                                   }"
                                   class="w-full px-2 py-1.5 text-sm border rounded-md focus:outline-none focus:ring-2"
                                   min="0"
                                   step="0.5">
                            <p x-show="errorHours" class="text-xs text-red-500 mt-1" x-text="errorHoursMessage"></p>
                        </div>
                        
                        <div class="md:col-span-1">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Spese (€)</label>
                            <input type="number" step="0.01" name="staff[0][spese]" value="0" 
                                   class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500"
                                   min="0"
                                   step="0.01">
                        </div>
                        
                        <div class="md:col-span-6">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Note</label>
                            <div class="flex gap-2">
                                <input type="text" name="staff[0][note]" placeholder="Note aggiuntive" 
                                    class="flex-1 px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500">
                                <button type="button" class="remove-staff-btn text-red-500 hover:text-red-700 px-2 transition-colors" style="display: none;">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <p id="staffGlobalError" class="text-xs text-red-500 mt-2 hidden">Aggiungi almeno un membro del personale</p>
        </div>
        
        <!-- Bottoni -->
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.activities.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                Annulla
            </a>
            <button type="submit" id="submitBtn" class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-4 py-2 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                <i class="fas fa-save mr-2"></i> Salva
            </button>
        </div>
    </form>
</div>

{{-- @push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    // Store condiviso per ID
    Alpine.store('costCenter', { selectedId: '' })
    Alpine.store('client', { selectedId: '' })
    
    // Centro di Costo Autocomplete
    Alpine.data('costCenterAutocomplete', () => ({
        search: '',
        selectedId: '',
        results: [],
        open: false,
        loading: false,
        highlightIndex: -1,
        errorCostCenter: false,
        errorCostCenterMessage: '',
        
        init() {
            this.$watch('selectedId', val => {
                Alpine.store('costCenter').selectedId = val
                if (val) {
                    this.fetchClientByCostCenter(val)
                    this.errorCostCenter = false
                    this.errorCostCenterMessage = ''
                } else if (this.search.length > 0) {
                    this.errorCostCenter = true
                    this.errorCostCenterMessage = 'Seleziona un centro di costo valido'
                }
            })
        },
        
        async searchItems() {
            if (this.search.length < 2) {
                this.results = [];
                this.open = this.search.length > 0;
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
        
        async fetchClientByCostCenter(costCenterId) {
            try {
                const response = await fetch(`{{ route('admin.api.cost-center-client') }}?id=${costCenterId}`);
                const data = await response.json();
                if (data && data.id_cliente) {
                    const clientEvent = new CustomEvent('set-client-data', {
                        detail: { client: data }
                    });
                    window.dispatchEvent(clientEvent);
                }
            } catch (error) {
                console.error('Errore nel recupero cliente:', error);
            }
        },
        
        selectItem(item) {
            this.search = item.Nome;
            this.selectedId = item.id;
            this.open = false;
            this.results = [];
            Alpine.store('costCenter').selectedId = item.id;
            this.errorCostCenter = false
            this.errorCostCenterMessage = ''
        },
        
        moveDown() { if (this.highlightIndex < this.results.length - 1) { this.highlightIndex++; this.scrollToHighlight(); } },
        moveUp() { if (this.highlightIndex > 0) { this.highlightIndex--; this.scrollToHighlight(); } },
        selectHighlighted() { if (this.highlightIndex >= 0 && this.results[this.highlightIndex]) { this.selectItem(this.results[this.highlightIndex]); } },
        scrollToHighlight() { this.$nextTick(() => { const h = document.querySelector('.bg-lime-50'); if (h) h.scrollIntoView({ block: 'nearest' }); }); }
    }));
    
    // Cliente Autocomplete (solo clienti)
    Alpine.data('clientAutocomplete', () => ({
        search: '',
        selectedId: '',
        results: [],
        open: false,
        loading: false,
        highlightIndex: -1,
        errorClient: false,
        errorClientMessage: '',
        
        init() {
            this.$watch('selectedId', val => {
                Alpine.store('client').selectedId = val
                if (val) {
                    this.errorClient = false
                    this.errorClientMessage = ''
                } else if (this.search.length > 0) {
                    this.errorClient = true
                    this.errorClientMessage = 'Seleziona un cliente valido'
                }
            })
            
            window.addEventListener('set-client-data', (event) => {
                if (event.detail.client) {
                    const client = event.detail.client
                    this.search = client.ragione_sociale || (client.nome + ' ' + (client.cognome || ''))
                    this.selectedId = client.id_cliente
                    this.open = false
                }
            })
        },
        
        async searchItems() {
            if (this.search.length < 2) {
                this.results = [];
                this.open = this.search.length > 0;
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
            this.errorClient = false
            this.errorClientMessage = ''
        },
        
        moveDown() { if (this.highlightIndex < this.results.length - 1) this.highlightIndex++; this.scrollToHighlight(); },
        moveUp() { if (this.highlightIndex > 0) this.highlightIndex--; this.scrollToHighlight(); },
        selectHighlighted() { if (this.highlightIndex >= 0 && this.results[this.highlightIndex]) this.selectItem(this.results[this.highlightIndex]); },
        scrollToHighlight() { this.$nextTick(() => { const h = document.querySelector('.bg-lime-50'); if (h) h.scrollIntoView({ block: 'nearest' }); }); }
    }));
    
    // Servizio Autocomplete
    Alpine.data('serviceAutocomplete', () => ({
        search: '',
        selectedId: '',
        results: [],
        open: false,
        loading: false,
        highlightIndex: -1,
        errorService: false,
        errorServiceMessage: '',
        
        init() {
            this.$watch('selectedId', val => {
                if (val) {
                    this.errorService = false
                    this.errorServiceMessage = ''
                } else if (this.search.length > 0) {
                    this.errorService = true
                    this.errorServiceMessage = 'Seleziona un servizio valido'
                }
            })
        },
        
        async searchItems() {
            if (this.search.length < 2) {
                this.results = [];
                this.open = this.search.length > 0;
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
            this.errorService = false
            this.errorServiceMessage = ''
        },
        
        moveDown() { if (this.highlightIndex < this.results.length - 1) this.highlightIndex++; this.scrollToHighlight(); },
        moveUp() { if (this.highlightIndex > 0) this.highlightIndex--; this.scrollToHighlight(); },
        selectHighlighted() { if (this.highlightIndex >= 0 && this.results[this.highlightIndex]) this.selectItem(this.results[this.highlightIndex]); },
        scrollToHighlight() { this.$nextTick(() => { const h = document.querySelector('.bg-lime-50'); if (h) h.scrollIntoView({ block: 'nearest' }); }); }
    }));
    
    // Staff Autocomplete - Versione migliorata per supportare più righe dinamiche
    window.staffAutocompleteInstances = {};
    
    Alpine.data('staffAutocomplete', (rowIndex) => ({
        index: rowIndex,
        search: '',
        selectedId: '',
        results: [],
        open: false,
        loading: false,
        highlightIndex: -1,
        errorStaff: false,
        errorStaffMessage: '',
        errorHours: false,
        errorHoursMessage: '',
        
        init() {
            // Registra l'istanza
            window.staffAutocompleteInstances[this.index] = this;
            
            this.$watch('selectedId', val => {
                if (val) {
                    this.errorStaff = false
                    this.errorStaffMessage = ''
                } else if (this.search.length > 0) {
                    this.errorStaff = true
                    this.errorStaffMessage = 'Seleziona un membro del personale'
                }
            })
        },
        
        async searchItems() {
            if (this.search.length < 2) {
                this.results = [];
                this.open = this.search.length > 0;
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
            this.errorStaff = false
            this.errorStaffMessage = ''
        },
        
        moveDown() { if (this.highlightIndex < this.results.length - 1) this.highlightIndex++; this.scrollToHighlight(); },
        moveUp() { if (this.highlightIndex > 0) this.highlightIndex--; this.scrollToHighlight(); },
        selectHighlighted() { if (this.highlightIndex >= 0 && this.results[this.highlightIndex]) this.selectItem(this.results[this.highlightIndex]); },
        scrollToHighlight() { this.$nextTick(() => { const h = document.querySelector('.bg-lime-50'); if (h) h.scrollIntoView({ block: 'nearest' }); }); }
    }));
});

// Validazione lato client
function validateForm() {
    let isValid = true;
    
    // 1. Validazione Centro di Costo
    const costCenterId = document.querySelector('input[name="id_cost_centers"]')?.value;
    const costCenterComponent = document.querySelector('[x-data="costCenterAutocomplete()"]')?.__x?.$data;
    if (costCenterComponent) {
        if (!costCenterId || costCenterId === '') {
            costCenterComponent.errorCostCenter = true;
            costCenterComponent.errorCostCenterMessage = 'Seleziona un centro di costo valido';
            isValid = false;
        } else {
            costCenterComponent.errorCostCenter = false;
            costCenterComponent.errorCostCenterMessage = '';
        }
    }
    
    // 2. Validazione Cliente
    const clientId = document.querySelector('input[name="id_entities"]')?.value;
    const clientComponent = document.querySelector('[x-data="clientAutocomplete()"]')?.__x?.$data;
    if (clientComponent) {
        if (!clientId || clientId === '') {
            clientComponent.errorClient = true;
            clientComponent.errorClientMessage = 'Seleziona un cliente valido';
            isValid = false;
        } else {
            clientComponent.errorClient = false;
            clientComponent.errorClientMessage = '';
        }
    }
    
    // 3. Validazione Servizio
    const serviceId = document.querySelector('input[name="id_services"]')?.value;
    const serviceComponent = document.querySelector('[x-data="serviceAutocomplete()"]')?.__x?.$data;
    if (serviceComponent) {
        if (!serviceId || serviceId === '') {
            serviceComponent.errorService = true;
            serviceComponent.errorServiceMessage = 'Seleziona un servizio valido';
            isValid = false;
        } else {
            serviceComponent.errorService = false;
            serviceComponent.errorServiceMessage = '';
        }
    }
    
    // 4. Validazione Personale e N. Ore
    const staffRows = document.querySelectorAll('.staff-row:not(.staff-row-template)');
    const globalErrorEl = document.getElementById('staffGlobalError');
    
    if (staffRows.length === 0) {
        if (globalErrorEl) {
            globalErrorEl.classList.remove('hidden');
            isValid = false;
        }
    } else {
        if (globalErrorEl) globalErrorEl.classList.add('hidden');
        
        staffRows.forEach((row, idx) => {
            // Trova l'istanza Alpine tramite l'attributo x-data
            const staffDiv = row.querySelector('[x-data^="staffAutocomplete"]');
            let staffComponent = null;
            
            if (staffDiv && staffDiv.__x) {
                staffComponent = staffDiv.__x.$data;
            }
            
            const hoursInput = row.querySelector('input[name*="[n_ore]"]') || row.querySelector('input[name="staff[0][n_ore]"]');
            const hours = parseFloat(hoursInput?.value || '0');
            
            if (staffComponent) {
                if (!staffComponent.selectedId || staffComponent.selectedId === '') {
                    staffComponent.errorStaff = true;
                    staffComponent.errorStaffMessage = 'Seleziona un membro del personale';
                    isValid = false;
                } else {
                    staffComponent.errorStaff = false;
                    staffComponent.errorStaffMessage = '';
                }
                
                if (hours <= 0) {
                    staffComponent.errorHours = true;
                    staffComponent.errorHoursMessage = 'Le ore devono essere maggiori di zero';
                    isValid = false;
                } else {
                    staffComponent.errorHours = false;
                    staffComponent.errorHoursMessage = '';
                }
            }
        });
    }
    
    return isValid;
}

// Submit con validazione
document.addEventListener('DOMContentLoaded', function() {
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
        submitBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (validateForm()) {
                document.getElementById('activityForm').submit();
            } else {
                // Scroll al primo errore
                const firstError = document.querySelector('.border-red-500, .text-red-500');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    }
});

// Gestione righe personale
let staffIndex = 1;

document.getElementById('addStaffBtn')?.addEventListener('click', function() {
    const template = document.querySelector('.staff-row-template');
    if (!template) return;
    
    const clone = template.cloneNode(true);
    clone.classList.remove('staff-row-template', 'hidden');
    clone.classList.add('staff-row');
    
    let html = clone.outerHTML;
    html = html.replace(/__INDEX__/g, staffIndex);
    
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = html;
    const newRow = tempDiv.firstElementChild;
    
    document.getElementById('staffContainer').appendChild(newRow);
    
    // Reinizializza Alpine per la nuova riga
    if (window.Alpine && newRow) {
        window.Alpine.initTree(newRow);
    }
    
    const removeBtn = newRow.querySelector('.remove-staff-btn');
    if (removeBtn) removeBtn.style.display = 'block';
    
    staffIndex++;
});

document.getElementById('staffContainer')?.addEventListener('click', function(e) {
    const removeBtn = e.target.closest('.remove-staff-btn');
    if (removeBtn) {
        const row = removeBtn.closest('.staff-row');
        if (row && document.querySelectorAll('.staff-row').length > 1) {
            row.remove();
            const globalErrorEl = document.getElementById('staffGlobalError');
            if (globalErrorEl && document.querySelectorAll('.staff-row').length > 0) {
                globalErrorEl.classList.add('hidden');
            }
        } else {
            const globalErrorEl = document.getElementById('staffGlobalError');
            if (globalErrorEl) globalErrorEl.classList.remove('hidden');
        }
    }
});
</script>
@endpush --}}

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    // Store condiviso per ID
    Alpine.store('costCenter', { selectedId: '' })
    Alpine.store('client', { selectedId: '' })
    
    // Centro di Costo Autocomplete
    Alpine.data('costCenterAutocomplete', () => ({
        search: '',
        selectedId: '',
        results: [],
        open: false,
        loading: false,
        highlightIndex: -1,
        errorCostCenter: false,
        errorCostCenterMessage: '',
        
        init() {
            this.$watch('selectedId', val => {
                Alpine.store('costCenter').selectedId = val
                if (val) {
                    this.fetchClientByCostCenter(val)
                    this.errorCostCenter = false
                    this.errorCostCenterMessage = ''
                } else if (this.search.length > 0) {
                    this.errorCostCenter = true
                    this.errorCostCenterMessage = 'Seleziona un centro di costo valido'
                }
            })
        },
        
        async searchItems() {
            if (this.search.length < 2) {
                this.results = [];
                this.open = this.search.length > 0;
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
        
        async fetchClientByCostCenter(costCenterId) {
            try {
                const response = await fetch(`{{ route('admin.api.cost-center-client') }}?id=${costCenterId}`);
                const data = await response.json();
                if (data && data.id_cliente) {
                    const clientEvent = new CustomEvent('set-client-data', {
                        detail: { client: data }
                    });
                    window.dispatchEvent(clientEvent);
                }
            } catch (error) {
                console.error('Errore nel recupero cliente:', error);
            }
        },
        
        selectItem(item) {
            this.search = item.Nome;
            this.selectedId = item.id;
            this.open = false;
            this.results = [];
            Alpine.store('costCenter').selectedId = item.id;
            this.errorCostCenter = false
            this.errorCostCenterMessage = ''
        },
        
        moveDown() { if (this.highlightIndex < this.results.length - 1) { this.highlightIndex++; this.scrollToHighlight(); } },
        moveUp() { if (this.highlightIndex > 0) { this.highlightIndex--; this.scrollToHighlight(); } },
        selectHighlighted() { if (this.highlightIndex >= 0 && this.results[this.highlightIndex]) { this.selectItem(this.results[this.highlightIndex]); } },
        scrollToHighlight() { this.$nextTick(() => { const h = document.querySelector('.bg-lime-50'); if (h) h.scrollIntoView({ block: 'nearest' }); }); }
    }));
    
    // Cliente Autocomplete (solo clienti)
    Alpine.data('clientAutocomplete', () => ({
        search: '',
        selectedId: '',
        results: [],
        open: false,
        loading: false,
        highlightIndex: -1,
        errorClient: false,
        errorClientMessage: '',
        
        init() {
            this.$watch('selectedId', val => {
                Alpine.store('client').selectedId = val
                if (val) {
                    this.errorClient = false
                    this.errorClientMessage = ''
                } else if (this.search.length > 0) {
                    this.errorClient = true
                    this.errorClientMessage = 'Seleziona un cliente valido'
                }
            })
            
            window.addEventListener('set-client-data', (event) => {
                if (event.detail.client) {
                    const client = event.detail.client
                    this.search = client.ragione_sociale || (client.nome + ' ' + (client.cognome || ''))
                    this.selectedId = client.id_cliente
                    this.open = false
                }
            })
        },
        
        async searchItems() {
            if (this.search.length < 2) {
                this.results = [];
                this.open = this.search.length > 0;
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
            this.errorClient = false
            this.errorClientMessage = ''
        },
        
        moveDown() { if (this.highlightIndex < this.results.length - 1) this.highlightIndex++; this.scrollToHighlight(); },
        moveUp() { if (this.highlightIndex > 0) this.highlightIndex--; this.scrollToHighlight(); },
        selectHighlighted() { if (this.highlightIndex >= 0 && this.results[this.highlightIndex]) this.selectItem(this.results[this.highlightIndex]); },
        scrollToHighlight() { this.$nextTick(() => { const h = document.querySelector('.bg-lime-50'); if (h) h.scrollIntoView({ block: 'nearest' }); }); }
    }));
    
    // Servizio Autocomplete
    Alpine.data('serviceAutocomplete', () => ({
        search: '',
        selectedId: '',
        results: [],
        open: false,
        loading: false,
        highlightIndex: -1,
        errorService: false,
        errorServiceMessage: '',
        
        init() {
            this.$watch('selectedId', val => {
                if (val) {
                    this.errorService = false
                    this.errorServiceMessage = ''
                } else if (this.search.length > 0) {
                    this.errorService = true
                    this.errorServiceMessage = 'Seleziona un servizio valido'
                }
            })
        },
        
        async searchItems() {
            if (this.search.length < 2) {
                this.results = [];
                this.open = this.search.length > 0;
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
            this.errorService = false
            this.errorServiceMessage = ''
        },
        
        moveDown() { if (this.highlightIndex < this.results.length - 1) this.highlightIndex++; this.scrollToHighlight(); },
        moveUp() { if (this.highlightIndex > 0) this.highlightIndex--; this.scrollToHighlight(); },
        selectHighlighted() { if (this.highlightIndex >= 0 && this.results[this.highlightIndex]) this.selectItem(this.results[this.highlightIndex]); },
        scrollToHighlight() { this.$nextTick(() => { const h = document.querySelector('.bg-lime-50'); if (h) h.scrollIntoView({ block: 'nearest' }); }); }
    }));
    
    // Staff Autocomplete - VERSIONE MIGLIORATA
    Alpine.data('staffAutocomplete', (rowIndex) => ({
        index: rowIndex,
        search: '',
        selectedId: '',
        results: [],
        open: false,
        loading: false,
        highlightIndex: -1,
        errorStaff: false,
        errorStaffMessage: '',
        errorHours: false,
        errorHoursMessage: '',
        
        init() {
            this.$watch('selectedId', val => {
                if (val) {
                    this.errorStaff = false
                    this.errorStaffMessage = ''
                } else if (this.search.length > 0) {
                    this.errorStaff = true
                    this.errorStaffMessage = 'Seleziona un membro del personale'
                }
            })
        },
        
        async searchItems() {
            if (this.search.length < 2) {
                this.results = [];
                this.open = this.search.length > 0;
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
            this.errorStaff = false
            this.errorStaffMessage = ''
        },
        
        moveDown() { if (this.highlightIndex < this.results.length - 1) this.highlightIndex++; this.scrollToHighlight(); },
        moveUp() { if (this.highlightIndex > 0) this.highlightIndex--; this.scrollToHighlight(); },
        selectHighlighted() { if (this.highlightIndex >= 0 && this.results[this.highlightIndex]) this.selectItem(this.results[this.highlightIndex]); },
        scrollToHighlight() { this.$nextTick(() => { const h = document.querySelector('.bg-lime-50'); if (h) h.scrollIntoView({ block: 'nearest' }); }); }
    }));
});

// ========== GESTIONE RIGHE STAFF ==========
let staffIndex = 1;

function addStaffRow() {
    const template = document.querySelector('.staff-row-template');
    if (!template) {
        console.error('Template non trovato!');
        return;
    }
    
    // Clona il template
    const clone = template.cloneNode(true);
    clone.classList.remove('staff-row-template', 'hidden');
    clone.classList.add('staff-row');
    
    // Sostituisci __INDEX__ con l'indice corrente
    let html = clone.outerHTML;
    html = html.replace(/__INDEX__/g, staffIndex);
    
    // Correggi i nomi dei campi
    html = html.replace(/name="staff\[__INDEX__\]\[n_ore\]"/g, `name="staff[${staffIndex}][n_ore]"`);
    html = html.replace(/name="staff\[__INDEX__\]\[spese\]"/g, `name="staff[${staffIndex}][spese]"`);
    html = html.replace(/name="staff\[__INDEX__\]\[note\]"/g, `name="staff[${staffIndex}][note]"`);
    html = html.replace(/name="staff\[__INDEX__\]\[id_staff\]"/g, `name="staff[${staffIndex}][id_staff]"`);
    
    // Se ci sono placeholder, sostituiscili
    html = html.replace(/staff\[__INDEX__\]/g, `staff[${staffIndex}]`);
    
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = html;
    const newRow = tempDiv.firstElementChild;
    
    // Forza l'impostazione dei nomi
    const hoursInput = newRow.querySelector('input[type="number"][step="0.5"]');
    if (hoursInput && !hoursInput.getAttribute('name')) {
        hoursInput.setAttribute('name', `staff[${staffIndex}][n_ore]`);
        hoursInput.setAttribute('value', '0');
    }
    
    const expensesInput = newRow.querySelectorAll('input[type="number"][step="0.01"]');
    if (expensesInput[0] && !expensesInput[0].getAttribute('name')) {
        expensesInput[0].setAttribute('name', `staff[${staffIndex}][spese]`);
        expensesInput[0].setAttribute('value', '0');
    }
    
    const noteInput = newRow.querySelector('input[placeholder="Note aggiuntive"]');
    if (noteInput && !noteInput.getAttribute('name')) {
        noteInput.setAttribute('name', `staff[${staffIndex}][note]`);
    }
    
    // Mostra il pulsante remove
    const removeBtn = newRow.querySelector('.remove-staff-btn');
    if (removeBtn) {
        removeBtn.style.display = 'block';
    }
    
    // Aggiungi la riga al container
    const container = document.getElementById('staffContainer');
    container.appendChild(newRow);
    
    // Reinizializza Alpine per la nuova riga
    if (window.Alpine && newRow) {
        window.Alpine.initTree(newRow);
    }
    
    staffIndex++;
}

function removeStaffRow(button) {
    const row = button.closest('.staff-row');
    const allRows = document.querySelectorAll('.staff-row:not(.staff-row-template)');
    
    if (allRows.length > 1) {
        row.remove();
        const globalErrorEl = document.getElementById('staffGlobalError');
        if (globalErrorEl && document.querySelectorAll('.staff-row:not(.staff-row-template)').length > 0) {
            globalErrorEl.classList.add('hidden');
        }
    } else {
        // Non permettere di rimuovere l'ultima riga
        const globalErrorEl = document.getElementById('staffGlobalError');
        if (globalErrorEl) {
            globalErrorEl.classList.remove('hidden');
        }
        alert('Devi avere almeno un membro del personale');
    }
}

// Inizializzazione eventi
document.addEventListener('DOMContentLoaded', function() {
    // Pulsante aggiungi staff
    const addBtn = document.getElementById('addStaffBtn');
    if (addBtn) {
        // Rimuovi vecchi listener
        const newAddBtn = addBtn.cloneNode(true);
        addBtn.parentNode.replaceChild(newAddBtn, addBtn);
        newAddBtn.addEventListener('click', addStaffRow);
    }
    
    // Delegazione eventi per pulsanti remove (per gestire anche le righe aggiunte dinamicamente)
    document.getElementById('staffContainer')?.addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.remove-staff-btn');
        if (removeBtn) {
            e.preventDefault();
            removeStaffRow(removeBtn);
        }
    });
    
    // Validazione form al submit
    const submitBtn = document.getElementById('submitBtn');
    const form = document.getElementById('activityForm');
    
    if (submitBtn) {
        submitBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Validazione base
            const costCenterId = document.querySelector('input[name="id_cost_centers"]')?.value;
            const serviceId = document.querySelector('input[name="id_services"]')?.value;
            
            if (!costCenterId) {
                alert('⚠️ Seleziona un Centro di Costo');
                return;
            }
            if (!serviceId) {
                alert('⚠️ Seleziona un Servizio');
                return;
            }
            
            // Validazione staff
            const staffRows = document.querySelectorAll('.staff-row:not(.staff-row-template)');
            let hasValidStaff = false;
            let staffErrors = [];
            
            staffRows.forEach((row, idx) => {
                const staffId = row.querySelector('input[name*="[id_staff]"]')?.value;
                const hoursInput = row.querySelector('input[name*="[n_ore]"]');
                const hours = parseFloat(hoursInput?.value || '0');
                
                if (!staffId || staffId === '') {
                    staffErrors.push(`Riga ${idx + 1}: Personale non selezionato`);
                } else if (hours <= 0) {
                    staffErrors.push(`Riga ${idx + 1}: Ore deve essere maggiore di 0 (attuale: ${hours})`);
                } else {
                    hasValidStaff = true;
                }
            });
            
            if (!hasValidStaff) {
                alert(`⚠️ Errori nello staff:\n${staffErrors.join('\n')}`);
                return;
            }
            
            // Submitting del form
            console.log('Form valido, invio...');
            form.submit();
        });
    }
});

// Debug per vedere quante righe staff vengono inviate
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('activityForm');
    if (form) {
        form.addEventListener('submit', function() {
            const staffRows = document.querySelectorAll('.staff-row:not(.staff-row-template)');
            console.log(`📊 Invio form con ${staffRows.length} righe staff`);
            staffRows.forEach((row, idx) => {
                const staffId = row.querySelector('input[name*="[id_staff]"]')?.value;
                const hours = row.querySelector('input[name*="[n_ore]"]')?.value;
                console.log(`  Staff ${idx + 1}: ID=${staffId}, Ore=${hours}`);
            });
        });
    }
});
</script>
@endpush
@endsection