{{-- resources/views/admin/staff/attendance/show.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Presenze · ' . $staff->NomePers . ' ' . $staff->CognomePers)

@section('content')
<div class="p-6">

    {{-- HEADER --}}
    <div class="mb-5 flex flex-wrap justify-between items-start gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-user-clock text-lime-500"></i>
                {{ $staff->NomePers }} {{ $staff->CognomePers }}
            </h1>
            <p class="text-gray-400 text-sm mt-1">
                <i class="fas fa-calendar-alt mr-1"></i>
                {{ ucfirst($startDate->locale('it')->isoFormat('MMMM YYYY')) }}
                @if($dataAssunzione)
                    &nbsp;·&nbsp;
                    <span class="text-gray-500">
                        Assunto dal <strong>{{ $dataAssunzione->format('d/m/Y') }}</strong>
                        @if($dataLicenziamento)
                            al <strong>{{ $dataLicenziamento->format('d/m/Y') }}</strong>
                        @endif
                    </span>
                @endif
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.staff.attendance.index', ['month' => $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT)]) }}"
               class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded-lg transition-colors text-sm flex items-center gap-1.5">
                <i class="fas fa-arrow-left"></i>
            </a>
            <button onclick="saveAllChanges()"
                    class="bg-lime-600 hover:bg-lime-700 text-white px-3 py-2 rounded-lg transition-colors text-sm flex items-center gap-1.5">
                <i class="fas fa-save"></i> Salva modifiche
            </button>
        </div>
    </div>

    {{-- BARRA NAVIGAZIONE --}}
    <div class="bg-white rounded-xl shadow border border-gray-200 p-3 mb-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap">
                    <i class="fas fa-building mr-1"></i> Proprietà
                </label>
                <select id="ownershipSelect"
                        class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lime-500 min-w-[200px]">
                    <option value="">— Tutte —</option>
                    @foreach($ownerships as $ow)
                        <option value="{{ $ow->id_proprieta }}"
                            {{ $selectedOwnershipId == $ow->id_proprieta ? 'selected' : '' }}>
                            {{ $ow->RagAbbrev }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.staff.attendance.show', [
                        'staffId' => $staff->id_personale,
                        'year'    => $startDate->copy()->subMonth()->year,
                        'month'   => $startDate->copy()->subMonth()->month,
                    ]) }}{{ $selectedOwnershipId ? '?ownership_id='.$selectedOwnershipId : '' }}"
                   class="w-8 h-8 flex items-center justify-center bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-lg transition-colors text-gray-600">
                    <i class="fas fa-chevron-left text-xs"></i>
                </a>

                <select id="monthSelectShow" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lime-500">
                    @php
                        $mesi = [1=>'Gennaio',2=>'Febbraio',3=>'Marzo',4=>'Aprile',
                                 5=>'Maggio',6=>'Giugno',7=>'Luglio',8=>'Agosto',
                                 9=>'Settembre',10=>'Ottobre',11=>'Novembre',12=>'Dicembre'];
                    @endphp
                    @foreach(range(1, 12) as $m)
                        <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}"
                            {{ $month == $m ? 'selected' : '' }}>
                            {{ $mesi[$m] }}
                        </option>
                    @endforeach
                </select>

                <select id="yearSelectShow" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lime-500">
                    @for($y = now()->year - 3; $y <= now()->year + 1; $y++)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>

                <a href="{{ route('admin.staff.attendance.show', [
                        'staffId' => $staff->id_personale,
                        'year'    => $startDate->copy()->addMonth()->year,
                        'month'   => $startDate->copy()->addMonth()->month,
                    ]) }}{{ $selectedOwnershipId ? '?ownership_id='.$selectedOwnershipId : '' }}"
                   class="w-8 h-8 flex items-center justify-center bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-lg transition-colors text-gray-600">
                    <i class="fas fa-chevron-right text-xs"></i>
                </a>

                <button id="goToMonthBtn" type="button"
                        class="px-4 py-1.5 bg-gradient-to-r from-lime-500 to-lime-600 hover:from-lime-600 hover:to-lime-700 text-white rounded-lg text-sm font-medium shadow-sm transition-all">
                    <i class="fas fa-arrow-right mr-1"></i> Vai
                </button>
            </div>
        </div>
    </div>

    {{-- STATISTICHE --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-4">
        <div class="bg-white rounded-xl shadow border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-400 uppercase font-semibold tracking-wide">Totale Ore</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1" id="statTotOre">
                        {{ number_format($totalOre, 1) }}<span class="text-sm font-normal text-gray-400 ml-1">h</span>
                    </p>
                </div>
                <div class="h-11 w-11 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-blue-500"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-400 uppercase font-semibold tracking-wide">Gg. Effettive</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1" id="statGgEffettive">
                        {{ $totalGiornateEffettive }}
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">Nel periodo assunzione</p>
                </div>
                <div class="h-11 w-11 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-calendar-day text-purple-500"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-400 uppercase font-semibold tracking-wide">Presenze</p>
                    <p class="text-2xl font-bold text-green-600 mt-1" id="statPresenze">
                        {{ $totalGiornateMesse }}
                    </p>
                </div>
                <div class="h-11 w-11 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-500"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-400 uppercase font-semibold tracking-wide">Assenze</p>
                    <p class="text-2xl font-bold text-amber-600 mt-1" id="statAssenze">
                        {{ $totalGiornateAssenza }}
                    </p>
                </div>
                <div class="h-11 w-11 bg-amber-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-times-circle text-amber-500"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-lime-50 to-lime-100 rounded-xl shadow border border-lime-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-lime-700 uppercase font-semibold tracking-wide">Media / Giorno</p>
                    <p class="text-2xl font-bold text-lime-700 mt-1" id="statMedia">
                        {{ $totalGiornateMesse > 0 ? number_format($totalOre / $totalGiornateMesse, 1) : '0.0' }}<span class="text-sm font-normal ml-1">h</span>
                    </p>
                </div>
                <div class="h-11 w-11 bg-lime-200 rounded-full flex items-center justify-center">
                    <i class="fas fa-chart-line text-lime-600"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- TABELLA PRESENZE --}}
    <div class="bg-white rounded-xl shadow overflow-hidden border border-gray-200 mb-6">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-10">#</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">Data</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">Gg.</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-20">Presenza</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">Azienda</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">Ore</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">Orario</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-20">Causale</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100" id="attendanceTableBody">
                    @foreach($presenze as $data => $presenza)
                    @php
                        // Calcolo ore totali per la giornata
                        $totaleOreGiornata = 0;
                        $dettagliValid = [];
                        
                        if (isset($presenza['dettagli']) && is_array($presenza['dettagli'])) {
                            foreach ($presenza['dettagli'] as $dettaglio) {
                                $oreDettaglio = 0;
                                
                                if (!empty($dettaglio['att_start']) && !empty($dettaglio['att_end'])) {
                                    try {
                                        $start = \Carbon\Carbon::parse($dettaglio['att_start']);
                                        $end = \Carbon\Carbon::parse($dettaglio['att_end']);
                                        $oreDettaglio = $start->diffInHours($end);
                                        $oreDettaglio = max(0, $oreDettaglio);
                                    } catch (\Exception $e) {
                                        $oreDettaglio = floatval($dettaglio['n_ore'] ?? 0);
                                    }
                                } elseif (!empty($dettaglio['n_ore'])) {
                                    $oreDettaglio = floatval($dettaglio['n_ore']);
                                }
                                
                                if ($oreDettaglio > 0 || !empty($dettaglio['att_start']) || !empty($dettaglio['att_end'])) {
                                    $dettagliValid[] = array_merge($dettaglio, ['ore_calcolate' => $oreDettaglio]);
                                    $totaleOreGiornata += $oreDettaglio;
                                }
                            }
                        }

                        if ($totaleOreGiornata == 0 && isset($presenza['ore']) && $presenza['ore'] > 0) {
                            $totaleOreGiornata = $presenza['ore'];
                        }

                        // Classi riga
                        if ($presenza['is_sunday']) {
                            $rowClass = 'bg-red-50';
                        } elseif (!$presenza['is_in_assunzione']) {
                            $rowClass = 'bg-gray-100';
                        } elseif (!empty($presenza['causale'])) {
                            $rowClass = 'bg-amber-50';
                        } elseif ($presenza['checked']) {
                            $rowClass = 'bg-lime-50/50';
                        } else {
                            $rowClass = '';
                        }

                        $giorniMap = [
                            'Mon'=>'Lun','Tue'=>'Mar','Wed'=>'Mer',
                            'Thu'=>'Gio','Fri'=>'Ven','Sat'=>'Sab','Sun'=>'Dom'
                        ];
                        $giornoLabel = $giorniMap[$presenza['giorno_settimana']] ?? $presenza['giorno_settimana'];

                        $causaleConfig = match($presenza['causale'] ?? '') {
                            'malattia' => ['bg-red-100 text-red-700', 'fas fa-thermometer-half', 'Malattia'],
                            'ferie'    => ['bg-blue-100 text-blue-700', 'fas fa-umbrella-beach', 'Ferie'],
                            'permesso' => ['bg-purple-100 text-purple-700', 'fas fa-calendar-check', 'Permesso'],
                            default    => ['bg-gray-100 text-gray-600', 'fas fa-info-circle', ucfirst($presenza['causale'] ?? '')],
                        };

                        $hasValidDetails = count($dettagliValid) > 0;
                        $hasMultiple = isset($presenza['has_multiple']) && $presenza['has_multiple'];
                        $numCantieri = $presenza['num_cantieri'] ?? 0;
                        $cantieriString = $presenza['cantieri_string'] ?? '';
                    @endphp
                    <tr class="{{ $rowClass }} hover:brightness-[0.97] transition-all" data-date="{{ $data }}">
                        <td class="px-3 py-2.5 text-xs text-gray-400 font-mono text-center">
                            {{ $presenza['giorno_num'] }}
                        </td>
                        <td class="px-3 py-2.5 text-sm whitespace-nowrap font-medium
                            {{ $presenza['is_sunday'] ? 'text-red-600' : 'text-gray-700' }}">
                            {{ \Carbon\Carbon::parse($data)->format('d/m/Y') }}
                        </td>
                        <td class="px-3 py-2.5 text-sm whitespace-nowrap
                            {{ $presenza['is_sunday'] ? 'text-red-500 font-semibold' : 'text-gray-500' }}">
                            {{ $giornoLabel }}
                        </td>

                        {{-- Checkbox presenza --}}
                        <td class="px-3 py-2.5 text-center">
                            @if($presenza['is_sunday'])
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-600 uppercase tracking-wide">
                                    Dom
                                </span>
                            @elseif(!$presenza['is_in_assunzione'])
                                <span class="inline-block w-5 h-5 rounded bg-gray-300"
                                      title="Fuori periodo assunzione"></span>
                            @elseif(!empty($presenza['causale']))
                                <span class="text-gray-300 text-xs">—</span>
                            @else
                                <input type="checkbox"
                                       class="attendance-checkbox w-4 h-4 rounded border-gray-300 text-lime-600 focus:ring-lime-500 cursor-pointer"
                                       data-date="{{ $data }}"
                                       data-staff="{{ $staff->id_personale }}"
                                       data-ore="{{ $totaleOreGiornata }}"
                                       data-saved-id="{{ $presenza['saved_id'] ?? '' }}"
                                       {{ $presenza['checked'] ? 'checked' : '' }}>
                            @endif
                        </td>

                        {{-- Azienda / Cantieri --}}
                        <td class="px-2 py-2 text-center text-xs">  {{-- Riduci padding da px-3 a px-2 --}}
                            @if($hasMultiple)
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700"
                                    title="{{ $cantieriString }}">
                                    <i class="fas fa-layer-group text-xs"></i>
                                    {{ $numCantieri }}
                                </span>
                            @elseif(!empty($cantieriString))
                                {{-- Trunca il testo se troppo lungo --}}
                                <span class="text-gray-600 text-xs font-medium truncate max-w-[80px] inline-block" title="{{ $cantieriString }}">
                                    {{ Str::limit($cantieriString, 15) }}
                                </span>
                            @else
                                <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>

                        {{-- Ore totali --}}
                        <td class="px-3 py-2.5 text-center text-sm font-semibold
                            {{ $totaleOreGiornata > 0 ? 'text-gray-800' : 'text-gray-300' }}">
                            {{ $totaleOreGiornata > 0 ? number_format($totaleOreGiornata, 1) . ' h' : '—' }}
                        </td>

                        {{-- Orario dettagliato --}}
                        <td class="px-3 py-2.5 text-center text-sm">
                            @if($hasValidDetails)
                                @foreach($dettagliValid as $index => $dettaglio)
                                    <div class="text-xs text-gray-600 {{ $index > 0 ? 'mt-1 pt-1 border-t border-gray-200' : '' }}">
                                        @if(!empty($dettaglio['nome_ownership']))
                                            <span class="font-medium text-gray-700">{{ $dettaglio['nome_ownership'] }}</span>
                                            @if(!empty($dettaglio['att_start']) || !empty($dettaglio['att_end']) || !empty($dettaglio['ore_calcolate']))
                                                <span class="text-gray-400 mx-1">·</span>
                                            @endif
                                        @endif
                                        
                                        @if(!empty($dettaglio['att_start']) && !empty($dettaglio['att_end']))
                                            {{ \Carbon\Carbon::parse($dettaglio['att_start'])->format('H:i') }} - 
                                            {{ \Carbon\Carbon::parse($dettaglio['att_end'])->format('H:i') }}
                                            @if(!empty($dettaglio['ore_calcolate']) && $dettaglio['ore_calcolate'] > 0)
                                                <span class="text-gray-400">({{ number_format($dettaglio['ore_calcolate'], 1) }}h)</span>
                                            @endif
                                        @elseif(!empty($dettaglio['ore_calcolate']) && $dettaglio['ore_calcolate'] > 0)
                                            <span class="text-gray-600">{{ number_format($dettaglio['ore_calcolate'], 1) }} h</span>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </div>
                                @endforeach
                            @elseif($totaleOreGiornata > 0)
                                <div class="text-xs text-gray-600">
                                    {{ number_format($totaleOreGiornata, 1) }} h
                                </div>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>

                        {{-- Causale --}}
                        <td class="px-3 py-2.5 text-center">
                            @if(!empty($presenza['causale']))
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold {{ $causaleConfig[0] }}">
                                    <i class="{{ $causaleConfig[1] }}"></i> {{ $causaleConfig[2] }}
                                </span>
                            @elseif(!$presenza['is_sunday'] && !$presenza['is_in_assunzione'])
                                <span class="text-xs text-gray-400 italic">Non assunto</span>
                            @else
                                <span class="text-gray-200 text-xs">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- LEGENDA --}}
    <div class="flex flex-wrap gap-4 text-xs text-gray-500">
        <span class="flex items-center gap-1.5">
            <span class="w-4 h-4 rounded bg-red-100 border border-red-200 inline-block"></span> Domenica
        </span>
        <span class="flex items-center gap-1.5">
            <span class="w-4 h-4 rounded bg-amber-100 border border-amber-200 inline-block"></span> Assenza (malattia / ferie / permesso)
        </span>
        <span class="flex items-center gap-1.5">
            <span class="w-4 h-4 rounded bg-gray-200 border border-gray-300 inline-block"></span> Fuori periodo assunzione
        </span>
        <span class="flex items-center gap-1.5">
            <span class="w-4 h-4 rounded bg-lime-100 border border-lime-200 inline-block"></span> Presenza registrata
        </span>
        <span class="flex items-center gap-1.5">
            <i class="fas fa-layer-group text-red-500"></i> Più cantieri nella stessa giornata
        </span>
    </div>
