<div>
    <!-- Header -->
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Gestione Scadenze</h1>
                <p class="text-gray-500 mt-1">
                    <i class="fas fa-calendar-alt mr-1"></i> Gestione completa di tutte le scadenze
                </p>
            </div>
            <div class="flex gap-3">
                <div class="relative group">
                    <button wire:click="openCreateModal" 
                            class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                        <i class="fas fa-plus"></i>
                    </button>
                    <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                        Nuova scadenza [ALT + N]
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtri e Ricerca -->
    <div class="bg-white rounded-lg shadow mb-6 p-4 border border-gray-200" wire:key="filters-{{ $search }}-{{ $tipologiaFilter }}-{{ $statusFilter }}-{{ $typeFilter }}">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="relative md:col-span-2">
                <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       placeholder="Cerca per titolo, qualifica o note..." 
                       class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent">
            </div>
            
            <select wire:model.live="tipologiaFilter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                <option value="">Tutte le tipologie</option>
                @foreach($tipologie as $tipologia)
                    <option value="{{ $tipologia->id }}">{{ $tipologia->valore }}</option>
                @endforeach
            </select>
            
            <select wire:model.live="typeFilter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                <option value="">Tutti i tipi</option>
                <option value="staff">Personale</option>
                <option value="vehicle">Mezzi</option>
            </select>
            
            <select wire:model.live="statusFilter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                <option value="">Tutti gli stati</option>
                <option value="active">Attive</option>
                <option value="expiring">In scadenza</option>
                <option value="expired">Scadute</option>
            </select>
        </div>
        
        <div class="flex justify-between items-center mt-4">
            @if($search || $tipologiaFilter || $statusFilter || $typeFilter)
            <button wire:click="resetFilters" class="text-sm text-gray-600 hover:text-gray-900 transition-colors">
                <i class="fas fa-sync-alt mr-1"></i>
                Resetta filtri
            </button>
            @endif
        </div>
        
        @if($search || $tipologiaFilter || $statusFilter || $typeFilter)
        <div class="mt-3 flex flex-wrap items-center gap-2">
            <span class="text-sm text-gray-500">Filtri attivi:</span>
            @if($search)
            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-blue-100 text-blue-800">
                Ricerca: "{{ $search }}"
                <button wire:click="$set('search', '')" class="ml-1 hover:text-blue-900">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </span>
            @endif
            @if($tipologiaFilter)
            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-blue-100 text-blue-800">
                Tipologia: {{ $tipologie->firstWhere('id', $tipologiaFilter)->valore ?? $tipologiaFilter }}
                <button wire:click="$set('tipologiaFilter', '')" class="ml-1 hover:text-blue-900">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </span>
            @endif
            @if($typeFilter)
            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-purple-100 text-purple-800">
                Tipo: {{ $typeFilter === 'staff' ? 'Personale' : 'Mezzi' }}
                <button wire:click="$set('typeFilter', '')" class="ml-1 hover:text-purple-900">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </span>
            @endif
            @if($statusFilter)
            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-yellow-100 text-yellow-800">
                Stato: {{ $statusFilter === 'active' ? 'Attive' : ($statusFilter === 'expiring' ? 'In scadenza' : 'Scadute') }}
                <button wire:click="$set('statusFilter', '')" class="ml-1 hover:text-yellow-900">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </span>
            @endif
        </div>
        @endif
    </div>

    <!-- Tabella Scadenze -->
    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('titolo')">
                            <div class="flex items-center space-x-1">
                                <span>Titolo</span>
                                @if($sortField === 'titolo')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipologia</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Associato a</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('data_inizio')">
                            <div class="flex items-center space-x-1">
                                <span>Data Inizio</span>
                                @if($sortField === 'data_inizio')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('data_fine')">
                            <div class="flex items-center space-x-1">
                                <span>Data Scadenza</span>
                                @if($sortField === 'data_fine')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stato</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expirations as $expiration)
                    @php
                        $linkedEntityName = $expiration->getLinkedEntityNameAttribute();
                        $linkedEntityType = $expiration->getLinkedEntityTypeAttribute();
                        $iconClass = $linkedEntityType === 'Personale' ? 'fa-user text-purple-500' : 'fa-truck text-blue-500';
                    @endphp
                    <tr wire:key="expiration-{{ $expiration->id }}" class="hover:bg-gray-50 transition-colors duration-150 border-t border-gray-200">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $expiration->titolo }}
                            </div>
                            @if($expiration->subtitolo)
                            <div class="text-xs text-gray-500">
                                <i class="fas fa-briefcase mr-1"></i> {{ $expiration->subtitolo }}
                            </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ $expiration->tipologiaName }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($linkedEntityName && $linkedEntityName != '-')
                            <div class="flex items-center space-x-2">
                                <i class="fas {{ $iconClass }}"></i>
                                <div class="flex flex-col">
                                    <span class="text-sm text-gray-900">{{ $linkedEntityName }}</span>
                                    <span class="text-xs text-gray-500">{{ $linkedEntityType }}</span>
                                </div>
                            </div>
                            @else
                            <span class="text-sm text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $expiration->data_inizio ? $expiration->data_inizio->format('d/m/Y') : '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $expiration->data_fine ? $expiration->data_fine->format('d/m/Y') : '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {!! $expiration->statusBadge !!}
                        </td>
                        <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                            <div class="flex space-x-3">
                                <button wire:click="viewExpiration({{ $expiration->id }})" 
                                        wire:key="view-{{ $expiration->id }}"
                                        class="text-blue-600 hover:text-blue-900 transition-colors"
                                        title="Visualizza">
                                    <i class="fa-regular fa-eye text-blue-600 hover:text-blue-900"></i>
                                </button>
                                
                                @if(auth()->guard('admin')->user()->hasPermission('edit_expiration'))
                                    @if($expiration->table_references === 'staff')
                                    <a href="{{ route('admin.expiration-staff.edit', ['id' => $expiration->id, 'staffId' => $expiration->id_references]) }}"
                                       wire:key="edit-{{ $expiration->id }}"
                                       class="text-yellow-600 hover:text-yellow-900 transition-colors"
                                       title="Modifica">
                                        <i class="fa-solid fa-pen-to-square text-yellow-600 hover:text-yellow-900"></i>
                                    </a>
                                    @elseif($expiration->table_references === 'vehicles')
                                    <a href="{{ route('admin.expiration-vehicle.edit', ['id' => $expiration->id, 'vehicleId' => $expiration->vehicles->first()->id ?? 0]) }}"
                                       wire:key="edit-{{ $expiration->id }}"
                                       class="text-yellow-600 hover:text-yellow-900 transition-colors"
                                       title="Modifica">
                                        <i class="fa-solid fa-pen-to-square text-yellow-600 hover:text-yellow-900"></i>
                                    </a>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="text-gray-500">
                                <i class="fas fa-calendar-times text-gray-400 text-5xl"></i>
                                <p class="mt-2 text-sm">Nessuna scadenza trovata</p>
                                @if($search || $tipologiaFilter || $statusFilter || $typeFilter)
                                <button wire:click="resetFilters" class="mt-2 text-sm text-lime-600 hover:text-lime-800">
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
    @if($expirations->hasPages())
    <div class="mt-6">
        <div class="text-sm text-gray-500 mb-2">
            Mostrando {{ $expirations->firstItem() ?? 0 }} - {{ $expirations->lastItem() ?? 0 }} di {{ $expirations->total() }} risultati
        </div>
        <div class="flex justify-center">
            {{ $expirations->links() }}
        </div>
    </div>
    @endif

    <style>
        nav[role="navigation"] div.flex-1 {
            display: none !important;
        }
        nav[role="navigation"] .relative.z-0 {
            justify-content: center !important;
            display: flex !important;
        }
        nav[role="navigation"] span[aria-current="page"] span,
        nav[role="navigation"] .relative.inline-flex.items-center {
            background-color: white !important;
            border-color: #e5e7eb !important;
            color: #374151 !important;
        }
        nav[role="navigation"] span[aria-current="page"] span {
            background-color: #84cc16 !important;
            border-color: #84cc16 !important;
            color: white !important;
        }
        nav[role="navigation"] .relative.inline-flex.items-center:hover {
            background-color: #f9fafb !important;
            border-color: #d1d5db !important;
        }
        nav[role="navigation"] p.text-sm {
            display: none !important;
        }
        nav[role="navigation"] > div:first-child {
            justify-content: center !important;
        }
        nav[role="navigation"] > div:first-child > div:first-child {
            display: none !important;
        }
    </style>

    <!-- MODAL CREAZIONE NUOVA SCADENZA -->
    @if($showCreateModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" 
        x-data="{ open: true }" 
        x-show="open"
        x-init="$watch('open', value => { if (!value) $wire.closeCreateModal() })"
        @keydown.escape.window="open = false">
        
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="open = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-center mb-4 border-b pb-3">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-plus-circle mr-2 text-lime-600"></i>
                            Nuova Scadenza
                        </h2>
                        <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-2xl"></i>
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <!-- RIGA 1: Tipologia + Ownership -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Tipologia Scadenza <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="createTipologiaId" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                    <option value="">Seleziona tipologia...</option>
                                    @foreach($tipologie as $tipologia)
                                        <option value="{{ $tipologia->id }}">{{ $tipologia->valore }}</option>
                                    @endforeach
                                </select>
                                @error('createTipologiaId') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ownership / Azienda</label>
                                <select wire:model="createOwnershipId" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                    <option value="">Seleziona ownership...</option>
                                    @foreach($ownerships as $ownership)
                                        <option value="{{ $ownership->id_proprieta }}">{{ $ownership->RagSocialePr }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        <!-- RIGA 2: Tipo di entità (Staff o Veicolo) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Tipo di entità <span class="text-red-500">*</span>
                            </label>
                            <div class="flex space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" wire:model="createEntityType" value="staff" class="form-radio text-lime-600">
                                    <span class="ml-2">Personale</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" wire:model="createEntityType" value="vehicle" class="form-radio text-lime-600">
                                    <span class="ml-2">Mezzo</span>
                                </label>
                            </div>
                            @error('createEntityType') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        
                        <!-- RIGA 3: Seleziona Staff -->
                        @if($createEntityType === 'staff')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Seleziona Personale <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="text" 
                                    wire:model.live.debounce.300ms="createStaffSearch" 
                                    placeholder="Cerca per nome, cognome o codice fiscale..."
                                    class="w-full px-3 py-2 pl-9 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                <i class="fas fa-search absolute left-2.5 top-2.5 text-gray-400"></i>
                            </div>
                            
                            @if(count($createStaffResults) > 0)
                            <div class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto" style="width: calc(100% - 3rem);">
                                @foreach($createStaffResults as $result)
                                <div class="px-4 py-2 hover:bg-lime-50 cursor-pointer border-b border-gray-100 last:border-0"
                                    wire:click="selectStaff({{ $result->id_personale }}, '{{ addslashes($result->full_name) }}')">
                                    <div class="font-medium text-gray-900">{{ $result->full_name }}</div>
                                    <div class="text-xs text-gray-500">Cod. Fiscale: {{ $result->CodFiscPers ?: '-' }}</div>
                                </div>
                                @endforeach
                            </div>
                            @endif
                            
                            @if($createStaffName)
                            <div class="mt-2 p-2 bg-green-50 rounded-md border border-green-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center text-sm text-green-700">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        <span class="font-medium">Personale selezionato:</span>
                                        <span class="ml-1">{{ $createStaffName }}</span>
                                    </div>
                                    <button type="button" wire:click="clearStaff" class="text-green-600 hover:text-green-800">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            @endif
                        </div>
                        @endif
                        
                        <!-- RIGA 4: Seleziona Veicoli -->
                        @if($createEntityType === 'vehicle')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Seleziona Mezzo/i <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="text" 
                                    wire:model.live.debounce.300ms="createVehicleSearch" 
                                    placeholder="Cerca per targa, marca o modello..."
                                    class="w-full px-3 py-2 pl-9 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                <i class="fas fa-search absolute left-2.5 top-2.5 text-gray-400"></i>
                            </div>
                            
                            @if(count($createVehicleResults) > 0)
                            <div class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto" style="width: calc(100% - 3rem);">
                                @foreach($createVehicleResults as $result)
                                <div class="px-4 py-2 hover:bg-lime-50 cursor-pointer border-b border-gray-100 last:border-0"
                                    wire:click="selectVehicle({{ $result->id }}, '{{ addslashes($result->full_name ?? $result->targa) }}')">
                                    <div class="font-medium text-gray-900">{{ $result->targa }}</div>
                                    <div class="text-xs text-gray-500">{{ $result->marca }} {{ $result->modello }}</div>
                                </div>
                                @endforeach
                            </div>
                            @endif
                            
                            @if(count($createVehicleNames) > 0)
                            <div class="mt-2 space-y-1">
                                @foreach($createVehicleNames as $index => $name)
                                <div class="p-2 bg-green-50 rounded-md border border-green-200">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center text-sm text-green-700">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            <span>{{ $name }}</span>
                                        </div>
                                        <button type="button" wire:click="removeVehicle({{ $index }})" class="text-green-600 hover:text-green-800">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @endif
                        
                        <!-- RIGA 5: Titolo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Titolo <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                wire:model="createTitolo" 
                                placeholder="es. Visita medica, Assicurazione, Revisione..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                            @error('createTitolo') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        
                        <!-- RIGA 6: Date -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Data Inizio <span class="text-red-500">*</span>
                                </label>
                                <input type="date" wire:model="createDataInizio" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                @error('createDataInizio') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data Scadenza</label>
                                <input type="date" wire:model="createDataFine" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                <p class="text-xs text-gray-400 mt-1">Opzionale: lascia vuoto se non ha scadenza</p>
                            </div>
                        </div>
                        
                        <!-- RIGA 7: Fornitore + Qualifica -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fornitore / Cliente</label>
                                <div class="relative">
                                    <input type="text" 
                                        wire:model.live.debounce.300ms="createEntitySearch" 
                                        placeholder="Cerca cliente o fornitore..."
                                        class="w-full px-3 py-2 pl-9 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                    <i class="fas fa-search absolute left-2.5 top-2.5 text-gray-400"></i>
                                </div>
                                
                                @if(count($createEntityResults) > 0)
                                <div class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto" style="width: calc(100% - 3rem);">
                                    @foreach($createEntityResults as $result)
                                    <div class="px-4 py-2 hover:bg-lime-50 cursor-pointer border-b border-gray-100 last:border-0"
                                        wire:click="selectEntityField({{ $result->id_cliente }}, '{{ addslashes($result->ragione_sociale ?: $result->nome . ' ' . $result->cognome) }}')">
                                        <div class="font-medium text-gray-900">{{ $result->ragione_sociale ?: $result->nome . ' ' . $result->cognome }}</div>
                                        <div class="text-xs text-gray-500">
                                            @if($result->partita_iva) P.IVA: {{ $result->partita_iva }} @endif
                                            Tipo: {{ $result->entity_type === 'fornitore' ? 'Fornitore' : 'Cliente' }}
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                                
                                @if($createEntityNome)
                                <div class="mt-2 p-2 bg-green-50 rounded-md border border-green-200">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center text-sm text-green-700">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            <span class="font-medium">Associato a:</span>
                                            <span class="ml-1">{{ $createEntityNome }}</span>
                                        </div>
                                        <button type="button" wire:click="clearEntityField" class="text-green-600 hover:text-green-800">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                @endif
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Qualifica / Sottotitolo</label>
                                <input type="text" 
                                    wire:model="createQualifica" 
                                    placeholder="es. Operaio agricolo, RCA, Tagliando..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                            </div>
                        </div>
                        
                        <!-- RIGA 8: Note -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                            <textarea wire:model="createNote" rows="3" placeholder="Note aggiuntive..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-col sm:flex-row sm:justify-end gap-3">
                    <button @click="open = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md">
                        <i class="fas fa-times mr-2"></i> Annulla
                    </button>
                    <button wire:click="saveExpiration" class="px-4 py-2 bg-lime-600 hover:bg-lime-700 text-white rounded-md">
                        <i class="fas fa-save mr-2"></i> Crea Scadenza
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
    
    <!-- Modal Visualizzazione Dettaglio Scadenza -->
    @if($showViewModal && $viewingExpiration)
    <div class="fixed inset-0 z-50 overflow-y-auto" 
         x-data="{ open: true }" 
         x-show="open"
         x-init="$watch('open', value => { if (!value) $wire.closeViewModal() })"
         @keydown.escape.window="open = false">
        
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="open = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-center mb-4 border-b pb-3">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-calendar-alt mr-2 text-lime-600"></i> Dettaglio Scadenza
                        </h2>
                        <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-2xl"></i>
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        @php
                            $linkedEntity = $viewingExpiration->getLinkedEntityAttribute();
                            $linkedEntityName = $viewingExpiration->getLinkedEntityNameAttribute();
                            $linkedEntityType = $viewingExpiration->getLinkedEntityTypeAttribute();
                            $iconClass = $linkedEntityType === 'Personale' ? 'fa-user text-purple-500' : 'fa-truck text-blue-500';
                        @endphp
                        
                        @if($linkedEntity && $linkedEntityName != '-')
                        <div class="{{ $linkedEntityType === 'Personale' ? 'bg-purple-50' : 'bg-blue-50' }} p-3 rounded-lg">
                            <p class="text-sm {{ $linkedEntityType === 'Personale' ? 'text-purple-700' : 'text-blue-700' }}">
                                <i class="fas {{ $iconClass }} mr-1"></i> 
                                {{ $linkedEntityType }} Associato: <strong>{{ $linkedEntityName }}</strong>
                            </p>
                        </div>
                        @endif
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Titolo</label>
                                <p class="text-gray-900 font-medium">{{ $viewingExpiration->titolo }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Tipologia</label>
                                <p><span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">{{ $viewingExpiration->tipologiaName }}</span></p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Data Inizio</label>
                                <p class="text-gray-900">{{ $viewingExpiration->data_inizio ? $viewingExpiration->data_inizio->format('d/m/Y') : '-' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Data Scadenza</label>
                                <p class="text-gray-900">{{ $viewingExpiration->data_fine ? $viewingExpiration->data_fine->format('d/m/Y') : '-' }}</p>
                            </div>
                            @if($viewingExpiration->subtitolo)
                            <div class="col-span-2">
                                <label class="text-sm font-medium text-gray-500">Qualifica</label>
                                <p class="text-gray-900">{{ $viewingExpiration->subtitolo }}</p>
                            </div>
                            @endif
                            <div>
                                <label class="text-sm font-medium text-gray-500">Stato</label>
                                <p>{!! $viewingExpiration->statusBadge !!}</p>
                            </div>
                        </div>
                        
                        @if($viewingExpiration->note)
                        <div>
                            <label class="text-sm font-medium text-gray-500">Note</label>
                            <p class="text-gray-700 mt-1">{{ $viewingExpiration->note }}</p>
                        </div>
                        @endif
                        
                        <div class="border-t pt-4 mt-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Tracciamento</h4>
                            <div class="text-xs text-gray-500 space-y-1">
                                @if($viewingExpiration->created_at && $viewingExpiration->createdBy)
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-plus-circle text-green-500"></i>
                                    <span>Inserito da <strong>{{ $viewingExpiration->createdBy->name }}</strong> il {{ $viewingExpiration->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                                @endif
                                @if($viewingExpiration->updated_at && $viewingExpiration->updatedBy && $viewingExpiration->created_at != $viewingExpiration->updated_at)
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-edit text-blue-500"></i>
                                    <span>Modificato da <strong>{{ $viewingExpiration->updatedBy->name }}</strong> il {{ $viewingExpiration->updated_at->format('d/m/Y H:i') }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-col sm:flex-row sm:justify-end gap-3">
                    <button @click="open = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md">
                        <i class="fas fa-times mr-2"></i> Chiudi
                    </button>
                    @if(auth()->guard('admin')->user()->hasPermission('edit_expiration'))
                        @if($viewingExpiration->table_references === 'staff')
                        <a href="{{ route('admin.expiration-staff.edit', ['id' => $viewingExpiration->id, 'staffId' => $viewingExpiration->id_references]) }}"
                           class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-md">
                            <i class="fas fa-edit mr-2"></i> Modifica
                        </a>
                        @elseif($viewingExpiration->table_references === 'vehicles')
                        <a href="{{ route('admin.expiration-vehicle.edit', ['id' => $viewingExpiration->id, 'vehicleId' => $viewingExpiration->vehicles->first()->id ?? 0]) }}"
                           class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-md">
                            <i class="fas fa-edit mr-2"></i> Modifica
                        </a>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        document.addEventListener('keydown', function (e) {
            if (e.altKey && (e.key === 'n' || e.key === 'N')) {
                const tag = document.activeElement.tagName;
                if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') {
                    return;
                }
                e.preventDefault();
                @this.call('openCreateModal');
            }
        });
    });
</script>