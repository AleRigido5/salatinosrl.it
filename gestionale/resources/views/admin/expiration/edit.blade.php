{{-- resources/views/admin/expiration/edit.blade.php --}}

@extends('admin.layouts.app')

@section('title', 'Modifica Scadenza')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Modifica Scadenza</h1>
            <a href="{{ route('admin.expiration.index', $staffId ? ['staff_id' => $staffId] : []) }}" 
               class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Torna indietro
            </a>
        </div>

        <form action="{{ route('admin.expiration.update', $expiration->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            @if($staffId)
            <input type="hidden" name="staff_id" value="{{ $staffId }}">
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Titolo <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="titolo" 
                           value="{{ old('titolo', $expiration->titolo) }}" 
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    @error('titolo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Tipologia Scadenza <span class="text-red-500">*</span>
                    </label>
                    <select name="id_settings" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">Seleziona tipologia...</option>
                        @foreach($tipologie as $tipologia)
                            <option value="{{ $tipologia->id }}" {{ old('id_settings', $expiration->id_settings) == $tipologia->id ? 'selected' : '' }}>
                                {{ $tipologia->valore }}
                            </option>
                        @endforeach
                    </select>
                    @error('id_settings') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Qualifica / Sottotitolo</label>
                    <input type="text" 
                           name="subtitolo" 
                           value="{{ old('subtitolo', $expiration->subtitolo) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Data Inizio <span class="text-red-500">*</span>
                    </label>
                    <input type="date" 
                           name="data_inizio" 
                           value="{{ old('data_inizio', $expiration->data_inizio ? $expiration->data_inizio->format('Y-m-d') : '') }}" 
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    @error('data_inizio') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data Scadenza</label>
                    <input type="date" 
                           name="data_fine" 
                           value="{{ old('data_fine', $expiration->data_fine ? $expiration->data_fine->format('Y-m-d') : '') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <p class="text-xs text-gray-400 mt-1">Opzionale: lascia vuoto se non ha scadenza</p>
                </div>

                <!-- Fornitore / Cliente con autocomplete -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fornitore</label>
                    <div class="relative">
                        <div class="relative">
                            <input type="text" 
                                   id="entity_search"
                                   placeholder="Cerca cliente o fornitore..."
                                   value="{{ $expiration->entityLegacy ? ($expiration->entityLegacy->ragione_sociale ?: $expiration->entityLegacy->nome . ' ' . $expiration->entityLegacy->cognome) : '' }}"
                                   class="w-full px-3 py-2 pl-9 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <i class="fas fa-search absolute left-2.5 top-2.5 text-gray-400"></i>
                        </div>
                        <div id="entity_results" class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto hidden"></div>
                    </div>
                    <input type="hidden" name="id_entities" id="entity_id" value="{{ old('id_entities', $expiration->id_entities) }}">
                    <p class="text-xs text-gray-400 mt-1">Opzionale: associa la scadenza a un cliente o fornitore</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ownership / Azienda</label>
                    <select name="id_ownership" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">Seleziona...</option>
                        @foreach($ownerships as $ownership)
                            <option value="{{ $ownership->id_proprieta }}" {{ old('id_ownership', $expiration->id_ownership) == $ownership->id_proprieta ? 'selected' : '' }}>
                                {{ $ownership->RagSocialePr }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                    <textarea name="note" 
                              rows="4" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('note', $expiration->note) }}</textarea>
                </div>
            </div>

            <!-- Info tracciamento -->
            @if($expiration->created_at || $expiration->updated_at)
            <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                <h4 class="text-sm font-semibold text-gray-700 mb-2">Tracciamento</h4>
                <div class="text-xs text-gray-500 space-y-1">
                    @if($expiration->created_at && $expiration->createdBy)
                    <div>Inserito da <strong>{{ $expiration->createdBy->name }}</strong> il {{ $expiration->created_at->format('d/m/Y H:i') }}</div>
                    @endif
                    @if($expiration->updated_at && $expiration->updatedBy && $expiration->created_at != $expiration->updated_at)
                    <div>Modificato da <strong>{{ $expiration->updatedBy->name }}</strong> il {{ $expiration->updated_at->format('d/m/Y H:i') }}</div>
                    @endif
                </div>
            </div>
            @endif

            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('admin.expiration.index', $staffId ? ['staff_id' => $staffId] : []) }}" 
                   class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                    Annulla
                </a>
                <button type="submit" 
                        class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-md transition-colors">
                    <i class="fas fa-save mr-2"></i>
                    Aggiorna Scadenza
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('entity_search');
    const resultsDiv = document.getElementById('entity_results');
    const entityIdInput = document.getElementById('entity_id');
    let searchTimeout;

    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            resultsDiv.classList.add('hidden');
            return;
        }

        searchTimeout = setTimeout(() => {
            fetch(`/admin/api/search-entities?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        resultsDiv.innerHTML = '';
                        data.forEach(entity => {
                            const div = document.createElement('div');
                            div.className = 'px-4 py-2 hover:bg-purple-50 cursor-pointer border-b border-gray-100 last:border-0';
                            div.innerHTML = `
                                <div class="font-medium text-gray-900">${entity.ragione_sociale || entity.nome + ' ' + entity.cognome}</div>
                                <div class="text-xs text-gray-500 flex flex-wrap gap-2 mt-0.5">
                                    ${entity.partita_iva ? `<span class="inline-flex items-center"><i class="fas fa-credit-card mr-1 text-xs"></i> P.IVA: ${entity.partita_iva}</span>` : ''}
                                    ${entity.codice_fiscale ? `<span>CF: ${entity.codice_fiscale}</span>` : ''}
                                    <span>Tipo: ${entity.entity_type === 'fornitore' ? 'Fornitore' : 'Cliente'}</span>
                                </div>
                            `;
                            div.addEventListener('click', () => {
                                searchInput.value = entity.ragione_sociale || entity.nome + ' ' + entity.cognome;
                                entityIdInput.value = entity.id_cliente;
                                resultsDiv.classList.add('hidden');
                            });
                            resultsDiv.appendChild(div);
                        });
                        resultsDiv.classList.remove('hidden');
                    } else {
                        resultsDiv.innerHTML = '<div class="px-4 py-2 text-gray-500">Nessun risultato trovato</div>';
                        resultsDiv.classList.remove('hidden');
                    }
                })
                .catch(error => {
                    console.error('Errore:', error);
                });
        }, 300);
    });

    // Nascondi i risultati quando si clicca fuori
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
            resultsDiv.classList.add('hidden');
        }
    });
});
</script>
@endsection