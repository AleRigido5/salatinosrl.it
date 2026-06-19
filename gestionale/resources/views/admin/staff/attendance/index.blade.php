{{-- resources/views/admin/staff/attendance/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Gestione Presenze')

@section('content')
<div class="p-4">

    {{-- HEADER --}}
    <div class="flex justify-between items-center mb-4">
        <div>
            <h1 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-clock text-lime-600"></i>
                GESTIONE PRESENZE {{ strtoupper(\Carbon\Carbon::createFromDate($selectedYear, $selectedMonth, 1)->locale('it')->isoFormat('MMMM YYYY')) }}
            </h1>
            <p class="text-xs text-gray-400 mt-0.5">
                Periodo: {{ \Carbon\Carbon::createFromDate($selectedYear, $selectedMonth, 1)->format('d/m/Y') }}
                – {{ \Carbon\Carbon::createFromDate($selectedYear, $selectedMonth, 1)->endOfMonth()->format('d/m/Y') }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.staff.index') }}"
               class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded-lg text-sm flex items-center gap-1.5 transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>
    </div>

    {{-- BARRA FILTRI --}}
    <div class="bg-white rounded-lg shadow border border-gray-200 p-3 mb-4">
        <div class="flex flex-wrap items-center justify-between gap-3">

            {{-- Range date a sinistra --}}
            <div class="flex items-center gap-2">
                <input type="date" id="date_from"
                       value="{{ $dateFrom }}"
                       class="px-2 py-1.5 border border-gray-300 rounded text-sm">
                <span class="text-gray-400">→</span>
                <input type="date" id="date_to"
                       value="{{ $dateTo }}"
                       class="px-2 py-1.5 border border-gray-300 rounded text-sm">
                <button id="applyDateRange"
                        class="bg-lime-500 hover:bg-lime-600 text-white px-3 py-1.5 rounded text-sm font-medium transition-colors">
                    Applica
                </button>
            </div>

            {{-- Select proprietà + mese/anno al centro/destra --}}
            <div class="flex items-center gap-2">
                <select id="ownershipFilter" class="px-2 py-1.5 border border-gray-300 rounded text-sm min-w-[160px]">
                    <option value="">Tutte le proprietà</option>
                    @foreach($ownerships as $ow)
                        <option value="{{ $ow->id_proprieta }}"
                            {{ $selectedOwnershipId == $ow->id_proprieta ? 'selected' : '' }}>
                            {{ $ow->RagAbbrev }}
                        </option>
                    @endforeach
                </select>

                <button id="prevMonth"
                        class="w-8 h-8 flex items-center justify-center bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded transition-colors text-gray-600">
                    <i class="fas fa-arrow-left text-xs"></i>
                </button>

                <select id="monthSelect" class="px-2 py-1.5 border border-gray-300 rounded text-sm">
                    @php $mesi = [1=>'Gennaio',2=>'Febbraio',3=>'Marzo',4=>'Aprile',5=>'Maggio',6=>'Giugno',7=>'Luglio',8=>'Agosto',9=>'Settembre',10=>'Ottobre',11=>'Novembre',12=>'Dicembre']; @endphp
                    @foreach(range(1,12) as $m)
                        <option value="{{ str_pad($m,2,'0',STR_PAD_LEFT) }}"
                            {{ $selectedMonth == str_pad($m,2,'0',STR_PAD_LEFT) ? 'selected' : '' }}>
                            {{ $mesi[$m] }}
                        </option>
                    @endforeach
                </select>

                <select id="yearSelect" class="px-2 py-1.5 border border-gray-300 rounded text-sm">
                    @for($y = date('Y')-3; $y <= date('Y')+1; $y++)
                        <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>

                <button id="nextMonth"
                        class="w-8 h-8 flex items-center justify-center bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded transition-colors text-gray-600">
                    <i class="fas fa-arrow-right text-xs"></i>
                </button>

                <button id="goToMonth"
                        class="bg-lime-500 hover:bg-lime-600 text-white px-3 py-1.5 rounded text-sm font-medium transition-colors">
                    Vai
                </button>
            </div>
        </div>
    </div>

    {{-- LEGENDA IN ALTO --}}
    <div class="flex flex-wrap gap-4 mb-3 text-xs text-gray-500 bg-white rounded-lg shadow border border-gray-200 p-3">
        <span class="flex items-center gap-1.5">
            <span class="inline-block w-4 h-4 rounded" style="background:#111827;"></span> Domenica / Festivo
        </span>
        <span class="flex items-center gap-1.5">
            <span class="inline-block w-4 h-4 rounded" style="background:#374151;"></span> Fuori periodo assunzione
        </span>
        <span class="flex items-center gap-1.5">
            <span class="inline-block w-4 h-4 rounded" style="background:#1f2937;"></span> Assenza (malattia / ferie / permesso)
        </span>
        <span class="flex items-center gap-1.5">
            <span class="inline-block w-4 h-4 border border-gray-300 rounded bg-white flex items-center justify-center font-bold text-gray-800">X</span>
            Presenza registrata
        </span>
        <span class="flex items-center gap-1.5 text-blue-500">
            <i class="fas fa-circle text-[8px]"></i>
            Attività rilevata (solo indicativa)
        </span>
    </div>

    {{-- TABELLA MATRICE --}}
    <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
        <div style="overflow-x: auto;">
            <table class="border-collapse text-xs" style="min-width: max-content; width: 100%;">
                <thead>
                    <tr class="border-b-2 border-gray-300">
                        {{-- Intestazione dipendente --}}
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 uppercase tracking-wide bg-gray-50 border-r border-gray-200 sticky left-0"
                            style="min-width:180px; z-index:2;">
                            DIPENDENTE
                        </th>

                        {{-- Intestazioni giorni --}}
                        @foreach($giorni as $giorno)
                        @php
                            $isSun = $giorno->isSunday();
                            $isSat = $giorno->isSaturday();
                            $isFestivo = in_array($giorno->format('m-d'), $festiviNazionali ?? []);
                            $isWeekend = $isSun || $isFestivo;
                        @endphp
                        <th class="text-center font-semibold border-r border-gray-200 py-1.5"
                            style="min-width:28px; width:28px;
                                   background: {{ $isWeekend ? '#1a1a1a' : ($isSat ? '#374151' : '#f9fafb') }};
                                   color: {{ $isWeekend || $isSat ? '#fff' : '#374151' }};">
                            <div class="text-xs font-bold leading-tight">{{ $giorno->format('j') }}</div>
                            <div style="font-size:9px; opacity:0.8; font-weight:400;">
                                {{ ['Dom','Lun','Mar','Mer','Gio','Ven','Sab'][$giorno->dayOfWeek] }}
                            </div>
                        </th>
                        @endforeach

                        {{-- N.GG --}}
                        <th class="px-3 py-2 text-center text-xs font-bold text-gray-700 uppercase bg-gray-50 border-l border-gray-300"
                            style="min-width:50px;">
                            N.GG
                        </th>

                        {{-- Azioni --}}
                        <th class="px-3 py-2 text-center text-xs font-bold text-gray-700 uppercase bg-gray-50 border-l border-gray-200"
                            style="min-width:60px;">
                            AZIONI
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($staff as $person)
                    @php
                        $personPresenze = $presenzeMatrix[$person->id_personale] ?? [];
                        $nGG = $totaliGiornate[$person->id_personale] ?? 0;
                    @endphp
                    <tr class="border-b border-gray-100 hover:bg-gray-50/60 transition-colors">

                        {{-- Nome --}}
                        <td class="px-3 py-1.5 font-semibold text-gray-800 border-r border-gray-200 bg-white sticky left-0 text-xs"
                            style="min-width:180px; z-index:2;">
                            {{ strtoupper($person->CognomePers) }} {{ $person->NomePers }}
                        </td>

                        {{-- Celle giorni --}}
                        @foreach($giorni as $giorno)
                        @php
                            $dk = $giorno->format('Y-m-d');
                            $isSun = $giorno->isSunday();
                            $isSat = $giorno->isSaturday();
                            $isFestivo = in_array($giorno->format('m-d'), $festiviNazionali ?? []);
                            $isWeekend = $isSun || $isFestivo;

                            $cellData = $personPresenze[$dk] ?? null;
                            $isInAssunzione = $cellData['is_in_assunzione'] ?? false;
                            $hasCausale = !empty($cellData['causale']);
                            $isPresent = $cellData['checked'] ?? false;
                            $hasActivity = $cellData['has_activity'] ?? false;

                            // Logica colore cella
                            if ($isWeekend || $isSat) {
                                $bgCell = '#111827'; // nero – domenica/festivo
                                $showX = false;
                                $blocked = true;
                                $showActivity = false;
                            } elseif (!$isInAssunzione) {
                                $bgCell = '#374151'; // grigio scuro – fuori assunzione
                                $showX = false;
                                $blocked = true;
                                $showActivity = false;
                            } elseif ($hasCausale) {
                                $bgCell = '#1f2937'; // scuro – malattia/ferie
                                $showX = false;
                                $blocked = true;
                                $showActivity = false;
                            } else {
                                $bgCell = 'transparent';
                                $showX = $isPresent;
                                $blocked = false;
                                $showActivity = $hasActivity && !$isPresent;
                            }
                        @endphp
                        <td class="text-center border-r border-gray-200 align-middle relative"
                            style="min-width:28px; width:28px; background:{{ $bgCell }}; height:32px;">
                            @if($showX)
                                <span class="font-bold text-gray-800" style="font-size:11px;">X</span>
                            @elseif($showActivity)
                                <span class="text-blue-400 text-[10px]" title="Attività rilevata in questo giorno">
                                    <i class="fas fa-circle"></i>
                                </span>
                            @endif
                        </td>
                        @endforeach

                        {{-- N.GG --}}
                        <td class="text-center font-bold text-gray-800 border-l border-gray-300 text-sm"
                            style="min-width:50px;">
                            {{ $nGG }}
                        </td>

                        {{-- Azioni --}}
                        <td class="text-center border-l border-gray-200" style="min-width:60px;">
                            <a href="{{ route('admin.staff.attendance.show', [
                                    'staffId' => $person->id_personale,
                                    'year'    => $selectedYear,
                                    'month'   => $selectedMonth,
                                ]) }}{{ $selectedOwnershipId ? '?ownership_id='.$selectedOwnershipId : '' }}"
                               class="inline-flex items-center justify-center w-7 h-7 text-gray-500 hover:text-lime-600 transition-colors"
                               title="Inserisci / Modifica Presenze">
                                <i class="fas fa-file-alt"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- NOTA IN BASSO --}}
    <div class="mt-3 text-xs text-gray-400 bg-blue-50 border border-blue-200 rounded-lg p-3">
        <i class="fas fa-info-circle text-blue-500 mr-1"></i>
        <strong>Nota:</strong> I checkbox delle presenze vanno spuntati manualmente. 
        Il puntino blu <span class="text-blue-400"><i class="fas fa-circle text-[8px]"></i></span> indica che sono state rilevate attività per quel giorno, 
        ma la presenza va comunque confermata manualmente.
    </div>

