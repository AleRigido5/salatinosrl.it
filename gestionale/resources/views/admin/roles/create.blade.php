@extends('admin.layouts.app')

@section('title', 'Nuovo Ruolo')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">
            <i class="fas fa-plus-circle mr-2"></i> Nuovo Ruolo
        </h1>
        <a href="{{ route('admin.roles.index') }}" class="text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left mr-1"></i> Torna indietro
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('admin.roles.store') }}">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-tag mr-1"></i> Nome Ruolo *
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-code mr-1"></i> Slug *
                    </label>
                    <input type="text" name="slug" value="{{ old('slug') }}" required
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Identificatore unico (es: content_manager)</p>
                    @error('slug') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-sort-numeric-down mr-1"></i> Livello *
                    </label>
                    <input type="number" name="level" value="{{ old('level', 5) }}" required min="1" max="100"
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Più basso è il numero, più alti sono i privilegi</p>
                    @error('level') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-align-left mr-1"></i> Descrizione
                    </label>
                    <textarea name="description" rows="2" 
                              class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">{{ old('description') }}</textarea>
                </div>
                
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600">
                        <span class="ml-2 text-sm text-gray-700">
                            <i class="fas fa-star text-yellow-500 mr-1"></i> Ruolo predefinito per nuovi admin
                        </span>
                    </label>
                </div>
                
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600">
                        <span class="ml-2 text-sm text-gray-700">
                            <i class="fas fa-check-circle text-green-500 mr-1"></i> Ruolo attivo
                        </span>
                    </label>
                </div>
            </div>
            
            <h3 class="text-lg font-semibold mb-4">
                <i class="fas fa-key mr-2 text-blue-500"></i> Permessi del Ruolo
            </h3>
            <div class="mb-6">
                @foreach($permissionsByGroup as $group => $perms)
                <div class="mb-4 border rounded-lg overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" class="group-selector mr-2" data-group="{{ $group }}">
                            <span class="font-semibold text-gray-700">
                                <i class="fas fa-folder-open mr-1"></i> {{ ucfirst($group) }}
                            </span>
                        </label>
                    </div>
                    <div class="p-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                        @foreach($perms as $perm)
                        <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 p-1 rounded">
                            <input type="checkbox" name="permissions[]" value="{{ $perm->id }}"
                                   class="rounded border-gray-300 text-blue-600 permission-checkbox"
                                   data-group="{{ $group }}">
                            <span class="text-sm text-gray-700">
                                <i class="{{ $perm->icon ? 'fas fa-' . strtolower($perm->icon) : 'fas fa-check' }} mr-1"></i>
                                {{ $perm->name }}
                            </span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                    <i class="fas fa-save mr-2"></i> Crea Ruolo
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.querySelectorAll('.group-selector').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const group = this.dataset.group;
            const groupPermissions = document.querySelectorAll(`.permission-checkbox[data-group="${group}"]`);
            groupPermissions.forEach(perm => perm.checked = this.checked);
        });
    });
</script>
@endsection