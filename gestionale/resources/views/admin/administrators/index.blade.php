@extends('admin.layouts.app')

@section('title', 'Gestione Amministratori')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-user-shield mr-2 text-emerald-600"></i> Gestione Amministratori
        </h1>
        @if(auth()->guard('admin')->user()->hasPermission('create_administrators'))
        <a href="{{ route('admin.administrators.create') }}" 
           class="bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white px-5 py-2.5 rounded-lg transition-all duration-200 shadow-md hover:shadow-lg">
            <i class="fas fa-plus mr-2"></i> Nuovo Amministratore
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
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" {{ request('role') == $role->id ? 'selected' : '' }}>
                        {{ $role->name }}
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

    <!-- Tabella Amministratori -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-emerald-100">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-emerald-50 to-green-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wider">Amministratore</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wider">Ruolo</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wider">Telefono</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wider">Stato</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wider">Ultimo Accesso</th>
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
                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full 
                            {{ $admin->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                            <i class="fas {{ $admin->is_active ? 'fa-circle' : 'fa-circle' }} text-xs mr-1.5"></i>
                            {{ $admin->is_active ? 'Attivo' : 'Disattivo' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        <i class="far fa-clock mr-1"></i>
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
                            
                            @if(auth()->guard('admin')->user()->hasPermission('edit_administrators') && $admin->id != auth()->guard('admin')->id())
                            <form method="POST" action="{{ route('admin.administrators.toggle-status', $admin) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-amber-600 hover:text-amber-800 transition-colors p-1.5 rounded-lg hover:bg-amber-50" 
                                        title="{{ $admin->is_active ? 'Disattiva' : 'Attiva' }}">
                                    <i class="fas {{ $admin->is_active ? 'fa-ban' : 'fa-check-circle' }}"></i>
                                </button>
                            </form>
                            @endif
                            
                            @if(auth()->guard('admin')->user()->hasPermission('delete_administrators') && $admin->id != auth()->guard('admin')->id() && !$admin->isSuperAdmin())
                            <form method="POST" action="{{ route('admin.administrators.destroy', $admin) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 transition-colors p-1.5 rounded-lg hover:bg-red-50" 
                                        onclick="return confirm('Sei sicuro di voler eliminare questo amministratore?')"
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
                            <i class="fas fa-user-shield text-5xl mb-3"></i>
                            <p class="text-lg">Nessun amministratore trovato</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        <div class="px-6 py-4 border-t border-emerald-100 bg-emerald-50/20">
            {{ $administrators->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection