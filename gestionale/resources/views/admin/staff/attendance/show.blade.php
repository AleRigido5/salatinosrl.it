{{-- resources/views/admin/staff/attendance/show.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Presenze · ' . $staff->NomePers . ' ' . $staff->CognomePers)

@section('content')
<div class="p-4">

    {{-- HEADER --}}
    <div class="flex justify-between items-center mb-3">
        <div>
            <h1 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-file-alt text-lime-500"></i>
                Inserisci / Modifica PRESENZE di {{ $staff->NomePers }} {{ $staff->CognomePers }}
            </h1>
            <p class="text-xs text-gray-400 mt-0.5">
                <i class="fas fa-calendar-alt mr-1"></i>
                Periodo: {{ $startDate->format('d/m/Y') }} – {{ $endDate->format('d/m/Y') }}
            </p>
        </div>
        <a href="{{ route('admin.staff.attendance.index', ['month' => $year.'-'.str_pad($month,2,'0',STR_PAD_LEFT)]) }}"
           class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded-lg text-sm flex items-center gap-1.5 transition-colors">
            <i class="fas fa-arrow-left"></i>
        </a>
    </div>

    {{-- BARRA NAVIGAZIONE --}}
    <div class="bg-white rounded-lg shadow border border-gray-200 p-3 mb-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <input type="date" id="date_from"
                       value="{{ $startDate->format('Y-m-d') }}"
                       class="px-2 py-1.5 border border-gray-300 rounded text-sm">
                <span class="text-gray-400">→</span>
                <input type="date" id="date_to"
                       value="{{ $endDate->format('Y-m-d') }}"
                       class="px-2 py-1.5 border border-gray-300 rounded text-sm">
                <button id="applyRange"
                        class="bg-lime-500 hover:bg-lime-600 text-white px-3 py-1.5 rounded text-sm font-medium transition-colors">
                    Applica
                </button>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.staff.attendance.show', [
                        'staffId' => $staff->id_personale,
                        'year'    => $startDate->copy()->subMonth()->year,
                        'month'   => $startDate->copy()->subMonth()->month,
                    ]) }}"
                   class="w-8 h-8 flex items-center justify-center bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded transition-colors text-gray-600">
                    <i class="fas fa-arrow-left text-xs"></i>
                </a>

                <select id="monthSelectShow" class="px-2 py-1.5 border border-gray-300 rounded text-sm">
                    @php $mesi = [1=>'Gennaio',2=>'Febbraio',3=>'Marzo',4=>'Aprile',5=>'Maggio',6=>'Giugno',7=>'Luglio',8=>'Agosto',9=>'Settembre',10=>'Ottobre',11=>'Novembre',12=>'Dicembre']; @endphp
                    @foreach(range(1,12) as $m)
                        <option value="{{ str_pad($m,2,'0',STR_PAD_LEFT) }}" {{ $month == $m ? 'selected' : '' }}>
                            {{ $mesi[$m] }}
                        </option>
                    @endforeach
                </select>

                <select id="yearSelectShow" class="px-2 py-1.5 border border-gray-300 rounded text-sm">
                    @for($y = date('Y')-3; $y <= date('Y')+1; $y++)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>

                <a href="{{ route('admin.staff.attendance.show', [
                        'staffId' => $staff->id_personale,
                        'year'    => $startDate->copy()->addMonth()->year,
                        'month'   => $startDate->copy()->addMonth()->month,
                    ]) }}"
                   class="w-8 h-8 flex items-center justify-center bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded transition-colors text-gray-600">
                    <i class="fas fa-arrow-right text-xs"></i>
                </a>

                <button id="goToMonthBtn"
                        class="bg-lime-500 hover:bg-lime-600 text-white px-3 py-1.5 rounded text-sm font-medium transition-colors">
                    Vai
                </button>
            </div>
        </div>
    </div>

    {{-- CARDS STATISTICHE --}}
    @php
        $totalOreCalc = 0;
        $totalGgEffettive = 0;
        $totalPresenze = 0;
        $totalAssenze = 0;
        foreach ($presenze as $dk => $p) {
            if (!$p['is_sunday'] && $p['is_in_assunzione']) {
                if (!empty($p['causale'])) {
                    $totalAssenze++;
                } else {
                    $totalGgEffettive++;
                    $hasPresenza = false;
                    foreach ($p['ownership_checked'] ?? [] as $owId => $checked) {
                        if ($checked) { $hasPresenza = true; break; }
                    }
                    if ($hasPresenza) {
                        $totalPresenze++;
                        $totalOreCalc += $p['ore'] ?? 0;
                    }
                }
            }
        }
        $mediaGiorno = $totalPresenze > 0 ? round($totalOreCalc / $totalPresenze, 1) : 0;
    @endphp

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <div class="bg-white rounded-xl shadow border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-400 uppercase font-semibold tracking-wide">Totale Ore</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1" id="statTotOre">
                        {{ number_format($totalOreCalc, 1) }}<span class="text-sm font-normal text-gray-400 ml-1">h</span>
                    </p>
                </div>
                <div class="h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-blue-500"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-400 uppercase font-semibold tracking-wide">GG. Effettive</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1" id="statGgEffettive">
                        {{ $totalGgEffettive }}
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">Nel periodo assunzione</p>
                </div>
                <div class="h-10 w-10 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-calendar-day text-purple-500"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-400 uppercase font-semibold tracking-wide">Presenze</p>
                    <p class="text-2xl font-bold text-green-600 mt-1" id="statPresenze">
                        {{ $totalPresenze }}
                    </p>
                </div>
                <div class="h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-500"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-lime-50 to-lime-100 rounded-xl shadow border border-lime-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-lime-700 uppercase font-semibold tracking-wide">Media / Giorno</p>
                    <p class="text-2xl font-bold text-lime-700 mt-1" id="statMedia">
                        {{ number_format($mediaGiorno, 1) }}<span class="text-sm font-normal ml-1">h</span>
                    </p>
                </div>
                <div class="h-10 w-10 bg-lime-200 rounded-full flex items-center justify-center">
                    <i class="fas fa-chart-line text-lime-600"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- NOTA ATTIVITÀ --}}
    <div class="mb-3 text-xs text-gray-500 bg-blue-50 border border-blue-200 rounded-lg p-2">
        <i class="fas fa-info-circle text-blue-500 mr-1"></i>
        Le attività mostrate sono <strong>solo indicative</strong>. 
        Spunta manualmente i checkbox per registrare le presenze.
        <span class="text-blue-400 ml-2"><i class="fas fa-circle text-[8px]"></i> = Attività rilevata</span>
    </div>

    {{-- TABELLA PRESENZE --}}
    <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden mb-4">
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="border-b-2 border-gray-200 bg-gray-50">
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide border-r border-gray-200"
                            style="min-width:100px;">DATA</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide border-r border-gray-200"
                            style="min-width:200px;">CLIENTE / CANTIERE</th>
                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide border-r border-gray-200"
                            style="min-width:80px;">N.ORE</th>
                        @foreach($ownerships as $ow)
                        <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide border-r border-gray-200"
                            style="min-width:80px;">
                            {{ $ow->RagAbbrev }}
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody id="attendanceBody">
                    @foreach($presenze as $data => $presenza)
                    @php
                        $isSunday = $presenza['is_sunday'];
                        $isInAssunzione = $presenza['is_in_assunzione'];
                        $causale = $presenza['causale'] ?? null;
                        $oreGiorno = $presenza['ore'] ?? 0;
                        $hasActivity = $presenza['has_activity'] ?? false;

                        if ($isSunday) {
                            $rowStyle = 'background:#fee2e2;';
                            $dateStyle = 'color:#dc2626; font-weight:700;';
                        } elseif (!$isInAssunzione) {
                            $rowStyle = 'background:#f3f4f6;';
                            $dateStyle = 'color:#9ca3af;';
                        } else {
                            $rowStyle = '';
                            $dateStyle = '';
                        }

                        $hasMultiple = $presenza['has_multiple'] ?? false;
                        $numCantieri = $presenza['num_cantieri'] ?? 0;
                        $cantiereLabel = $presenza['cantieri_string'] ?? '';
                        $primoDettaglio = !empty($presenza['dettagli']) ? $presenza['dettagli'][0] : null;
                    @endphp
                    <tr class="border-b border-gray-100 hover:brightness-[0.98]"
                        style="{{ $rowStyle }}"
                        data-date="{{ $data }}">

                        <td class="px-4 py-2 border-r border-gray-100 whitespace-nowrap text-sm"
                            style="{{ $dateStyle }}">
                            {{ \Carbon\Carbon::parse($data)->format('d/m/Y') }}
                            @if($hasActivity && !$isSunday && $isInAssunzione && !$causale)
                                <span class="text-blue-400 text-[10px] ml-1" title="Attività rilevata">
                                    <i class="fas fa-circle"></i>
                                </span>
                            @endif
                        </td>

                        <td class="px-4 py-2 border-r border-gray-100">
                            @if($causale || $isSunday || !$isInAssunzione)
                            @elseif($hasMultiple)
                                <div class="text-sm text-gray-700 font-medium">{{ $primoDettaglio['nome_ownership'] ?? '—' }}</div>
                                <div class="text-xs text-gray-400">{{ $cantiereLabel }}</div>
                                @if($numCantieri > 1)
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-blue-500 text-white text-xs font-bold ml-1">
                                        +{{ $numCantieri - 1 }}
                                    </span>
                                @endif
                            @elseif($primoDettaglio)
                                <div class="text-sm text-gray-700 font-medium">{{ $primoDettaglio['nome_ownership'] ?? '—' }}</div>
                                @if(!empty($primoDettaglio['cost_center_name']))
                                    <div class="text-xs text-gray-400">{{ $primoDettaglio['cost_center_name'] }}</div>
                                @endif
                            @else
                                <span class="text-gray-300 text-sm">—</span>
                            @endif
                        </td>

                        <td class="px-4 py-2 text-center border-r border-gray-100 font-medium text-gray-700 text-sm">
                            @if($causale || $isSunday || !$isInAssunzione)
                                <span class="text-gray-300">—</span>
                            @elseif($oreGiorno > 0)
                                {{ number_format($oreGiorno, 1) }}
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>

                        @foreach($ownerships as $owLoop)
                        @php
                            $owId = $owLoop->id_proprieta;
                            $isChecked = isset($presenza['ownership_checked'][$owId]) && $presenza['ownership_checked'][$owId];
                            $hasActivityForOw = $hasActivity && $isInAssunzione && !$causale && !$isSunday;
                        @endphp
                        <td class="text-center border-r border-gray-100 py-2" style="min-width:80px; position:relative;">
                            @if($causale && $loop->first)
                                <span class="font-bold text-red-500 text-sm uppercase tracking-wide">
                                    {{ ucfirst($causale) }}
                                </span>
                            @elseif($causale)
                            @elseif($isSunday || !$isInAssunzione)
                            @else
                                <div class="flex flex-col items-center gap-0.5">
                                    <input type="checkbox"
                                           class="attendance-checkbox w-4 h-4 rounded border-2 border-gray-300 text-lime-600 focus:ring-lime-500 cursor-pointer"
                                           data-date="{{ $data }}"
                                           data-ownership="{{ $owId }}"
                                           data-staff="{{ $staff->id_personale }}"
                                           data-ore="{{ $oreGiorno }}"
                                           {{ $isChecked ? 'checked' : '' }}>
                                    @if(!$isChecked && $hasActivityForOw)
                                        <span class="text-[8px] text-blue-400" title="Attività rilevata in questo giorno">
                                            <i class="fas fa-circle"></i>
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>

                <tfoot>
                    <tr class="border-t-2 border-gray-300 bg-gray-50">
                        <td colspan="3" class="px-4 py-3 text-right font-bold text-gray-800 uppercase tracking-wide text-sm border-r border-gray-200">
                            TOTALE PRESENZE
                        </td>
                        @foreach($ownerships as $ow)
                        <td class="text-center py-3 border-r border-gray-200 font-bold text-gray-800 text-base"
                            id="total-{{ $ow->id_proprieta }}"
                            style="min-width:80px;">
                            {{ $totaliPerOwnership[$ow->id_proprieta] ?? 0 }}
                        </td>
                        @endforeach
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- AZIONI BOTTOM --}}
    <div class="flex justify-end gap-3">
        <a href="{{ route('admin.staff.attendance.index', ['month' => $year.'-'.str_pad($month,2,'0',STR_PAD_LEFT)]) }}"
           class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">
            Annulla
        </a>
        <button onclick="saveAllChanges()"
                id="saveBtn"
                class="px-4 py-2 bg-lime-600 hover:bg-lime-700 text-white rounded-lg text-sm font-medium flex items-center gap-2 transition-colors shadow-sm">
            <i class="fas fa-save"></i> Salva
        </button>
    </div>

