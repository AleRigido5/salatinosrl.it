@extends('admin.layouts.app')

@section('title', 'Report Attività - ' . $staff->NomePers . ' ' . $staff->CognomePers)

@section('content')
<style>
    /* Alert popup styles */
    .alert-popup {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        animation: slideInRight 0.3s ease-out;
    }
    
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .alert-popup.success {
        background-color: #10b981;
        color: white;
        border-left: 4px solid #047857;
    }
    
    .alert-popup.error {
        background-color: #ef4444;
        color: white;
        border-left: 4px solid #b91c1c;
    }
    
    .alert-popup.info {
        background-color: #3b82f6;
        color: white;
        border-left: 4px solid #1e40af;
    }
    
    /* Tooltip per Giornate Effettive */
    .info-tooltip {
        position: relative;
        display: inline-block;
        cursor: help;
        margin-left: 5px;
    }
    
    .info-tooltip .tooltip-text {
        visibility: hidden;
        background-color: #1f2937;
        color: #fff;
        text-align: center;
        padding: 8px 12px;
        border-radius: 6px;
        position: fixed;
        z-index: 99999;
        font-size: 12px;
        font-weight: normal;
        font-family: system-ui, -apple-system, sans-serif;
        white-space: nowrap;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        opacity: 0;
        transition: opacity 0.2s;
        pointer-events: none;
    }
    
    .info-tooltip .tooltip-text::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        margin-left: -5px;
        border-width: 5px;
        border-style: solid;
        border-color: #1f2937 transparent transparent transparent;
    }
    
    .info-tooltip:hover .tooltip-text {
        visibility: visible;
        opacity: 1;
    }
    
    [x-cloak] {
        display: none !important;
    }
</style>