</div>

<script>
const monthSelect     = document.getElementById('monthSelect');
const yearSelect      = document.getElementById('yearSelect');
const ownershipFilter = document.getElementById('ownershipFilter');
const dateFrom        = document.getElementById('date_from');
const dateTo          = document.getElementById('date_to');
const baseUrl         = '{{ route("admin.staff.attendance.index") }}';

function buildParams(extraMonth = null) {
    const params = new URLSearchParams();
    const m = extraMonth ?? (yearSelect.value + '-' + monthSelect.value);
    params.append('month', m);
    if (ownershipFilter.value) params.append('ownership_id', ownershipFilter.value);
    if (dateFrom.value)        params.append('date_from', dateFrom.value);
    if (dateTo.value)          params.append('date_to', dateTo.value);
    return params;
}

document.getElementById('goToMonth').addEventListener('click', () => {
    window.location.href = baseUrl + '?' + buildParams();
});
document.getElementById('applyDateRange').addEventListener('click', () => {
    window.location.href = baseUrl + '?' + buildParams();
});
ownershipFilter.addEventListener('change', () => {
    window.location.href = baseUrl + '?' + buildParams();
});

document.getElementById('prevMonth').addEventListener('click', () => {
    const d = new Date(yearSelect.value, parseInt(monthSelect.value) - 2, 1);
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const y = d.getFullYear();
    window.location.href = baseUrl + '?' + buildParams(y + '-' + m);
});
document.getElementById('nextMonth').addEventListener('click', () => {
    const d = new Date(yearSelect.value, parseInt(monthSelect.value), 1);
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const y = d.getFullYear();
    window.location.href = baseUrl + '?' + buildParams(y + '-' + m);
});
</script>
@endsection