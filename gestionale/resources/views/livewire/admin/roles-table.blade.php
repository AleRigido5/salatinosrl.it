<div>
    <!-- Header con Bottone Cestino -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-shield-alt mr-2 text-lime-600"></i> Gestione Ruoli
            </h1>
            <p class="text-gray-500 mt-1">Gestione dei ruoli e dei permessi</p>
        </div>
        <div class="flex gap-3">    
            @if(auth()->guard('admin')->user()->hasPermission('create_roles'))
            <a href="{{ route('admin.roles.create') }}" 
               class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-5 py-2.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                <i class="fas fa-plus"></i>
            </a>
            @endif

            <!-- Bottone Cestino con contatore -->
            <button wire:click="openTrashModal" class="relative px-5 py-2.5 rounded-lg shadow-md transition-all duration-200 bg-gray-200 text-gray-700 hover:bg-gray-300">
                <i class="fas fa-trash-alt"></i>
                @php
                    $trashCount = \App\Models\Role::onlyTrashed()->count();
                @endphp
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
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="relative md:col-span-2">
                <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                <input type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Cerca per nome o slug..." 
                    class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent">
            </div>
        </div>
        
        <div class="flex justify-between items-center mt-4">
            @if($search)
            <button wire:click="resetFilters" class="text-sm text-gray-600 hover:text-gray-900 transition-colors">
                <i class="fas fa-sync-alt mr-1"></i>
                Resetta filtri
            </button>
            @endif
        </div>
        
        @if($search)
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
        </div>
        @endif
    </div>

    <!-- Tabella Ruoli -->
    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('name')">
                            <div class="flex items-center space-x-1">
                                <span>Ruolo</span>
                                @if($sortField === 'name')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('slug')">
                            <div class="flex items-center space-x-1">
                                <span>Identificativo</span>
                                @if($sortField === 'slug')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('level')">
                            <div class="flex items-center space-x-1">
                                <span>Livello</span>
                                @if($sortField === 'level')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amministratori Associati</th>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($roles as $role)
                    <tr wire:key="role-{{ $role->id }}" class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4">
                            <div>
                                <div class="text-sm font-medium text-gray-900">
                                    <i class="fas fa-shield-alt text-lime-500 mr-2"></i>
                                    {{ $role->name }}
                                </div>
                                @if($role->description)
                                <div class="text-xs text-gray-500 mt-1">{{ $role->description }}</div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <code class="text-sm bg-gray-100 text-gray-700 px-2 py-1 rounded">{{ $role->slug }}</code>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-r from-lime-400 to-green-500 flex items-center justify-center text-white font-bold text-sm shadow-md">
                                    {{ $role->level }}
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                <i class="fas fa-users mr-1"></i> {{ $role->administrators_count }} amministratori
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <button wire:click="toggleStatus({{ $role->id }})" 
                                    class="px-2 py-1 text-xs font-medium rounded-md transition-colors duration-200
                                        {{ $role->is_active ? 'bg-lime-100 text-lime-800 hover:bg-lime-200' : 'bg-red-100 text-red-800 hover:bg-red-200' }}">
                                {{ $role->is_active ? 'Attivo' : 'Disattivo' }}
                            </button>
                        </td>
                        <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                            <div class="flex space-x-3">
                                <!-- Modifica -->
                                @if(auth()->guard('admin')->user()->hasPermission('edit_roles') && $role->slug != 'super_admin')
                                <a href="{{ route('admin.roles.edit', $role) }}" 
                                class="text-yellow-600 hover:text-yellow-900 transition-colors text-base"
                                title="Modifica">
                                    <i class="fas fa-pen-to-square"></i>
                                </a>
                                @endif
                                
                                <!-- Elimina -->
                                @if(auth()->guard('admin')->user()->hasPermission('delete_roles') && !in_array($role->slug, ['super_admin', 'admin', 'editor', 'viewer']))
                                <button wire:click="confirmDelete({{ $role->id }})" 
                                        class="text-red-600 hover:text-red-900 transition-colors text-base"
                                        title="Elimina">
                                    <i class="fas fa-trash-can"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="text-gray-500">
                                <i class="fas fa-shield-alt text-4xl mb-2 text-gray-400"></i>
                                <p class="mt-2 text-sm">Nessun ruolo trovato</p>
                                @if($search)
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
    @if($roles->hasPages())
    <div class="mt-6">
        <div class="text-sm text-gray-500 mb-2">
            Mostrando {{ $roles->firstItem() ?? 0 }} - {{ $roles->lastItem() ?? 0 }} di {{ $roles->total() }} risultati
        </div>
        <div class="flex justify-center">
            {{ $roles->links() }}
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

    <!-- MODALE CONFERMA ELIMINAZIONE -->
    @if($showDeleteModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" 
         x-data="{ open: true }" 
         x-show="open"
         x-init="$watch('open', value => { if (!value) $wire.cancelDelete() })"
         @keydown.escape.window="open = false">
        
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6" 
             x-on:click.away="open = false">
            
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Conferma eliminazione</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Sei sicuro di voler eliminare il ruolo <strong>{{ $roleNameToDelete }}</strong>?
                    <br>
                    <span class="text-xs text-gray-400">Il ruolo verrà spostato nel cestino e potrà essere ripristinato.</span>
                </p>
                <div class="flex justify-center space-x-3">
                    <button wire:click="cancelDelete" 
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                        Annulla
                    </button>
                    <button wire:click="deleteRole" 
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md transition-colors">
                        Elimina
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
                            Cestino - Ruoli Eliminati
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
                                            <span>Ruolo</span>
                                            @if($trashSortField === 'name')
                                                <i class="fas fa-arrow-{{ $trashSortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                            @endif
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="trashSortBy('slug')">
                                        <div class="flex items-center space-x-1">
                                            <span>Slug</span>
                                            @if($trashSortField === 'slug')
                                                <i class="fas fa-arrow-{{ $trashSortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                            @endif
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="trashSortBy('level')">
                                        <div class="flex items-center space-x-1">
                                            <span>Livello</span>
                                            @if($trashSortField === 'level')
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
                                @forelse($trashedRoles as $role)
                                <tr wire:key="trash-{{ $role->id }}" class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $role->name }}
                                        </div>
                                        @if($role->description)
                                        <div class="text-xs text-gray-500">{{ $role->description }}</div>
                                        @endif
                                    </div>
                                    <td class="px-6 py-4">
                                        <code class="text-sm bg-gray-100 text-gray-700 px-2 py-1 rounded">{{ $role->slug }}</code>
                                    </div>
                                    <td class="px-6 py-4">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-r from-lime-400 to-green-500 flex items-center justify-center text-white font-bold text-sm shadow-md">
                                            {{ $role->level }}
                                        </div>
                                    </div>
                                    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                        {{ $role->deleted_at ? $role->deleted_at->format('d/m/Y H:i') : '-' }}
                                    </div>
                                    <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                        <div class="flex space-x-3">
                                            <button wire:click="restoreFromTrash({{ $role->id }})" 
                                                    class="text-green-600 hover:text-green-900 transition-colors text-base"
                                                    title="Ripristina">
                                                <i class="fas fa-trash-restore"></i>
                                            </button>
                                            <button wire:click="forceDeleteFromTrash({{ $role->id }})" 
                                                    onclick="return confirm('Eliminazione definitiva? Questa operazione non può essere annullata.')"
                                                    class="text-red-600 hover:text-red-900 transition-colors text-base"
                                                    title="Elimina definitivamente">
                                                <i class="fas fa-skull-crossbones"></i>
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
                                            <p class="text-xs text-gray-400 mt-1">Nessun ruolo eliminato</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginazione Cestino -->
                    @if($trashedRoles->hasPages())
                    <div class="mt-6">
                        <div class="text-sm text-gray-500 mb-2">
                            Mostrando {{ $trashedRoles->firstItem() ?? 0 }} - {{ $trashedRoles->lastItem() ?? 0 }} di {{ $trashedRoles->total() }} ruoli nel cestino
                        </div>
                        <div class="flex justify-center">
                            {{ $trashedRoles->links() }}
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