<div class="p-6">
    <!-- Header -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-clipboard-user text-lime-500 mr-2"></i> 
                Report Attività: {{ $staff->NomePers }} {{ $staff->CognomePers }}
            </h1>
            <p class="text-gray-500 mt-1">
                <i class="fas fa-calendar-alt mr-1"></i> Periodo: {{ $dateFrom->format('d/m/Y') }} - {{ $dateTo->format('d/m/Y') }}
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.staff.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>
    </div>

    <!-- Filtri - Tutto su una riga -->
    <div class="bg-white rounded-lg shadow mb-6 p-4 border border-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <!-- Range personalizzato a sinistra -->
            <div class="flex items-center gap-3">
                <input type="date" name="date_from" value="{{ request('date_from', $dateFrom->format('Y-m-d')) }}" 
                       id="date_from"
                       class="px-3 py-1.5 border border-gray-300 rounded-md text-sm">
                <span class="text-gray-500">→</span>
                <input type="date" name="date_to" value="{{ request('date_to', $dateTo->format('Y-m-d')) }}" 
                       id="date_to"
                       class="px-3 py-1.5 border border-gray-300 rounded-md text-sm">
                <button type="button" id="applyDateRange" class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-4 py-1.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 text-sm">
                    Applica
                </button>
            </div>

            <!-- Aggiornamento costo orario di massa con Alpine.js Modal -->
            <div x-data="bulkUpdateCosto()" class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-200">
                <label class="text-sm text-gray-600 font-medium whitespace-nowrap">
                    <i class="fas fa-euro-sign text-lime-500 mr-1"></i> Aggiorna costo/h:
                </label>
                <input type="number" step="0.5" x-model="newCosto" 
                       class="px-3 py-1.5 border border-gray-300 rounded-md text-sm w-28" 
                       placeholder="0.00">
                <button type="button" x-on:click="openConfirmModal" 
                        class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-4 py-1.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 text-sm">
                    Applica
                </button>
                
                <!-- Modal di conferma Alpine.js -->
                <div x-show="showConfirmModal" 
                     x-cloak
                     class="fixed inset-0 z-[200] flex items-center justify-center bg-black bg-opacity-50"
                     x-on:click.away="showConfirmModal = false">
                    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 overflow-hidden">
                        <div class="bg-blue-500 px-6 py-4">
                            <h3 class="text-lg font-semibold text-white">Conferma Aggiornamento Massivo</h3>
                        </div>
                        <div class="px-6 py-4">
                            <p class="text-gray-700 mb-4">
                                Sei sicuro di voler impostare il costo orario a 
                                <strong class="text-blue-600">€ <span x-text="parseFloat(newCosto).toFixed(2)"></span></strong> 
                                per <strong class="text-blue-600" x-text="totalActivities + ' attività'"></strong>?
                            </p>
                            <p class="text-sm text-gray-500">Questa operazione è definitiva e modificherà tutti i record nel database.</p>
                        </div>
                        <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                            <button type="button" 
                                    x-on:click="showConfirmModal = false"
                                    class="px-4 py-2 text-sm bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors">
                                Annulla
                            </button>
                            <button type="button" 
                                    x-on:click="confirmUpdate"
                                    x-bind:disabled="isUpdating"
                                    class="px-4 py-2 text-sm bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors disabled:opacity-50">
                                <span x-show="!isUpdating">Conferma</span>
                                <span x-show="isUpdating"><i class="fas fa-spinner fa-spin mr-1"></i> Aggiornamento...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigazione e select a destra -->
            <div class="flex items-center gap-4">
                <div class="flex gap-2">
                    <a href="{{ route('admin.staff.activity-report', ['staff' => $staff->id_personale, 'month' => $previousMonth->format('m'), 'year' => $previousMonth->format('Y')]) }}" 
                       class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 rounded-md transition-colors" title="Mese precedente">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <a href="{{ route('admin.staff.activity-report', ['staff' => $staff->id_personale, 'month' => $nextMonth->format('m'), 'year' => $nextMonth->format('Y')]) }}" 
                       class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 rounded-md transition-colors" title="Mese successivo">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <div class="flex items-center gap-2">
                    <select id="monthSelect" class="px-3 py-1.5 border border-gray-300 rounded-md text-sm">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}" {{ $selectedMonth == str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                                {{ Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                            </option>
                        @endforeach
                    </select>
                    <select id="yearSelect" class="px-3 py-1.5 border border-gray-300 rounded-md text-sm">
                        @for($y = $currentYear - 2; $y <= $currentYear + 1; $y++)
                            <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                    <button type="button" id="goToMonth" class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-4 py-1.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 text-sm">
                        Vai
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Container per notifiche -->
    <div x-data="alertManager()" x-cloak>
        <template x-for="(alert, index) in alerts" :key="index">
            <div class="alert-popup shadow-lg rounded-lg p-4 mb-3 min-w-[300px] flex items-center justify-between"
                 :class="alert.type"
                 x-init="setTimeout(() => removeAlert(index), 5000)">
                <div class="flex items-center gap-2">
                    <i x-show="alert.type === 'success'" class="fas fa-check-circle text-white"></i>
                    <i x-show="alert.type === 'error'" class="fas fa-exclamation-circle text-white"></i>
                    <i x-show="alert.type === 'info'" class="fas fa-info-circle text-white"></i>
                    <span class="text-white text-sm" x-text="alert.message"></span>
                </div>
                <button x-on:click="removeAlert(index)" class="text-white hover:text-gray-200 ml-4">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </template>
    </div>

    <!-- Tabella Attività -->
    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente / Cantiere</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Località / Servizio</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">N. Ore</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Costo €/h</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Spese (€)</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Note</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="activitiesTableBody">
                    @forelse($activities as $activity)
                    @php
                        $entity = $activity->entity;
                        $costCenter = $activity->costCenter;
                        $clienteNome = $entity ? ($entity->ragione_sociale ?: ($entity->nome . ' ' . $entity->cognome)) : '-';
                        $cantiereNome = $costCenter ? $costCenter->Nome : '-';
                        $localita = $costCenter ? $costCenter->Localita : '-';
                        $serviceName = $activity->service ? $activity->service->Titolo : '-';
                        $staffDetail = $activity->staffDetails->first();
                        $staffDetailId = $staffDetail ? $staffDetail->id : null;
                        $ore = floatval($staffDetail->n_ore ?? 0);
                        $costoOrario = floatval($staffDetail->costo_orario ?? 0);
                        $spese = floatval($staffDetail->spese ?? 0);
                        $staffDetailNote = $staffDetail->note ?? '';
                        
                        // Ottieni la città dal cliente
                        $clienteCitta = '';
                        if ($entity && $entity->addresses && $entity->addresses->isNotEmpty()) {
                            $primaryAddress = $entity->addresses->firstWhere('sede', 'principale') ?? $entity->addresses->first();
                            $clienteCitta = $primaryAddress->citta ?? '';
                        }
                        
                        // Determina la località da mostrare
                        $displayLocalita = '-';
                        if ($localita && $localita != '-') {
                            $displayLocalita = $localita;
                        } elseif ($clienteCitta) {
                            $displayLocalita = $clienteCitta;
                        }
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors" data-ore="{{ $ore }}">
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                            {{ Carbon\Carbon::parse($activity->data_activities)->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <div class="flex flex-col">
                                <span class="font-medium text-gray-800">{{ html_entity_decode($clienteNome, ENT_QUOTES, 'UTF-8') }}</span>
                                @if($cantiereNome != '-')
                                    <span class="text-xs text-gray-500">{{ html_entity_decode($cantiereNome, ENT_QUOTES, 'UTF-8') }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <div class="flex flex-col">
                                @if($displayLocalita != '-')
                                    <span class="text-gray-600">
                                        <i class="fas fa-map-marker-alt text-gray-400 mr-1 text-xs"></i>
                                        {{ html_entity_decode($displayLocalita, ENT_QUOTES, 'UTF-8') }}
                                    </span>
                                @endif
                                <span class="text-gray-800 mt-1">
                                    <i class="fas fa-tag text-gray-400 mr-1 text-xs"></i>
                                    {{ html_entity_decode($serviceName, ENT_QUOTES, 'UTF-8') }}
                                </span>
                            </div>
                        </td>
                        
                        <!-- N. Ore modificabile -->
                        <td class="px-4 py-3 text-sm text-center relative">
                            <div x-data="{ 
                                ore: {{ $ore }},
                                showTooltip: false,
                                isEditing: false,
                                editedValue: {{ $ore }},
                                tooltipStyle: { display: 'none' },
                                saveOre() {
                                    this.isEditing = true;
                                    fetch('{{ route('admin.staff.update-ore', $staffDetailId) }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                        },
                                        body: JSON.stringify({ value: this.editedValue })
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            this.ore = parseFloat(this.editedValue);
                                            this.showTooltip = false;
                                            window.dispatchEvent(new CustomEvent('show-alert', {
                                                detail: { type: 'success', message: 'Ore aggiornate con successo!' }
                                            }));
                                            window.dispatchEvent(new CustomEvent('update-totals'));
                                            window.dispatchEvent(new CustomEvent('update-giornate'));
                                        }
                                        this.isEditing = false;
                                    })
                                    .catch(() => {
                                        this.isEditing = false;
                                        window.dispatchEvent(new CustomEvent('show-alert', {
                                            detail: { type: 'error', message: 'Errore durante l\'aggiornamento' }
                                        }));
                                    });
                                },
                                cancelOre() {
                                    this.editedValue = this.ore;
                                    this.showTooltip = false;
                                },
                                positionTooltip(event) {
                                    const rect = event.target.getBoundingClientRect();
                                    this.tooltipStyle = {
                                        display: 'block',
                                        position: 'fixed',
                                        left: (rect.left + rect.width/2 - 100) + 'px',
                                        top: (rect.bottom + 10) + 'px',
                                        zIndex: 100
                                    };
                                }
                            }">
                                <span class="font-medium cursor-pointer hover:text-lime-600 hover:underline"
                                    x-on:click="showTooltip = true; editedValue = ore; positionTooltip($event)">
                                    <span x-text="ore.toFixed(1)">{{ number_format($ore, 1) }}</span>
                                </span>
                                <div x-show="showTooltip" 
                                    x-on:click.away="cancelOre()"
                                    x-on:keydown.window.escape="cancelOre()"
                                    class="bg-white border border-gray-300 rounded-lg shadow-xl p-3 min-w-[200px]"
                                    x-bind:style="tooltipStyle"
                                    x-cloak>
                                    <div class="absolute -top-2 left-1/2 transform -translate-x-1/2 w-4 h-4 bg-white border-l border-t border-gray-300 rotate-45"></div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">N. Ore</label>
                                    <input type="number" step="0.5" x-model="editedValue" 
                                           x-on:keydown.enter="saveOre()"
                                           x-on:keydown.escape="cancelOre()"
                                           class="w-full px-2 py-1 text-sm border rounded-md focus:ring-2 focus:ring-lime-500" placeholder="0">
                                    <div class="flex justify-end gap-2 mt-2">
                                        <button type="button" x-on:click="cancelOre()" class="px-2 py-1 text-xs bg-gray-200 rounded hover:bg-gray-300">Annulla</button>
                                        <button type="button" x-on:click="saveOre()" x-bind:disabled="isEditing" class="px-2 py-1 text-xs bg-lime-500 text-white rounded hover:bg-lime-600">Salva</button>
                                    </div>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Costo €/h modificabile -->
                        <td class="px-4 py-3 text-sm text-center relative" data-staff-detail-id="{{ $staffDetailId }}">
                            <div x-data="{ 
                                costoOrario: {{ $costoOrario }},
                                staffDetailId: '{{ $staffDetailId }}',
                                showTooltip: false,
                                newValue: '',
                                tooltipStyle: { display: 'none' },
                                
                                saveCosto() {
                                    if (this.newValue === '') {
                                        window.dispatchEvent(new CustomEvent('show-alert', {
                                            detail: { type: 'error', message: '⚠️ Inserisci un valore valido' }
                                        }));
                                        return;
                                    }
                                    
                                    const valueToSave = parseFloat(this.newValue);
                                    if (isNaN(valueToSave)) {
                                        window.dispatchEvent(new CustomEvent('show-alert', {
                                            detail: { type: 'error', message: '⚠️ Inserisci un numero valido' }
                                        }));
                                        return;
                                    }
                                    
                                    fetch('{{ route('admin.staff.update-costo-orario', $staffDetailId) }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                        },
                                        body: JSON.stringify({ value: valueToSave })
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            this.costoOrario = valueToSave;
                                            this.showTooltip = false;
                                            this.newValue = '';
                                            window.dispatchEvent(new CustomEvent('show-alert', {
                                                detail: { type: 'success', message: 'Costo orario aggiornato con successo!' }
                                            }));
                                            window.dispatchEvent(new CustomEvent('update-totals'));
                                        } else {
                                            window.dispatchEvent(new CustomEvent('show-alert', {
                                                detail: { type: 'error', message: 'Errore durante l\'aggiornamento' }
                                            }));
                                        }
                                    })
                                    .catch(() => {
                                        window.dispatchEvent(new CustomEvent('show-alert', {
                                            detail: { type: 'error', message: '❌ Errore durante l\'aggiornamento' }
                                        }));
                                    });
                                },
                                
                                openTooltip(event) {
                                    this.newValue = '';
                                    this.showTooltip = true;
                                    this.positionTooltip(event);
                                },
                                
                                cancelCosto() {
                                    this.showTooltip = false;
                                    this.newValue = '';
                                },
                                
                                positionTooltip(event) {
                                    const rect = event.target.getBoundingClientRect();
                                    this.tooltipStyle = {
                                        display: 'block',
                                        position: 'fixed',
                                        left: (rect.left + rect.width/2 - 100) + 'px',
                                        top: (rect.bottom + 10) + 'px',
                                        zIndex: 10000
                                    };
                                },
                                
                                updateFromOutside(newValue) {
                                    this.costoOrario = newValue;
                                }
                            }"
                            x-init="$nextTick(() => {
                                if (!window.costoComponents) window.costoComponents = {};
                                window.costoComponents[this.staffDetailId] = this;
                                
                                window.addEventListener('bulk-update-costo', (event) => {
                                    if (event.detail && event.detail.id === this.staffDetailId) {
                                        this.updateFromOutside(event.detail.newValue);
                                    }
                                });
                            })">
                                <span class="font-medium cursor-pointer hover:text-lime-600 hover:underline"
                                    x-on:click="openTooltip($event)">
                                    € <span x-text="costoOrario.toFixed(2)"></span>
                                </span>
                                <div x-show="showTooltip" 
                                    x-on:click.away="cancelCosto()"
                                    x-on:keydown.window.escape="cancelCosto()"
                                    class="fixed bg-white border border-gray-300 rounded-lg shadow-xl p-3 min-w-[200px]"
                                    x-bind:style="tooltipStyle"
                                    x-cloak>
                                    <div class="absolute -top-2 left-1/2 transform -translate-x-1/2 w-4 h-4 bg-white border-l border-t border-gray-300 rotate-45"></div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Nuovo Costo €/h</label>
                                    <input type="number" step="0.5" x-model="newValue" 
                                        x-on:keydown.enter="saveCosto()"
                                        x-on:keydown.escape="cancelCosto()"
                                        class="w-full px-2 py-1 text-sm border rounded-md focus:ring-2 focus:ring-lime-500" 
                                        placeholder="Inserisci nuovo valore">
                                    <div class="flex justify-end gap-2 mt-2">
                                        <button type="button" x-on:click="cancelCosto()" class="px-2 py-1 text-xs bg-gray-200 rounded hover:bg-gray-300">Annulla</button>
                                        <button type="button" x-on:click="saveCosto()" class="px-2 py-1 text-xs bg-lime-500 text-white rounded hover:bg-lime-600">Salva</button>
                                    </div>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Spese modificabili -->
                        <td class="px-4 py-3 text-sm text-center relative">
                            <div x-data="{ 
                                spese: {{ $spese }},
                                showTooltip: false,
                                isEditing: false,
                                editedValue: {{ $spese }},
                                tooltipStyle: { display: 'none' },
                                saveSpese() {
                                    this.isEditing = true;
                                    fetch('{{ route('admin.staff.update-spese', $staffDetailId) }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                        },
                                        body: JSON.stringify({ value: this.editedValue })
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            this.spese = parseFloat(this.editedValue);
                                            this.showTooltip = false;
                                            window.dispatchEvent(new CustomEvent('show-alert', {
                                                detail: { type: 'success', message: 'Spese aggiornate con successo!' }
                                            }));
                                            window.dispatchEvent(new CustomEvent('update-totals'));
                                        }
                                        this.isEditing = false;
                                    })
                                    .catch(() => {
                                        this.isEditing = false;
                                        window.dispatchEvent(new CustomEvent('show-alert', {
                                            detail: { type: 'error', message: '❌ Errore durante l\'aggiornamento' }
                                        }));
                                    });
                                },
                                cancelSpese() {
                                    this.editedValue = this.spese;
                                    this.showTooltip = false;
                                },
                                positionTooltip(event) {
                                    const rect = event.target.getBoundingClientRect();
                                    this.tooltipStyle = {
                                        display: 'block',
                                        position: 'fixed',
                                        left: (rect.left + rect.width/2 - 100) + 'px',
                                        bottom: (window.innerHeight - rect.top + 10) + 'px',
                                        zIndex: 100
                                    };
                                }
                            }">
                                <span class="font-medium cursor-pointer hover:text-lime-600 hover:underline"
                                    x-on:click="showTooltip = true; editedValue = spese; positionTooltip($event)">
                                    € <span x-text="spese.toFixed(2)">{{ number_format($spese, 2) }}</span>
                                </span>
                                <div x-show="showTooltip" 
                                    x-on:click.away="cancelSpese()"
                                    x-on:keydown.window.escape="cancelSpese()"
                                    class="bg-white border border-gray-300 rounded-lg shadow-xl p-3 min-w-[200px]"
                                    x-bind:style="tooltipStyle"
                                    x-cloak>
                                    <div class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 w-4 h-4 bg-white border-r border-b border-gray-300 rotate-45"></div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Spese (€)</label>
                                    <input type="number" step="0.01" x-model="editedValue" 
                                           x-on:keydown.enter="saveSpese()"
                                           x-on:keydown.escape="cancelSpese()"
                                           class="w-full px-2 py-1 text-sm border rounded-md focus:ring-2 focus:ring-lime-500" placeholder="0.00">
                                    <div class="flex justify-end gap-2 mt-2">
                                        <button type="button" x-on:click="cancelSpese()" class="px-2 py-1 text-xs bg-gray-200 rounded hover:bg-gray-300">Annulla</button>
                                        <button type="button" x-on:click="saveSpese()" x-bind:disabled="isEditing" class="px-2 py-1 text-xs bg-lime-500 text-white rounded hover:bg-lime-600">Salva</button>
                                    </div>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Note modificabili (da activities_staff_lnk) -->
                        <td class="px-4 py-3 text-sm text-gray-500 max-w-[300px]">
                            <div x-data="{ 
                                showTooltip: false, 
                                isEditing: false,
                                editedValue: '{{ addslashes($staffDetailNote) }}',
                                noteText: '{{ addslashes($staffDetailNote) }}',
                                saveNote() {
                                    this.isEditing = true;
                                    fetch('{{ route('admin.staff.update-staff-note', $staffDetailId) }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                        },
                                        body: JSON.stringify({ value: this.editedValue })
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            this.noteText = this.editedValue;
                                            this.showTooltip = false;
                                            window.dispatchEvent(new CustomEvent('show-alert', {
                                                detail: { type: 'success', message: 'Nota aggiornata con successo!' }
                                            }));
                                        }
                                        this.isEditing = false;
                                    })
                                    .catch(() => {
                                        this.isEditing = false;
                                        window.dispatchEvent(new CustomEvent('show-alert', {
                                            detail: { type: 'error', message: '❌ Errore durante l\'aggiornamento' }
                                        }));
                                    });
                                },
                                cancelNote() {
                                    this.editedValue = this.noteText;
                                    this.showTooltip = false;
                                }
                            }">
                                <div class="whitespace-normal break-words cursor-pointer hover:text-lime-600 hover:underline" 
                                    x-on:click="showTooltip = true; editedValue = noteText">
                                    <span x-text="noteText ? (noteText.length > 50 ? noteText.substring(0, 50) + '...' : noteText) : '-'">
                                        {{ $staffDetailNote ? (Str::limit($staffDetailNote, 50) ?: '-') : '-' }}
                                    </span>
                                </div>
                                <div x-show="showTooltip" 
                                    x-on:click.away="cancelNote()"
                                    x-on:keydown.window.escape="cancelNote()"
                                    class="fixed z-[100] bg-white border border-gray-300 rounded-lg shadow-xl p-4"
                                    style="top: 50%; left: 50%; transform: translate(-50%, -50%); width: 500px; max-width: 90vw;">
                                    <div class="flex justify-between items-center mb-3">
                                        <h3 class="text-md font-semibold text-gray-800">Modifica Nota</h3>
                                        <button type="button" x-on:click="cancelNote()" class="text-gray-400 hover:text-gray-600">✕</button>
                                    </div>
                                    <textarea x-model="editedValue" rows="6" 
                                              x-on:keydown.enter.prevent="saveNote()"
                                              x-on:keydown.escape="cancelNote()"
                                              class="w-full px-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-lime-500" placeholder="Inserisci nota..."></textarea>
                                    <div class="flex justify-end gap-2 mt-3">
                                        <button type="button" x-on:click="cancelNote()" class="px-4 py-2 text-sm bg-gray-200 rounded-md hover:bg-gray-300">Annulla</button>
                                        <button type="button" x-on:click="saveNote()" x-bind:disabled="isEditing" class="px-4 py-2 text-sm bg-lime-500 text-white rounded-md hover:bg-lime-600">Salva</button>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-gray-500">
                            <i class="fas fa-tasks text-4xl mb-2 text-gray-300"></i>
                            <p>Nessuna attività trovata per questo periodo</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Statistiche -->
    <div class="mt-6 grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-lg shadow p-4 border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 uppercase">Totale Ore</p>
                    <p class="text-2xl font-bold text-gray-800" id="totalHoursDisplay">{{ number_format($totalHours, 1) }} h</p>
                </div>
                <div class="h-12 w-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-blue-500 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Giornate Effettive con tooltip informativo e arrotondamento 0.5 -->
        <div class="bg-white rounded-lg shadow p-4 border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 uppercase flex items-center gap-1">
                        Giornate Effettive
                        <span class="info-tooltip">
                            <span class="tooltip-text" id="giornateTooltipText">
                                Calcolo in corso...
                            </span>
                        </span>
                    </p>
                    <p class="text-2xl font-bold text-gray-800" id="giornateEffettiveDisplay">
                        {{ number_format($totalWorkingDays, 1) }} gg
                    </p>
                    <p class="text-xs text-gray-400 mt-1">
                        Base {{ $hoursPerDay }} h/gg
                    </p>
                </div>
                <div class="h-12 w-12 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-calendar-day text-purple-500 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4 border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 uppercase">Maturato</p>
                    <p class="text-2xl font-bold text-green-600" id="totalMaturatoDisplay">€ {{ number_format($totalMaturato, 2) }}</p>
                </div>
                <div class="h-12 w-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-chart-line text-green-500 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4 border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 uppercase">Spese</p>
                    <p class="text-2xl font-bold text-orange-600" id="totalSpeseDisplay">€ {{ number_format($totalSpese, 2) }}</p>
                </div>
                <div class="h-12 w-12 bg-orange-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-receipt text-orange-500 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4 border border-gray-200 bg-gradient-to-r from-lime-50 to-lime-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 uppercase font-semibold">Totale</p>
                    <p class="text-2xl font-bold text-lime-600" id="totalTotaleDisplay">€ {{ number_format($totalMaturato + $totalSpese, 2) }}</p>
                </div>
                <div class="h-12 w-12 bg-lime-200 rounded-full flex items-center justify-center">
                    <i class="fas fa-euro-sign text-lime-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottoni Export PDF ed Excel -->
    <div class="mt-6 flex justify-end gap-4">
        <x-export-buttons
            pdf-route="admin.staff.export-report-pdf"
            excel-route="admin.staff.export-report-excel"
            :params="[
                'staff'     => $staff->id_personale,
                'date_from' => $dateFrom->format('Y-m-d'),
                'date_to'   => $dateTo->format('Y-m-d'),
            ]"
        />
    </div>
