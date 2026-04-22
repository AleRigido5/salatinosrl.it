@extends('admin.layouts.app')

@section('title', 'Modifica Centro di Costo')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-edit mr-2 text-lime-600"></i> Modifica Centro di Costo
            </h1>
            <p class="text-gray-500 text-sm mt-1">
                Modifica i dati di: <strong>{{ $costCenter->nome ?: 'Centro di costo' }}</strong>
                @if($costCenter->contrada)
                <span class="text-gray-400">| {{ $costCenter->contrada }}</span>
                @endif
            </p>
        </div>
        <div class="relative group">
            <a href="{{ route('admin.cost_centers.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="absolute bottom-full transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                Torna ai Centri di Costo
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow border border-gray-200">
        <form method="POST" action="{{ route('admin.cost_centers.update', $costCenter->id) }}" id="costCenterForm">
            @csrf
            @method('PUT')
            
            <div class="p-6">
                <!-- RIGA 1: Tipo Riferimento + Riferimento (6+6) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Tipo Riferimento <span class="text-red-500">*</span>
                        </label>
                        <select name="table_references" id="table_references" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent">
                            <option value="ownership" {{ old('table_references', $costCenter->table_references) == 'ownership' ? 'selected' : '' }}>Proprietà</option>
                            <option value="entities" {{ old('table_references', $costCenter->table_references) == 'entities' ? 'selected' : '' }}>Cliente/Fornitore</option>
                        </select>
                        @error('table_references') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Riferimento <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="text" 
                                   id="reference_autocomplete" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent"
                                   placeholder="Seleziona prima il tipo..."
                                   autocomplete="off">
                            <input type="hidden" name="id_references" id="id_references" value="{{ old('id_references', $costCenter->id_references) }}">
                            <div id="autocomplete_results" class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"></div>
                        </div>
                        @error('id_references') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                
                <!-- RIGA 2: Nome + Contrada (6+6) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                        <input type="text" name="nome" value="{{ old('nome', $costCenter->nome) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent"
                               placeholder="es. Terreno Via Roma">
                        @error('nome') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contrada</label>
                        <input type="text" name="contrada" value="{{ old('contrada', $costCenter->contrada) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent"
                               placeholder="es. Contrada San Giovanni">
                    </div>
                </div>
                
                <!-- RIGA 3: Località + Coltura (6+6) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Località</label>
                        <input type="text" name="localita" value="{{ old('localita', $costCenter->localita) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent"
                               placeholder="es. Zona Industriale">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Coltura</label>
                        <input type="text" name="coltura" value="{{ old('coltura', $costCenter->coltura) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent"
                               placeholder="es. Grano, Mais, Vigneto">
                    </div>
                </div>
                
                <!-- RIGA 4: Foglio + Particella (6+6) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Foglio</label>
                        <input type="text" name="foglio" value="{{ old('foglio', $costCenter->foglio) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent"
                               placeholder="es. 12">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Particella</label>
                        <input type="text" name="particella" value="{{ old('particella', $costCenter->particella) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent"
                               placeholder="es. 345">
                    </div>
                </div>
                
                <!-- RIGA 5: Superficie + Competenza (6+6) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Superficie <span class="text-gray-400 text-xs">(ha)</span>
                        </label>
                        <input type="number" name="superficie" value="{{ old('superficie', $costCenter->superficie) }}" step="0.0001"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent"
                               placeholder="0.0000">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Competenza</label>
                        <input type="text" name="competenza" value="{{ old('competenza', $costCenter->competenza) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent"
                               placeholder="es. Solare, Annuale">
                    </div>
                </div>
                
                <!-- RIGA 6: Costo Orario + Ore Giornaliere (6+6) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Costo Orario <span class="text-gray-400 text-xs">(€/h)</span>
                        </label>
                        <input type="number" name="costo_h" value="{{ old('costo_h', $costCenter->costo_h) }}" step="0.50"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent"
                               placeholder="7.00">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Ore Giornaliere <span class="text-gray-400 text-xs">(h/giorno)</span>
                        </label>
                        <input type="number" name="num_h" value="{{ old('num_h', $costCenter->num_h) }}" min="0" max="24"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent"
                               placeholder="8">
                    </div>
                </div>
                
                <!-- RIGA 7: Note (full width) -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                    <textarea name="note" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent"
                              placeholder="Eventuali note aggiuntive...">{{ old('note', $costCenter->note) }}</textarea>
                </div>
                
                <!-- RIGA 8: Stato Attivo (full width) -->
                <div class="mb-4">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="valid" value="1" {{ old('valid', $costCenter->valid) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-lime-600 focus:ring-lime-500">
                        <span class="ml-2 text-sm text-gray-700">
                            <i class="fas fa-check-circle text-lime-600 mr-1"></i> Centro di Costo attivo
                        </span>
                    </label>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
                <a href="{{ route('admin.cost_centers.index') }}" 
                   class="px-4 py-2 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                    <i class="fas fa-times mr-2"></i> Annulla
                </a>
                <button type="submit" 
                        class="px-4 py-2 bg-lime-600 hover:bg-lime-700 text-white rounded-md transition-colors shadow-sm">
                    <i class="fas fa-save mr-2"></i> Aggiorna Centro di Costo
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let searchTimeout = null;
    let currentType = '{{ old("table_references", $costCenter->table_references) }}';

    // Funzione per cercare i riferimenti
    function searchReferences(searchTerm, type) {
        if (!type || searchTerm.length < 2) {
            document.getElementById('autocomplete_results').classList.add('hidden');
            return;
        }

        fetch(`/admin/api/references/search?type=${type}&q=${encodeURIComponent(searchTerm)}`)
            .then(response => response.json())
            .then(data => {
                const resultsDiv = document.getElementById('autocomplete_results');
                
                if (data.length === 0) {
                    resultsDiv.innerHTML = '<div class="px-3 py-2 text-gray-500 text-sm">Nessun risultato trovato</div>';
                    resultsDiv.classList.remove('hidden');
                    return;
                }
                
                resultsDiv.innerHTML = data.map(item => `
                    <div class="px-3 py-2 hover:bg-gray-100 cursor-pointer transition-colors" data-id="${item.id}" data-name="${item.name.replace(/'/g, "\\'")}">
                        <div class="font-medium text-gray-800">${item.name}</div>
                        ${item.details ? `<div class="text-xs text-gray-500">${item.details}</div>` : ''}
                    </div>
                `).join('');
                
                resultsDiv.querySelectorAll('div[data-id]').forEach(el => {
                    el.addEventListener('click', function() {
                        const id = this.dataset.id;
                        const name = this.dataset.name;
                        document.getElementById('reference_autocomplete').value = name;
                        document.getElementById('id_references').value = id;
                        resultsDiv.classList.add('hidden');
                    });
                });
                
                resultsDiv.classList.remove('hidden');
            })
            .catch(error => {
                console.error('Errore:', error);
                document.getElementById('autocomplete_results').innerHTML = '<div class="px-3 py-2 text-red-500 text-sm">Errore nel caricamento</div>';
                document.getElementById('autocomplete_results').classList.remove('hidden');
            });
    }

    // Funzione per caricare il nome del riferimento corrente
    function loadCurrentReference(type, id) {
        if (!type || !id) return;
        
        fetch(`/admin/api/references/${type}/${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.name) {
                    document.getElementById('reference_autocomplete').value = data.name;
                }
            })
            .catch(error => console.error('Errore nel caricamento del riferimento:', error));
    }

    // Event listener per il cambio del tipo
    document.getElementById('table_references').addEventListener('change', function() {
        const type = this.value;
        currentType = type;
        const autocompleteInput = document.getElementById('reference_autocomplete');
        const idReferences = document.getElementById('id_references');
        
        if (!type) {
            autocompleteInput.disabled = true;
            autocompleteInput.placeholder = 'Seleziona prima il tipo...';
            autocompleteInput.value = '';
            idReferences.value = '';
            document.getElementById('autocomplete_results').classList.add('hidden');
        } else {
            autocompleteInput.disabled = false;
            autocompleteInput.placeholder = `Cerca ${type === 'ownership' ? 'proprietà' : 'cliente/fornitore'}...`;
            autocompleteInput.value = '';
            idReferences.value = '';
            
            // Se c'è un ID selezionato in precedenza, caricalo
            const oldId = "{{ old('id_references', $costCenter->id_references) }}";
            if (oldId && type === "{{ old('table_references', $costCenter->table_references) }}") {
                loadCurrentReference(type, oldId);
                idReferences.value = oldId;
            }
        }
    });
    
    // Event listener per l'input di ricerca
    document.getElementById('reference_autocomplete').addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        const searchTerm = e.target.value;
        
        if (!currentType) {
            return;
        }
        
        if (searchTerm.length < 2) {
            document.getElementById('autocomplete_results').classList.add('hidden');
            return;
        }
        
        searchTimeout = setTimeout(() => {
            searchReferences(searchTerm, currentType);
        }, 300);
    });
    
    // Chiudi i risultati quando si clicca fuori
    document.addEventListener('click', function(e) {
        const container = document.querySelector('#reference_autocomplete').parentElement;
        if (!container.contains(e.target)) {
            document.getElementById('autocomplete_results').classList.add('hidden');
        }
    });
    
    // Trigger iniziale se c'è un valore selezionato
    const initialType = document.getElementById('table_references').value;
    if (initialType) {
        document.getElementById('table_references').dispatchEvent(new Event('change'));
    }
</script>
@endsection