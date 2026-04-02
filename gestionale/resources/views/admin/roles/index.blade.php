@extends('admin.layouts.app')

@section('title', 'Gestione Ruoli')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">
            <i class="fas fa-shield-alt mr-2"></i> Gestione Ruoli
        </h1>
        @if(auth()->guard('admin')->user()->hasPermission('create_roles'))
        <a href="{{ route('admin.roles.create') }}" 
           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
            <i class="fas fa-plus mr-2"></i> Nuovo Ruolo
        </a>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ruolo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slug</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Livello</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Admin Associati</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stato</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Azioni</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($roles as $role)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div>
                            <div class="font-medium text-gray-900">{{ $role->name }}</div>
                            <div class="text-sm text-gray-500">{{ $role->description ?? 'Nessuna descrizione' }}</div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <code class="text-sm bg-gray-100 px-2 py-1 rounded">{{ $role->slug }}</code>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $role->level }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                            <i class="fas fa-users mr-1"></i> {{ $role->administrators_count }} admin
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full {{ $role->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            <i class="fas {{ $role->is_active ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                            {{ $role->is_active ? 'Attivo' : 'Disattivo' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <div class="flex space-x-3">
                            @if(auth()->guard('admin')->user()->hasPermission('edit_roles') && $role->slug != 'super_admin')
                            <a href="{{ route('admin.roles.edit', $role) }}" 
                               class="text-green-600 hover:text-green-900" title="Modifica">
                                <i class="fas fa-edit"></i>
                            </a>
                            @endif
                            
                            @if(auth()->guard('admin')->user()->hasPermission('delete_roles') && !in_array($role->slug, ['super_admin', 'admin', 'editor', 'viewer']))
                            <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900" 
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
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2 block"></i>
                        Nessun ruolo trovato
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection