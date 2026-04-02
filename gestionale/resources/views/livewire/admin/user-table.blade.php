<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-users mr-2 text-emerald-600"></i> Gestione Utenti
        </h1>
        @if(auth()->guard('admin')->user()->hasPermission('create_users'))
            <a href="{{ route('admin.users.create') }}" 
               class="bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white px-5 py-2.5 rounded-lg transition-all duration-200 shadow-md hover:shadow-lg">
                <i class="fas fa-plus mr-2"></i> Nuovo Utente
            </a>
        @endif
    </div>

    <!-- Filtri -->
    <div class="bg-gradient-to-r from-white to-emerald-50 rounded-xl shadow-md mb-6 p-5 border border-emerald-100">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                <input type="text" name="search" placeholder="Cerca per nome o email..." 
                       value="{{ request('search') }}"
                       class="w-full pl-10 pr-3 py-2 border border-emerald-200 rounded-lg focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition">
            </div>
            
            <select name="role" class="px-3 py-2 border border-emerald-200 rounded-lg focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200">
                <option value="">Tutti i ruoli</option>
                @foreach($roles as $key => $label)
                    <option value="{{ $key }}" {{ request('role') == $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            
            <select name="status" class="px-3 py-2 border border-emerald-200 rounded-lg focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200">
                <option value="">Tutti gli stati</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Attivi</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Disattivi</option>
            </select>
            
            <button type="submit" class="bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 text-white px-4 py-2 rounded-lg transition-all duration-200 shadow-md">
                <i class="fas fa-search mr-2"></i> Filtra
            </button>
        </form>
    </div>

    <!-- Tabella Utenti -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-emerald-100">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-emerald-50 to-green-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wider">Utente</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wider">Ruolo</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wider">Telefono</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wider">Stato</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wider">Registrato</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wider">Azioni</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($users as $user)
                <tr class="hover:bg-emerald-50/30 transition-colors duration-150">
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-green-500 rounded-full flex items-center justify-center shadow-md">
                                <span class="text-white font-semibold">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-800">{{ $user->name }}</div>
                                <div class="text-sm text-gray-500">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 text-xs font-semibold rounded-full 
                            @if($user->role == 'moderator') bg-purple-100 text-purple-700
                            @elseif($user->role == 'vip') bg-amber-100 text-amber-700
                            @elseif($user->role == 'premium') bg-emerald-100 text-emerald-700
                            @else bg-lime-100 text-lime-700
                            @endif">
                            <i class="fas 
                                @if($user->role == 'moderator') fa-gavel
                                @elseif($user->role == 'vip') fa-gem
                                @elseif($user->role == 'premium') fa-star
                                @else fa-user
                                @endif mr-1"></i>
                            {{ $roles[$user->role] ?? $user->role }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $user->phone ?? '-' }}</td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full 
                            {{ $user->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                            <i class="fas {{ $user->is_active ? 'fa-circle' : 'fa-circle' }} text-xs mr-1.5"></i>
                            {{ $user->is_active ? 'Attivo' : 'Disattivo' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        <i class="far fa-calendar-alt mr-1 text-emerald-500"></i>
                        {{ $user->created_at->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <div class="flex space-x-2">
                            @if(auth()->guard('admin')->user()->hasPermission('view_users'))
                            <a href="{{ route('admin.users.show', $user) }}" 
                               class="text-emerald-600 hover:text-emerald-800 transition-colors p-1.5 rounded-lg hover:bg-emerald-50"
                               title="Visualizza">
                                <i class="fas fa-eye"></i>
                            </a>
                            @endif
                            
                            @if(auth()->guard('admin')->user()->hasPermission('edit_users'))
                            <a href="{{ route('admin.users.edit', $user) }}" 
                               class="text-lime-600 hover:text-lime-800 transition-colors p-1.5 rounded-lg hover:bg-lime-50"
                               title="Modifica">
                                <i class="fas fa-edit"></i>
                            </a>
                            @endif
                            
                            @if(auth()->guard('admin')->user()->hasPermission('edit_users'))
                            <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-amber-600 hover:text-amber-800 transition-colors p-1.5 rounded-lg hover:bg-amber-50" 
                                        title="{{ $user->is_active ? 'Disattiva' : 'Attiva' }}">
                                    <i class="fas {{ $user->is_active ? 'fa-ban' : 'fa-check-circle' }}"></i>
                                </button>
                            </form>
                            @endif
                            
                            @if(auth()->guard('admin')->user()->hasPermission('delete_users'))
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 transition-colors p-1.5 rounded-lg hover:bg-red-50" 
                                        onclick="return confirm('Sei sicuro di voler eliminare questo utente?')"
                                        title="Elimina">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                     </td>
                 </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center justify-center text-gray-400">
                            <i class="fas fa-users text-5xl mb-3"></i>
                            <p class="text-lg">Nessun utente trovato</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        <div class="px-6 py-4 border-t border-emerald-100 bg-emerald-50/20">
            {{ $users->withQueryString()->links() }}
        </div>
    </div>
</div>