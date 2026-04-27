@extends('admin.layouts.app')

@section('title', 'Modifica Ruolo')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-edit mr-2 text-lime-600"></i> Modifica Ruolo: {{ $role->name }}
            </h1>
            <p class="text-gray-500 mt-1">Modifica i dati e i permessi del ruolo</p>
        </div>
        <div class="relative group">
            <a href="{{ route('admin.roles.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Torna indietro
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200">
        <form method="POST" action="{{ route('admin.roles.update', $role) }}">
            @csrf
            @method('PUT')
            
            @php
                $isSuperAdmin = auth()->guard('admin')->user()->isSuperAdmin();
            @endphp

            <div class="p-6">
                <!-- Informazioni Ruolo -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                        <i class="fas fa-info-circle mr-2 text-blue-500"></i> Informazioni Ruolo
                    </h3>
                    
                    @if($isSuperAdmin)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Nome Ruolo <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" value="{{ old('name', $role->name) }}" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent">
                                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Slug (Identificativo) <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="slug" value="{{ old('slug', $role->slug) }}" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent">
                                <p class="text-xs text-gray-500 mt-1">Identificatore unico (es: content_manager)</p>
                                @error('slug') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Livello <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="level" value="{{ old('level', $role->level) }}" required min="1" max="100"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent">
                                <p class="text-xs text-gray-500 mt-1">Più basso è il numero, più alti sono i privilegi</p>
                                @error('level') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Descrizione
                                </label>
                                <textarea name="description" rows="2" 
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent">{{ old('description', $role->description) }}</textarea>
                            </div>
                            
                            <div class="flex space-x-6">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="is_default" value="1" {{ old('is_default', $role->is_default) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-lime-600 focus:ring-lime-500">
                                    <span class="ml-2 text-sm text-gray-700">
                                        <i class="fas fa-star text-amber-500 mr-1"></i> Ruolo predefinito
                                    </span>
                                </label>
                                
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $role->is_active) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-lime-600 focus:ring-lime-500">
                                    <span class="ml-2 text-sm text-gray-700">
                                        <i class="fas fa-check-circle text-lime-500 mr-1"></i> Ruolo attivo
                                    </span>
                                </label>
                            </div>
                        </div>
                    @else
                        <div class="bg-gray-100 rounded-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-500">Nome Ruolo</p>
                                    <p class="font-medium text-gray-800">{{ $role->name }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Slug</p>
                                    <p class="font-medium text-gray-800">{{ $role->slug }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Livello</p>
                                    <p class="font-medium text-gray-800">{{ $role->level }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Descrizione</p>
                                    <p class="font-medium text-gray-800">{{ $role->description ?: 'Nessuna descrizione' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Stato</p>
                                    <span class="px-2 py-1 text-xs font-medium rounded-md {{ $role->is_active ? 'bg-lime-100 text-lime-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $role->is_active ? 'Attivo' : 'Disattivo' }}
                                    </span>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Ruolo predefinito</p>
                                    <span class="px-2 py-1 text-xs font-medium rounded-md {{ $role->is_default ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-500' }}">
                                        {{ $role->is_default ? 'Sì' : 'No' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Campi hidden per non perdere i valori -->
                        <input type="hidden" name="name" value="{{ $role->name }}">
                        <input type="hidden" name="slug" value="{{ $role->slug }}">
                        <input type="hidden" name="level" value="{{ $role->level }}">
                        <input type="hidden" name="description" value="{{ $role->description }}">
                        <input type="hidden" name="is_default" value="{{ $role->is_default }}">
                        <input type="hidden" name="is_active" value="{{ $role->is_active }}">
                    @endif
                </div>
                
                <!-- Permessi -->
                <h3 class="text-lg font-semibold mb-4 text-gray-800">
                    <i class="fas fa-key mr-2 text-lime-500"></i> Permessi del Ruolo
                </h3>
                
                @if($isSuperAdmin)
                    <p class="text-sm text-gray-500 mb-4">Seleziona o deseleziona i permessi per questo ruolo</p>
                    <div class="mb-6 space-y-4 max-h-[500px] overflow-y-auto pr-2">
                        @foreach($permissionsByGroup as $group => $perms)
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" class="group-selector mr-2" data-group="{{ $group }}"
                                           {{ count($perms->whereIn('id', $rolePermissions)) == count($perms) ? 'checked' : '' }}>
                                    <span class="font-semibold text-gray-800">
                                        <i class="fas 
                                            @if($group == 'dashboard') fa-tachometer-alt
                                            @elseif($group == 'administrators') fa-user-shield
                                            @elseif($group == 'roles') fa-users-cog
                                            @elseif($group == 'entities') fa-building
                                            @elseif($group == 'staff') fa-users
                                            @elseif($group == 'vehicles') fa-truck
                                            @elseif($group == 'services') fa-concierge-bell
                                            @elseif($group == 'expiration') fa-calendar-alt
                                            @elseif($group == 'cost_centers') fa-chart-line
                                            @elseif($group == 'settings') fa-cog
                                            @elseif($group == 'trash') fa-trash-alt
                                            @else fa-folder-open
                                            @endif mr-2 text-lime-500"></i> 
                                        @if($group == 'dashboard') Dashboard
                                        @elseif($group == 'administrators') Amministratori
                                        @elseif($group == 'roles') Ruoli e Permessi
                                        @elseif($group == 'entities') Clienti / Fornitori
                                        @elseif($group == 'staff') Personale
                                        @elseif($group == 'vehicles') Mezzi
                                        @elseif($group == 'services') Servizi
                                        @elseif($group == 'expiration') Scadenze
                                        @elseif($group == 'cost_centers') Centri di Costo
                                        @elseif($group == 'settings') Impostazioni
                                        @elseif($group == 'trash') Cestino
                                        @else {{ ucfirst($group) }}
                                        @endif
                                    </span>
                                </label>
                            </div>
                            <div class="p-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                                @foreach($perms as $perm)
                                    @php
                                        $icon = 'fa-check';
                                        if(str_contains($perm->slug, 'view')) $icon = 'fa-eye';
                                        elseif(str_contains($perm->slug, 'create')) $icon = 'fa-plus-circle';
                                        elseif(str_contains($perm->slug, 'edit')) $icon = 'fa-edit';
                                        elseif(str_contains($perm->slug, 'delete')) $icon = 'fa-trash-alt';
                                        elseif(str_contains($perm->slug, 'restore')) $icon = 'fa-undo-alt';
                                        elseif(str_contains($perm->slug, 'force_delete')) $icon = 'fa-skull-crossbones';
                                        elseif(str_contains($perm->slug, 'empty')) $icon = 'fa-broom';
                                    @endphp
                                    <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 p-1 rounded transition-colors">
                                        <input type="checkbox" name="permissions[]" value="{{ $perm->id }}"
                                               class="rounded border-gray-300 text-lime-600 focus:ring-lime-500 permission-checkbox"
                                               data-group="{{ $group }}"
                                               {{ in_array($perm->id, $rolePermissions) ? 'checked' : '' }}>
                                        <span class="text-sm text-gray-700">
                                            <i class="fas {{ $icon }} text-lime-500 mr-1"></i>
                                            {{ $perm->name }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                        <p class="text-amber-700 text-sm">
                            <i class="fas fa-info-circle mr-2"></i> 
                            Solo il Super Amministratore può modificare i permessi dei ruoli.
                        </p>
                    </div>
                    
                    <!-- Mostra permessi attuali in sola lettura -->
                    <div class="mt-4 bg-gray-50 rounded-lg p-4">
                        <h4 class="text-md font-semibold text-gray-700 mb-3">Permessi attuali:</h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach($role->permissions as $perm)
                                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-lime-100 text-lime-800">
                                    <i class="fas fa-check mr-1"></i> {{ $perm->name }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
            
            <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
                <a href="{{ route('admin.roles.index') }}" 
                   class="px-4 py-2 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                    <i class="fas fa-times mr-2"></i> Annulla
                </a>
                <button type="submit" 
                        class="px-4 py-2 bg-lime-600 hover:bg-lime-700 text-white rounded-md transition-colors shadow-sm">
                    <i class="fas fa-save mr-2"></i> Aggiorna Ruolo
                </button>
            </div>
        </form>
    </div>
</div>

@if($isSuperAdmin)
<script>
    document.querySelectorAll('.group-selector').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const group = this.dataset.group;
            const groupPermissions = document.querySelectorAll(`.permission-checkbox[data-group="${group}"]`);
            groupPermissions.forEach(perm => perm.checked = this.checked);
        });
    });
</script>
@endif
@endsection