</div>

{{-- 
    ═══════════════════════════════════════════════════════════════════════════
    MODAL DETTAGLIO PRESENZA - COMMENTATO PER FUTURO UTILIZZO
    ═══════════════════════════════════════════════════════════════════════════
    Questo modal è stato progettato per gestire campi aggiuntivi come:
    - Orario di entrata
    - Orario di uscita
    - Pausa (minuti)
    - Causale
    - Note
    
    Per attivarlo:
    1. Decommentare questo blocco
    2. Scommentare le funzioni nel JavaScript
    3. Scommentare i campi nel controller (save)
    4. Aggiungere i campi nel modello StaffAttendanceJson
--}}
{{--
<div id="attendanceDetailModal" class="fixed inset-0 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="relative bg-white rounded-xl shadow-2xl max-w-md w-full p-6 transform transition-all">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900">
                    <i class="fas fa-pen text-lime-500 mr-2"></i>
                    Dettaglio Presenza
                </h3>
                <button onclick="closeAttendanceModal()"
                        class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="space-y-4">
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs text-gray-500">Data</p>
                    <p class="font-medium text-gray-800" id="modal-date">—</p>
                </div>

                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs text-gray-500">Proprietà / Ownership</p>
                    <p class="font-medium text-gray-800" id="modal-ownership">—</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Entrata</label>
                        <input type="time" id="modal-entrata"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lime-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Uscita</label>
                        <input type="time" id="modal-uscita"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lime-500 focus:border-transparent">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Pausa (minuti)</label>
                    <input type="number" id="modal-pausa"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lime-500 focus:border-transparent"
                           placeholder="0" min="0" value="0">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Causale</label>
                    <select id="modal-causale"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lime-500 focus:border-transparent">
                        <option value="">Nessuna</option>
                        <option value="malattia">Malattia</option>
                        <option value="ferie">Ferie</option>
                        <option value="permesso">Permesso</option>
                        <option value="formazione">Formazione</option>
                        <option value="sciopero">Sciopero</option>
                        <option value="altro">Altro</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Note</label>
                    <textarea id="modal-note" rows="2"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lime-500 focus:border-transparent"
                              placeholder="Inserisci eventuali note..."></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button onclick="closeAttendanceModal()"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors">
                    Annulla
                </button>
                <button onclick="confirmAttendance()"
                        class="px-4 py-2 bg-lime-600 hover:bg-lime-700 text-white rounded-lg text-sm font-medium flex items-center gap-2 transition-colors shadow-sm">
                    <i class="fas fa-check"></i> Conferma
                </button>
            </div>
        </div>
    </div>
</div>
--}}

