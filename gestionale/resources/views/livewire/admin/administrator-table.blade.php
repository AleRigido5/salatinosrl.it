<div>
    <!-- Header con Bottone Cestino -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-user-shield mr-2 text-lime-600"></i> Gestione Amministratori
            </h1>
            <p class="text-gray-500 mt-1">Gestione degli utenti con accesso al pannello di amministrazione</p>
        </div>
        <div class="flex gap-3">
            @if(auth()->guard('admin')->user()->hasPermission('create_administrators'))
            <a href="{{ route('admin.administrators.create') }}" 
               class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-5 py-2.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 flex items-center">
                <i class="fas fa-plus"></i>
            </a>
            @endif

            <!-- Bottone Cestino con contatore -->
            <button wire:click="openTrashModal" class="relative px-5 py-2.5 rounded-lg shadow-md transition-all duration-200 bg-gray-200 text-gray-700 hover:bg-gray-300">
                <i class="fas fa-trash-alt"></i>
                @if($trashCount > 0)
                    <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                        {{ $trashCount }}
                    </span>
                @endif
            </button>
        </div>
    </div>

    <!-- Filtri e Ricerca -->
    <div class="bg-white rounded-lg shadow mb-6 p-4 border border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="relative md:col-span-2">
                <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       placeholder="Cerca per nome, email o telefono..." 
                       class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent">
            </div>
            
            <select wire:model.live="roleFilter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                <option value="">Tutti i ruoli</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
            </select>
            
            <select wire:model.live="statusFilter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                <option value="">Tutti gli stati</option>
                <option value="active">Attivi</option>
                <option value="inactive">Disattivi</option>
            </select>
        </div>
        
        <div class="flex justify-between items-center mt-4">
            @if($search || $roleFilter || $statusFilter)
            <button wire:click="resetFilters" class="text-sm text-gray-600 hover:text-gray-900 transition-colors">
                <i class="fas fa-sync-alt mr-1"></i>
                Resetta filtri
            </button>
            @endif
        </div>
        
        @if($search || $roleFilter || $statusFilter)
        <div class="mt-3 flex flex-wrap items-center gap-2">
            <span class="text-sm text-gray-500">Filtri attivi:</span>
            @if($search)
            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-lime-100 text-lime-800">
                <i class="fas fa-search mr-1"></i> "{{ $search }}"
                <button wire:click="$set('search', '')" class="ml-1 hover:text-lime-900">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </span>
            @endif
            @if($roleFilter)
            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-lime-100 text-lime-800">
                <i class="fas fa-tag mr-1"></i> {{ $roles->firstWhere('id', $roleFilter)->name ?? '' }}
                <button wire:click="$set('roleFilter', '')" class="ml-1 hover:text-lime-900">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </span>
            @endif
            @if($statusFilter)
            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-lime-100 text-lime-800">
                <i class="fas fa-filter mr-1"></i> {{ $statusFilter === 'active' ? 'Attivi' : 'Disattivi' }}
                <button wire:click="$set('statusFilter', '')" class="ml-1 hover:text-lime-900">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </span>
            @endif
        </div>
        @endif
    </div>

    <!-- Tabella Amministratori -->
    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('name')">
                            <div class="flex items-center space-x-1">
                                <span>Amministratore</span>
                                @if($sortField === 'name')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('role_id')">
                            <div class="flex items-center space-x-1">
                                <span>Ruolo</span>
                                @if($sortField === 'role_id')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telefono</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('is_active')">
                            <div class="flex items-center space-x-1">
                                <span>Stato</span>
                                @if($sortField === 'is_active')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('last_login_at')">
                            <div class="flex items-center space-x-1">
                                <span>Ultimo Accesso</span>
                                @if($sortField === 'last_login_at')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($administrators as $admin)
                    <tr wire:key="admin-{{ $admin->id }}" class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center shadow-md
                                    @if($admin->role && $admin->role->slug == 'super_admin') bg-gradient-to-r from-red-500 to-red-600
                                    @elseif($admin->role && $admin->role->slug == 'admin') bg-gradient-to-r from-lime-500 to-lime-600
                                    @else bg-gradient-to-r from-gray-500 to-gray-600
                                    @endif">
                                    <span class="text-white font-semibold">{{ strtoupper(substr($admin->name, 0, 1)) }}</span>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $admin->name }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <i class="fas fa-envelope mr-1 text-gray-400"></i> {{ $admin->email }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full 
                                @if($admin->role && $admin->role->slug == 'super_admin') bg-red-100 text-red-700
                                @elseif($admin->role && $admin->role->slug == 'admin') bg-lime-100 text-lime-700
                                @elseif($admin->role && $admin->role->slug == 'editor') bg-blue-100 text-blue-700
                                @else bg-gray-100 text-gray-700
                                @endif">
                                <i class="fas 
                                    @if($admin->role && $admin->role->slug == 'super_admin') fa-crown
                                    @elseif($admin->role && $admin->role->slug == 'admin') fa-user-shield
                                    @elseif($admin->role && $admin->role->slug == 'editor') fa-edit
                                    @else fa-eye
                                    @endif mr-1"></i>
                                {{ $admin->role->name ?? 'Nessun ruolo' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($admin->phone)
                                <i class="fas fa-phone mr-1 text-gray-400"></i> {{ $admin->phone }}
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button wire:click="toggleStatus({{ $admin->id }})" 
                                    class="px-2 py-1 text-xs font-medium rounded-md transition-colors duration-200
                                        {{ $admin->is_active ? 'bg-lime-100 text-lime-800 hover:bg-lime-200' : 'bg-red-100 text-red-800 hover:bg-red-200' }}">
                                {{ $admin->is_active ? 'Attivo' : 'Disattivo' }}
                            </button>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <i class="fas fa-clock mr-1 text-gray-400"></i>
                            {{ $admin->last_login_at ? $admin->last_login_at->format('d/m/Y H:i') : 'Mai' }}
                        </td>
                        <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                            <div class="flex space-x-3">
                                @if(auth()->guard('admin')->user()->hasPermission('view_administrators'))
                                <a href="{{ route('admin.administrators.show', $admin) }}" 
                                   class="text-blue-600 hover:text-blue-900 transition-colors text-base"
                                   title="Visualizza">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @endif
                                
                                @if(auth()->guard('admin')->user()->hasPermission('edit_administrators') && $admin->id != auth()->guard('admin')->id())
                                <a href="{{ route('admin.administrators.edit', $admin) }}" 
                                   class="text-yellow-600 hover:text-yellow-900 transition-colors text-base"
                                   title="Modifica">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endif
                                
                                @if(auth()->guard('admin')->user()->hasPermission('delete_administrators') && $admin->id != auth()->guard('admin')->id() && !($admin->role && $admin->role->slug == 'super_admin'))
                                <button wire:click="confirmDelete({{ $admin->id }})" 
                                        class="text-red-600 hover:text-red-900 transition-colors text-base"
                                        title="Sposta nel cestino">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="text-gray-500">
                                <i class="fas fa-user-shield text-4xl mb-2 text-gray-400"></i>
                                <p class="mt-2 text-sm">Nessun amministratore trovato</p>
                                @if($search || $roleFilter || $statusFilter)
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
    @if($administrators->hasPages())
    <div class="mt-6">
        <div class="text-sm text-gray-500 mb-2">
            Mostrando {{ $administrators->firstItem() ?? 0 }} - {{ $administrators->lastItem() ?? 0 }} di {{ $administrators->total() }} risultati
        </div>
        <div class="flex justify-center">
            {{ $administrators->links() }}
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

    <!-- Modal Conferma Soft Delete -->
    @if($confirmingDelete)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" 
         x-data="{ open: true }" 
         x-show="open"
         x-init="$watch('open', value => { if (!value) $wire.cancelDelete() })"
         @keydown.escape.window="open = false">
        
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6" x-on:click.away="open = false">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-amber-100 mb-4">
                    <i class="fas fa-trash-alt text-amber-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Conferma eliminazione</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Sei sicuro di voler spostare nel cestino <strong>{{ $selectedAdminName }}</strong>?
                    <br>
                    <span class="text-xs text-gray-400">L'utente verrà spostato nel cestino e potrà essere ripristinato.</span>
                </p>
                <div class="flex justify-center space-x-3">
                    <button wire:click="cancelDelete" 
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                        Annulla
                    </button>
                    <button wire:click="deleteAdministrator" 
                            class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-md transition-colors">
                        Sposta nel cestino
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Conferma Restore -->
    @if($confirmingRestore)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" 
         x-data="{ open: true }" 
         x-show="open"
         x-init="$watch('open', value => { if (!value) $wire.cancelDelete() })"
         @keydown.escape.window="open = false">
        
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6" x-on:click.away="open = false">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                    <i class="fas fa-trash-restore text-green-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Conferma ripristino</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Sei sicuro di voler ripristinare <strong>{{ $selectedAdminName }}</strong>?
                </p>
                <div class="flex justify-center space-x-3">
                    <button wire:click="cancelDelete" 
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                        Annulla
                    </button>
                    <button wire:click="restoreAdministrator" 
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md transition-colors">
                        Ripristina
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Conferma Force Delete -->
    @if($confirmingForceDelete)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" 
         x-data="{ open: true }" 
         x-show="open"
         x-init="$watch('open', value => { if (!value) $wire.cancelDelete() })"
         @keydown.escape.window="open = false">
        
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6" x-on:click.away="open = false">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-skull-crossbones text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Eliminazione permanente</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Sei sicuro di voler eliminare PERMANENTEMENTE <strong>{{ $selectedAdminName }}</strong>?
                    <br>
                    <span class="text-xs text-red-500">⚠️ Questa azione è irreversibile!</span>
                </p>
                <div class="flex justify-center space-x-3">
                    <button wire:click="cancelDelete" 
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                        Annulla
                    </button>
                    <button wire:click="forceDeleteAdministrator" 
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md transition-colors">
                        Elimina definitivamente
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL Cestino -->
    @if($showTrashModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" 
         x-data="{ open: true }" 
         x-show="open"
         x-init="$watch('open', value => { if (!value) $wire.closeTrashModal() })"
         @keydown.escape.window="open = false">
        
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="open = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white px-4 pt-4 pb-2 sm:px-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-trash-alt mr-2 text-red-600"></i>
                            Cestino - Amministratori Eliminati
                        </h2>
                        <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-2xl"></i>
                        </button>
                    </div>
                </div>
                
                <div class="px-4 pt-4 pb-2 sm:px-6 max-h-[70vh] overflow-y-auto">
                    <!-- Filtri Cestino -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <div class="grid grid-cols-1 gap-4">
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                                <input type="text" 
                                       wire:model.live="trashSearch" 
                                       placeholder="Cerca nel cestino..." 
                                       class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                            </div>
                        </div>
                        
                        @if($trashSearch)
                        <div class="mt-3">
                            <button wire:click="resetTrashFilters" class="text-sm text-lime-600 hover:text-lime-800">
                                <i class="fas fa-sync-alt mr-1"></i> Resetta filtri
                            </button>
                        </div>
                        @endif
                    </div>
                    
                    <!-- Tabella Elementi Cancellati -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="trashSortBy('name')">
                                        <div class="flex items-center space-x-1">
                                            <span>Amministratore</span>
                                            @if($trashSortField === 'name')
                                                <i class="fas fa-arrow-{{ $trashSortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                            @endif
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="trashSortBy('email')">
                                        <div class="flex items-center space-x-1">
                                            <span>Email</span>
                                            @if($trashSortField === 'email')
                                                <i class="fas fa-arrow-{{ $trashSortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                            @endif
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="trashSortBy('role_id')">
                                        <div class="flex items-center space-x-1">
                                            <span>Ruolo</span>
                                            @if($trashSortField === 'role_id')
                                                <i class="fas fa-arrow-{{ $trashSortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                            @endif
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="trashSortBy('deleted_at')">
                                        <div class="flex items-center space-x-1">
                                            <span>Data eliminazione</span>
                                            @if($trashSortField === 'deleted_at')
                                                <i class="fas fa-arrow-{{ $trashSortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                            @endif
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($trashedAdministrators as $admin)
                                <tr wire:key="trash-{{ $admin->id }}" class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $admin->name }}
                                        </div>
                                    </div>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ $admin->email }}
                                    </div>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700">
                                            <i class="fas fa-user-shield mr-1"></i>
                                            {{ $admin->role->name ?? 'Nessun ruolo' }}
                                        </span>
                                    </div>
                                    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                        {{ $admin->deleted_at ? $admin->deleted_at->format('d/m/Y H:i') : '-' }}
                                    </div>
                                    <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                        <div class="flex space-x-3">
                                            <button wire:click="confirmRestore({{ $admin->id }})" 
                                                    class="text-green-600 hover:text-green-900 transition-colors text-base"
                                                    title="Ripristina">
                                                <i class="fas fa-trash-restore"></i>
                                            </button>
                                            <button wire:click="confirmForceDelete({{ $admin->id }})" 
                                                    class="text-red-600 hover:text-red-900 transition-colors text-base"
                                                    title="Elimina definitivamente">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <div class="text-gray-500">
                                            <i class="fas fa-trash-alt text-gray-400 text-5xl mb-2"></i>
                                            <p class="text-sm">Il cestino è vuoto</p>
                                            <p class="text-xs text-gray-400 mt-1">Nessun amministratore eliminato</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginazione Cestino -->
                    @if($trashedAdministrators->hasPages())
                    <div class="mt-6">
                        <div class="text-sm text-gray-500 mb-2">
                            Mostrando {{ $trashedAdministrators->firstItem() ?? 0 }} - {{ $trashedAdministrators->lastItem() ?? 0 }} di {{ $trashedAdministrators->total() }} amministratori nel cestino
                        </div>
                        <div class="flex justify-center">
                            {{ $trashedAdministrators->links() }}
                        </div>
                    </div>
                    @endif
                </div>
                
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex justify-end">
                    <button @click="open = false" 
                            class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm">
                        <i class="fas fa-times mr-2"></i> Chiudi
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>