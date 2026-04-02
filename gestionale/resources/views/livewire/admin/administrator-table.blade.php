<div>
    <!-- Filtri e Ricerca Live -->
    <div class="bg-gradient-to-r from-white to-emerald-50 rounded-xl shadow-md mb-6 p-5 border border-emerald-100">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Ricerca live -->
            <div class="relative md:col-span-2">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       placeholder="Cerca per nome, email o telefono..." 
                       class="w-full pl-10 pr-3 py-2 border border-emerald-200 rounded-lg focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition">
            </div>
            
            <!-- Filtro ruolo -->
            <select wire:model.live="roleFilter" class="px-3 py-2 border border-emerald-200 rounded-lg focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200">
                <option value="">Tutti i ruoli</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
            </select>
            
            <!-- Filtro stato -->
            <select wire:model.live="statusFilter" class="px-3 py-2 border border-emerald-200 rounded-lg focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200">
                <option value="">Tutti gli stati</option>
                <option value="active">Attivi</option>
                <option value="inactive">Disattivi</option>
            </select>
        </div>
        
        <!-- Seconda riga: elementi per pagina e reset filtri -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div class="flex items-center space-x-3">
                <span class="text-sm text-gray-500">Mostra:</span>
                <select wire:model.live="perPage" class="px-3 py-2 border border-emerald-200 rounded-lg focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="text-sm text-gray-500">elementi per pagina</span>
            </div>
            
            <div class="flex justify-end">
                @if($search || $roleFilter || $statusFilter)
                <button wire:click="resetFilters" class="text-emerald-600 hover:text-emerald-800 text-sm transition-colors">
                    <i class="fas fa-undo-alt mr-1"></i> Resetta tutti i filtri
                </button>
                @endif
            </div>
        </div>
        
        <!-- Info ricerca attiva -->
        @if($search || $roleFilter || $statusFilter)
        <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
            <span class="text-gray-500">Filtri attivi:</span>
            @if($search)
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-emerald-100 text-emerald-700">
                <i class="fas fa-search mr-1"></i> "{{ $search }}"
                <button wire:click="$set('search', '')" class="ml-1 hover:text-emerald-900">
                    <i class="fas fa-times-circle"></i>
                </button>
            </span>
            @endif
            @if($roleFilter)
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-emerald-100 text-emerald-700">
                <i class="fas fa-tag mr-1"></i> {{ $roles->firstWhere('id', $roleFilter)->name ?? '' }}
                <button wire:click="$set('roleFilter', '')" class="ml-1 hover:text-emerald-900">
                    <i class="fas fa-times-circle"></i>
                </button>
            </span>
            @endif
            @if($statusFilter)
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-emerald-100 text-emerald-700">
                <i class="fas fa-filter mr-1"></i> {{ $statusFilter === 'active' ? 'Attivi' : 'Disattivi' }}
                <button wire:click="$set('statusFilter', '')" class="ml-1 hover:text-emerald-900">
                    <i class="fas fa-times-circle"></i>
                </button>
            </span>
            @endif
        </div>
        @endif
    </div>

    <!-- Tabella Amministratori -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-emerald-100">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-emerald-50 to-green-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wider cursor-pointer hover:bg-emerald-100 transition" wire:click="sortBy('name')">
                            <div class="flex items-center space-x-1">
                                <span>Amministratore</span>
                                @if($sortField === 'name')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-emerald-500"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wider cursor-pointer hover:bg-emerald-100 transition" wire:click="sortBy('role_id')">
                            <div class="flex items-center space-x-1">
                                <span>Ruolo</span>
                                @if($sortField === 'role_id')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-emerald-500"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wider">Telefono</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wider cursor-pointer hover:bg-emerald-100 transition" wire:click="sortBy('is_active')">
                            <div class="flex items-center space-x-1">
                                <span>Stato</span>
                                @if($sortField === 'is_active')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-emerald-500"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wider cursor-pointer hover:bg-emerald-100 transition" wire:click="sortBy('last_login_at')">
                            <div class="flex items-center space-x-1">
                                <span>Ultimo Accesso</span>
                                @if($sortField === 'last_login_at')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-emerald-500"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($administrators as $admin)
                    <tr class="hover:bg-emerald-50/30 transition-colors duration-150">
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-green-500 rounded-full flex items-center justify-center shadow-md">
                                    <span class="text-white font-semibold">{{ strtoupper(substr($admin->name, 0, 1)) }}</span>
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-800">{{ $admin->name }}</div>
                                    <div class="text-sm text-gray-500">
                                        <i class="fas fa-envelope mr-1 text-gray-400"></i> {{ $admin->email }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full 
                                @if($admin->role && $admin->role->slug == 'super_admin') bg-red-100 text-red-700
                                @elseif($admin->role && $admin->role->slug == 'admin') bg-emerald-100 text-emerald-700
                                @elseif($admin->role && $admin->role->slug == 'editor') bg-green-100 text-green-700
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
                        <td class="px-6 py-4 text-sm text-gray-600">
                            @if($admin->phone)
                                <i class="fas fa-phone mr-1 text-gray-400"></i> {{ $admin->phone }}
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <button wire:click="toggleStatus({{ $admin->id }})" 
                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full transition-all duration-200
                                        {{ $admin->is_active ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-red-100 text-red-800 hover:bg-red-200' }}">
                                <i class="fas {{ $admin->is_active ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                                {{ $admin->is_active ? 'Attivo' : 'Disattivo' }}
                            </button>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <i class="far fa-clock mr-1 text-emerald-500"></i>
                            {{ $admin->last_login_at ? $admin->last_login_at->format('d/m/Y H:i') : 'Mai' }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <div class="flex space-x-2">
                                @if(auth()->guard('admin')->user()->hasPermission('view_administrators'))
                                <a href="{{ route('admin.administrators.show', $admin) }}" 
                                   class="text-emerald-600 hover:text-emerald-800 transition-colors p-1.5 rounded-lg hover:bg-emerald-50"
                                   title="Visualizza">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @endif
                                
                                @if(auth()->guard('admin')->user()->hasPermission('edit_administrators') && $admin->id != auth()->guard('admin')->id())
                                <a href="{{ route('admin.administrators.edit', $admin) }}" 
                                   class="text-lime-600 hover:text-lime-800 transition-colors p-1.5 rounded-lg hover:bg-lime-50"
                                   title="Modifica">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endif
                                
                                @if(auth()->guard('admin')->user()->hasPermission('delete_administrators') && $admin->id != auth()->guard('admin')->id() && !$admin->isSuperAdmin())
                                <button wire:click="deleteAdministrator({{ $admin->id }})" 
                                        onclick="return confirm('Sei sicuro di voler eliminare questo amministratore?')"
                                        class="text-red-600 hover:text-red-800 transition-colors p-1.5 rounded-lg hover:bg-red-50"
                                        title="Elimina">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center text-gray-400">
                                <i class="fas fa-user-shield text-5xl mb-3"></i>
                                <p class="text-lg">Nessun amministratore trovato</p>
                                @if($search || $roleFilter || $statusFilter)
                                <button wire:click="resetFilters" class="mt-2 text-emerald-600 hover:text-emerald-800">
                                    <i class="fas fa-undo-alt mr-1"></i> Resetta filtri
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Paginazione -->
        <div class="px-6 py-4 border-t border-emerald-100 bg-emerald-50/20">
            <div class="flex justify-between items-center flex-wrap gap-3">
                <div class="text-sm text-gray-500">
                    <i class="fas fa-chart-line mr-1 text-emerald-500"></i>
                    Mostrando {{ $administrators->firstItem() ?? 0 }} - {{ $administrators->lastItem() ?? 0 }} di {{ $administrators->total() }} risultati
                </div>
                <div>
                    {{ $administrators->links() }}
                </div>
            </div>
        </div>
    </div>
</div>