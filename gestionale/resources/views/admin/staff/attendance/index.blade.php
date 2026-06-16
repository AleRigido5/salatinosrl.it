{{-- resources/views/admin/staff/attendance/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Gestione Presenze')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-clock mr-3 text-lime-600"></i>
            Gestione Presenze
        </h1>
        <a href="{{ route('admin.staff.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors text-sm flex items-center gap-2">
            <i class="fas fa-arrow-left"></i>
        </a>
    </div>

    <!-- Filtri - Tutto su una riga -->
    <div class="bg-white rounded-lg shadow mb-6 p-4 border border-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <!-- Range personalizzato a sinistra -->
            <div class="flex items-center gap-3">
                <input type="date" name="date_from" value="{{ $dateFrom ?? now()->format('Y-m-d') }}" 
                       id="date_from"
                       class="px-3 py-1.5 border border-gray-300 rounded-md text-sm">
                <span class="text-gray-500">→</span>
                <input type="date" name="date_to" value="{{ $dateTo ?? now()->format('Y-m-d') }}" 
                       id="date_to"
                       class="px-3 py-1.5 border border-gray-300 rounded-md text-sm">
                <button type="button" id="applyDateRange" class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-4 py-1.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 text-sm">
                    Applica
                </button>
            </div>

            <!-- Select proprietà -->
            <div class="flex items-center gap-2">
                <select id="ownershipFilter" class="px-3 py-1.5 border border-gray-300 rounded-md text-sm">
                    <option value="">Tutte le proprietà</option>
                    @foreach($properties ?? $ownerships as $prop)
                        <option value="{{ $prop->id_proprieta ?? $prop->id }}" {{ request('ownership_id') == ($prop->id_proprieta ?? $prop->id) ? 'selected' : '' }}>
                            {{ $prop->RagAbbrev ?? $prop->name }}
                        </option>
                    @endforeach
                </select>
                
                <select id="monthSelect" class="px-3 py-1.5 border border-gray-300 rounded-md text-sm">
                    @php
                        $mesi = [
                            1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile',
                            5 => 'Maggio', 6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto',
                            9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre'
                        ];
                    @endphp
                    @foreach(range(1, 12) as $m)
                        <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}" {{ ($selectedMonth ?? date('m')) == str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                            {{ $mesi[$m] }}
                        </option>
                    @endforeach
                </select>
                
                <select id="yearSelect" class="px-3 py-1.5 border border-gray-300 rounded-md text-sm">
                    @for($y = ($currentYear ?? date('Y')) - 2; $y <= ($currentYear ?? date('Y')) + 1; $y++)
                        <option value="{{ $y }}" {{ ($selectedYear ?? date('Y')) == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
                
                <button type="button" id="goToMonth" class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-4 py-1.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 text-sm">
                    Vai
                </button>
            </div>

            <!-- Navigazione e select a destra -->
            <div class="flex items-center gap-4">
                <div class="flex gap-2">
                    @if(isset($previousMonth))
                    <a href="{{ route('admin.staff.attendance.index', ['month' => $previousMonth->format('Y-m')]) }}" 
                       class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 rounded-md transition-colors" title="Mese precedente">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <a href="{{ route('admin.staff.attendance.index', ['month' => $nextMonth->format('Y-m')]) }}" 
                       class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 rounded-md transition-colors" title="Mese successivo">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Tabella Personale -->
    <div class="bg-white rounded-xl shadow overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome e Cognome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoria</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach($staff as $person)
                    <tr class="hover:bg-lime-50/30 transition-colors">
                        <td class="px-6 py-3 text-sm text-gray-400 font-mono">{{ $person->id_personale }}</td>
                        <td class="px-6 py-3 text-sm font-semibold text-gray-900">
                            {{ $person->NomePers }} {{ $person->CognomePers }}
                        </td>
                        <td class="px-6 py-3 text-sm text-gray-500">
                            {{ $person->gruppo->valore ?? '—' }}
                        </td>
                        <td class="px-6 py-3 text-center">
                            <button type="button"
                                onclick="goToAttendance({{ $person->id_personale }})"
                                class="inline-flex items-center px-3 py-1.5 bg-lime-600 hover:bg-lime-700 text-white rounded-lg transition-colors text-sm font-medium">
                                <i class="fas fa-user-clock mr-1.5"></i> Presenze
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const monthSelect = document.getElementById('monthSelect');
const yearSelect  = document.getElementById('yearSelect');
const ownershipFilter = document.getElementById('ownershipFilter');
const dateFrom = document.getElementById('date_from');
const dateTo = document.getElementById('date_to');

function getCurrentMonthYear() {
    return {
        month: monthSelect.value,
        year: yearSelect.value
    };
}

function applyFilters() {
    const month = monthSelect.value;
    const year  = yearSelect.value;
    const ownership = ownershipFilter.value;
    const from = dateFrom ? dateFrom.value : '';
    const to = dateTo ? dateTo.value : '';
    
    const url = '{{ route("admin.staff.attendance.index") }}';
    let params = new URLSearchParams();
    params.append('month', year + '-' + month);
    if (ownership) params.append('ownership_id', ownership);
    if (from) params.append('date_from', from);
    if (to) params.append('date_to', to);
    
    window.location.href = url + '?' + params.toString();
}

// Pulsante "Vai" per mese/anno
document.getElementById('goToMonth').addEventListener('click', applyFilters);

// Pulsante "Applica" per date range
document.getElementById('applyDateRange').addEventListener('click', applyFilters);

// Enter key per applicare filtri
monthSelect.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') applyFilters();
});
yearSelect.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') applyFilters();
});
if (dateFrom) {
    dateFrom.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') applyFilters();
    });
}
if (dateTo) {
    dateTo.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') applyFilters();
    });
}

function goToAttendance(staffId) {
    const month = monthSelect.value;
    const year  = yearSelect.value;
    const ownership = ownershipFilter.value;
    const from = dateFrom ? dateFrom.value : '';
    const to = dateTo ? dateTo.value : '';
    
    let url = '{{ route("admin.staff.attendance.show", ["staffId" => "STAFF_ID", "year" => "YEAR", "month" => "MONTH"]) }}'
        .replace('STAFF_ID', staffId)
        .replace('YEAR', year)
        .replace('MONTH', month);
    
    const params = new URLSearchParams();
    if (ownership) params.append('ownership_id', ownership);
    if (from) params.append('date_from', from);
    if (to) params.append('date_to', to);
    
    const queryString = params.toString();
    if (queryString) url += '?' + queryString;
    
    window.location.href = url;
}
</script>
@endsection