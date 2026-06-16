{{-- resources/views/admin/staff/attendance/show.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Presenze - ' . $staff->NomePers . ' ' . $staff->CognomePers)

@section('content')
<div class="p-6">

    {{-- ══════════════════════════════════════════════════════
         HEADER
    ══════════════════════════════════════════════════════ --}}
    <div class="mb-5 flex flex-wrap justify-between items-start gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-user-clock text-lime-500"></i>
                {{ $staff->NomePers }} {{ $staff->CognomePers }}
            </h1>
            <p class="text-gray-400 text-sm mt-1">
                <i class="fas fa-calendar-alt mr-1"></i>
                {{ $startDate->isoFormat('MMMM YYYY') }}
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
            <a href="{{ route('admin.staff.attendance.index') }}"
               class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded-lg transition-colors text-sm flex items-center gap-1.5">
                <i class="fas fa-arrow-left"></i> Lista
            </a>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         BARRA NAVIGAZIONE MESE + PROPRIETÀ
    ══════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-xl shadow border border-gray-200 p-3 mb-5">
        <div class="flex flex-wrap items-center justify-between gap-3">

            {{-- Proprietà --}}
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

            {{-- Navigazione mese --}}
            <div class="flex items-center gap-2">
                {{-- Mese precedente --}}
                <a href="{{ route('admin.staff.attendance.show', [
                        'staffId' => $staff->id_personale,
                        'year'    => $startDate->copy()->subMonth()->year,
                        'month'   => $startDate->copy()->subMonth()->month,
                    ]) }}{{ $selectedOwnershipId ? '?ownership_id='.$selectedOwnershipId : '' }}"
                   class="w-8 h-8 flex items-center justify-center bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-lg transition-colors text-gray-600"
                   title="Mese precedente">
                    <i class="fas fa-chevron-left text-xs"></i>
                </a>

                <select id="monthSelectShow" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lime-500">
                    @php
                        $mesi = [
                            1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile',
                            5 => 'Maggio', 6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto',
                            9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre'
                        ];
                    @endphp
                    @foreach(range(1, 12) as $m)
                        <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}"
                            {{ $month == str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                            {{ $mesi[$m] }}
                        </option>
                    @endforeach
                </select>

                <select id="yearSelectShow" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lime-500">
                    @for($y = \Carbon\Carbon::now()->year - 3; $y <= \Carbon\Carbon::now()->year + 1; $y++)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>

                {{-- Mese successivo --}}
                <a href="{{ route('admin.staff.attendance.show', [
                        'staffId' => $staff->id_personale,
                        'year'    => $startDate->copy()->addMonth()->year,
                        'month'   => $startDate->copy()->addMonth()->month,
                    ]) }}{{ $selectedOwnershipId ? '?ownership_id='.$selectedOwnershipId : '' }}"
                   class="w-8 h-8 flex items-center justify-center bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-lg transition-colors text-gray-600"
                   title="Mese successivo">
                    <i class="fas fa-chevron-right text-xs"></i>
                </a>

                <button id="goToMonthBtn" type="button"
                    class="px-4 py-1.5 bg-gradient-to-r from-lime-500 to-lime-600 hover:from-lime-600 hover:to-lime-700 text-white rounded-lg text-sm font-medium shadow-sm transition-all">
                    <i class="fas fa-arrow-right mr-1"></i> Vai
                </button>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         TABELLA PRESENZE
    ══════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-xl shadow overflow-hidden border border-gray-200 mb-6">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-10">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Data</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">Giorno</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">Presente</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Azienda / Cantieri</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-20">N. Ore</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">Causale</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($presenze as $data => $presenza)
                    @php
                        $rowClass = '';
                        if ($presenza['is_sunday']) {
                            $rowClass = 'bg-red-50';
                        } elseif (!$presenza['is_in_assunzione']) {
                            $rowClass = 'bg-gray-100';
                        } elseif ($presenza['causale']) {
                            $rowClass = 'bg-amber-50';
                        } elseif ($presenza['checked']) {
                            $rowClass = 'bg-lime-50/40';
                        }
                        
                        // Giorni della settimana in italiano
                        $giorniSettimana = [
                            'Mon' => 'Lun',
                            'Tue' => 'Mar',
                            'Wed' => 'Mer',
                            'Thu' => 'Gio',
                            'Fri' => 'Ven',
                            'Sat' => 'Sab',
                            'Sun' => 'Dom'
                        ];
                        $giornoLabel = $giorniSettimana[$presenza['giorno_settimana']] ?? $presenza['giorno_settimana'];
                    @endphp
                    <tr class="{{ $rowClass }} hover:brightness-[0.97] transition-all">

                        {{-- Numero giorno --}}
                        <td class="px-4 py-2.5 text-xs text-gray-400 font-mono text-center">
                            {{ $presenza['giorno_num'] }}
                        </td>

                        {{-- Data --}}
                        <td class="px-4 py-2.5 text-sm whitespace-nowrap font-medium
                            {{ $presenza['is_sunday'] ? 'text-red-600' : 'text-gray-700' }}">
                            {{ \Carbon\Carbon::parse($data)->format('d/m/Y') }}
                        </td>

                        {{-- Giorno settimana --}}
                        <td class="px-4 py-2.5 text-sm whitespace-nowrap
                            {{ $presenza['is_sunday'] ? 'text-red-500 font-semibold' : 'text-gray-500' }}">
                            {{ $giornoLabel }}
                        </td>

                        {{-- Checkbox presenza --}}
                        <td class="px-4 py-2.5 text-center">
                            @if($presenza['is_sunday'])
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-600 uppercase tracking-wide">
                                    Dom
                                </span>
                            @elseif(!$presenza['is_in_assunzione'])
                                <span class="inline-block w-5 h-5 rounded bg-gray-300" title="Fuori periodo assunzione"></span>
                            @elseif($presenza['causale'])
                                {{-- causale gestita nella colonna apposita --}}
                                <span class="text-gray-300 text-xs">—</span>
                            @else
                                <input type="checkbox"
                                    class="attendance-checkbox w-4.5 h-4.5 rounded border-gray-300 text-lime-600 focus:ring-lime-500 cursor-pointer"
                                    data-date="{{ $data }}"
                                    data-staff="{{ $staff->id_personale }}"
                                    {{ $presenza['checked'] ? 'checked' : '' }}>
                            @endif
                        </td>

                        {{-- Azienda / Cantieri --}}
                        <td class="px-4 py-2.5 text-center text-sm">
                            @if($presenza['has_multiple'])
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700"
                                      title="{{ $presenza['num_cantieri'] }} cantieri in questo giorno">
                                    <i class="fas fa-layer-group"></i>
                                    {{ $presenza['num_cantieri'] }} cantieri
                                </span>
                            @elseif($presenza['checked'] && $presenza['id_ownership'])
                                @php
                                    $owName = $ownerships->firstWhere('id_proprieta', $presenza['id_ownership']);
                                @endphp
                                <span class="text-gray-600 text-xs">
                                    {{ $owName ? $owName->RagAbbrev : '—' }}
                                </span>
                            @else
                                <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>

                        {{-- Ore --}}
                        <td class="px-4 py-2.5 text-center text-sm font-semibold
                            {{ $presenza['ore'] > 0 ? 'text-gray-800' : 'text-gray-300' }}">
                            {{ $presenza['ore'] > 0 ? number_format($presenza['ore'], 1) . ' h' : '—' }}
                        </td>

                        {{-- Causale --}}
                        <td class="px-4 py-2.5 text-center">
                            @if($presenza['causale'])
                                @php
                                    $causaleConfig = match($presenza['causale']) {
                                        'malattia' => ['bg-red-100 text-red-700', 'fas fa-thermometer-half', 'Malattia'],
                                        'ferie'    => ['bg-blue-100 text-blue-700', 'fas fa-umbrella-beach', 'Ferie'],
                                        'permesso' => ['bg-purple-100 text-purple-700', 'fas fa-calendar-check', 'Permesso'],
                                        default    => ['bg-gray-100 text-gray-600', 'fas fa-info-circle', ucfirst($presenza['causale'])],
                                    };
                                @endphp
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold {{ $causaleConfig[0] }}">
                                    <i class="{{ $causaleConfig[1] }}"></i>
                                    {{ $causaleConfig[2] }}
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

    {{-- ══════════════════════════════════════════════════════
         STATISTICHE
    ══════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

        {{-- Totale Ore --}}
        <div class="bg-white rounded-xl shadow border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-400 uppercase font-semibold tracking-wide">Totale Ore</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($totalOre, 1) }}<span class="text-sm font-normal text-gray-400 ml-1">h</span></p>
                </div>
                <div class="h-11 w-11 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-blue-500"></i>
                </div>
            </div>
        </div>

        {{-- Giornate Effettive --}}
        <div class="bg-white rounded-xl shadow border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-400 uppercase font-semibold tracking-wide">Gg. Effettive</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1">{{ $totalGiornateEffettive }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">Escl. dom. e assenze</p>
                </div>
                <div class="h-11 w-11 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-calendar-day text-purple-500"></i>
                </div>
            </div>
        </div>

        {{-- Giornate Messe --}}
        <div class="bg-white rounded-xl shadow border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-400 uppercase font-semibold tracking-wide">Giornate Messe</p>
                    <p class="text-2xl font-bold text-green-600 mt-1" id="giornateMesseDisplay">{{ $totalGiornateMesse }}</p>
                </div>
                <div class="h-11 w-11 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-500"></i>
                </div>
            </div>
        </div>

        {{-- Media Giornaliera --}}
        <div class="bg-gradient-to-br from-lime-50 to-lime-100 rounded-xl shadow border border-lime-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-lime-700 uppercase font-semibold tracking-wide">Media / Giorno</p>
                    <p class="text-2xl font-bold text-lime-700 mt-1" id="mediaGiornalieraDisplay">
                        {{ $totalGiornateMesse > 0 ? number_format($totalOre / $totalGiornateMesse, 1) : '0.0' }}<span class="text-sm font-normal ml-1">h</span>
                    </p>
                </div>
                <div class="h-11 w-11 bg-lime-200 rounded-full flex items-center justify-center">
                    <i class="fas fa-chart-line text-lime-600"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Legenda --}}
    <div class="mt-4 flex flex-wrap gap-4 text-xs text-gray-500">
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
    </div>