</div>

<script>
// Funzione per calcolare e aggiornare le Giornate Effettive con arrotondamento soglia 0.5
function updateGiornateEffettive() {
    const hoursPerDay = {{ $hoursPerDay }};
    let totalOre = 0;
    
    // Somma tutte le ore dalla tabella (colonna N. Ore)
    document.querySelectorAll('#activitiesTableBody tr:not(:has(td[colspan])) td:nth-child(4) span[x-text*="ore"]').forEach(span => {
        let ore = parseFloat(span.innerText);
        if (!isNaN(ore)) totalOre += ore;
    });
    
    // Calcolo giornate effettive non arrotondate
    const giornateRaw = totalOre / hoursPerDay;
    
    // Arrotondamento con soglia 0.5 (Math.round prende già soglia 0.5)
    // Ma per avere mezzo giorno (0.5) corretto: Math.round(numero * 2) / 2
    const giornateArrotondate = Math.round(giornateRaw * 2) / 2;
    
    // Aggiorna il display principale
    const displayElement = document.getElementById('giornateEffettiveDisplay');
    if (displayElement) {
        displayElement.textContent = giornateArrotondate.toFixed(1) + ' gg';
    }
    
    // Aggiorna il tooltip con il dettaglio del calcolo
    const tooltipElement = document.getElementById('giornateTooltipText');
    if (tooltipElement) {
        tooltipElement.innerHTML = `
            Calcolo dettagliato:<br>
            📊 Ore totali: ${totalOre.toFixed(1)} h<br>
            ➗ Divisione: ${totalOre.toFixed(1)} / ${hoursPerDay} = ${giornateRaw.toFixed(4)} gg<br>
            🎯 Arrotondamento (soglia 0.5): <strong>${giornateArrotondate.toFixed(1)} gg</strong><br>
            <span class="text-xs text-gray-300">↑ da 0.5 sale, &lt;0.5 scende</span>
        `;
    }
    
    // Log in console per debug
    console.log(`Giornate Effettive: ${totalOre.toFixed(1)}h / ${hoursPerDay}h = ${giornateRaw.toFixed(4)}gg → arrotondato: ${giornateArrotondate.toFixed(1)}gg`);
    
    return giornateArrotondate;
}