{{-- MODAL NOTIFICA --}}
<div id="notificationModal" class="fixed inset-0 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75"></div>
        <div class="relative bg-white rounded-xl shadow-xl p-6 max-w-sm w-full">
            <div class="flex items-start gap-4">
                <div id="modalIconWrap" class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center bg-green-100">
                    <i id="modalIcon" class="fas fa-check text-green-600"></i>
                </div>
                <div>
                    <h3 id="modalTitle" class="font-semibold text-gray-900">Successo</h3>
                    <p id="modalMessage" class="text-sm text-gray-500 mt-1"></p>
                </div>
            </div>
            <div class="mt-4 flex justify-end">
                <button id="modalCloseBtn"
                        class="px-4 py-2 bg-lime-600 text-white rounded-lg text-sm font-medium hover:bg-lime-700">
                    OK
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const staffId   = {{ $staff->id_personale }};
    const csrfToken = '{{ csrf_token() }}';
    const saveUrl   = '{{ route("admin.staff.attendance.save") }}';
    const baseShowUrl = '{{ route("admin.staff.attendance.show", ["staffId" => "__SID__", "year" => "__Y__", "month" => "__M__"]) }}';

    let pendingChanges = new Map();

    // ── Modal Notifica ──────────────────────────────────────────────────
    const modal = document.getElementById('notificationModal');
    function showModal(title, message, type = 'success') {
        const wrap = document.getElementById('modalIconWrap');
        const icon = document.getElementById('modalIcon');
        wrap.className = 'flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center';
        if (type === 'success') { wrap.classList.add('bg-green-100'); icon.className = 'fas fa-check text-green-600'; }
        else if (type === 'error') { wrap.classList.add('bg-red-100'); icon.className = 'fas fa-times text-red-600'; }
        else { wrap.classList.add('bg-yellow-100'); icon.className = 'fas fa-exclamation text-yellow-600'; }
        document.getElementById('modalTitle').textContent = title;
        document.getElementById('modalMessage').textContent = message;
        modal.classList.remove('hidden');
    }
    document.getElementById('modalCloseBtn').addEventListener('click', () => modal.classList.add('hidden'));

    // ── Navigazione mese ──────────────────────────────────────────────────
    document.getElementById('goToMonthBtn').addEventListener('click', () => {
        const m = document.getElementById('monthSelectShow').value;
        const y = document.getElementById('yearSelectShow').value;
        window.location.href = baseShowUrl.replace('__SID__', staffId).replace('__Y__', y).replace('__M__', m);
    });

    // ── Live stats + totali colonna ───────────────────────────────────────
    function updateStats() {
        const totals = {};
        let totalPresenze = 0;
        let totalOre = 0;

        document.querySelectorAll('.attendance-checkbox').forEach(chk => {
            const owId = chk.dataset.ownership;
            if (!totals[owId]) totals[owId] = 0;
            if (chk.checked) {
                totals[owId]++;
            }
        });

        const dateChecked = {};
        document.querySelectorAll('.attendance-checkbox:checked').forEach(chk => {
            const date = chk.dataset.date;
            if (!dateChecked[date]) {
                dateChecked[date] = true;
                totalPresenze++;
                const ore = parseFloat(chk.dataset.ore || 0);
                if (!isNaN(ore)) totalOre += ore;
            }
        });

        Object.entries(totals).forEach(([owId, count]) => {
            const el = document.getElementById('total-' + owId);
            if (el) el.textContent = count;
        });

        const statPresenze = document.getElementById('statPresenze');
        const statTotOre   = document.getElementById('statTotOre');
        const statMedia    = document.getElementById('statMedia');

        if (statPresenze) statPresenze.textContent = totalPresenze;
        if (statTotOre)   statTotOre.innerHTML = totalOre.toFixed(1) + '<span class="text-sm font-normal text-gray-400 ml-1">h</span>';

        const media = totalPresenze > 0 ? (totalOre / totalPresenze).toFixed(1) : '0.0';
        if (statMedia) statMedia.innerHTML = media + '<span class="text-sm font-normal ml-1">h</span>';
    }

    // ── Gestione checkbox ─────────────────────────────────────────────────
    document.querySelectorAll('.attendance-checkbox').forEach(chk => {
        chk.addEventListener('change', function () {
            const key = this.dataset.date + '|' + this.dataset.ownership;
            
            if (this.checked) {
                // ✅ Presenza attivata - salva solo checked = true
                pendingChanges.set(key, {
                    date: this.dataset.date,
                    ownership_id: this.dataset.ownership,
                    checked: true,
                });
            } else {
                // ❌ Presenza disattivata - salva checked = false
                pendingChanges.set(key, {
                    date: this.dataset.date,
                    ownership_id: this.dataset.ownership,
                    checked: false,
                });
            }
            
            updateStats();
        });
    });

    // ── Salva modifiche ───────────────────────────────────────────────────
    window.saveAllChanges = function () {
        if (pendingChanges.size === 0) {
            showModal('Nessuna modifica', 'Non ci sono modifiche da salvare.', 'warning');
            return;
        }

        const saveBtn = document.getElementById('saveBtn');
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvataggio...';
        saveBtn.disabled = true;

        const changes = Array.from(pendingChanges.values()).map(c => ({
            staff_id: staffId,
            date: c.date,
            checked: c.checked,
            ownership_id: c.ownership_id || null,
        }));

        fetch(saveUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ changes }),
        })
        .then(r => r.json())
        .then(data => {
            saveBtn.innerHTML = '<i class="fas fa-save"></i> Salva';
            saveBtn.disabled = false;
            if (data.success) {
                pendingChanges.clear();
                showModal('Salvato', data.message || 'Presenze salvate con successo!', 'success');
                setTimeout(() => location.reload(), 1200);
            } else {
                showModal('Errore', data.message || 'Errore durante il salvataggio.', 'error');
            }
        })
        .catch(() => {
            saveBtn.innerHTML = '<i class="fas fa-save"></i> Salva';
            saveBtn.disabled = false;
            showModal('Errore', 'Impossibile connettersi al server.', 'error');
        });
    };

    // ── Init ──────────────────────────────────────────────────────────────
    updateStats();
});
</script>
@endsection