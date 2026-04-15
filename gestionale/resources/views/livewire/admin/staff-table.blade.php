<div>
    <!-- Filtri e Ricerca -->
    <div class="bg-white rounded-lg shadow mb-6 p-4 border border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="relative md:col-span-2">
                <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       placeholder="Cerca per: Nome, Cognome, Soprannome, Cellulare, Email, Codice Fiscale..." 
                       class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent">
            </div>
            
            <select wire:model.live="statusFilter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                <option value="">Tutti gli stati</option>
                <option value="active">Attivi</option>
                <option value="inactive">Disattivi</option>
            </select>
        </div>
        
        <div class="flex justify-between items-center mt-4">
            @if($search || $statusFilter)
            <button wire:click="resetFilters" class="text-sm text-gray-600 hover:text-gray-900 transition-colors">
                <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Resetta filtri
            </button>
            @endif
        </div>
        
        @if($search || $statusFilter)
        <div class="mt-3 flex flex-wrap items-center gap-2">
            <span class="text-sm text-gray-500">Filtri attivi:</span>
            @if($search)
            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-lime-100 text-lime-800">
                Ricerca: "{{ $search }}"
                <button wire:click="$set('search', '')" class="ml-1 hover:text-lime-900">
                    <svg class="inline-block w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </span>
            @endif
            @if($statusFilter)
            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-lime-100 text-lime-800">
                Stato: {{ $statusFilter === 'active' ? 'Attivi' : 'Disattivi' }}
                <button wire:click="$set('statusFilter', '')" class="ml-1 hover:text-lime-900">
                    <svg class="inline-block w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </span>
            @endif
        </div>
        @endif
    </div>

    <!-- Tabella Personale -->
    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('id_personale')">
                            <div class="flex items-center space-x-1">
                                <span>ID</span>
                                @if($sortField === 'id_personale')
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($sortDirection === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        @endif
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('CognomePers')">
                            <div class="flex items-center space-x-1">
                                <span>Nome e Cognome</span>
                                @if($sortField === 'CognomePers')
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($sortDirection === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        @endif
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contatti</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Codice Fiscale</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('valid')">
                            <div class="flex items-center space-x-1">
                                <span>Stato</span>
                                @if($sortField === 'valid')
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($sortDirection === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        @endif
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($staff as $person)
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $person->id_personale }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full flex items-center justify-center bg-gray-100">
                                    <i class="fas fa-user text-gray-500 text-lg"></i>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $person->NomePers ?: '-' }} {{ $person->CognomePers ?: '-' }}
                                    </div>
                                    @if($person->Soprannome)
                                    <div class="text-xs text-gray-500">
                                        <i class="fas fa-tag mr-1"></i> {{ $person->Soprannome }}
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <div class="space-y-1">
                                @if($person->CellPers)
                                <div class="flex items-center">
                                    <i class="fas fa-mobile-alt w-4 text-gray-400 mr-2"></i>
                                    <span>{{ $person->CellPers }}</span>
                                </div>
                                @endif
                                @if($person->TelPers)
                                <div class="flex items-center">
                                    <i class="fas fa-phone w-4 text-gray-400 mr-2"></i>
                                    <span>{{ $person->TelPers }}</span>
                                </div>
                                @endif
                                @if($person->EmailPers)
                                <div class="flex items-center">
                                    <i class="fas fa-envelope w-4 text-gray-400 mr-2"></i>
                                    <span class="truncate max-w-[200px]">{{ $person->EmailPers }}</span>
                                </div>
                                @endif
                                @if(!$person->CellPers && !$person->TelPers && !$person->EmailPers)
                                <span class="text-gray-400 italic text-xs">Nessun contatto</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 font-mono">
                            {{ $person->CodFiscPers ?: '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $person->valid ? 'bg-lime-100 text-lime-800' : 'bg-red-100 text-red-800' }}">
                                {{ $person->valid ? 'Attivo' : 'Disattivo' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                            <div class="flex space-x-3">
                                @if(auth()->guard('admin')->user()->hasPermission('view_staff'))
                                <button wire:click="viewStaff({{ $person->id_personale }})" 
                                        class="text-blue-600 hover:text-blue-900 transition-colors"
                                        title="Visualizza">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>
                                @endif
                                
                                @if(auth()->guard('admin')->user()->hasPermission('edit_staff'))
                                <button wire:click="editStaff({{ $person->id_personale }})" 
                                        class="text-yellow-600 hover:text-yellow-900 transition-colors"
                                        title="Modifica">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                @endif
                                
                                <!-- Icona Scadenze - versione SVG puro -->
                                @if(auth()->guard('admin')->user()->hasPermission('view_expiration'))
                                <button wire:click="goToExpiration({{ $person->id_personale }})" 
                                        class="text-purple-600 hover:text-purple-900 transition-colors"
                                        title="Gestisci Scadenze">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 12.75h.008v.008H12v-.008Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12.75h.008v.008H15v-.008Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75h.008v.008H9v-.008Z" />
                                    </svg>
                                </button>
                                @endif

                                @if(auth()->guard('admin')->user()->hasPermission('edit_staff'))
                                <button wire:click="toggleStatus({{ $person->id_personale }})" 
                                        class="transition-colors {{ $person->valid ? 'text-lime-600 hover:text-lime-800' : 'text-gray-400 hover:text-gray-600' }}"
                                        title="{{ $person->valid ? 'Disattiva' : 'Attiva' }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($person->valid)
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        @endif
                                    </svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                                <p class="mt-2 text-sm">Nessun personale trovato</p>
                                @if($search || $statusFilter)
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
    @if($staff->hasPages())
    <div class="mt-6">
        <div class="text-sm text-gray-500 mb-2">
            Mostrando {{ $staff->firstItem() ?? 0 }} - {{ $staff->lastItem() ?? 0 }} di {{ $staff->total() }} risultati
        </div>
        <div class="flex justify-center">
            {{ $staff->links() }}
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
    
    <!-- MODAL VISUALIZZAZIONE CON TRACCIAMENTO (responsive con stato nell'header) -->
    @if($showViewModal && $viewingStaff)
    <div wire:ignore.self class="fixed inset-0 z-50 overflow-y-auto" 
         x-data="{ show: true }" 
         x-show="show" 
         x-transition.opacity.duration.200ms>
        
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Sfondo -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-on:click="show = false; $wire.closeViewModal()" aria-hidden="true"></div>
            
            <!-- Spaziatore per centrare -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <!-- Modal -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full"
                 x-transition.scale.origin.top>
                
                <!-- Header con nome, stato e pulsante chiusura -->
                <div class="bg-white px-4 pt-4 pb-2 sm:px-6 border-b border-gray-200">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center space-x-3 flex-1 min-w-0">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                <i class="fas fa-user text-blue-600 text-lg"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h2 class="text-lg font-bold text-gray-800 truncate">
                                    {{ $viewingStaff->NomePers ?: '-' }} {{ $viewingStaff->CognomePers ?: '-' }}
                                </h2>
                                @if($viewingStaff->Soprannome)
                                <p class="text-xs text-gray-500 truncate">
                                    <i class="fas fa-tag mr-1"></i> {{ $viewingStaff->Soprannome }}
                                </p>
                                @endif
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-3">
                            <!-- Badge Stato -->
                            <span class="px-2 py-1 text-xs font-semibold rounded-full whitespace-nowrap
                                {{ $viewingStaff->valid ? 'bg-lime-100 text-lime-800' : 'bg-red-100 text-red-800' }}">
                                {{ $viewingStaff->valid ? 'Attivo' : 'Disattivo' }}
                            </span>
                            
                            <!-- Pulsante chiusura -->
                            <button wire:click="closeViewModal" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Contenuto scrollabile -->
                <div class="px-4 pt-4 pb-2 sm:px-6 max-h-[70vh] overflow-y-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-md font-semibold text-gray-800 mb-3 border-b pb-2">
                                <i class="fas fa-info-circle mr-2 text-blue-500"></i> Dati Anagrafici
                            </h3>
                            <div class="space-y-2">
                                <div class="flex"><span class="w-32 text-gray-600">Codice Fiscale:</span><span class="text-gray-800 font-mono">{{ $viewingStaff->CodFiscPers ?: '-' }}</span></div>
                                <div class="flex"><span class="w-32 text-gray-600">Data Nascita:</span><span class="text-gray-800">{{ $viewingStaff->DataNascPers ?: '-' }}</span></div>
                                <div class="flex"><span class="w-32 text-gray-600">Luogo Nascita:</span><span class="text-gray-800">{{ $viewingStaff->LuogoNasc ?: '-' }}</span></div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-md font-semibold text-gray-800 mb-3 border-b pb-2">
                                <i class="fas fa-address-card mr-2 text-orange-500"></i> Contatti
                            </h3>
                            <div class="space-y-2">
                                @if($viewingStaff->TelPers)
                                <div class="flex"><span class="w-20 text-gray-600">Tel:</span><span class="text-gray-800">{{ $viewingStaff->TelPers }}</span></div>
                                @endif
                                @if($viewingStaff->CellPers)
                                <div class="flex"><span class="w-20 text-gray-600">Cell:</span><span class="text-gray-800">{{ $viewingStaff->CellPers }}</span></div>
                                @endif
                                @if($viewingStaff->EmailPers)
                                <div class="flex"><span class="w-20 text-gray-600">Email:</span><span class="text-gray-800 break-all">{{ $viewingStaff->EmailPers }}</span></div>
                                @endif
                                @if(!$viewingStaff->TelPers && !$viewingStaff->CellPers && !$viewingStaff->EmailPers)
                                <p class="text-gray-400 italic">Nessun contatto disponibile</p>
                                @endif
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4 md:col-span-2">
                            <h3 class="text-md font-semibold text-gray-800 mb-3 border-b pb-2">
                                <i class="fas fa-map-marker-alt mr-2 text-red-500"></i> Indirizzo
                            </h3>
                            <div class="space-y-2">
                                <div class="flex"><span class="w-20 text-gray-600">Indirizzo:</span><span class="text-gray-800">{{ $viewingStaff->IndirPers ?: '-' }}</span></div>
                                <div class="flex"><span class="w-20 text-gray-600">Città:</span><span class="text-gray-800">{{ $viewingStaff->CittaPers ?: '-' }}</span></div>
                                <div class="flex"><span class="w-20 text-gray-600">Provincia:</span><span class="text-gray-800">{{ $viewingStaff->ProvPers ?: '-' }}</span></div>
                                <div class="flex"><span class="w-20 text-gray-600">CAP:</span><span class="text-gray-800">{{ $viewingStaff->CapPers ?: '-' }}</span></div>
                            </div>
                        </div>
                        
                        <!-- ========== TRACCIAMENTO ========== -->
                        <div class="bg-gray-50 rounded-lg p-4 md:col-span-2">
                            <h3 class="text-md font-semibold text-gray-800 mb-3 border-b pb-2">
                                <i class="fas fa-history mr-2 text-indigo-500"></i> Tracciamento
                            </h3>
                            <div class="space-y-3">
                                {{-- Creato da --}}
                                <div class="flex items-start">
                                    <div class="w-28 text-gray-600 text-sm pt-0.5">Inserito da:</div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                            <span class="text-gray-800 font-medium">
                                                {{ $viewingStaff->createdBy ? $viewingStaff->createdBy->name : 'Sistema' }}
                                            </span>
                                            <span class="text-gray-400">•</span>
                                            <span class="text-gray-500 text-sm">
                                                {{ $viewingStaff->created_at ? $viewingStaff->created_at->format('d/m/Y H:i') : '-' }}
                                            </span>
                                        </div>
                                        @if($viewingStaff->createdBy && $viewingStaff->createdBy->email)
                                        <div class="text-xs text-gray-400 mt-1 ml-6 break-all">
                                            {{ $viewingStaff->createdBy->email }}
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                
                                {{-- Modificato da --}}
                                @if($viewingStaff->updated_at && $viewingStaff->created_at != $viewingStaff->updated_at)
                                <div class="flex items-start">
                                    <div class="w-28 text-gray-600 text-sm pt-0.5">Modificato da:</div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            <span class="text-gray-800 font-medium">
                                                {{ $viewingStaff->updatedBy ? $viewingStaff->updatedBy->name : 'Sistema' }}
                                            </span>
                                            <span class="text-gray-400">•</span>
                                            <span class="text-gray-500 text-sm">
                                                {{ $viewingStaff->updated_at ? $viewingStaff->updated_at->format('d/m/Y H:i') : '-' }}
                                            </span>
                                        </div>
                                        @if($viewingStaff->updatedBy && $viewingStaff->updatedBy->email)
                                        <div class="text-xs text-gray-400 mt-1 ml-6 break-all">
                                            {{ $viewingStaff->updatedBy->email }}
                                        </div>
                                        @endif
                                        <div class="text-xs text-gray-400 mt-1 ml-6">
                                            ({{ $viewingStaff->updated_at->diffForHumans() }})
                                        </div>
                                    </div>
                                </div>
                                @else
                                <div class="flex items-start">
                                    <div class="w-28 text-gray-600 text-sm pt-0.5">Modificato da:</div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            <span class="text-gray-400 italic">Mai modificato</span>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        <!-- ========== FINE TRACCIAMENTO ========== -->
                    </div>
                </div>
                
                <!-- Footer con bottoni -->
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-col sm:flex-row sm:justify-end gap-3">
                    @if(auth()->guard('admin')->user()->hasPermission('edit_staff'))
                    <button wire:click="editStaff({{ $viewingStaff->id_personale }})" 
                            class="w-full sm:w-auto inline-flex justify-center items-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:text-sm">
                        <i class="fas fa-edit mr-2"></i> Modifica
                    </button>
                    @endif
                    <button wire:click="closeViewModal" 
                            class="w-full sm:w-auto inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm">
                        Chiudi
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL MODIFICA -->
    @if($showEditModal && $editingStaff)
    <div wire:ignore.self class="fixed inset-0 z-50 overflow-y-auto" 
         x-data="{ show: true }" 
         x-show="show" 
         x-transition.opacity.duration.200ms>
        
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-on:click="show = false; $wire.closeEditModal()" aria-hidden="true"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-center mb-4 border-b pb-3">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-edit mr-2 text-yellow-600"></i> Modifica Personale
                        </h2>
                        <button wire:click="closeEditModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[60vh] overflow-y-auto">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                            <input type="text" wire:model="editNome" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cognome</label>
                            <input type="text" wire:model="editCognome" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Soprannome</label>
                            <input type="text" wire:model="editSoprannome" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Codice Fiscale</label>
                            <input type="text" wire:model="editCodFiscale" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefono</label>
                            <input type="text" wire:model="editTelefono" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cellulare</label>
                            <input type="text" wire:model="editCellulare" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" wire:model="editEmail" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Indirizzo</label>
                            <input type="text" wire:model="editIndirizzo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Città</label>
                            <input type="text" wire:model="editCitta" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Provincia</label>
                            <input type="text" wire:model="editProvincia" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">CAP</label>
                            <input type="text" wire:model="editCap" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data Nascita</label>
                            <input type="date" wire:model="editDataNascita" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Luogo Nascita</label>
                            <input type="text" wire:model="editLuogoNascita" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div>
                            <label class="inline-flex items-center">
                                <input type="checkbox" wire:model="editValid" class="rounded border-gray-300 text-lime-600">
                                <span class="ml-2 text-sm text-gray-700">Account attivo</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-col sm:flex-row sm:justify-end gap-3">
                    <button wire:click="updateStaff" 
                            class="w-full sm:w-auto inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:text-sm">
                        <i class="fas fa-save mr-2"></i> Aggiorna
                    </button>
                    <button wire:click="closeEditModal" 
                            class="w-full sm:w-auto inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm">
                        Annulla
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL CREAZIONE -->
    @if($showCreateModal)
    <div wire:ignore.self class="fixed inset-0 z-50 overflow-y-auto" 
         x-data="{ show: true }" 
         x-show="show" 
         x-transition.opacity.duration.200ms>
        
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-on:click="show = false; $wire.closeCreateModal()" aria-hidden="true"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-center mb-4 border-b pb-3">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-plus-circle mr-2 text-green-600"></i> Nuovo Personale
                        </h2>
                        <button wire:click="closeCreateModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[60vh] overflow-y-auto">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                            <input type="text" wire:model="createNome" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            @error('createNome') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cognome</label>
                            <input type="text" wire:model="createCognome" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            @error('createCognome') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Soprannome</label>
                            <input type="text" wire:model="createSoprannome" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Codice Fiscale</label>
                            <input type="text" wire:model="createCodFiscale" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefono</label>
                            <input type="text" wire:model="createTelefono" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cellulare</label>
                            <input type="text" wire:model="createCellulare" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" wire:model="createEmail" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            @error('createEmail') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Indirizzo</label>
                            <input type="text" wire:model="createIndirizzo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Città</label>
                            <input type="text" wire:model="createCitta" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Provincia</label>
                            <input type="text" wire:model="createProvincia" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">CAP</label>
                            <input type="text" wire:model="createCap" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data Nascita</label>
                            <input type="date" wire:model="createDataNascita" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Luogo Nascita</label>
                            <input type="text" wire:model="createLuogoNascita" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div>
                            <label class="inline-flex items-center">
                                <input type="checkbox" wire:model="createValid" class="rounded border-gray-300 text-lime-600" checked>
                                <span class="ml-2 text-sm text-gray-700">Account attivo</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-col sm:flex-row sm:justify-end gap-3">
                    <button wire:click="saveStaff" 
                            class="w-full sm:w-auto inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:text-sm">
                        <i class="fas fa-save mr-2"></i> Salva
                    </button>
                    <button wire:click="closeCreateModal" 
                            class="w-full sm:w-auto inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm">
                        Annulla
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>