// Funzione per aggiornare tutti i totali (Ore, Maturato, Spese, Totale)
function updateAllTotals() {
    const rows = document.querySelectorAll('#activitiesTableBody tr:not(:has(td[colspan]))');
    let totalOre = 0;
    let totalMaturato = 0;
    let totalSpese = 0;
    
    rows.forEach(row => {
        // Estrai ore (colonna 4)
        const oreCell = row.querySelector('td:nth-child(4) span[x-text*="ore"]');
        let ore = 0;
        if (oreCell) {
            ore = parseFloat(oreCell.innerText);
            if (isNaN(ore)) ore = 0;
        }
        totalOre += ore;
        
        // Estrai costo orario (colonna 5)
        const costoCells = row.querySelectorAll('td:nth-child(5) span[x-text*="costoOrario"]');
        let costoOrario = 0;
        if (costoCells.length > 0) {
            // Cerca il secondo span che contiene il valore numerico
            for (let span of costoCells) {
                const text = span.innerText;
                if (text && !isNaN(parseFloat(text))) {
                    costoOrario = parseFloat(text);
                    break;
                }
            }
        }
        
        // Estrai spese (colonna 6)
        const speseCell = row.querySelector('td:nth-child(6) span[x-text*="spese"]');
        let spese = 0;
        if (speseCell) {
            spese = parseFloat(speseCell.innerText);
            if (isNaN(spese)) spese = 0;
        }
        
        totalMaturato += ore * costoOrario;
        totalSpese += spese;
    });
    
    // Aggiorna i display
    const totalOreDisplay = document.getElementById('totalHoursDisplay');
    if (totalOreDisplay) totalOreDisplay.textContent = totalOre.toFixed(1) + ' h';
    
    const totalMaturatoDisplay = document.getElementById('totalMaturatoDisplay');
    if (totalMaturatoDisplay) totalMaturatoDisplay.textContent = '€ ' + totalMaturato.toFixed(2);
    
    const totalSpeseDisplay = document.getElementById('totalSpeseDisplay');
    if (totalSpeseDisplay) totalSpeseDisplay.textContent = '€ ' + totalSpese.toFixed(2);
    
    const totalTotaleDisplay = document.getElementById('totalTotaleDisplay');
    if (totalTotaleDisplay) totalTotaleDisplay.textContent = '€ ' + (totalMaturato + totalSpese).toFixed(2);
    
    // Aggiorna anche le giornate effettive
    updateGiornateEffettive();
    
    return { totalOre, totalMaturato, totalSpese };
}

