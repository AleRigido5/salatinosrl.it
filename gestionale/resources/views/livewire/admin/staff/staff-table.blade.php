<div>
    <!-- Filtri e Ricerca -->
    <div class="bg-white rounded-lg shadow mb-6 p-4 border border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="relative md:col-span-2">
                <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                <input type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Cerca per: Nome, Cognome, Soprannome, Cellulare, Email..." 
                    class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent">
            </div>
            
            <select wire:model.live="statusFilter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                <option value="active">Attivi</option>
                <option value="inactive">Disattivi</option>
                <option value="">Tutti</option>
            </select>
            
            <select wire:model.live="gruppoFilter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                <option value="">Tutti i gruppi</option>
                @foreach($staffGroups as $group)
                    <option value="{{ $group->id }}">{{ $group->valore }}</option>
                @endforeach
            </select>
            
            <div class="flex items-center space-x-2">
                <select wire:model.live="perPage" class="text-sm px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                    <option value="15">15 per pagina</option>
                    <option value="25">25 per pagina</option>
                    <option value="50">50 per pagina</option>
                    <option value="100">100 per pagina</option>
                </select>
                
                @if($search || $statusFilter != 'active' || $gruppoFilter)
                <button type="button" wire:click="resetFilters" class="text-sm text-gray-500 hover:text-gray-700" title="Resetta tutti i filtri">
                    <i class="fas fa-sync-alt"></i>
                </button>
                @endif
            </div>
        </div>
        
        @if($search || $statusFilter != 'active' || $gruppoFilter)
        <div class="mt-3 flex flex-wrap items-center gap-2">
            <span class="text-sm text-gray-500">Filtri attivi:</span>
            @if($search)
            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-lime-100 text-lime-800">
                Ricerca: "{{ $search }}"
                <button wire:click="$set('search', '')" class="ml-1 hover:text-lime-900">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </span>
            @endif
            @if($statusFilter != 'active')
            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-lime-100 text-lime-800">
                Stato: {{ $statusFilter === 'active' ? 'Attivi' : ($statusFilter === 'inactive' ? 'Disattivi' : 'Tutti') }}
                <button wire:click="$set('statusFilter', 'active')" class="ml-1 hover:text-lime-900">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </span>
            @endif
            @if($gruppoFilter)
            @php
                $selectedGroup = $staffGroups->firstWhere('id', $gruppoFilter);
            @endphp
            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-lime-100 text-lime-800">
                Gruppo: {{ $selectedGroup->valore ?? $gruppoFilter }}
                <button wire:click="$set('gruppoFilter', '')" class="ml-1 hover:text-lime-900">
                    <i class="fas fa-times text-xs"></i>
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
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('CognomePers')">
                            <div class="flex items-center space-x-1">
                                <span>Nome e Cognome</span>
                                @if($sortField === 'CognomePers')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contatti</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('id_gruppo')">
                            <div class="flex items-center space-x-1">
                                <span>Categoria</span>
                                @if($sortField === 'id_gruppo')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scadenze</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($staff as $person)
                    @php
                        $assunzioneDate = $this->getLatestExpiration($person->id_personale, 'Assunzione');
                        $visitaMedicaDate = $this->getLatestExpiration($person->id_personale, 'Visita medica');
                        
                        $assunzioneInfo = $assunzioneDate ? $this->formatExpirationDate($assunzioneDate->data_fine) : null;
                        $visitaMedicaInfo = $visitaMedicaDate ? $this->formatExpirationDate($visitaMedicaDate->data_fine) : null;
                    @endphp
                    <tr wire:key="staff-{{ $person->id_personale }}" class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $person->id_personale }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="ml-0">
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
                        <!-- COLONNA CATEGORIA (id_gruppo -> settings.valore) -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($person->gruppo)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-lime-100 text-lime-800">
                                    <i class="fas fa-layer-group mr-1 text-xs"></i>
                                    {{ $person->gruppo->valore }}
                                </span>
                            @else
                                <span class="text-gray-400 italic text-xs">Nessuna categoria</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="space-y-2">
                                <!-- Assunzione -->
                                <div class="flex flex-col">
                                    <span class="text-xs font-medium text-gray-500">Assunzione:</span>
                                    @if($assunzioneInfo)
                                        <div class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium {{ $assunzioneInfo['bg_class'] }}">
                                            <i class="fas fa-calendar-alt mr-1"></i>
                                            {{ $assunzioneInfo['formatted'] }}
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400 italic px-2 py-1 bg-gray-100 rounded-md">Nessuna scadenza</span>
                                    @endif
                                </div>
                                <!-- Visita Medica -->
                                <div class="flex flex-col">
                                    <span class="text-xs font-medium text-gray-500">Visita Medica:</span>
                                    @if($visitaMedicaInfo)
                                        <div class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium {{ $visitaMedicaInfo['bg_class'] }}">
                                            <i class="fas fa-calendar-alt mr-1"></i>
                                            {{ $visitaMedicaInfo['formatted'] }}
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400 italic px-2 py-1 bg-gray-100 rounded-md">Nessuna scadenza</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                            <div class="flex space-x-3">
                                <!-- Report Attività -->
                                @if(auth()->guard('admin')->user()->hasPermission('view_activity_report'))
                                <button wire:click="goToActivityReport({{ $person->id_personale }})" 
                                        wire:key="report-{{ $person->id_personale }}"
                                        class="text-indigo-600 hover:text-indigo-900 transition-colors text-base"
                                        title="Report Attività">
                                    <i class="fa-solid fa-clipboard-user"></i>
                                </button>
                                @endif
                                
                                <!-- Visualizza -->
                                @if(auth()->guard('admin')->user()->hasPermission('view_staff'))
                                <button wire:click="viewStaff({{ $person->id_personale }})" 
                                        wire:key="view-{{ $person->id_personale }}"
                                        class="text-blue-600 hover:text-blue-900 transition-colors text-base"
                                        title="Visualizza">
                                    <i class="fa-regular fa-eye text-blue-600 hover:text-blue-900"></i>
                                </button>
                                @endif

                                <!-- Modifica -->
                                @if(auth()->guard('admin')->user()->hasPermission('edit_staff'))
                                <button wire:click="editStaff({{ $person->id_personale }})" 
                                        wire:key="edit-{{ $person->id_personale }}"
                                        class="text-yellow-600 hover:text-yellow-900 transition-colors text-base"
                                        title="Modifica">
                                    <i class="fa-solid fa-pen-to-square text-yellow-600 hover:text-yellow-900"></i>
                                </button>
                                @endif

                                <!-- Gestisci Scadenze -->
                                @if(auth()->guard('admin')->user()->hasPermission('view_expiration'))
                                <button wire:click="goToExpiration({{ $person->id_personale }})" 
                                        wire:key="expiration-{{ $person->id_personale }}"
                                        class="text-purple-600 hover:text-purple-900 transition-colors text-base"
                                        title="Gestisci Scadenze">
                                    <i class="fa-regular fa-calendar text-purple-600 hover:text-purple-900"></i>
                                </button>
                                @endif

                                <!-- Attiva/Disattiva -->
                                @if(auth()->guard('admin')->user()->hasPermission('edit_staff'))
                                <button wire:click="toggleStatus({{ $person->id_personale }})" 
                                        wire:key="status-{{ $person->id_personale }}"
                                        class="transition-colors text-base {{ $person->valid ? 'text-lime-600 hover:text-lime-800' : 'text-gray-400 hover:text-gray-600' }}"
                                        title="{{ $person->valid ? 'Disattiva' : 'Attiva' }}">
                                    <i class="{{ $person->valid ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-xmark text-red-400' }}"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="text-gray-500">
                                <i class="fas fa-users-slash mx-auto h-12 w-12 text-gray-400 text-5xl"></i>
                                <p class="mt-2 text-sm">Nessun personale trovato</p>
                                @if($search || $statusFilter != 'active' || $gruppoFilter)
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
    
    <!-- MODAL VISUALIZZAZIONE -->
    @if($showViewModal && $viewingStaff)
    <div wire:ignore.self class="fixed inset-0 z-50 overflow-y-auto" 
         x-data="{ show: true }" 
         x-show="show" 
         x-transition.opacity.duration.200ms>
        
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-on:click="show = false; $wire.closeViewModal()" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
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
                            <button wire:click="closeViewModal" class="text-gray-400 hover:text-gray-600">
                                <i class="fa-solid fa-xmark text-2xl"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
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
                                <div class="flex"><span class="w-32 text-gray-600">Categoria:</span>
                                    <span class="text-gray-800">
                                        @if($viewingStaff->gruppo)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                                                {{ $viewingStaff->gruppo->valore }}
                                            </span>
                                        @else
                                            <span class="text-gray-400 italic">Nessuna categoria</span>
                                        @endif
                                    </span>
                                </div>
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
                        
                        <div class="bg-gray-50 rounded-lg p-4 md:col-span-2">
                            <h3 class="text-md font-semibold text-gray-800 mb-3 border-b pb-2">
                                <i class="fas fa-history mr-2 text-indigo-500"></i> Tracciamento
                            </h3>
                            <div class="space-y-3">
                                <div class="flex items-start">
                                    <div class="w-28 text-gray-600 text-sm pt-0.5">Inserito da:</div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <i class="fas fa-plus text-green-500"></i>
                                            <span class="text-gray-800 font-medium">
                                                {{ $viewingStaff->createdBy ? $viewingStaff->createdBy->name : 'Sistema' }}
                                            </span>
                                            <span class="text-gray-400">•</span>
                                            <span class="text-gray-500 text-sm">
                                                {{ $viewingStaff->created_at ? $viewingStaff->created_at->format('d/m/Y H:i') : '-' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                @if($viewingStaff->updated_at && $viewingStaff->created_at != $viewingStaff->updated_at)
                                <div class="flex items-start">
                                    <div class="w-28 text-gray-600 text-sm pt-0.5">Modificato da:</div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <i class="fas fa-edit text-blue-500"></i>
                                            <span class="text-gray-800 font-medium">
                                                {{ $viewingStaff->updatedBy ? $viewingStaff->updatedBy->name : 'Sistema' }}
                                            </span>
                                            <span class="text-gray-400">•</span>
                                            <span class="text-gray-500 text-sm">
                                                {{ $viewingStaff->updated_at ? $viewingStaff->updated_at->format('d/m/Y H:i') : '-' }}
                                            </span>
                                        </div>
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
                                            <i class="fas fa-minus-circle text-gray-400"></i>
                                            <span class="text-gray-400 italic">Mai modificato</span>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-col sm:flex-row sm:justify-end gap-3">
                    @if(auth()->guard('admin')->user()->hasPermission('edit_staff'))
                    <button wire:click="editStaff({{ $viewingStaff->id_personale }})" 
                            class="w-full sm:w-auto inline-flex justify-center items-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:text-sm">
                        <i class="fas fa-edit mr-2"></i> Modifica
                    </button>
                    @endif
                    <button wire:click="closeViewModal" 
                            class="w-full sm:w-auto inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm">
                        <i class="fas fa-times mr-2"></i> Chiudi
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
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-center mb-4 border-b pb-3">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-edit mr-2 text-yellow-600"></i> Modifica Personale
                        </h2>
                        <button wire:click="closeEditModal" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-2xl"></i>
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 max-h-[70vh] overflow-y-auto p-2">
                        <div class="md:col-span-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                            <input type="text" wire:model="editNome" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div class="md:col-span-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cognome</label>
                            <input type="text" wire:model="editCognome" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        
                        <div class="md:col-span-8">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Soprannome</label>
                            <input type="text" wire:model="editSoprannome" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div class="md:col-span-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Indirizzo</label>
                            <input type="text" wire:model="editIndirizzo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        
                        <div class="md:col-span-7">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Città</label>
                            <input type="text" wire:model="editCitta" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prov.</label>
                            <input type="text" wire:model="editProvincia" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500" maxlength="2">
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">CAP</label>
                            <input type="text" wire:model="editCap" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500" maxlength="5">
                        </div>
                        
                        <div class="md:col-span-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefono</label>
                            <input type="text" wire:model="editTelefono" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div class="md:col-span-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cellulare</label>
                            <input type="text" wire:model="editCellulare" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div class="md:col-span-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" wire:model="editEmail" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        
                        <div class="md:col-span-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data Nascita</label>
                            <input type="date" wire:model="editDataNascita" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div class="md:col-span-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Luogo Nascita</label>
                            <input type="text" wire:model="editLuogoNascita" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        
                        <div class="md:col-span-8">
                            <label class="block text-sm font-medium text-gray-700 mb-1">IBAN</label>
                            <input type="text" wire:model="editIban" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div class="md:col-span-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Codice Fiscale</label>
                            <input type="text" wire:model="editCodFiscale" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500" maxlength="16">
                        </div>
                        
                        <div class="md:col-span-8">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Gruppo/Categoria</label>
                            <select wire:model="editGruppo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                                <option value="">Seleziona gruppo...</option>
                                @foreach($staffGroups as $group)
                                    <option value="{{ $group->id }}">{{ $group->valore }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-4 flex items-center pt-6">
                            <label class="inline-flex items-center">
                                <input type="checkbox" wire:model="editValid" class="rounded border-gray-300 text-lime-600 focus:ring-lime-500">
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
                        <i class="fas fa-times mr-2"></i> Annulla
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
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-center mb-4 border-b pb-3">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-plus-circle mr-2 text-green-600"></i> Nuovo Personale
                        </h2>
                        <button wire:click="closeCreateModal" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-2xl"></i>
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 max-h-[70vh] overflow-y-auto p-2">
                        <div class="md:col-span-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                            <input type="text" wire:model="createNome" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            @error('createNome') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div class="md:col-span-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cognome</label>
                            <input type="text" wire:model="createCognome" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            @error('createCognome') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="md:col-span-8">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Soprannome</label>
                            <input type="text" wire:model="createSoprannome" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div class="md:col-span-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Indirizzo</label>
                            <input type="text" wire:model="createIndirizzo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        
                        <div class="md:col-span-7">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Città</label>
                            <input type="text" wire:model="createCitta" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prov.</label>
                            <input type="text" wire:model="createProvincia" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500" maxlength="2">
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">CAP</label>
                            <input type="text" wire:model="createCap" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500" maxlength="5">
                        </div>
                        
                        <div class="md:col-span-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefono</label>
                            <input type="text" wire:model="createTelefono" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div class="md:col-span-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cellulare</label>
                            <input type="text" wire:model="createCellulare" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div class="md:col-span-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" wire:model="createEmail" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            @error('createEmail') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="md:col-span-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data Nascita</label>
                            <input type="date" wire:model="createDataNascita" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div class="md:col-span-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Luogo Nascita</label>
                            <input type="text" wire:model="createLuogoNascita" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        
                        <div class="md:col-span-8">
                            <label class="block text-sm font-medium text-gray-700 mb-1">IBAN</label>
                            <input type="text" wire:model="createIban" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div class="md:col-span-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Codice Fiscale</label>
                            <input type="text" wire:model="createCodFiscale" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500" maxlength="16">
                        </div>
                        
                        <div class="md:col-span-8">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Gruppo/Categoria</label>
                            <select wire:model="createGruppo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                                <option value="">Seleziona gruppo...</option>
                                @foreach($staffGroups as $group)
                                    <option value="{{ $group->id }}">{{ $group->valore }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-4 flex items-center pt-6">
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
                        <i class="fas fa-times mr-2"></i> Annulla
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>