</div>

{{-- MODAL NOTIFICA --}}
<div id="notificationModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div id="modalIconContainer" class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:mx-0 sm:h-10 sm:w-10 bg-green-100">
                        <svg id="modalIconSvg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalTitle">
                            Notifica
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500" id="modalMessage">
                                Operazione completata con successo.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button id="modalCloseBtn" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-lime-600 text-base font-medium text-white hover:bg-lime-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-lime-500 sm:ml-3 sm:w-auto sm:text-sm">
                    OK
                </button>
            </div>
        </div>
    </div>
</div>

{{-- JAVASCRIPT --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const staffId      = {{ $staff->id_personale }};
    const csrfToken    = '{{ csrf_token() }}';
    const saveUrl      = '{{ route("admin.staff.attendance.save") }}';
    const baseShowUrl  = '{{ route("admin.staff.attendance.show", ["staffId" => "__SID__", "year" => "__Y__", "month" => "__M__"]) }}';
    let pendingChanges = new Map();

    // ── Modal Functions ──────────────────────────────────────────────────────
    const modal = document.getElementById('notificationModal');
    const modalCloseBtn = document.getElementById('modalCloseBtn');
    const modalIconContainer = document.getElementById('modalIconContainer');
    const modalIconSvg = document.getElementById('modalIconSvg');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');

    function showModal(title, message, type = 'success') {
        modalIconContainer.className = 'mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:mx-0 sm:h-10 sm:w-10';
        
        if (type === 'success') {
            modalIconContainer.classList.add('bg-green-100');
            modalIconSvg.className = 'h-6 w-6 text-green-600';
            modalIconSvg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />';
            modalCloseBtn.className = 'w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm';
        } else if (type === 'error') {
            modalIconContainer.classList.add('bg-red-100');
            modalIconSvg.className = 'h-6 w-6 text-red-600';
            modalIconSvg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />';
            modalCloseBtn.className = 'w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm';
        } else if (type === 'warning') {
            modalIconContainer.classList.add('bg-yellow-100');
            modalIconSvg.className = 'h-6 w-6 text-yellow-600';
            modalIconSvg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />';
            modalCloseBtn.className = 'w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:ml-3 sm:w-auto sm:text-sm';
        }
        
        modalTitle.textContent = title;
        modalMessage.textContent = message;
        modal.classList.remove('hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    modalCloseBtn.addEventListener('click', closeModal);
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });

    // ── Navigazione mese ──────────────────────────────────────────────────────
    const monthSelect = document.getElementById('monthSelectShow');
    const yearSelect  = document.getElementById('yearSelectShow');
    const ownerSelect = document.getElementById('ownershipSelect');

    document.getElementById('goToMonthBtn').addEventListener('click', () => {
        let url = baseShowUrl
            .replace('__SID__', staffId)
            .replace('__Y__', yearSelect.value)
            .replace('__M__', monthSelect.value);
        if (ownerSelect.value) url += '?ownership_id=' + ownerSelect.value;
        window.location.href = url;
    });

    ownerSelect.addEventListener('change', () => {
        let url = baseShowUrl
            .replace('__SID__', staffId)
            .replace('__Y__', '{{ $year }}')
            .replace('__M__', '{{ str_pad($month, 2, "0", STR_PAD_LEFT) }}');
        if (ownerSelect.value) url += '?ownership_id=' + ownerSelect.value;
        window.location.href = url;
    });

    // ── Statistiche live ──────────────────────────────────────────────────────
    function updateStats() {
        const checkboxes = document.querySelectorAll('.attendance-checkbox');
        let presenze = 0;
        let totOre = 0;

        checkboxes.forEach(chk => {
            if (chk.checked) {
                presenze++;
                const ore = parseFloat(chk.dataset.ore || 0);
                if (!isNaN(ore) && ore > 0) {
                    totOre += ore;
                }
            }
        });

        let assenze = 0;
        document.querySelectorAll('#attendanceTableBody tr').forEach(row => {
            if (row.classList.contains('bg-amber-50')) {
                assenze++;
            }
        });

        const statPresenze = document.getElementById('statPresenze');
        const statTotOre = document.getElementById('statTotOre');
        const statAssenze = document.getElementById('statAssenze');
        const statMedia = document.getElementById('statMedia');

        if (statPresenze) statPresenze.textContent = presenze;
        if (statTotOre) statTotOre.innerHTML = totOre.toFixed(1) + '<span class="text-sm font-normal text-gray-400 ml-1">h</span>';
        if (statAssenze) statAssenze.textContent = assenze;
        
        const media = presenze > 0 && totOre > 0 ? (totOre / presenze).toFixed(1) : '0.0';
        if (statMedia) statMedia.innerHTML = media + '<span class="text-sm font-normal ml-1">h</span>';
    }

    // ── Salvataggio modifiche ────────────────────────────────────────────────
    window.saveAllChanges = function() {
        if (pendingChanges.size === 0) {
            showModal('Nessuna modifica', 'Non ci sono modifiche da salvare.', 'warning');
            return;
        }

        const saveBtn = document.querySelector('button[onclick="saveAllChanges()"]');
        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Salvataggio...';
        saveBtn.disabled = true;

        // ⭐ VERIFICA I DATI PRIMA DI INVIARLI
        const changes = Array.from(pendingChanges.entries()).map(([date, data]) => {
            // Assicurati che 'checked' sia un booleano esplicito
            const checked = data.checked === true || data.checked === 'true' || data.checked === 1;
            
            return {
                staff_id: staffId,
                date: date,
                checked: checked, // ⭐ BOOLEANO PURO
                ownership_id: ownerSelect.value || null
            };
        });

        console.log('📤 Invio modifiche:', changes); // ⭐ DEBUG

        fetch(saveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ changes: changes }),
        })
        .then(response => {
            console.log('📥 Risposta ricevuta:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('📥 Dati risposta:', data); // ⭐ DEBUG
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
            
            if (data.success) {
                pendingChanges.clear();
                if (data.results) {
                    data.results.forEach(result => {
                        const checkbox = document.querySelector(`.attendance-checkbox[data-date="${result.date}"]`);
                        if (checkbox) {
                            checkbox.dataset.savedId = result.id;
                            // ⭐ AGGIORNA LO STATO DEL CHECKBOX
                            checkbox.checked = result.is_present === true;
                        }
                    });
                }
                showModal('Salvataggio completato', data.message || 'Modifiche salvate con successo!', 'success');
                updateStats();
                // ⭐ RICARICA LA PAGINA PER MOSTRARE I DATI AGGIORNATI
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showModal('Errore', data.message || 'Errore durante il salvataggio.', 'error');
            }
        })
        .catch(err => {
            console.error('❌ Errore fetch:', err);
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
            showModal('Errore di connessione', 'Impossibile connettersi al server. Riprova più tardi.', 'error');
        });
    };

    // ── Gestione checkbox ─────────────────────────────────────────────────────
    document.querySelectorAll('.attendance-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            const date    = this.dataset.date;
            const checked = this.checked;

            pendingChanges.set(date, {
                checked: checked,
                savedId: this.dataset.savedId || null
            });

            updateStats();

            const row = this.closest('tr');
            if (checked) {
                row.classList.remove('bg-lime-50/50');
                row.classList.add('bg-lime-50/50');
            } else {
                row.classList.remove('bg-lime-50/50');
            }
        });
    });

    // ── Inizializzazione ──────────────────────────────────────────────────────
    updateStats();

    // Auto-save ogni 30 secondi se ci sono modifiche pendenti
    setInterval(() => {
        if (pendingChanges.size > 0) {
            window.saveAllChanges();
        }
    }, 30000);
});
</script>
@endsection