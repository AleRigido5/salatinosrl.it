<div>
    <!-- Header -->
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-truck mr-2 text-lime-600"></i> Gestione Mezzi
                </h1>
            </div>
            <div class="flex gap-3">
                @if(auth()->guard('admin')->user()->hasPermission('create_vehicles'))
                <button wire:click="openCreateModal" 
                        class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-5 py-2.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                    <i class="fas fa-plus"></i>
                </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Filtri e Ricerca -->
    <div class="bg-white rounded-lg shadow mb-6 p-4 border border-gray-200" wire:key="filters-{{ $search }}-{{ $tipoFilter }}-{{ $statoFilter }}-{{ $ownershipFilter }}-{{ $polizzaSearch }}-{{ $perPage }}">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <!-- Proprietà (col 2) -->
            <div class="md:col-span-2">
                <select wire:model.live="ownershipFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                    <option value="">Tutte le proprietà</option>
                    @foreach($proprietaList as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            
            <!-- Tipo (col 2) -->
            <div class="md:col-span-2">
                <select wire:model.live="tipoFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                    <option value="">Tutti i tipi</option>
                    @foreach($tipiList as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            
            <!-- Ricerca (col 3) -->
            <div class="relative md:col-span-3">
                <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       placeholder="Cerca per targa, marca, modello..." 
                       class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent">
            </div>

            <!-- Ricerca per numero di polizza (col 2) -->
            <div class="relative md:col-span-2">
                <i class="fas fa-file-contract absolute left-3 top-2.5 text-gray-400"></i>
                <input type="text"
                       wire:model.live.debounce.300ms="polizzaSearch"
                       placeholder="N. polizza..."
                       class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent">
            </div>
            
            <!-- Stato (col 2) -->
            <div class="md:col-span-2">
                <select wire:model.live="statoFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                    <option value="">Tutti gli stati</option>
                    @foreach($statiList as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Record per pagina (col 1) -->
            <div class="md:col-span-1">
                <select wire:model.live="perPage" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                    @foreach($perPageOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        
        <div class="flex justify-between items-center mt-4">
            @if($search || $tipoFilter || $statoFilter || $ownershipFilter || $polizzaSearch)
            <button wire:click="resetFilters" class="text-sm text-gray-600 hover:text-gray-900 transition-colors">
                <i class="fas fa-sync-alt mr-1"></i>
                Resetta filtri
            </button>
            @endif

            @if($polizzaSearch)
            <span class="text-sm text-gray-600">
                <i class="fas fa-info-circle mr-1 text-lime-600"></i>
                {{ $vehicles->total() }} {{ $vehicles->total() == 1 ? 'mezzo trovato' : 'mezzi trovati' }} per la polizza "<strong>{{ $polizzaSearch }}</strong>"
            </span>
            @endif
        </div>
    </div>

    <!-- Tabella Mezzi -->
    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('id')">
                            <div class="flex items-center space-x-1">
                                <span>ID</span>
                                @if($sortField === 'id')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('id_ownership')">
                            <div class="flex items-center space-x-1">
                                <span>Proprietà</span>
                                @if($sortField === 'id_ownership')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('tipologia')">
                            <div class="flex items-center space-x-1">
                                <span>Tipo</span>
                                @if($sortField === 'tipologia')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('marca')">
                            <div class="flex items-center space-x-1">
                                <span>Marca / Modello</span>
                                @if($sortField === 'marca')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('targa')">
                            <div class="flex items-center space-x-1">
                                <span>Targa</span>
                                @if($sortField === 'targa')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('immatricolazione')">
                            <div class="flex items-center space-x-1">
                                <span>Immatricolazione</span>
                                @if($sortField === 'immatricolazione')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scadenze</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vehicles as $vehicle)
                    @php
                        // Ottieni l'ultima scadenza (quella con data_fine più grande)
                        $nextExpiration = null;
                        $expirationColor = '';
                        $expirationDate = '';
                        $expirationTitle = '';

                        if($vehicle->expirations && $vehicle->expirations->count() > 0) {
                            $latestExpiration = null;
                            $latestDate = null;

                            foreach($vehicle->expirations as $exp) {
                                if($exp->data_fine) {
                                    if($latestExpiration === null || $exp->data_fine > $latestDate) {
                                        $latestExpiration = $exp;
                                        $latestDate = $exp->data_fine;
                                    }
                                }
                            }

                            if($latestExpiration) {
                                $nextExpiration = $latestExpiration;
                                $expirationTitle = $nextExpiration->titolo;
                                $expirationDate = $nextExpiration->data_fine->format('d/m/Y');
                                $today = now();

                                if($nextExpiration->status === 'renewed') {
                                    // Scadenza rinnovata: sempre verde, indipendentemente dalla data
                                    $expirationColor = 'text-green-600 bg-green-50';
                                } elseif($nextExpiration->data_fine < $today) {
                                    $expirationColor = 'text-red-600 bg-red-50';
                                } elseif($nextExpiration->data_fine <= $today->copy()->addDays(30)) {
                                    $expirationColor = 'text-yellow-600 bg-yellow-50';
                                } else {
                                    $expirationColor = 'text-green-600 bg-green-50';
                                }
                            }
                        }
                    @endphp
                    <tr wire:key="vehicle-{{ $vehicle->id }}" class="hover:bg-gray-50 transition-colors duration-150 border-t border-gray-200">
                        <!-- ID -->
                        <td class="px-4 py-4 whitespace-nowrap">
                            <span class="text-sm font-sans font-medium text-gray-400">
                                {{ $vehicle->id }}
                            </span>
                        </td>

                        <!-- Proprietà -->
                        <td class="px-4 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900">
                                {{ $vehicle->proprieta_nome ?? '-' }}
                            </span>
                        </td>
                        
                        <!-- Tipo -->
                        <td class="px-4 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                {{ $vehicle->tipologia ?: '-' }}
                            </span>
                        </td>
                        
                        <!-- Marca / Modello -->
                        <td class="px-4 py-4">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $vehicle->marca ?: '-' }}
                            </div>
                            @if($vehicle->modello)
                            <div class="text-xs text-gray-500">
                                {{ $vehicle->modello }}
                            </div>
                            @endif
                        </td>
                        
                        <!-- Targa -->
                        <td class="px-4 py-4 whitespace-nowrap">
                            <div class="text-sm font-mono font-medium text-gray-900">
                                {{ $vehicle->targa ?: '-' }}
                            </div>
                        </td>
                        
                        <!-- Immatricolazione -->
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                            @php
                                $anno = '-';
                                if($vehicle->immatricolazione && $vehicle->immatricolazione != '0000-00-00') {
                                    try {
                                        $date = date_create($vehicle->immatricolazione);
                                        if($date && $date->format('Y') > 1900 && $date->format('Y') <= date('Y') + 1) {
                                            $anno = $date->format('d/m/Y');
                                        }
                                    } catch(Exception $e) {
                                        $anno = '-';
                                    }
                                }
                            @endphp
                            {{ $anno }}
                        </td>
                        
                        <!-- Scadenze -->
                        <td class="px-4 py-4">
                            @if($nextExpiration)
                            <div class="flex flex-col space-y-1">
                                <div class="text-xs text-gray-500">
                                    <span class="font-medium">{{ $expirationTitle }}</span>
                                </div>
                                <div class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium {{ $expirationColor }}">
                                    <i class="fas fa-calendar-alt mr-1"></i>
                                    {{ $expirationDate }}
                                    @if($nextExpiration->status === 'renewed')
                                        <i class="fas fa-check-circle ml-1" title="Rinnovata"></i>
                                    @endif
                                </div>
                                @if($nextExpiration->codice)
                                <div class="text-xs text-gray-400">
                                    Pol. {{ $nextExpiration->codice }}
                                </div>
                                @endif
                            </div>
                            @else
                            <span class="text-sm text-gray-400 italic">Nessuna scadenza</span>
                            @endif
                        </td>
                        
                        <!-- Azioni -->
                        <td class="px-4 py-4 text-sm font-medium whitespace-nowrap">
                            <div class="flex space-x-3">
                                @if(auth()->guard('admin')->user()->hasPermission('view_vehicles'))
                                <button wire:click="viewVehicle({{ $vehicle->id }})" 
                                        wire:key="view-{{ $vehicle->id }}"
                                        class="text-blue-600 hover:text-blue-900 transition-colors text-base"
                                        title="Visualizza">
                                    <i class="fa-regular fa-eye text-blue-600 hover:text-blue-900"></i>
                                </button>
                                @endif

                                @if(auth()->guard('admin')->user()->hasPermission('edit_vehicles'))
                                <button wire:click="openEditModal({{ $vehicle->id }})" 
                                        wire:key="edit-{{ $vehicle->id }}"
                                        class="text-yellow-600 hover:text-yellow-900 transition-colors text-base"
                                        title="Modifica">
                                    <i class="fa-solid fa-pen-to-square text-yellow-600 hover:text-yellow-900"></i>
                                </button>
                                @endif

                                @if(auth()->guard('admin')->user()->hasPermission('view_expiration'))
                                <button wire:click="goToExpiration({{ $vehicle->id }})" 
                                        wire:key="expiration-{{ $vehicle->id }}"
                                        class="text-purple-600 hover:text-purple-900 transition-colors text-base"
                                        title="Gestisci Scadenze">
                                    <i class="fa-regular fa-calendar text-purple-600 hover:text-purple-900"></i>
                                </button>
                                @endif

                                @if(auth()->guard('admin')->user()->hasPermission('view_purchases'))
                                <button wire:click="goToInvoicesReceived({{ $vehicle->id }})" 
                                        wire:key="invoices-{{ $vehicle->id }}"
                                        class="text-orange-600 hover:text-orange-900 transition-colors text-base"
                                        title="Fatture di Acquisto collegate">
                                    <i class="fa-solid fa-dolly text-orange-600 hover:text-orange-900"></i>
                                </button>
                                @endif

                                @if(auth()->guard('admin')->user()->hasPermission('edit_vehicles'))
                                <button wire:click="toggleStatus({{ $vehicle->id }})" 
                                        wire:key="status-{{ $vehicle->id }}"
                                        class="transition-colors text-base {{ $vehicle->valid == 1 ? 'text-lime-600 hover:text-lime-800' : 'text-gray-400 hover:text-gray-600' }}"
                                        title="{{ $vehicle->valid == 1 ? 'Disattiva' : 'Attiva' }}">
                                    <i class="{{ $vehicle->valid == 1 ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-xmark text-red-400' }}"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center">
                            <div class="text-gray-500">
                                <i class="fas fa-truck fa-3x text-gray-400 mb-3"></i>
                                <p class="mt-2 text-sm">Nessun mezzo trovato</p>
                                @if($search || $tipoFilter || $statoFilter || $ownershipFilter || $polizzaSearch)
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

    <!-- Bottoni Export -->
    <div class="flex justify-end gap-3 mt-6">
        <button wire:click="exportPdf"
                class="flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
            <i class="fas fa-file-pdf"></i>
            <span>Esporta PDF</span>
        </button>
        <button wire:click="exportExcel"
                class="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
            <i class="fas fa-file-excel"></i>
            <span>Esporta Excel</span>
        </button>
    </div>
    
    <!-- Paginazione -->
    @if($vehicles->hasPages())
    <div class="mt-6">
        <div class="text-sm text-gray-500 mb-2">
            Mostrando {{ $vehicles->firstItem() ?? 0 }} - {{ $vehicles->lastItem() ?? 0 }} di {{ $vehicles->total() }} risultati
        </div>
        <div class="flex justify-center">
            {{ $vehicles->links() }}
        </div>
    </div>
    @else
    <div class="mt-6">
        <div class="text-sm text-gray-500">
            Mostrando {{ $vehicles->total() }} di {{ $vehicles->total() }} risultati
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

    <!-- MODAL CREAZIONE NUOVO MEZZO -->
    @if($showCreateModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" 
        x-data="{ open: true }" 
        x-show="open"
        x-init="$watch('open', value => { if (!value) $wire.closeCreateModal() })"
        @keydown.escape.window="open = false">
        
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="open = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-center mb-4 border-b pb-3">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">
                                <i class="fas fa-plus-circle text-lime-600 mr-2"></i>
                                Nuovo Mezzo
                            </h2>
                            <p class="text-xs text-gray-500 mt-1">Compila i campi per aggiungere un nuovo mezzo</p>
                        </div>
                        <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Targa <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                    wire:model="createTarga" 
                                    placeholder="es. AB123CD"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                @error('createTarga') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Anno Immatricolazione</label>
                                <input type="date" 
                                    wire:model="createImmatricolazione" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                @error('createImmatricolazione') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
                                <input type="text" 
                                    wire:model="createMarca" 
                                    placeholder="es. Fiat, New Holland, John Deere"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Modello</label>
                                <input type="text" 
                                    wire:model="createModello" 
                                    placeholder="es. Panda, T4050, 5075E"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-12 gap-4">
                            <div class="col-span-12 md:col-span-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Tipologia <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="createTipologia" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                    <option value="">Seleziona tipologia...</option>
                                    @foreach($tipiList as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('createTipologia') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            
                            <div class="col-span-12 md:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Proprietà <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="createIdOwnership" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                    <option value="">Seleziona proprietà...</option>
                                    @foreach($proprietaList as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('createIdOwnership') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            
                            <div class="col-span-12 md:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Stato <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="createValid" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                    <option value="1">Attivo</option>
                                    <option value="0">Disattivo</option>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                            <textarea wire:model="createNote" 
                                    rows="3"
                                    placeholder="Note aggiuntive..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-col sm:flex-row sm:justify-end gap-3">
                    <button @click="open = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md">
                        <i class="fas fa-times mr-2"></i>Annulla
                    </button>
                    <button wire:click="saveVehicle" class="px-4 py-2 bg-lime-600 hover:bg-lime-700 text-white rounded-md">
                        <i class="fas fa-save mr-2"></i>Salva Mezzo
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL MODIFICA MEZZO -->
    @if($showEditModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" 
        x-data="{ open: true }" 
        x-show="open"
        x-init="$watch('open', value => { if (!value) $wire.closeEditModal() })"
        @keydown.escape.window="open = false">
        
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="open = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-center mb-4 border-b pb-3">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">
                                <i class="fas fa-edit text-yellow-600 mr-2"></i>
                                Modifica Mezzo
                            </h2>
                            <p class="text-xs text-gray-500 mt-1">Modifica i dati del mezzo</p>
                        </div>
                        <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Targa</label>
                                <input type="text" wire:model="editTarga" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                @error('editTarga') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Anno Immatricolazione</label>
                                <input type="date" wire:model="editImmatricolazione" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
                                <input type="text" wire:model="editMarca" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Modello</label>
                                <input type="text" wire:model="editModello" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-12 gap-4">
                            <div class="col-span-12 md:col-span-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipologia</label>
                                <select wire:model="editTipologia" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                    @foreach($tipiList as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-span-12 md:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Proprietà</label>
                                <select wire:model="editIdOwnership" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                    <option value="">Seleziona proprietà...</option>
                                    @foreach($proprietaList as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-span-12 md:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Stato</label>
                                <select wire:model="editValid" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                    <option value="1">Attivo</option>
                                    <option value="0">Disattivo</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-12 gap-4">
                            <div class="col-span-12">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Archivio</label>
                                <input type="text" 
                                    wire:model="editArchivio" 
                                    placeholder="es. Faldone 12, Scaffale B, Cartella fisica..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                            <textarea wire:model="editNote" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-col sm:flex-row sm:justify-end gap-3">
                    <button @click="open = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md">
                        <i class="fas fa-times mr-2"></i> Annulla
                    </button>
                    <button wire:click="updateVehicle" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-md">
                        <i class="fas fa-save mr-2"></i> Aggiorna Mezzo
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL VISUALIZZAZIONE DETTAGLIO MEZZO -->
    @if($showViewModal && $viewingVehicle)
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
                            <i class="fas fa-truck mr-2 text-lime-600"></i> Dettaglio Mezzo
                        </h2>
                        <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-gray-500">ID Mezzo</label>
                                <p class="text-gray-900 font-medium">#{{ $viewingVehicle->id }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Targa</label>
                                <p class="text-gray-900 font-medium">{{ $viewingVehicle->targa ?: '-' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Anno Immatricolazione</label>
                                <p class="text-gray-900">{{ $viewingVehicle->immatricolazione ? date('d/m/Y', strtotime($viewingVehicle->immatricolazione)) : '-' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Marca</label>
                                <p class="text-gray-900">{{ $viewingVehicle->marca ?: '-' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Modello</label>
                                <p class="text-gray-900">{{ $viewingVehicle->modello ?: '-' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Tipologia</label>
                                <p><span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">{{ $viewingVehicle->tipologia ?: '-' }}</span></p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Proprietà</label>
                                <p class="text-gray-900">{{ $viewingVehicle->proprieta_nome ?? '-' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Stato</label>
                                <p>
                                    @if($viewingVehicle->valid == 1)
                                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i> Attivo
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">
                                            <i class="fas fa-times-circle mr-1"></i> Disattivo
                                        </span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        @if($viewingVehicle->archivio)
                        <div>
                            <label class="text-sm font-medium text-gray-500">Archivio</label>
                            <p class="text-gray-700 mt-1">{{ $viewingVehicle->archivio }}</p>
                        </div>
                        @endif
                        
                        @if($viewingVehicle->note)
                        <div>
                            <label class="text-sm font-medium text-gray-500">Note</label>
                            <p class="text-gray-700 mt-1">{{ $viewingVehicle->note }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-col sm:flex-row sm:justify-end gap-3">
                    <button @click="open = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md">
                        <i class="fas fa-times mr-2"></i>Chiudi
                    </button>
                    @if(auth()->guard('admin')->user()->hasPermission('edit_vehicles'))
                    <button wire:click="openEditModal({{ $viewingVehicle->id }})" @click="open = false" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-md">
                        <i class="fas fa-edit mr-2"></i> Modifica
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL CONFERMA ELIMINAZIONE -->
    @if($showDeleteModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" 
         x-data="{ open: true }" 
         x-show="open"
         x-init="$watch('open', value => { if (!value) $wire.cancelDelete() })"
         @keydown.escape.window="open = false">
        
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="open = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex items-center">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Conferma eliminazione</h3>
                            <p class="text-sm text-gray-500 mt-1">
                                Sei sicuro di voler eliminare il mezzo <strong>{{ $deleteName }}</strong>?
                                Questa azione è irreversibile.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-col sm:flex-row sm:justify-end gap-3">
                    <button @click="open = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md">
                        <i class="fas fa-times mr-2"></i>Annulla
                    </button>
                    <button wire:click="deleteVehicle" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md">
                        <i class="fas fa-trash-alt mr-2"></i>Elimina
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <script>
        document.addEventListener('livewire:init', function () {
            Livewire.on('filters-reset', () => {
                const selects = document.querySelectorAll('select');
                selects.forEach(select => {
                    select.dispatchEvent(new Event('change'));
                });
            });
        });
    </script> 
</div>