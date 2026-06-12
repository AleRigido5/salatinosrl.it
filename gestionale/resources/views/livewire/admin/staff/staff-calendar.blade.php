{{-- resources/views/livewire/admin/staff/staff-calendar.blade.php --}}
<div>
    <!-- Header con titolo e pulsanti vista -->
    <div class="flex flex-wrap justify-between items-center mb-4">
        <div class="flex items-center space-x-4">
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fa-regular fa-calendar-alt mr-3 text-lime-600"></i>
                Calendario Scadenze Personale
            </h1>
            <div class="flex bg-gray-100 rounded-lg p-1">
                <button wire:click="$set('view', 'calendar')" 
                    class="px-4 py-2 rounded-md text-sm font-medium transition-all {{ $view === 'calendar' ? 'bg-white shadow-md text-lime-600' : 'text-gray-600 hover:text-gray-900' }}">
                    <i class="fas fa-calendar-week mr-1"></i> Calendario
                </button>
                <button wire:click="$set('view', 'table')" 
                    class="px-4 py-2 rounded-md text-sm font-medium transition-all {{ $view === 'table' ? 'bg-white shadow-md text-lime-600' : 'text-gray-600 hover:text-gray-900' }}">
                    <i class="fas fa-table mr-1"></i> Tabella
                </button>
            </div>
        </div>
        
        <a href="{{ route('admin.staff.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center" title="Torna al personale">
            <i class="fas fa-arrow-left"></i>
        </a>
    </div>

    <!-- VISTA CALENDARIO -->
    @if($view === 'calendar')
    <!-- Filtri per il calendario -->
    <div class="bg-white rounded-lg shadow mb-6 p-4 border border-gray-200">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Filtro per tipologia scadenza (bottoni) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-tags mr-1 text-gray-400"></i> Tipologia Scadenza
                </label>
                <div class="flex flex-wrap gap-2">
                    @foreach($expirationTypes as $type)
                        <button type="button"
                            wire:click="toggleType({{ $type->id }})"
                            class="px-3 py-1.5 text-xs rounded-full font-medium transition-all {{ $selectedType == $type->id ? 'bg-lime-500 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                            {{ $type->label }}
                        </button>
                    @endforeach
                    @if($selectedType)
                        <button type="button"
                            wire:click="selectAllTypes"
                            class="px-2 py-1.5 text-xs text-blue-600 hover:text-blue-800">
                            <i class="fas fa-check-double"></i> Tutti
                        </button>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Filtri attivi -->
        @if($selectedStaffId || $selectedType)
        <div class="mt-3 pt-3 border-t flex flex-wrap gap-2">
            <span class="text-xs text-gray-500">Filtri attivi:</span>
            @if($selectedStaffId)
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                <i class="fas fa-user mr-1"></i> {{ $selectedStaffName }}
                <button wire:click="clearStaff" class="ml-1 hover:text-lime-900"><i class="fas fa-times text-xs"></i></button>
            </span>
            @endif
            @if($selectedType)
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                <i class="fas fa-filter mr-1"></i> {{ $expirationTypes->firstWhere('id', $selectedType)->label ?? $selectedType }}
                <button wire:click="selectAllTypes" class="ml-1 hover:text-lime-900"><i class="fas fa-times text-xs"></i></button>
            </span>
            @endif
        </div>
        @endif
    </div>

    <!-- Calendario -->
    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <!-- Calendar Header -->
        <div class="flex justify-between items-center p-4 border-b bg-gray-50">
            <button wire:click="previousMonth" class="p-2 hover:bg-gray-200 rounded-full transition">
                <i class="fas fa-chevron-left"></i>
            </button>
            <h2 class="text-xl font-semibold text-gray-800">
                @php
                    $mesi = [
                        1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile',
                        5 => 'Maggio', 6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto',
                        9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre'
                    ];
                @endphp
                {{ $mesi[$currentMonth] }} {{ $currentYear }}
            </h2>
            <div class="flex gap-2">
                <button wire:click="goToToday" class="px-3 py-1 text-sm bg-gray-200 rounded-md hover:bg-gray-300">
                    Oggi
                </button>
                <button wire:click="nextMonth" class="p-2 hover:bg-gray-200 rounded-full transition">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
        
        <!-- Weekday Headers -->
        <div class="grid grid-cols-7 bg-gray-100 border-b">
            @php
                $giorniSettimana = ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato', 'Domenica'];
            @endphp
            @foreach($giorniSettimana as $weekday)
                <div class="py-3 text-center text-sm font-medium text-gray-600 border-r last:border-r-0">
                    {{ $weekday }}
                </div>
            @endforeach
        </div>
        
        <!-- Calendar Grid -->
        <div class="grid grid-cols-7 auto-rows-fill">
            @foreach($calendarDays as $day)
                @php
                    $isToday = $day->date->isToday();
                    $isSelected = $selectedDate && \Carbon\Carbon::parse($selectedDate)->format('Y-m-d') === $day->date->format('Y-m-d');
                @endphp
                <div class="min-h-[120px] border-r border-b p-2 {{ !$day->current_month ? 'bg-gray-50' : '' }} {{ $isToday ? 'bg-lime-50' : '' }} hover:bg-gray-100 transition cursor-pointer"
                    wire:click="selectDate('{{ $day->date->format('Y-m-d') }}')">
                    <div class="flex justify-between items-start">
                        <span class="text-sm font-medium {{ !$day->current_month ? 'text-gray-400' : ($isToday ? 'text-lime-600' : 'text-gray-700') }}">
                            {{ $day->date->day }}
                        </span>
                        @if($day->expirations->count() > 0)
                            <span class="text-xs bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center">
                                {{ $day->expirations->count() }}
                            </span>
                        @endif
                    </div>
                    <div class="mt-1 space-y-1">
                        @foreach($day->expirations->take(3) as $exp)
                            @php $expStatus = $this->getExpirationStatus($exp->data_fine); @endphp
                            <div class="text-xs p-1 rounded {{ $this->getTypeColor($exp->id_settings) }} truncate"
                                title="{{ $exp->staff ? $exp->staff->NomePers . ' ' . $exp->staff->CognomePers : '—' }} - {{ $exp->setting->valore ?? $this->getTypeLabel($exp->id_settings) }}">
                                <i class="fas {{ $expStatus->icon }} text-xs mr-1"></i>
                                {{ $exp->staff ? $exp->staff->NomePers . ' ' . $exp->staff->CognomePers : '—' }}
                            </div>
                        @endforeach
                        @if($day->expirations->count() > 3)
                            <div class="text-xs text-gray-400 text-center">
                                +{{ $day->expirations->count() - 3 }} altre
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    
    <!-- Modal Dettaglio Scadenze del Giorno -->
    @if($selectedDate)
    <div wire:ignore.self class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: true }" x-show="show" x-transition.opacity.duration.200ms>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" x-on:click="show = false; $wire.set('selectedDate', null)"></div>
            <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] overflow-y-auto">
                <div class="sticky top-0 bg-white px-6 py-4 border-b flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-calendar-day mr-2 text-lime-600"></i>
                        Scadenze del {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}
                    </h3>
                    <button wire:click="$set('selectedDate', null)" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="p-6">
                    @php
                        $dayExpirations = $expirations->filter(fn($e) => \Carbon\Carbon::parse($e->data_fine)->format('Y-m-d') === $selectedDate);
                    @endphp
                    @if($dayExpirations->count() > 0)
                        <div class="space-y-3">
                            @foreach($dayExpirations as $exp)
                                @php $expStatus = $this->getExpirationStatus($exp->data_fine); @endphp
                                <div class="border rounded-lg p-4 {{ $expStatus->class }} bg-white shadow-sm">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $this->getTypeColor($exp->id_settings) }}">
                                                    {{ $exp->setting->valore ?? $this->getTypeLabel($exp->id_settings) }}
                                                </span>
                                                <span class="text-sm font-medium text-gray-800">
                                                    {{ $exp->staff ? $exp->staff->NomePers . ' ' . $exp->staff->CognomePers : '—' }}
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-600 mt-1">{{ $exp->titolo ?? 'Scadenza' }}</p>
                                            @if($exp->note)
                                                <p class="text-xs text-gray-500 mt-1">{{ $exp->note }}</p>
                                            @endif
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm font-mono">{{ \Carbon\Carbon::parse($exp->data_fine)->format('d/m/Y') }}</div>
                                            @if(\Carbon\Carbon::parse($exp->data_fine)->isPast())
                                                <span class="text-xs text-red-500 font-medium">SCADUTA</span>
                                            @elseif(\Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($exp->data_fine)) <= 30)
                                                <span class="text-xs text-yellow-600">In scadenza</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-gray-500 py-8">Nessuna scadenza in questa data</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    @elseif($view === 'table')
    <!-- VISTA TABELLARE - Card con filtri -->
    <div class="bg-white rounded-lg shadow p-4 mb-4 border border-gray-200">
        <!-- RIGA SUPERIORE: Date Range Filter -->
        @livewire('components.date-range-filter', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo
        ], key('date-filter-' . $dateFrom . $dateTo))
        
        <!-- Linea di separazione -->
        <div class="border-t border-gray-200 my-4"></div>
        
        <!-- RIGA INFERIORE: Filtri -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Filtro per tipologia scadenza -->
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">
                    <i class="fas fa-tags mr-1 text-gray-400"></i> Tipo Scadenza
                </label>
                <div class="relative">
                    <i class="fas fa-tag absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                    <select wire:model.live="selectedType" class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                        <option value="">Tutti i tipi</option>
                        @foreach($expirationTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            
            <!-- Filtro per personale (AUTOCOMPLETE) -->
            <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                <label class="block text-xs font-medium text-gray-500 mb-1">
                    <i class="fas fa-user mr-1 text-gray-400"></i> Personale
                </label>
                <div class="relative">
                    <i class="fas fa-user absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                    <input type="text"
                        id="staff_input_table"
                        wire:model.live.debounce.300ms="staffSearch"
                        x-on:focus="open = true"
                        x-on:keydown="open = true"
                        placeholder="Cerca personale..."
                        class="w-full pl-9 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                        autocomplete="off">
                    @if($selectedStaffId)
                        <button type="button"
                            wire:click="clearStaff"
                            x-on:click="document.getElementById('staff_input_table').value = ''"
                            class="absolute right-2 top-2 text-gray-400 hover:text-red-500">
                            <i class="fas fa-times-circle text-sm"></i>
                        </button>
                    @endif
                </div>

                <div x-show="open && @entangle('showStaffDropdown')"
                    class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                    @if($staffResults && $staffResults->count() > 0)
                        @foreach($staffResults as $item)
                            <div
                                x-on:click="
                                    open = false;
                                    document.getElementById('staff_input_table').value = '{{ addslashes($item->NomePers . ' ' . $item->CognomePers) }}';
                                    @this.call('selectStaff', '{{ $item->id_personale }}', '{{ addslashes($item->NomePers . ' ' . $item->CognomePers) }}');
                                    @this.set('showStaffDropdown', false);
                                "
                                class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                                <div class="font-medium text-gray-800">{{ $item->NomePers }} {{ $item->CognomePers }}</div>
                                <div class="text-xs text-gray-500">ID: {{ $item->id_personale }}</div>
                            </div>
                        @endforeach
                    @elseif(strlen($staffSearch) >= 2)
                        <div class="px-3 py-2 text-sm text-gray-500 text-center">Nessun risultato trovato</div>
                    @endif
                </div>
            </div>
            
            <!-- Select Stato Scadenza -->
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">
                    <i class="fas fa-chart-line mr-1 text-gray-400"></i> Stato Scadenza
                </label>
                <div class="relative">
                    <i class="fas fa-flag-checkered absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                    <select wire:model.live="expirationStatus" class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                        <option value="">Tutti</option>
                        <option value="expired">Scadute</option>
                        <option value="expiring">In scadenza</option>
                        <option value="valid">Valide</option>
                    </select>
                </div>
            </div>
            
            <!-- Per pagina -->
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">
                    <i class="fas fa-table-list mr-1 text-gray-400"></i> Per pagina
                </label>
                <div class="relative">
                    <i class="fas fa-list-ol absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                    <select wire:model.live="perPage" class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                        <option value="100">100 per pagina</option>
                        <option value="200">200 per pagina</option>
                        <option value="10000">Tutti</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Active Filters Tags con pulsante resetta (solo icona e solo quando ci sono filtri attivi) -->
        @if($selectedStaffId || $expirationStatus || $selectedType || $dateFrom || $dateTo)
        <div class="mt-4 pt-3 border-t border-gray-200">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs text-gray-500">Filtri attivi:</span>
                    
                    @if($selectedStaffId)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                        <i class="fas fa-user mr-1 text-xs"></i> {{ $selectedStaffName }}
                        <button wire:click="clearStaff" class="ml-1 hover:text-lime-900">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </span>
                    @endif
                    
                    @if($expirationStatus)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                        <i class="fas fa-flag-checkered mr-1 text-xs"></i> 
                        @if($expirationStatus === 'expired') Scadute
                        @elseif($expirationStatus === 'expiring') In scadenza
                        @else Valide
                        @endif
                        <button wire:click="$set('expirationStatus', '')" class="ml-1 hover:text-lime-900">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </span>
                    @endif
                    
                    @if($selectedType)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                        <i class="fas fa-filter mr-1 text-xs"></i> {{ $expirationTypes->firstWhere('id', $selectedType)->label ?? $selectedType }}
                        <button wire:click="$set('selectedType', '')" class="ml-1 hover:text-lime-900">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </span>
                    @endif
                    
                    @if($dateFrom || $dateTo)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                        <i class="fas fa-calendar mr-1 text-xs"></i> {{ $dateFrom ?: '...' }} → {{ $dateTo ?: '...' }}
                        <button wire:click="clearDates" class="ml-1 hover:text-lime-900">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </span>
                    @endif
                </div>
                
                <!-- Pulsante Resetta (solo icona) - appare solo quando ci sono filtri attivi -->
                <button wire:click="resetTableFilters" class="text-red-500 hover:text-red-700 transition-colors" title="Resetta tutti i filtri">
                    <i class="fas fa-trash-alt text-sm"></i>
                </button>
            </div>
        </div>
        @endif
    </div>

    <!-- Tabella Scadenze -->
    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 cursor-pointer hover:bg-gray-100" wire:click="sortBy('data_fine')">
                            <div class="flex items-center gap-1">Data Scadenza @if($sortField === 'data_fine')<i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>@endif</div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 cursor-pointer hover:bg-gray-100" wire:click="sortBy('id_references')">
                            <div class="flex items-center gap-1">Personale @if($sortField === 'id_references')<i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>@endif</div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 cursor-pointer hover:bg-gray-100" wire:click="sortBy('id_settings')">
                            <div class="flex items-center gap-1">Tipologia @if($sortField === 'id_settings')<i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>@endif</div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Titolo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Note</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">Stato</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($paginatedExpirations as $exp)
                    @php
                        $status = $this->getExpirationStatus($exp->data_fine);
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-mono whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($exp->data_fine)->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if($exp->staff)
                                <div class="font-medium text-gray-800">{{ $exp->staff->NomePers }} {{ $exp->staff->CognomePers }}</div>
                                @if($exp->staff->gruppo)
                                    <div class="text-xs text-gray-500">{{ $exp->staff->gruppo->valore }}</div>
                                @endif
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium {{ $this->getTypeColor($exp->id_settings) }}">
                                {{ $exp->setting->valore ?? $this->getTypeLabel($exp->id_settings) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate" title="{{ $exp->titolo }}">
                            {{ $exp->titolo ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate" title="{{ $exp->note }}">
                            {{ $exp->note ?: '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if(\Carbon\Carbon::parse($exp->data_fine)->isPast())
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <i class="fas fa-exclamation-circle mr-1"></i> Scaduta
                                </span>
                            @elseif(\Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($exp->data_fine)) <= 30)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-clock mr-1"></i> In scadenza
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i> Valida
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-calendar-times text-4xl mb-2 text-gray-300"></i>
                            <p>Nessuna scadenza trovata</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginazione e Esportazione (FUORI dalla tabella) -->
    @if($paginatedExpirations->total() > 0)
    <div class="mt-4 flex flex-wrap justify-between items-center gap-3">
        <div class="text-sm text-gray-500">
            Mostrati {{ $paginatedExpirations->firstItem() ?? 0 }} - {{ $paginatedExpirations->lastItem() ?? 0 }} di {{ $paginatedExpirations->total() }} risultati
        </div>
        <div class="flex gap-2">
            <a href="{{ $this->getExportPdfUrl() }}" target="_blank"
                class="inline-flex items-center bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-lg shadow-md flex items-center gap-2 font-medium transition-colors">
                <i class="fas fa-file-pdf mr-2"></i> Esporta PDF
            </a>
            <a href="{{ $this->getExportExcelUrl() }}" target="_blank"
                class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-lg shadow-md flex items-center gap-2 font-medium transition-colors">
                <i class="fas fa-file-excel mr-2"></i> Esporta Excel
            </a>
        </div>
    </div>
    
    @if($paginatedExpirations->hasPages() && $perPage != 10000)
    <div class="mt-4">
        {{ $paginatedExpirations->links() }}
    </div>
    @endif
    @endif
    
    @endif
</div>