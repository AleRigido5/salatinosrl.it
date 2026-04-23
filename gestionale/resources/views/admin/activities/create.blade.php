@extends('admin.layouts.app')

@section('title', 'Nuova Attività')

@section('content')
<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-plus-circle text-lime-500 mr-2"></i> Nuova Attività
        </h1>
        <div class="relative group">
            <a href="{{ route('admin.activities.index') }}" 
               class="bg-gray-600 hover:bg-gray-900 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="absolute bottom-full transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                Torna alla lista
            </div>
        </div>
    </div>

    <form action="{{ route('admin.activities.store') }}" method="POST" class="bg-white rounded-lg shadow p-6">
        @csrf
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Data -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data</label>
                <input type="date" name="data_activities" value="{{ old('data_activities', date('Y-m-d')) }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
            </div>
            
            <!-- Centro di Costo Autocomplete -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Centro di Costo *</label>
                <div class="relative" x-data="{ open: false, search: '' }">
                    <input type="hidden" name="id_cost_centers" id="cost_center_id" value="{{ old('id_cost_centers') }}">
                    <input type="text" 
                           x-model="search"
                           x-on:focus="open = true"
                           x-on:keydown.escape="open = false"
                           x-on:click.away="open = false"
                           placeholder="Cerca centro di costo..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500"
                           autocomplete="off">
                    
                    <div x-show="open" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                        @foreach($costCenters as $cc)
                        <div x-show="search === '' || '{{ strtolower($cc->Nome) }}'.includes(search.toLowerCase())"
                             @click="search = '{{ addslashes($cc->Nome) }}'; open = false; document.getElementById('cost_center_id').value = {{ $cc->id }}"
                             class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                            <div class="font-medium text-gray-800">{{ $cc->Nome }}</div>
                            @if($cc->Localita)
                            <div class="text-xs text-gray-500">{{ $cc->Localita }}</div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @error('id_cost_centers') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            
            <!-- Servizio Autocomplete -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Servizio *</label>
                <div class="relative" x-data="{ open: false, search: '' }">
                    <input type="hidden" name="id_services" id="service_id" value="{{ old('id_services') }}">
                    <input type="text" 
                           x-model="search"
                           x-on:focus="open = true"
                           x-on:keydown.escape="open = false"
                           x-on:click.away="open = false"
                           placeholder="Cerca servizio..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500"
                           autocomplete="off">
                    
                    <div x-show="open" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                        @foreach($services as $service)
                        <div x-show="search === '' || '{{ strtolower($service->Titolo) }}'.includes(search.toLowerCase())"
                             @click="search = '{{ addslashes($service->Titolo) }}'; open = false; document.getElementById('service_id').value = {{ $service->id }}"
                             class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                            <div class="font-medium text-gray-800">{{ $service->Titolo }}</div>
                            @if($service->Descrizione)
                            <div class="text-xs text-gray-500 truncate">{{ $service->Descrizione }}</div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @error('id_services') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            
            <!-- Cliente/Fornitore Autocomplete -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cliente/Fornitore *</label>
                <div class="relative" x-data="{ open: false, search: '' }">
                    <input type="hidden" name="id_entities" id="entity_id" value="{{ old('id_entities') }}">
                    <input type="text" 
                           x-model="search"
                           x-on:focus="open = true"
                           x-on:keydown.escape="open = false"
                           x-on:click.away="open = false"
                           placeholder="Cerca cliente/fornitore..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500"
                           autocomplete="off">
                    
                    <div x-show="open" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                        @foreach($entities as $entity)
                        @php
                            $entityName = $entity->ragione_sociale ?: ($entity->nome . ' ' . $entity->cognome);
                        @endphp
                        <div x-show="search === '' || '{{ strtolower($entityName) }}'.includes(search.toLowerCase())"
                             @click="search = '{{ addslashes($entityName) }}'; open = false; document.getElementById('entity_id').value = {{ $entity->id_cliente }}"
                             class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                            <div class="font-medium text-gray-800">{{ $entityName }}</div>
                            @if($entity->partita_iva)
                            <div class="text-xs text-gray-500">P.IVA: {{ $entity->partita_iva }}</div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @error('id_entities') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
        </div>
        
        <!-- Note -->
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
            <textarea name="note" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">{{ old('note') }}</textarea>
        </div>
        
        <!-- Personale Associato -->
        <div class="mt-6">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-users text-blue-500 mr-2"></i> Personale Associato
                </h3>
                <button type="button" id="addStaffBtn" class="px-3 py-1 bg-lime-500 text-white rounded-md hover:bg-lime-600 text-sm">
                    <i class="fas fa-plus"></i> Aggiungi personale
                </button>
            </div>
            
            <div id="staffContainer" class="space-y-3">
                <!-- Template riga personale -->
                <div class="staff-row-template hidden bg-gray-50 p-4 rounded-lg border">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Personale *</label>
                            <select name="staff[__INDEX__][id_staff]" class="staff-select w-full px-2 py-1.5 text-sm border rounded-md">
                                <option value="">Seleziona...</option>
                                @foreach($staffList as $staff)
                                    <option value="{{ $staff->id_personale }}">{{ $staff->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">N. Ore</label>
                            <input type="number" step="0.5" name="staff[__INDEX__][n_ore]" value="0" 
                                   class="w-full px-2 py-1.5 text-sm border rounded-md">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Spese (€)</label>
                            <input type="number" step="0.01" name="staff[__INDEX__][spese]" value="0" 
                                   class="w-full px-2 py-1.5 text-sm border rounded-md">
                        </div>
                        <div class="relative">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Note</label>
                            <div class="flex gap-2">
                                <input type="text" name="staff[__INDEX__][note]" placeholder="Note aggiuntive" 
                                       class="flex-1 px-2 py-1.5 text-sm border rounded-md">
                                <button type="button" class="remove-staff-btn text-red-500 hover:text-red-700 px-2">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Prima riga personale predefinita -->
                <div class="staff-row bg-gray-50 p-4 rounded-lg border">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Personale *</label>
                            <select name="staff[0][id_staff]" class="staff-select w-full px-2 py-1.5 text-sm border rounded-md">
                                <option value="">Seleziona...</option>
                                @foreach($staffList as $staff)
                                    <option value="{{ $staff->id_personale }}">{{ $staff->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">N. Ore</label>
                            <input type="number" step="0.5" name="staff[0][n_ore]" value="0" 
                                   class="w-full px-2 py-1.5 text-sm border rounded-md">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Spese (€)</label>
                            <input type="number" step="0.01" name="staff[0][spese]" value="0" 
                                   class="w-full px-2 py-1.5 text-sm border rounded-md">
                        </div>
                        <div class="relative">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Note</label>
                            <div class="flex gap-2">
                                <input type="text" name="staff[0][note]" placeholder="Note aggiuntive" 
                                       class="flex-1 px-2 py-1.5 text-sm border rounded-md">
                                <button type="button" class="remove-staff-btn text-red-500 hover:text-red-700 px-2" style="display: none;">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Bottoni -->
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.activities.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition">
                Annulla
            </a>
            <button type="submit" class="px-4 py-2 bg-lime-500 text-white rounded-md hover:bg-lime-600 transition">
                <i class="fas fa-save mr-2"></i> Salva
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    let staffIndex = 1;
    
    // Aggiungi riga personale
    document.getElementById('addStaffBtn').addEventListener('click', function() {
        const template = document.querySelector('.staff-row-template');
        const clone = template.cloneNode(true);
        
        // Rimuovi classe hidden
        clone.classList.remove('staff-row-template');
        clone.classList.add('staff-row');
        clone.style.display = 'block';
        
        // Sostituisci __INDEX__ con l'indice corrente
        const html = clone.innerHTML.replace(/__INDEX__/g, staffIndex);
        clone.innerHTML = html;
        
        // Mostra il bottone remove
        const removeBtn = clone.querySelector('.remove-staff-btn');
        if (removeBtn) removeBtn.style.display = 'block';
        
        // Aggiungi al container
        document.getElementById('staffContainer').appendChild(clone);
        
        staffIndex++;
    });
    
    // Rimuovi riga personale (delegation)
    document.getElementById('staffContainer').addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.remove-staff-btn');
        if (removeBtn) {
            const row = removeBtn.closest('.staff-row');
            if (row && document.querySelectorAll('.staff-row').length > 1) {
                row.remove();
            } else {
                alert('Deve rimanere almeno una riga per il personale');
            }
        }
    });
    
    // Precompila i campi autocomplete se ci sono errori di validazione
    document.addEventListener('DOMContentLoaded', function() {
        @if(old('id_cost_centers'))
            const costCenterId = {{ old('id_cost_centers') }};
            const costCenterName = '{{ old('cost_center_name') }}';
            const costCenterInput = document.querySelector('#cost_center_id');
            const costCenterText = costCenterInput?.closest('.relative')?.querySelector('input[type="text"]');
            if (costCenterText && costCenterName) costCenterText.value = costCenterName;
        @endif
        
        @if(old('id_services'))
            const serviceId = {{ old('id_services') }};
            const serviceName = '{{ old('service_name') }}';
            const serviceInput = document.querySelector('#service_id');
            const serviceText = serviceInput?.closest('.relative')?.querySelector('input[type="text"]');
            if (serviceText && serviceName) serviceText.value = serviceName;
        @endif
        
        @if(old('id_entities'))
            const entityId = {{ old('id_entities') }};
            const entityName = '{{ old('entity_name') }}';
            const entityInput = document.querySelector('#entity_id');
            const entityText = entityInput?.closest('.relative')?.querySelector('input[type="text"]');
            if (entityText && entityName) entityText.value = entityName;
        @endif
    });
</script>
@endpush
@endsection