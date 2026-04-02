@extends('admin.layouts.app')

@section('title', 'Gestione Utenti')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">
            <i class="fas fa-users mr-2"></i> Gestione Utenti
        </h1>
        @if(auth()->guard('admin')->user()->hasPermission('create_users'))
        <a href="{{ route('admin.users.create') }}" 
           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
            <i class="fas fa-plus mr-2"></i> Nuovo Utente
        </a>
        @endif
    </div>

    <!-- Filtri -->
    <div class="bg-white rounded-lg shadow mb-6 p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <input type="text" name="search" placeholder="Cerca per nome o email..." 
                       value="{{ request('search') }}"
                       class="w-full pl-10 pr-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            
            <select name="role" class="px-3 py-2 border rounded-lg">
                <option value="">Tutti i ruoli</option>
                @foreach($roles as $key => $label)
                    <option value="{{ $key }}" {{ request('role') == $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            
            <select name="status" class="px-3 py-2 border rounded-lg">
                <option value="">Tutti gli stati</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Attivi</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Disattivi</option>
            </select>
            
            <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                <i class="fas fa-filter mr-2"></i> Filtra
            </button>
        </form>
    </div>

    <!-- Tabella Utenti -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Utente</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ruolo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Telefono</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stato</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Registrato</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Azioni</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($users as $user)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div>
                            <div class="font-medium text-gray-900">{{ $user->name }}</div>
                            <div class="text-sm text-gray-500">{{ $user->email }}</div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full 
                            @if($user->role == 'moderator') bg-purple-100 text-purple-800
                            @elseif($user->role == 'vip') bg-yellow-100 text-yellow-800
                            @elseif($user->role == 'premium') bg-green-100 text-green-800
                            @else bg-gray-100 text-gray-800
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
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $user->phone ?? '-' }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            <i class="fas {{ $user->is_active ? 'fa-circle-check' : 'fa-circle-xmark' }} mr-1"></i>
                            {{ $user->is_active ? 'Attivo' : 'Disattivo' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $user->created_at->format('d/m/Y') }}</td>
                    <td class="px-6 py-4 text-sm">
                        <div class="flex space-x-3">
                            @if(auth()->guard('admin')->user()->hasPermission('view_users'))
                            <a href="{{ route('admin.users.show', $user) }}" 
                               class="text-blue-600 hover:text-blue-900" title="Visualizza">
                                <i class="fas fa-eye"></i>
                            </a>
                            @endif
                            
                            @if(auth()->guard('admin')->user()->hasPermission('edit_users'))
                            <a href="{{ route('admin.users.edit', $user) }}" 
                               class="text-green-600 hover:text-green-900" title="Modifica">
                                <i class="fas fa-edit"></i>
                            </a>
                            @endif
                            
                            @if(auth()->guard('admin')->user()->hasPermission('edit_users'))
                            <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-yellow-600 hover:text-yellow-900" 
                                        title="{{ $user->is_active ? 'Disattiva' : 'Attiva' }}">
                                    <i class="fas {{ $user->is_active ? 'fa-ban' : 'fa-check-circle' }}"></i>
                                </button>
                            </form>
                            @endif
                            
                            @if(auth()->guard('admin')->user()->hasPermission('delete_users'))
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900" 
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
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2 block"></i>
                        Nessun utente trovato
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        <div class="px-6 py-4 border-t">
            {{ $users->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection