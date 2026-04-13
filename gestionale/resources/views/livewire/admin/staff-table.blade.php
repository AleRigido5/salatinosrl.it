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
                <tbody class="bg-white divide-y divide-gray-200" wire:key="staff-table-body-{{ $staff->currentPage() }}">
                    @forelse($staff as $person)
                    <tr class="hover:bg-gray-50 transition-colors duration-150" wire:key="staff-row-{{ $person->id_personale }}">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $person->id_personale }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full flex items-center justify-center bg-gray-100">
                                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $person->NomePers ?: '-' }} {{ $person->CognomePers ?: '-' }}
                                    </div>
                                    @if($person->Soprannome)
                                    <div class="text-xs text-gray-500">
                                        <svg class="inline-block w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                                        </svg>
                                        {{ $person->Soprannome }}
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <div class="space-y-1">
                                @if($person->CellPers)
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    <span>{{ $person->CellPers }}</span>
                                </div>
                                @endif
                                @if($person->TelPers)
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                    <span>{{ $person->TelPers }}</span>
                                </div>
                                @endif
                                @if($person->EmailPers)
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
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
    <div class="mt-6" wire:key="pagination-{{ $staff->currentPage() }}">
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
    
    <!-- MODAL VISUALIZZAZIONE -->
    @if($showViewModal && $viewingStaff)
    <div wire:ignore.self class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" 
         x-data="{ show: true }" 
         x-show="show" 
         x-transition.opacity.duration.200ms>
        
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl p-6" 
             x-on:click.away="show = false; $wire.closeViewModal()"
             x-transition.scale.origin.top>
            
            <div class="flex justify-between items-center mb-4 border-b pb-3">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0 h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">{{ $viewingStaff->NomePers ?: '-' }} {{ $viewingStaff->CognomePers ?: '-' }}</h2>
                        @if($viewingStaff->Soprannome)
                        <p class="text-xs text-gray-500">
                            <svg class="inline-block w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                            </svg>
                            {{ $viewingStaff->Soprannome }}
                        </p>
                        @endif
                    </div>
                </div>
                <button wire:click="closeViewModal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-md font-semibold text-gray-800 mb-3 border-b pb-2">
                        <svg class="inline-block w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Dati Anagrafici
                    </h3>
                    <div class="space-y-2">
                        <div class="flex"><span class="w-32 text-gray-600">Codice Fiscale:</span><span class="text-gray-800 font-mono">{{ $viewingStaff->CodFiscPers ?: '-' }}</span></div>
                        <div class="flex"><span class="w-32 text-gray-600">Data Nascita:</span><span class="text-gray-800">{{ $viewingStaff->DataNascPers ?: '-' }}</span></div>
                        <div class="flex"><span class="w-32 text-gray-600">Luogo Nascita:</span><span class="text-gray-800">{{ $viewingStaff->LuogoNasc ?: '-' }}</span></div>
                    </div>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-md font-semibold text-gray-800 mb-3 border-b pb-2">
                        <svg class="inline-block w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        Contatti
                    </h3>
                    <div class="space-y-2">
                        @if($viewingStaff->TelPers)
                        <div class="flex"><span class="w-20 text-gray-600">Tel:</span><span class="text-gray-800">{{ $viewingStaff->TelPers }}</span></div>
                        @endif
                        @if($viewingStaff->CellPers)
                        <div class="flex"><span class="w-20 text-gray-600">Cell:</span><span class="text-gray-800">{{ $viewingStaff->CellPers }}</span></div>
                        @endif
                        @if($viewingStaff->EmailPers)
                        <div class="flex"><span class="w-20 text-gray-600">Email:</span><span class="text-gray-800">{{ $viewingStaff->EmailPers }}</span></div>
                        @endif
                        @if(!$viewingStaff->TelPers && !$viewingStaff->CellPers && !$viewingStaff->EmailPers)
                        <p class="text-gray-400 italic">Nessun contatto disponibile</p>
                        @endif
                    </div>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-4 md:col-span-2">
                    <h3 class="text-md font-semibold text-gray-800 mb-3 border-b pb-2">
                        <svg class="inline-block w-4 h-4 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Indirizzo
                    </h3>
                    <div class="space-y-2">
                        <div class="flex"><span class="w-20 text-gray-600">Indirizzo:</span><span class="text-gray-800">{{ $viewingStaff->IndirPers ?: '-' }}</span></div>
                        <div class="flex"><span class="w-20 text-gray-600">Città:</span><span class="text-gray-800">{{ $viewingStaff->CittaPers ?: '-' }}</span></div>
                        <div class="flex"><span class="w-20 text-gray-600">Provincia:</span><span class="text-gray-800">{{ $viewingStaff->ProvPers ?: '-' }}</span></div>
                        <div class="flex"><span class="w-20 text-gray-600">CAP:</span><span class="text-gray-800">{{ $viewingStaff->CapPers ?: '-' }}</span></div>
                    </div>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-md font-semibold text-gray-800 mb-3 border-b pb-2">
                        <svg class="inline-block w-4 h-4 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Stato
                    </h3>
                    <div>
                        <span class="px-2 py-1 text-xs rounded-full {{ $viewingStaff->valid ? 'bg-lime-100 text-lime-800' : 'bg-red-100 text-red-800' }}">
                            {{ $viewingStaff->valid ? 'Attivo' : 'Disattivo' }}
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end mt-6 pt-4 border-t">
                <button wire:click="closeViewModal" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">Chiudi</button>
            </div>
        </div>
    </div>
    @endif
</div>