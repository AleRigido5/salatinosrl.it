@extends('admin.layouts.app')

@section('title', 'Gestione Ruoli')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-shield-alt mr-2 text-emerald-600"></i> Gestione Ruoli
        </h1>
        @if(auth()->guard('admin')->user()->hasPermission('create_roles'))
        <a href="{{ route('admin.roles.create') }}" 
           class="bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white px-5 py-2.5 rounded-lg transition-all duration-200 shadow-md hover:shadow-lg">
            <i class="fas fa-plus mr-2"></i> Nuovo Ruolo
        </a>
        @endif
    </div>

    <!-- Filtri -->
    <div class="bg-gradient-to-r from-white to-emerald-50 rounded-xl shadow-md mb-6 p-5 border border-emerald-100">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                <input type="text" name="search" placeholder="Cerca ruolo..." 
                       value="{{ request('search') }}"
                       class="w-full pl-10 pr-3 py-2 border border-emerald-200 rounded-lg focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition">
            </div>
            
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

    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-emerald-100">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-emerald-50 to-green-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wider">Ruolo</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wider">Slug</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wider">Livello</th>
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
                            <div class="w-8 h-8 rounded-full bg-gradient-to-r from-emerald-400 to-green-500 flex items-center justify-center text-white font-bold text-sm">
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
                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full 
                            {{ $role->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                            <i class="fas {{ $role->is_active ? 'fa-circle' : 'fa-circle' }} text-xs mr-1.5"></i>
                            {{ $role->is_active ? 'Attivo' : 'Disattivo' }}
                        </span>
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
                            <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 transition-colors p-1.5 rounded-lg hover:bg-red-50" 
                                        onclick="return confirm('Sei sicuro di voler eliminare questo ruolo?')"
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
                            <i class="fas fa-shield-alt text-5xl mb-3"></i>
                            <p class="text-lg">Nessun ruolo trovato</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        <div class="px-6 py-4 border-t border-emerald-100 bg-emerald-50/20">
            {{ $roles->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection