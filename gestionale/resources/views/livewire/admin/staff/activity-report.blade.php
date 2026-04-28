@extends('admin.layouts.app')

@section('title', 'Report Attività - ' . $staff->NomePers . ' ' . $staff->CognomePers)

@section('content')
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
                <i class="fas fa-arrow-left mr-2"></i> Torna al Personale
            </a>
        </div>
    </div>

    <!-- Navigazione per mese -->
    <div class="bg-white rounded-lg shadow mb-6 p-4 border border-gray-200">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="flex gap-2">
                    <a href="{{ route('admin.staff.activity-report', ['staff' => $staff->id_personale, 'month' => $previousMonth->format('m'), 'year' => $previousMonth->format('Y')]) }}" 
                       class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-md transition-colors">
                        <i class="fas fa-chevron-left"></i> Mese precedente
                    </a>
                    <a href="{{ route('admin.staff.activity-report', ['staff' => $staff->id_personale, 'month' => $nextMonth->format('m'), 'year' => $nextMonth->format('Y')]) }}" 
                       class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-md transition-colors">
                        Mese successivo <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
                
                <div class="flex items-center gap-2">
                    <form method="GET" action="{{ route('admin.staff.activity-report', $staff->id_personale) }}" class="flex items-center gap-2">
                        <select name="month" class="px-3 py-2 border border-gray-300 rounded-md">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}" {{ $selectedMonth == str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                                    {{ Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                </option>
                            @endforeach
                        </select>
                        <select name="year" class="px-3 py-2 border border-gray-300 rounded-md">
                            @for($y = $currentYear - 2; $y <= $currentYear + 1; $y++)
                                <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                        <button type="submit" class="px-4 py-2 bg-lime-500 text-white rounded-md hover:bg-lime-600 transition-colors">
                            Vai
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="text-lg font-semibold text-gray-700">
                {{ Carbon\Carbon::createFromDate($selectedYear, $selectedMonth, 1)->translatedFormat('F Y') }}
            </div>
        </div>
        
        <!-- Range data personalizzato -->
        <div class="mt-4 pt-3 border-t border-gray-200">
            <form method="GET" action="{{ route('admin.staff.activity-report', $staff->id_personale) }}" class="flex items-center gap-3">
                <label class="text-sm text-gray-600">Range personalizzato:</label>
                <input type="date" name="date_from" value="{{ request('date_from', $dateFrom->format('Y-m-d')) }}" 
                       class="px-3 py-1.5 border border-gray-300 rounded-md text-sm">
                <span class="text-gray-500">→</span>
                <input type="date" name="date_to" value="{{ request('date_to', $dateTo->format('Y-m-d')) }}" 
                       class="px-3 py-1.5 border border-gray-300 rounded-md text-sm">
                <button type="submit" class="px-4 py-1.5 bg-lime-500 text-white rounded-md hover:bg-lime-600 transition-colors text-sm">
                    Applica
                </button>
                @if(request('date_from') || request('date_to'))
                    <a href="{{ route('admin.staff.activity-report', ['staff' => $staff->id_personale, 'month' => $selectedMonth, 'year' => $selectedYear]) }}" 
                       class="text-sm text-red-500 hover:text-red-700">
                        <i class="fas fa-times"></i> Resetta
                    </a>
                @endif
            </form>
        </div>
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
                <tbody class="bg-white divide-y divide-gray-200">
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
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                            {{ Carbon\Carbon::parse($activity->data_activities)->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <div class="flex flex-col">
                                <span class="font-medium text-gray-800">{{ htmlspecialchars($clienteNome, ENT_QUOTES, 'UTF-8') }}</span>
                                @if($cantiereNome != '-')
                                    <span class="text-xs text-gray-500">{{ htmlspecialchars($cantiereNome, ENT_QUOTES, 'UTF-8') }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <div class="flex flex-col">
                                @if($displayLocalita != '-')
                                    <span class="text-gray-600">
                                        <i class="fas fa-map-marker-alt text-gray-400 mr-1 text-xs"></i>
                                        {{ htmlspecialchars($displayLocalita, ENT_QUOTES, 'UTF-8') }}
                                    </span>
                                @endif
                                <span class="text-gray-800 mt-1">
                                    <i class="fas fa-tag text-gray-400 mr-1 text-xs"></i>
                                    {{ htmlspecialchars($serviceName, ENT_QUOTES, 'UTF-8') }}
                                </span>
                            </div>
                        </td>
                        
                        <!-- N. Ore modificabile -->
                        <td class="px-4 py-3 text-sm relative">
                            <div x-data="{ 
                                ore: {{ $ore }},
                                showTooltip: false,
                                isEditing: false,
                                editedValue: {{ $ore }},
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
                                            this.ore = this.editedValue;
                                            this.showTooltip = false;
                                            location.reload();
                                        }
                                        this.isEditing = false;
                                    })
                                    .catch(() => {
                                        this.isEditing = false;
                                    });
                                }
                            }">
                                <div class="flex justify-center">
                                    <span class="font-medium cursor-pointer hover:text-lime-600 hover:underline text-center"
                                          x-on:click="showTooltip = true; editedValue = ore">
                                        {{ number_format($ore, 1) }}
                                    </span>
                                </div>
                                <div x-show="showTooltip" 
                                     x-on:click.away="showTooltip = false"
                                     class="absolute z-[100] bg-white border border-gray-300 rounded-lg shadow-xl p-3 min-w-[200px]"
                                     style="top: 100%; left: 50%; transform: translateX(-50%); margin-top: 10px;"
                                     x-cloak>
                                    <div class="absolute -top-2 left-1/2 transform -translate-x-1/2 w-4 h-4 bg-white border-l border-t border-gray-300 rotate-45"></div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">N. Ore</label>
                                    <input type="number" 
                                           step="0.5" 
                                           x-model="editedValue" 
                                           class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500"
                                           placeholder="0"
                                           x-on:keydown.enter="saveOre()">
                                    <div class="flex justify-end gap-2 mt-2">
                                        <button type="button" 
                                                x-on:click="showTooltip = false"
                                                class="px-2 py-1 text-xs bg-gray-200 hover:bg-gray-300 rounded">
                                            <i class="fas fa-times"></i> Annulla
                                        </button>
                                        <button type="button" 
                                                x-on:click="saveOre()"
                                                x-bind:disabled="isEditing"
                                                class="px-2 py-1 text-xs bg-lime-500 hover:bg-lime-600 text-white rounded disabled:opacity-50">
                                            <i class="fas fa-check" x-show="!isEditing"></i>
                                            <i class="fas fa-spinner fa-spin" x-show="isEditing"></i>
                                            <span x-show="!isEditing"> Salva</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Costo €/h modificabile -->
                        <td class="px-4 py-3 text-sm relative">
                            <div x-data="{ 
                                costoOrario: {{ $costoOrario }},
                                showTooltip: false,
                                isEditing: false,
                                editedValue: {{ $costoOrario }},
                                saveCosto() {
                                    this.isEditing = true;
                                    fetch('{{ route('admin.staff.update-costo-orario', $staffDetailId) }}', {
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
                                            this.costoOrario = this.editedValue;
                                            this.showTooltip = false;
                                            location.reload();
                                        }
                                        this.isEditing = false;
                                    })
                                    .catch(() => {
                                        this.isEditing = false;
                                    });
                                }
                            }">
                                <div class="flex justify-center">
                                    <span class="font-medium cursor-pointer hover:text-lime-600 hover:underline text-center"
                                          x-on:click="showTooltip = true; editedValue = costoOrario">
                                        € {{ number_format($costoOrario, 2) }}
                                    </span>
                                </div>
                                <div x-show="showTooltip" 
                                     x-on:click.away="showTooltip = false"
                                     class="absolute z-[100] bg-white border border-gray-300 rounded-lg shadow-xl p-3 min-w-[200px]"
                                     style="top: 100%; left: 50%; transform: translateX(-50%); margin-top: 10px;"
                                     x-cloak>
                                    <div class="absolute -top-2 left-1/2 transform -translate-x-1/2 w-4 h-4 bg-white border-l border-t border-gray-300 rotate-45"></div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Costo €/h</label>
                                    <input type="number" 
                                           step="0.50" 
                                           x-model="editedValue" 
                                           class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500"
                                           placeholder="0.00"
                                           x-on:keydown.enter="saveCosto()">
                                    <div class="flex justify-end gap-2 mt-2">
                                        <button type="button" 
                                                x-on:click="showTooltip = false"
                                                class="px-2 py-1 text-xs bg-gray-200 hover:bg-gray-300 rounded">
                                            <i class="fas fa-times"></i> Annulla
                                        </button>
                                        <button type="button" 
                                                x-on:click="saveCosto()"
                                                x-bind:disabled="isEditing"
                                                class="px-2 py-1 text-xs bg-lime-500 hover:bg-lime-600 text-white rounded disabled:opacity-50">
                                            <i class="fas fa-check" x-show="!isEditing"></i>
                                            <i class="fas fa-spinner fa-spin" x-show="isEditing"></i>
                                            <span x-show="!isEditing"> Salva</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Spese modificabili -->
                        <td class="px-4 py-3 text-sm relative">
                            <div x-data="{ 
                                spese: {{ $spese }},
                                showTooltip: false,
                                isEditing: false,
                                editedValue: {{ $spese }},
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
                                            this.spese = this.editedValue;
                                            this.showTooltip = false;
                                            location.reload();
                                        }
                                        this.isEditing = false;
                                    })
                                    .catch(() => {
                                        this.isEditing = false;
                                    });
                                }
                            }">
                                <div class="flex justify-center">
                                    <span class="font-medium cursor-pointer hover:text-lime-600 hover:underline text-center"
                                          x-on:click="showTooltip = true; editedValue = spese">
                                        € {{ number_format($spese, 2) }}
                                    </span>
                                </div>
                                <div x-show="showTooltip" 
                                     x-on:click.away="showTooltip = false"
                                     class="absolute z-[100] bg-white border border-gray-300 rounded-lg shadow-xl p-3 min-w-[200px]"
                                     style="top: 100%; left: 50%; transform: translateX(-50%); margin-top: 10px;"
                                     x-cloak>
                                    <div class="absolute -top-2 left-1/2 transform -translate-x-1/2 w-4 h-4 bg-white border-l border-t border-gray-300 rotate-45"></div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Spese (€)</label>
                                    <input type="number" 
                                           step="0.01" 
                                           x-model="editedValue" 
                                           class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500"
                                           placeholder="0.00"
                                           x-on:keydown.enter="saveSpese()">
                                    <div class="flex justify-end gap-2 mt-2">
                                        <button type="button" 
                                                x-on:click="showTooltip = false"
                                                class="px-2 py-1 text-xs bg-gray-200 hover:bg-gray-300 rounded">
                                            <i class="fas fa-times"></i> Annulla
                                        </button>
                                        <button type="button" 
                                                x-on:click="saveSpese()"
                                                x-bind:disabled="isEditing"
                                                class="px-2 py-1 text-xs bg-lime-500 hover:bg-lime-600 text-white rounded disabled:opacity-50">
                                            <i class="fas fa-check" x-show="!isEditing"></i>
                                            <i class="fas fa-spinner fa-spin" x-show="isEditing"></i>
                                            <span x-show="!isEditing"> Salva</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Note modificabili -->
                        <td class="px-4 py-3 text-sm text-gray-500 max-w-[300px] relative">
                            <div x-data="{ 
                                showTooltip: false, 
                                isEditing: false,
                                editedValue: '{{ addslashes($activity->note) }}',
                                saveNote() {
                                    this.isEditing = true;
                                    fetch('{{ route('admin.staff.update-activity-note', $activity->id) }}', {
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
                                            this.showTooltip = false;
                                            location.reload();
                                        }
                                        this.isEditing = false;
                                    })
                                    .catch(() => {
                                        this.isEditing = false;
                                    });
                                }
                            }">
                                <div class="whitespace-normal break-words cursor-pointer hover:text-lime-600" 
                                     x-on:click="showTooltip = true">
                                    {!! nl2br(e(Str::limit($activity->note ?? '-', 50))) !!}
                                </div>
                                
                                <div x-show="showTooltip" 
                                     x-on:click.away="showTooltip = false"
                                     class="fixed z-[100] bg-white border border-gray-300 rounded-lg shadow-xl p-4"
                                     style="top: 50%; left: 50%; transform: translate(-50%, -50%); width: 500px; max-width: 90vw;">
                                    <div class="flex justify-between items-center mb-3">
                                        <h3 class="text-md font-semibold text-gray-800">
                                            <i class="fas fa-edit text-lime-500 mr-2"></i> Modifica Nota
                                        </h3>
                                        <button type="button" 
                                                x-on:click="showTooltip = false"
                                                class="text-gray-400 hover:text-gray-600">
                                            <i class="fas fa-times text-xl"></i>
                                        </button>
                                    </div>
                                    <textarea x-model="editedValue" 
                                              rows="6"
                                              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500"
                                              placeholder="Inserisci nota..."></textarea>
                                    <div class="flex justify-end gap-2 mt-3">
                                        <button type="button" 
                                                x-on:click="showTooltip = false"
                                                class="px-4 py-2 text-sm bg-gray-200 hover:bg-gray-300 rounded-md">
                                            <i class="fas fa-times mr-1"></i> Annulla
                                        </button>
                                        <button type="button" 
                                                x-on:click="saveNote()"
                                                x-bind:disabled="isEditing"
                                                class="px-4 py-2 text-sm bg-lime-500 hover:bg-lime-600 text-white rounded-md disabled:opacity-50">
                                            <i class="fas fa-check mr-1" x-show="!isEditing"></i>
                                            <i class="fas fa-spinner fa-spin mr-1" x-show="isEditing"></i>
                                            <span x-show="!isEditing"> Salva</span>
                                            <span x-show="isEditing"> Salvataggio...</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </td--^
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
    <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Totale Ore -->
        <div class="bg-white rounded-lg shadow p-4 border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 uppercase">Totale Ore</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($totalHours, 1) }} h</p>
                </div>
                <div class="h-12 w-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-blue-500 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Giornate Effettive -->
        <div class="bg-white rounded-lg shadow p-4 border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 uppercase">Giornate Effettive</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($totalWorkingDays, 1) }} gg</p>
                    <p class="text-xs text-gray-400 mt-1">Base 8 h/gg</p>
                </div>
                <div class="h-12 w-12 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-calendar-day text-purple-500 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Totale Maturato + Spese -->
        <div class="bg-white rounded-lg shadow p-4 border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 uppercase">Maturato + Spese</p>
                    <p class="text-2xl font-bold text-lime-600">€ {{ number_format($totalMaturato + $totalSpese, 2) }}</p>
                </div>
                <div class="h-12 w-12 bg-lime-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-euro-sign text-lime-500 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Costo Medio Orario -->
        <div class="bg-white rounded-lg shadow p-4 border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 uppercase">Costo Medio Orario</p>
                    <p class="text-2xl font-bold text-gray-800">€ {{ number_format($averageHourlyCost, 2) }}</p>
                </div>
                <div class="h-12 w-12 bg-yellow-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-chart-line text-yellow-500 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Dettaglio Statistiche -->
    <div class="mt-4 bg-white rounded-lg shadow p-4 border border-gray-200">
        <h3 class="text-md font-semibold text-gray-800 mb-3">
            <i class="fas fa-chart-pie text-lime-500 mr-2"></i> Dettaglio
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                <span class="text-gray-600">Totale Maturato:</span>
                <span class="font-bold text-green-600">€ {{ number_format($totalMaturato, 2) }}</span>
            </div>
            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                <span class="text-gray-600">Totale Spese:</span>
                <span class="font-bold text-orange-600">€ {{ number_format($totalSpese, 2) }}</span>
            </div>
            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                <span class="text-gray-600">Numero Attività:</span>
                <span class="font-bold text-gray-800">{{ $activities->count() }}</span>
            </div>
        </div>
    </div>
</div>
@endsection