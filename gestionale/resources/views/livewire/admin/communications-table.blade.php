<div>
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">
            <i class="fa-solid fa-envelope mr-3 text-lime-600"></i>
            Comunicazioni
        </h1>
    </div>

    <!-- Card Filtri -->
    <div class="bg-white rounded-lg shadow p-4 mb-4 border border-gray-200">
        <!-- Filtri Data con componente DateRangeFilter -->
        <div>
            @livewire('components.date-range-filter', [
                'dateFrom' => $dateFrom, 
                'dateTo' => $dateTo
            ], key('date-filter-' . $dateFrom . $dateTo))
        </div>

        <div class="border-t border-gray-200 my-4"></div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Ricerca fulltext -->
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium text-gray-500 mb-1">Cerca</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                    <input type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cerca nel testo, contatto, mittente o cliente/fornitore..."
                        class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                </div>
            </div>

            <!-- Autocomplete Cliente/Fornitore -->
            <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                <label class="block text-xs font-medium text-gray-500 mb-1">Cliente / Fornitore</label>
                <div class="relative">
                    <i class="fas fa-building absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                    <input type="text"
                        id="comm_entity_input"
                        wire:model.live.debounce.300ms="entitySearch"
                        x-on:focus="open = true"
                        x-on:keydown="open = true"
                        placeholder="Cerca cliente o fornitore..."
                        class="w-full pl-9 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                        autocomplete="off">
                    @if($selectedEntityId)
                        <button type="button"
                            wire:click="clearEntity"
                            x-on:click="document.getElementById('comm_entity_input').value = ''"
                            class="absolute right-2 top-2 text-gray-400 hover:text-red-500">
                            <i class="fas fa-times-circle text-sm"></i>
                        </button>
                    @endif
                </div>

                <div x-show="open && @entangle('showEntityDropdown')"
                    class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                    @if($entityResults && $entityResults->count() > 0)
                        @foreach($entityResults as $item)
                            @php
                                $itemName = $item->ragione_sociale ?: trim($item->nome . ' ' . $item->cognome);
                            @endphp
                            <div
                                x-on:click="
                                    open = false;
                                    document.getElementById('comm_entity_input').value = '{{ addslashes($itemName) }}';
                                    @this.set('entitySearch', '{{ addslashes($itemName) }}');
                                    @this.call('selectEntity', {{ $item->id }}, '{{ addslashes($itemName) }}');
                                "
                                class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                                <div class="font-medium text-gray-800">{{ $itemName }}</div>
                                @if($item->partita_iva)
                                    <div class="text-xs text-gray-500">P.IVA: {{ $item->partita_iva }}</div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="px-3 py-2 text-sm text-gray-500 text-center">Nessun risultato trovato</div>
                    @endif
                </div>
            </div>

            <!-- Per pagina -->
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Visualizza</label>
                <select wire:model.live="perPage" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                    <option value="100">100</option>
                    <option value="200">200</option>
                    <option value="10000">Tutto</option>
                </select>
            </div>
        </div>

        <!-- Active Filters Tags -->
        @if($search || $selectedEntityId || $dateFrom || $dateTo)
        <div class="mt-4 pt-3 border-t border-gray-200">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs text-gray-500">Filtri attivi:</span>

                @if($search)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    <i class="fas fa-search mr-1 text-xs"></i> "{{ $search }}"
                    <button wire:click="clearSearch" class="ml-1 hover:text-lime-900">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </span>
                @endif

                @if($selectedEntityId && $selectedEntityName)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    <i class="fas fa-building mr-1 text-xs"></i> {{ $selectedEntityName }}
                    <button wire:click="clearEntity" class="ml-1 hover:text-lime-900">
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

                <span class="text-xs text-gray-400 ml-2">
                    <button wire:click="resetFilters" class="hover:text-red-500">
                        <i class="fas fa-trash-alt mr-1 text-xs"></i> Rimuovi tutti
                    </button>
                </span>
            </div>
        </div>
        @endif
    </div>

    <!-- Tabella -->
    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="sortBy('data')">
                            <div class="flex items-center gap-1">
                                Data
                                @if($sortField === 'data')<i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>@else<i class="fas fa-sort text-gray-400"></i>@endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente / Fornitore</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Testo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contatto</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mittente</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Allegato</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inserito da</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($communications as $comm)
                    <tr wire:key="comm-{{ $comm->id }}" class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                            {{ $comm->data->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if($comm->entity)
                                <a href="{{ route('admin.entities.communications.index', $comm->entity->id_cliente) }}" class="text-gray-800 hover:text-lime-700 font-medium">
                                    {{ $comm->entity->full_name }}
                                </a>
                            @else
                                <span class="text-gray-400 italic">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 max-w-sm truncate" title="{{ $comm->testo }}">
                            {{ Str::limit($comm->testo, 80) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            @if($comm->contatto)
                                @if(filter_var($comm->contatto, FILTER_VALIDATE_EMAIL))
                                    <a href="mailto:{{ $comm->contatto }}" class="text-blue-600 hover:text-blue-800">{{ $comm->contatto }}</a>
                                @else
                                    {{ $comm->contatto }}
                                @endif
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ $comm->mittente ?? 'Amministrazione' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($comm->allegato)
                                <a href="{{ route('admin.entities.communications.download', ['entityId' => $comm->id_entities, 'id' => $comm->id]) }}"
                                   class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800"
                                   title="{{ $comm->allegato_nome }}">
                                    <i class="{{ $comm->allegato_icon }} {{ $comm->allegato_color }}"></i>
                                </a>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">
                            <div class="flex flex-col">
                                <span>{{ $comm->createdBy->name ?? 'Sistema' }}</span>
                                <span class="text-[10px] text-gray-400">{{ $comm->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($comm->entity)
                            <a href="{{ route('admin.entities.communications.index', $comm->entity->id_cliente) }}"
                               class="text-lime-600 hover:text-lime-800"
                               title="Apri comunicazioni di {{ $comm->entity->full_name }}">
                                <i class="fa-regular fa-eye"></i>
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="text-gray-500">
                                <i class="fa-solid fa-envelope text-gray-300 text-4xl block mb-2"></i>
                                <p class="text-sm">Nessuna comunicazione trovata</p>
                                @if($search || $selectedEntityId || $dateFrom || $dateTo)
                                <button wire:click="resetFilters" class="mt-2 text-sm text-blue-600 hover:text-blue-800">
                                    Resetta filtri
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginazione -->
    @if((int) $perPage < 10000 && $communications->hasPages())
    <div class="mt-6">
        <div class="text-sm text-gray-500 mb-2">
            Mostrando {{ $communications->firstItem() ?? 0 }} - {{ $communications->lastItem() ?? 0 }} di {{ $communications->total() }} risultati
        </div>
        <div class="flex justify-center">
            {{ $communications->links() }}
        </div>
    </div>
    @elseif((int) $perPage >= 10000 && $communications->count() > 0)
    <div class="mt-6">
        <div class="text-sm text-gray-500 mb-2 text-center bg-green-50 p-2 rounded-lg">
            <i class="fas fa-database text-green-500 mr-1"></i>
            Mostrati tutti i <strong>{{ $communications->count() }}</strong> risultati
        </div>
    </div>
    @endif

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('clearEntityInput', () => {
                const input = document.getElementById('comm_entity_input');
                if (input) input.value = '';
            });

            Livewire.on('resetDates', () => {
                const dateInputs = document.querySelectorAll('input[type="date"]');
                dateInputs.forEach(input => {
                    if (input.id.includes('date-from') || input.id.includes('date-to')) {
                        input.value = '';
                        input.dispatchEvent(new Event('change'));
                    }
                });
            });
        });
    </script>

    <style>
        nav[role="navigation"] div.flex-1 { display: none !important; }
        nav[role="navigation"] .relative.z-0 { justify-content: center !important; display: flex !important; }
        nav[role="navigation"] span[aria-current="page"] span,
        nav[role="navigation"] .relative.inline-flex.items-center {
            background-color: white !important; border-color: #e5e7eb !important; color: #374151 !important;
        }
        nav[role="navigation"] span[aria-current="page"] span {
            background-color: #84cc16 !important; border-color: #84cc16 !important; color: white !important;
        }
        nav[role="navigation"] .relative.inline-flex.items-center:hover {
            background-color: #f9fafb !important; border-color: #d1d5db !important;
        }
        nav[role="navigation"] p.text-sm { display: none !important; }
        nav[role="navigation"] > div:first-child { justify-content: center !important; }
        nav[role="navigation"] > div:first-child > div:first-child { display: none !important; }
    </style>
</div>