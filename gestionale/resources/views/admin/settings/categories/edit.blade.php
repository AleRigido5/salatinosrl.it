@extends('admin.layouts.app')

@section('title', 'Modifica Impostazione')

@section('content')
<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-edit mr-2 text-lime-600"></i> Modifica Impostazione
        </h1>
        <a href="{{ url()->previous() }}" class="text-gray-600 hover:text-gray-800">
            <i class="fas fa-arrow-left mr-1"></i> Indietro
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6">
        <form method="POST" action="{{ route('admin.settings.update', $setting->id) }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                    <select name="category_id" id="category_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                        <option value="">Senza categoria</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $setting->category_id == $cat->id ? 'selected' : '' }} data-tabella="{{ $cat->tabella_riferimento }}">
                                {{ $cat->titolo }}
                                @if($cat->tabella_riferimento)
                                    ({{ $cat->tabella_riferimento }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Tabella di Riferimento
                        <span class="text-xs text-gray-500" id="tabellaHint"></span>
                    </label>
                    <input type="text" 
                           name="tabella_riferimento" 
                           id="tabella_riferimento"
                           value="{{ old('tabella_riferimento', $setting->tabella_riferimento) }}"
                           placeholder="es: staff, contacts, expiration"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                    <p class="text-xs text-gray-500 mt-1">
                        Lascia vuoto per ereditare il valore dalla categoria (se presente)
                    </p>
                    @error('tabella_riferimento')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valore</label>
                    <input type="text" name="valore" value="{{ old('valore', $setting->valore) }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                    @error('valore')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrizione</label>
                    <textarea name="descrizione" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">{{ old('descrizione', $setting->descrizione) }}</textarea>
                    @error('descrizione')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ordinamento</label>
                        <input type="number" name="ordinamento" value="{{ old('ordinamento', $setting->ordinamento) }}"
                               class="w-32 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                        @error('ordinamento')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Attivo</label>
                        <select name="valid" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                            <option value="1" {{ old('valid', $setting->valid) ? 'selected' : '' }}>Sì</option>
                            <option value="0" {{ !old('valid', $setting->valid) ? 'selected' : '' }}>No</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 pt-4 border-t">
                    <button type="button" onclick="window.history.back()" 
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                        Annulla
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-lime-600 hover:bg-lime-700 text-white rounded-md transition-colors">
                        <i class="fas fa-save mr-2"></i> Aggiorna Impostazione
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('category_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const tabellaValore = selectedOption.getAttribute('data-tabella');
    const tabellaInput = document.getElementById('tabella_riferimento');
    const tabellaHint = document.getElementById('tabellaHint');
    
    if (tabellaValore && !tabellaInput.value) {
        tabellaHint.innerHTML = `(ereditato: ${tabellaValore})`;
        tabellaHint.classList.add('text-lime-600');
        tabellaInput.placeholder = `Lascia vuoto per usare "${tabellaValore}"`;
    } else {
        tabellaHint.innerHTML = '';
        tabellaInput.placeholder = 'es: staff, contacts, expiration';
    }
});

// Trigger iniziale
document.getElementById('category_id').dispatchEvent(new Event('change'));
</script>
@endsection