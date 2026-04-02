<div>
    <!-- Filtri e Ricerca Live -->
    <div class="bg-gradient-to-r from-white to-emerald-50 rounded-xl shadow-md mb-6 p-5 border border-emerald-100">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Ricerca live con autocomplete -->
            <div class="relative md:col-span-2">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       placeholder="Cerca per nome, slug o descrizione..." 
                       class="w-full pl-10 pr-3 py-2 border border-emerald-200 rounded-lg focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition">
                
                <!-- Suggerimenti autocomplete -->
                @if(strlen($search) > 2 && !empty($search))
                <div class="absolute z-10 w-full mt-1 bg-white rounded-lg shadow-lg border border-emerald-100 max-h-60 overflow-y-auto">
                    @php
                        $suggestions = App\Models\Role::where('name', 'like', '%'.$search.'%')
                            ->orWhere('slug', 'like', '%'.$search.'%')
                            ->limit(5)
                            ->get();
                    @endphp
                    @foreach($suggestions as $suggestion)
                    <div class="px-4 py-2 hover:bg-emerald-50 cursor-pointer transition-colors"
                         wire:click="$set('search', '{{ $suggestion->name }}')">
                        <div class="font-medium text-gray-800">{{ $suggestion->name }}</div>
                        <div class="text-xs text-gray-500">{{ $suggestion->slug }}</div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            
            <!-- Filtro stato -->
            <select wire:model.live="status" class="px-3 py-2 border border-emerald-200 rounded-lg focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200">
                <option value="">Tutti gli stati</option>
                <option value="active">Attivi</option>
                <option value="inactive">Disattivi</option>
            </select>
            
            <!-- Select per elementi per pagina -->
            <select wire:model.live="perPage" class="px-3 py-2 border border-emerald-200 rounded-lg focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200">
                <option value="10">10 per pagina</option>
                <option value="15">15 per pagina</option>
                <option value="25">25 per pagina</option>
                <option value="50">50 per pagina</option>
                <option value="100">100 per pagina</option>
            </select>
        </div>
        
        <!-- Info ricerca attiva -->
        @if($search || $status)
        <div class="mt-3 flex items-center space-x-2 text-sm">
            <span class="text-gray-500">Filtri attivi:</span>
            @if($search)
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-emerald-100 text-emerald-700">
                <i class="fas fa-search mr-1"></i> {{ $search }}
                <button wire:click="$set('search', '')" class="ml-1 hover:text-emerald-900">
                    <i class="fas fa-times-circle"></i>
                </button>
            </span>
            @endif
            @if($status)
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-emerald-100 text-emerald-700">
                <i class="fas fa-filter mr-1"></i> {{ $status === 'active' ? 'Attivi' : 'Disattivi' }}
                <button wire:click="$set('status', '')" class="ml-1 hover:text-emerald-900">
                    <i class="fas fa-times-circle"></i>
                </button>
            </span>
            @endif
            <button wire:click="resetFilters" class="text-emerald-600 hover:text-emerald-800 text-xs">
                <i class="fas fa-undo-alt mr-1"></i> Reset filtri
            </button>
        </div>
        @endif
    </div>

    <!-- Tabella Ruoli -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-emerald-100">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-emerald-50 to-green-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wider cursor-pointer hover:bg-emerald-100 transition" wire:click="sortBy('name')">
                            <div class="flex items-center space-x-1">
                                <span>Ruolo</span>
                                @if($sortField === 'name')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-emerald-500"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wider cursor-pointer hover:bg-emerald-100 transition" wire:click="sortBy('slug')">
                            <div class="flex items-center space-x-1">
                                <span>Slug</span>
                                @if($sortField === 'slug')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-emerald-500"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wider cursor-pointer hover:bg-emerald-100 transition" wire:click="sortBy('level')">
                            <div class="flex items-center space-x-1">
                                <span>Livello</span>
                                @if($sortField === 'level')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-emerald-500"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wider">Admin Associati</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wider">Stato</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($roles as $role)
                    <tr class="hover:bg-emerald-50/30 transition-colors duration-150">
                        <td class="px-6 py-4">
                            <div>
                                <div class="font-semibold text-gray-800">
                                    <i class="fas fa-shield-alt text-emerald-500 mr-2"></i>
                                    {{ $role->name }}
                                </div>
                                <div class="text-sm text-gray-500 mt-1">{{ $role->description ?? 'Nessuna descrizione' }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <code class="text-sm bg-emerald-100 text-emerald-700 px-2 py-1 rounded">{{ $role->slug }}</code>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-r from-emerald-400 to-green-500 flex items-center justify-center text-white font-bold text-sm shadow-md">
                                    {{ $role->level }}
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-emerald-100 text-emerald-800">
                                <i class="fas fa-users mr-1"></i> {{ $role->administrators_count }} admin
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <button wire:click="toggleStatus({{ $role->id }})" 
                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full transition-all duration-200
                                        {{ $role->is_active ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-red-100 text-red-800 hover:bg-red-200' }}">
                                <i class="fas {{ $role->is_active ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                                {{ $role->is_active ? 'Attivo' : 'Disattivo' }}
                            </button>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <div class="flex space-x-2">
                                @if(auth()->guard('admin')->user()->hasPermission('edit_roles') && $role->slug != 'super_admin')
                                <a href="{{ route('admin.roles.edit', $role) }}" 
                                   class="text-lime-600 hover:text-lime-800 transition-colors p-1.5 rounded-lg hover:bg-lime-50"
                                   title="Modifica">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endif
                                
                                @if(auth()->guard('admin')->user()->hasPermission('delete_roles') && !in_array($role->slug, ['super_admin', 'admin', 'editor', 'viewer']))
                                <button wire:click="deleteRole({{ $role->id }})" 
                                        wire:confirm="Sei sicuro di voler eliminare questo ruolo?"
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
                                <i class="fas fa-shield-alt text-5xl mb-3"></i>
                                <p class="text-lg">Nessun ruolo trovato</p>
                                @if($search || $status)
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
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-500">
                    Mostrando {{ $roles->firstItem() ?? 0 }} - {{ $roles->lastItem() ?? 0 }} di {{ $roles->total() }} risultati
                </div>
                <div>
                    {{ $roles->links() }}
                </div>
            </div>
        </div>
    </div>
    
    <!-- Script per notifiche -->
    @push('scripts')
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('success', (message) => {
                const alert = document.createElement('div');
                alert.className = 'fixed top-4 right-4 z-50 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded-lg shadow-lg animate-pulse';
                alert.innerHTML = '<i class="fas fa-check-circle mr-2"></i> ' + message;
                document.body.appendChild(alert);
                setTimeout(() => alert.remove(), 3000);
            });
            
            Livewire.on('error', (message) => {
                const alert = document.createElement('div');
                alert.className = 'fixed top-4 right-4 z-50 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-lg shadow-lg';
                alert.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i> ' + message;
                document.body.appendChild(alert);
                setTimeout(() => alert.remove(), 3000);
            });
        });
    </script>
    @endpush
</div>