// Alpine.js component per il bulk update costo orario
function bulkUpdateCosto() {
    return {
        newCosto: '',
        showConfirmModal: false,
        isUpdating: false,
        totalActivities: 0,
        
        getCurrentDateRange() {
            const dateFrom = document.getElementById('date_from')?.value;
            const dateTo = document.getElementById('date_to')?.value;
            return { date_from: dateFrom, date_to: dateTo };
        },
        
        getStaffDetailIds() {
            const ids = [];
            document.querySelectorAll('td[data-staff-detail-id]').forEach(cell => {
                const id = cell.getAttribute('data-staff-detail-id');
                if (id && !ids.includes(id)) {
                    ids.push(id);
                }
            });
            return ids;
        },
        
        countActivities() {
            const count = this.getStaffDetailIds().length;
            this.totalActivities = count;
            return count;
        },
        
        openConfirmModal() {
            const value = parseFloat(this.newCosto);
            if (isNaN(value) || value <= 0) {
                window.dispatchEvent(new CustomEvent('show-alert', {
                    detail: { type: 'error', message: '⚠️ Inserisci un valore valido per il costo orario' }
                }));
                return;
            }
            
            const count = this.countActivities();
            if (count === 0) {
                window.dispatchEvent(new CustomEvent('show-alert', {
                    detail: { type: 'error', message: '⚠️ Nessuna attività trovata in questo report' }
                }));
                return;
            }
            
            this.showConfirmModal = true;
        },
        
        async confirmUpdate() {
            const newCostoValue = parseFloat(this.newCosto);
            const dateRange = this.getCurrentDateRange();
            
            if (!dateRange.date_from || !dateRange.date_to) {
                window.dispatchEvent(new CustomEvent('show-alert', {
                    detail: { type: 'error', message: '⚠️ Periodo non valido' }
                }));
                this.showConfirmModal = false;
                return;
            }
            
            this.isUpdating = true;
            
            try {
                const response = await fetch('{{ route("admin.staff.bulk-update-costo", $staff->id_personale) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        costo_orario: newCostoValue,
                        date_from: dateRange.date_from,
                        date_to: dateRange.date_to
                    })
                });
                
                const data = await response.json();
                
                this.showConfirmModal = false;
                
                if (data.success) {
                    // Aggiorna le celle
                    document.querySelectorAll('td[data-staff-detail-id]').forEach(cell => {
                        const costoElement = cell.querySelector('[x-text*="costoOrario"]');
                        if (costoElement && costoElement.__x) {
                            costoElement.__x.$data.costoOrario = newCostoValue;
                        }
                    });
                    
                    // Aggiorna i totali
                    updateAllTotals();
                    
                    window.dispatchEvent(new CustomEvent('show-alert', {
                        detail: { type: 'success', message: `✅ Costo orario aggiornato a € ${newCostoValue.toFixed(2)} per ${data.updated || this.totalActivities} attività di questo periodo!` }
                    }));
                } else {
                    window.dispatchEvent(new CustomEvent('show-alert', {
                        detail: { type: 'error', message: `❌ Errore: ${data.message || 'Operazione fallita'}` }
                    }));
                }
            } catch (error) {
                console.error('Bulk update error:', error);
                window.dispatchEvent(new CustomEvent('show-alert', {
                    detail: { type: 'error', message: `❌ Errore durante l'aggiornamento: ${error.message}` }
                }));
            } finally {
                this.isUpdating = false;
            }
        }
    }
}