</div>

<script>
const staffId        = {{ $staff->id_personale }};
const csrfToken      = '{{ csrf_token() }}';
const saveUrl        = '{{ route("admin.staff.attendance.save") }}';
const baseShowUrl    = '{{ route("admin.staff.attendance.show", ["staffId" => "STAFF_ID", "year" => "YEAR", "month" => "MONTH"]) }}';
const totalOreServer = {{ $totalOre }};

// ── MESI IN ITALIANO per il JavaScript ─────────────────────────────────────
const mesiItaliano = {
    '01': 'Gennaio', '02': 'Febbraio', '03': 'Marzo', '04': 'Aprile',
    '05': 'Maggio', '06': 'Giugno', '07': 'Luglio', '08': 'Agosto',
    '09': 'Settembre', '10': 'Ottobre', '11': 'Novembre', '12': 'Dicembre'
};

// ── Navigazione mese ────────────────────────────────────────────────────────
const monthSelect  = document.getElementById('monthSelectShow');
const yearSelect   = document.getElementById('yearSelectShow');
const ownerSelect  = document.getElementById('ownershipSelect');
const goToMonthBtn = document.getElementById('goToMonthBtn');

if (goToMonthBtn) {
    goToMonthBtn.addEventListener('click', function () {
        const m  = monthSelect.value;
        const y  = yearSelect.value;
        const ow = ownerSelect ? ownerSelect.value : '';
        let url  = baseShowUrl
            .replace('STAFF_ID', staffId)
            .replace('YEAR', y)
            .replace('MONTH', m);
        if (ow) url += '?ownership_id=' + ow;
        window.location.href = url;
    });
}

