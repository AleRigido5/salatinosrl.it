<div>
    <!-- Header con breadcrumb -->
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Gestione Scadenze Personale</h1>
                @if($staffName)
                <p class="text-gray-500 mt-1">
                    <i class="fas fa-user mr-1"></i> Scadenze per: <strong>{{ $staffName }}</strong>
                </p>
                @endif
            </div>
            <div class="flex gap-3">
                <div class="relative group">
                    <button wire:click="openCreateModal" 
                            class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                        <i class="fas fa-plus"></i>
                    </button>
                    <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                        Nuova scadenza
                    </div>
                </div>
                
                @if($staffId)
                <div class="relative group">
                    <button wire:click="backToStaff" 
                            class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Messaggi di Sistema -->
    @if(session()->has('success'))
    <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
        <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
    </div>
    @endif

    @if(session()->has('error'))
    <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
        <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
    </div>
    @endif

    @if($errors->any())
    <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Filtri e Ricerca -->
    <div class="bg-white rounded-lg shadow mb-6 p-4 border border-gray-200">
        <!-- RIGA SUPERIORE: Date Range Filter -->
        @livewire('components.date-range-filter', [
            'dateFrom' => $dateFrom, 
            'dateTo' => $dateTo
        ], key('date-filter-' . $dateFrom . $dateTo))
        
        <!-- Linea di separazione -->
        <div class="border-t border-gray-200 my-4"></div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-7 gap-3">
            <div class="relative sm:col-span-2 lg:col-span-2">
                <i class="fas fa-search absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       placeholder="Cerca per titolo, qualifica o note..." 
                       class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent">
            </div>
            
            <select wire:model.live="tipologiaFilter" class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                <option value="">Tutte le tipologie</option>
                @foreach($tipologie as $tipologia)
                    <option value="{{ $tipologia->id }}">{{ $tipologia->valore }}</option>
                @endforeach
            </select>
            
            <select wire:model.live="statusFilter" class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                <option value="">Tutti gli stati</option>
                <option value="active">Attive</option>
                <option value="expiring">In scadenza</option>
                <option value="expired">Scadute</option>
            </select>

            <select wire:model.live="ownershipFilter" class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                <option value="">Tutte le proprietà</option>
                @foreach($ownerships as $ownership)
                    <option value="{{ $ownership->id_proprieta }}">{{ $ownership->RagAbbrev ?? $ownership->RagSocialePr }}</option>
                @endforeach
            </select>

            <select wire:model.live="staffCategoryFilter" class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                <option value="">Tutte le categorie</option>
                @foreach($staffCategories as $category)
                    <option value="{{ $category->id }}">{{ $category->valore }}</option>
                @endforeach
            </select>

            <select wire:model.live="perPage" class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                <option value="100">100 per pagina</option>
                <option value="200">200 per pagina</option>
                <option value="10000">Tutti</option>
            </select>
        </div>
        
        <div class="flex justify-between items-center mt-4">
            @if($search || $tipologiaFilter || $statusFilter || $ownershipFilter || $staffCategoryFilter)
            <button wire:click="resetFilters" class="text-sm text-gray-600 hover:text-gray-900 transition-colors">
                <i class="fas fa-sync-alt mr-1"></i>
                Resetta filtri
            </button>
            @endif
        </div>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('id_ownership')">
                            <div class="flex items-center space-x-1">
                                <span>Proprietà</span>
                                @if($sortField === 'id_ownership')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
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
                            @if($expiration->ownershipLegacy)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                    {{ $expiration->ownershipLegacy->RagAbbrev ?? $expiration->ownershipLegacy->RagSocialePr }}
                                </span>
                            @else
                                <span class="text-sm text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $entityName = $expiration->getLinkedEntityNameAttribute();
                                $entityType = $expiration->getLinkedEntityTypeAttribute();
                            @endphp
                            @if($entityName && $entityName != '-')
                            <div class="flex flex-col">
                                <span class="text-sm text-gray-900">{{ $entityName }}</span>
                                <span class="text-xs text-gray-500">{{ $entityType }}</span>
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
                                <button wire:click="openEditModal({{ $expiration->id }})" 
                                        wire:key="edit-{{ $expiration->id }}"
                                        class="text-yellow-600 hover:text-yellow-900 transition-colors"
                                        title="Modifica">
                                    <i class="fa-solid fa-pen-to-square text-yellow-600 hover:text-yellow-900"></i>
                                </button>
                                @endif

                                <!-- Pulsante Documenti per Staff -->
                                <a href="{{ route('admin.documents.index', ['expiration-staff', $expiration->id]) . '?staff_id=' . ($staffId ?? $expiration->id_references) }}" 
                                class="text-indigo-600 hover:text-indigo-900 transition-colors relative"
                                title="Gestisci Documenti">
                                    <i class="fas fa-paperclip"></i>
                                    @if($expiration->documents_count > 0)
                                    <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">
                                        {{ $expiration->documents_count }}
                                    </span>
                                    @endif
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="text-gray-500">
                                <i class="fas fa-calendar-times text-gray-400 text-5xl"></i>
                                <p class="mt-2 text-sm">Nessuna scadenza trovata</p>
                                @if($search || $tipologiaFilter || $statusFilter)
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
                            @if($staffName)
                            <span class="text-sm font-normal text-gray-500 ml-2">{{ $staffName }}</span>
                            @endif
                        </h2>
                        <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-2xl"></i>
                        </button>
                    </div>
                    
                    <div class="space-y-4">
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
                                        <option value="{{ $ownership->id_proprieta }}">{{ $ownership->RagAbbrev }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- CAMPO STAFF -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Dipendente <span class="text-red-500">*</span>
                            </label>
                            <div class="relative" x-data="{ open: false }" @click.away="open = false">
                                <div class="relative">
                                    <input type="text" 
                                        wire:model.live.debounce.300ms="createStaffSearch" 
                                        placeholder="Cerca dipendente per nome, cognome o email..."
                                        class="w-full px-3 py-2 pl-9 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent"
                                        @focus="open = true"
                                        @input="open = true">
                                    <i class="fas fa-user absolute left-2.5 top-2.5 text-gray-400"></i>
                                    
                                    @if($createStaffNome)
                                    <button type="button" 
                                            wire:click="clearStaff" 
                                            class="absolute right-2 top-2 text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    @endif
                                </div>
                                
                                @if(count($createStaffResults) > 0)
                                <div class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto" x-show="open">
                                    @foreach($createStaffResults as $staffResult)
                                    <div class="px-4 py-2 hover:bg-lime-50 cursor-pointer border-b border-gray-100 last:border-0"
                                        wire:click="selectStaff({{ $staffResult->id_personale }}, '{{ addslashes($staffResult->full_name) }}')"
                                        @click="open = false">
                                        <div class="font-medium text-gray-900">
                                            {{ $staffResult->full_name }}
                                        </div>
                                        <div class="text-xs text-gray-500 flex flex-wrap gap-2 mt-0.5">
                                            @if($staffResult->EmailPers)
                                            <span class="inline-flex items-center">
                                                <i class="fas fa-envelope mr-1 text-xs"></i>
                                                {{ $staffResult->EmailPers }}
                                            </span>
                                            @endif
                                            @if($staffResult->CellPers)
                                            <span>{{ $staffResult->CellPers }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                            
                            @if($createStaffNome)
                            <div class="mt-2 p-2 bg-green-50 rounded-md border border-green-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center text-sm text-green-700">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        <span class="font-medium">Dipendente selezionato:</span>
                                        <span class="ml-1">{{ $createStaffNome }}</span>
                                    </div>
                                    <button type="button" wire:click="clearStaff" class="text-green-600 hover:text-green-800">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            @endif
                            @error('createStaffId') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Titolo <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                wire:model="createTitolo" 
                                placeholder="es. Visita medica, Corso formazione, Scadenza contratto..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent">
                            @error('createTitolo') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Data Inizio <span class="text-red-500">*</span>
                                </label>
                                <input type="date" 
                                    wire:model="createDataInizio" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                @error('createDataInizio') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data Scadenza</label>
                                <input type="date" 
                                    wire:model="createDataFine" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                <p class="text-xs text-gray-400 mt-1">Opzionale: lascia vuoto se non ha scadenza</p>
                                @error('createDataFine') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fornitore</label>
                                <div class="relative" x-data="{ open: false }" @click.away="open = false">
                                    <div class="relative">
                                        <input type="text" 
                                            wire:model.live.debounce.300ms="createEntitySearch" 
                                            placeholder="Cerca fornitore..."
                                            class="w-full px-3 py-2 pl-9 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent"
                                            @focus="open = true"
                                            @input="open = true">
                                        <i class="fas fa-search absolute left-2.5 top-2.5 text-gray-400"></i>
                                        
                                        @if($createEntityNome)
                                        <button type="button" 
                                                wire:click="clearEntity" 
                                                class="absolute right-2 top-2 text-gray-400 hover:text-gray-600">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        @endif
                                    </div>
                                    
                                    @if(count($createEntityResults) > 0)
                                    <div class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto" x-show="open">
                                        @foreach($createEntityResults as $result)
                                        <div class="px-4 py-2 hover:bg-lime-50 cursor-pointer border-b border-gray-100 last:border-0"
                                            wire:click="selectEntity({{ $result->id_cliente }}, '{{ addslashes($result->ragione_sociale ?: $result->nome . ' ' . $result->cognome) }}')"
                                            @click="open = false">
                                            <div class="font-medium text-gray-900">
                                                {{ $result->ragione_sociale ?: $result->nome . ' ' . $result->cognome }}
                                            </div>
                                            <div class="text-xs text-gray-500 flex flex-wrap gap-2 mt-0.5">
                                                @if($result->partita_iva)
                                                <span class="inline-flex items-center">
                                                    <i class="fas fa-credit-card mr-1 text-xs"></i>
                                                    P.IVA: {{ $result->partita_iva }}
                                                </span>
                                                @endif
                                                @if($result->codice_fiscale)
                                                <span>CF: {{ $result->codice_fiscale }}</span>
                                                @endif
                                                <span>Tipo: {{ $result->entity_type }}</span>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                                
                                @if($createEntityNome)
                                <div class="mt-2 p-2 bg-green-50 rounded-md border border-green-200">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center text-sm text-green-700">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            <span class="font-medium">Associato a:</span>
                                            <span class="ml-1">{{ $createEntityNome }}</span>
                                        </div>
                                        <button type="button" wire:click="clearEntity" class="text-green-600 hover:text-green-800">
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
                                    placeholder="es. Operaio agricolo, Addetto vendemmia, ..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
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
                        <i class="fas fa-times mr-2"></i> Annulla
                    </button>
                    <button wire:click="saveExpiration" 
                            class="px-4 py-2 bg-lime-600 hover:bg-lime-700 text-white rounded-md">
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
                        @endphp
                        
                        @if($linkedEntity && $linkedEntityName != '-')
                        <div class="bg-lime-50 p-3 rounded-lg">
                            <p class="text-sm text-lime-700">
                                <i class="fas fa-user mr-1"></i> 
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
                                <label class="text-sm font-medium text-gray-500">Proprietà</label>
                                <p class="text-gray-900">
                                    @if($viewingExpiration->ownershipLegacy)
                                        <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800">
                                            {{ $viewingExpiration->ownershipLegacy->RagAbbrev ?? $viewingExpiration->ownershipLegacy->RagSocialePr }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </p>
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
                    <a href="{{ route('admin.expiration-staff.edit', ['id' => $viewingExpiration->id, 'staff_id' => $staffId ?? $viewingExpiration->id_references]) }}"
                       class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-md">
                        <i class="fas fa-edit mr-2"></i> Modifica
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL MODIFICA SCADENZA -->
    @if($showEditModal && $editingExpiration)
    <div class="fixed inset-0 z-50 overflow-y-auto" 
        x-data="{ open: true }" 
        x-show="open"
        x-init="$watch('open', value => { if (!value) $wire.closeEditModal() })"
        @keydown.escape.window="open = false">
        
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="open = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-center mb-4 border-b pb-3">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-edit mr-2 text-yellow-600"></i>
                            Modifica Scadenza
                            @if($staffName)
                            <span class="text-sm font-normal text-gray-500 ml-2">{{ $staffName }}</span>
                            @endif
                        </h2>
                        <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-2xl"></i>
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Tipologia Scadenza <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="editTipologiaId" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                    <option value="">Seleziona tipologia...</option>
                                    @foreach($tipologie as $tipologia)
                                        <option value="{{ $tipologia->id }}">{{ $tipologia->valore }}</option>
                                    @endforeach
                                </select>
                                @error('editTipologiaId') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ownership / Azienda</label>
                                <select wire:model="editOwnershipId" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                    <option value="">Seleziona ownership...</option>
                                    @foreach($ownerships as $ownership)
                                        <option value="{{ $ownership->id_proprieta }}">{{ $ownership->RagAbbrev }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- CAMPO STAFF - MODIFICA -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Dipendente <span class="text-red-500">*</span>
                            </label>
                            <div class="relative" x-data="{ open: false }" @click.away="open = false">
                                <div class="relative">
                                    <input type="text" 
                                        wire:model.live.debounce.300ms="editStaffSearch" 
                                        placeholder="Cerca dipendente per nome, cognome o email..."
                                        class="w-full px-3 py-2 pl-9 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent"
                                        @focus="open = true"
                                        @input="open = true">
                                    <i class="fas fa-user absolute left-2.5 top-2.5 text-gray-400"></i>
                                    
                                    @if($editStaffNome)
                                    <button type="button" 
                                            wire:click="clearEditStaff" 
                                            class="absolute right-2 top-2 text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    @endif
                                </div>
                                
                                @if(count($editStaffResults) > 0)
                                <div class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto" x-show="open">
                                    @foreach($editStaffResults as $staffResult)
                                    <div class="px-4 py-2 hover:bg-lime-50 cursor-pointer border-b border-gray-100 last:border-0"
                                        wire:click="selectEditStaff({{ $staffResult->id_personale }}, '{{ addslashes($staffResult->full_name) }}')"
                                        @click="open = false">
                                        <div class="font-medium text-gray-900">
                                            {{ $staffResult->full_name }}
                                        </div>
                                        <div class="text-xs text-gray-500 flex flex-wrap gap-2 mt-0.5">
                                            @if($staffResult->EmailPers)
                                            <span class="inline-flex items-center">
                                                <i class="fas fa-envelope mr-1 text-xs"></i>
                                                {{ $staffResult->EmailPers }}
                                            </span>
                                            @endif
                                            @if($staffResult->CellPers)
                                            <span>{{ $staffResult->CellPers }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                            
                            @if($editStaffNome)
                            <div class="mt-2 p-2 bg-green-50 rounded-md border border-green-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center text-sm text-green-700">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        <span class="font-medium">Dipendente selezionato:</span>
                                        <span class="ml-1">{{ $editStaffNome }}</span>
                                    </div>
                                    <button type="button" wire:click="clearEditStaff" class="text-green-600 hover:text-green-800">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            @endif
                            @error('editStaffId') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Titolo <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                wire:model="editTitolo" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                            @error('editTitolo') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Data Inizio <span class="text-red-500">*</span>
                                </label>
                                <input type="date" wire:model="editDataInizio" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                @error('editDataInizio') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data Scadenza</label>
                                <input type="date" wire:model="editDataFine" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                <p class="text-xs text-gray-400 mt-1">Opzionale: lascia vuoto se non ha scadenza</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fornitore</label>
                                <div class="relative" x-data="{ open: false }" @click.away="open = false">
                                    <input type="text" 
                                        wire:model.live.debounce.300ms="editEntitySearch" 
                                        placeholder="Cerca fornitore..."
                                        class="w-full px-3 py-2 pl-9 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                                        @focus="open = true"
                                        @input="open = true">
                                    <i class="fas fa-search absolute left-2.5 top-2.5 text-gray-400"></i>
                                    
                                    @if($editEntityNome)
                                    <button type="button" wire:click="clearEditEntity" class="absolute right-2 top-2 text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    @endif
                                </div>
                                
                                @if(count($editEntityResults) > 0)
                                <div class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto" x-show="open">
                                    @foreach($editEntityResults as $result)
                                    <div class="px-4 py-2 hover:bg-lime-50 cursor-pointer border-b border-gray-100 last:border-0"
                                        wire:click="selectEditEntity({{ $result->id_cliente }}, '{{ addslashes($result->ragione_sociale ?: $result->nome . ' ' . $result->cognome) }}')"
                                        @click="open = false">
                                        <div class="font-medium text-gray-900">
                                            {{ $result->ragione_sociale ?: $result->nome . ' ' . $result->cognome }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            @if($result->partita_iva) P.IVA: {{ $result->partita_iva }} @endif
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                                
                                @if($editEntityNome)
                                <div class="mt-2 p-2 bg-green-50 rounded-md border border-green-200">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-green-700">Associato a: {{ $editEntityNome }}</span>
                                        <button type="button" wire:click="clearEditEntity" class="text-green-600 hover:text-green-800">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                @endif
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Qualifica / Sottotitolo</label>
                                <input type="text" wire:model="editQualifica" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
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
                    <button wire:click="updateExpiration" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-md">
                        <i class="fas fa-save mr-2"></i> Aggiorna
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>