// Alert Manager per notifiche popup
function alertManager() {
    return {
        alerts: [],
        
        addAlert(type, message) {
            this.alerts.push({ type, message });
            if (this.alerts.length > 5) {
                this.alerts.shift();
            }
        },
        
        removeAlert(index) {
            this.alerts.splice(index, 1);
        },
        
        init() {
            window.addEventListener('show-alert', (event) => {
                this.addAlert(event.detail.type, event.detail.message);
            });
        }
    }
}

// Gestione filtri data e navigazione
document.addEventListener('DOMContentLoaded', function() {
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');
    const applyBtn = document.getElementById('applyDateRange');
    const monthSelect = document.getElementById('monthSelect');
    const yearSelect = document.getElementById('yearSelect');
    const goToMonthBtn = document.getElementById('goToMonth');
    const currentUrl = '{{ route("admin.staff.activity-report", $staff->id_personale) }}';

    if (applyBtn) {
        applyBtn.addEventListener('click', function() {
            if (dateFrom.value && dateTo.value) {
                window.location.href = currentUrl + '?date_from=' + dateFrom.value + '&date_to=' + dateTo.value;
            }
        });
    }

    if (goToMonthBtn) {
        goToMonthBtn.addEventListener('click', function() {
            window.location.href = currentUrl + '?month=' + monthSelect.value + '&year=' + yearSelect.value;
        });
    }
    
    // Aggiorna i totali iniziali dopo il caricamento
    setTimeout(() => {
        updateAllTotals();
    }, 100);
    
    // Ascolta eventi di aggiornamento
    window.addEventListener('update-totals', updateAllTotals);
    window.addEventListener('update-giornate', updateGiornateEffettive);
});
</script>
@endsection