// Quando cambia la proprietà -> ricarica con nuova ownership
if (ownerSelect) {
    ownerSelect.addEventListener('change', function () {
        const m  = '{{ str_pad($month, 2, "0", STR_PAD_LEFT) }}';
        const y  = '{{ $year }}';
        const ow = this.value;
        let url  = baseShowUrl
            .replace('STAFF_ID', staffId)
            .replace('YEAR', y)
            .replace('MONTH', m);
        if (ow) url += '?ownership_id=' + ow;
        window.location.href = url;
    });
}

// ── Gestione checkbox ───────────────────────────────────────────────────────
const giornateDisplay = document.getElementById('giornateMesseDisplay');
const mediaDisplay    = document.getElementById('mediaGiornalieraDisplay');

function countChecked() {
    return document.querySelectorAll('.attendance-checkbox:checked').length;
}

function updateStats() {
    const count = countChecked();
    if (giornateDisplay) giornateDisplay.textContent = count;
    if (mediaDisplay) {
        const media = count > 0 ? (totalOreServer / count).toFixed(1) : '0.0';
        mediaDisplay.innerHTML = media + '<span class="text-sm font-normal ml-1">h</span>';
    }
}

document.querySelectorAll('.attendance-checkbox').forEach(function (checkbox) {
    checkbox.addEventListener('change', function () {
        const date    = this.dataset.date;
        const checked = this.checked;
        const ownerId = ownerSelect ? ownerSelect.value : '';

        updateStats();

        fetch(saveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                staff_id:     staffId,
                date:         date,
                checked:      checked,
                ownership_id: ownerId || null,
            }),
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                console.error('Errore salvataggio:', data.message);
                this.checked = !checked; // ripristina
                updateStats();
            }
        })
        .catch(err => {
            console.error('Errore fetch:', err);
            this.checked = !checked;
            updateStats();
        });
    });
});

// Inizializza contatore
updateStats();
</script>
@endsection