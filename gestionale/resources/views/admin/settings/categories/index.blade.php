@extends('admin.layouts.app')

@section('title', 'Categorie Impostazioni')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-layer-group mr-2 text-lime-600"></i> Categorie Impostazioni
        </h1>
        @if(auth()->guard('admin')->user()->hasPermission('edit_settings'))
        <div class="relative group">
            <button type="button" 
                    onclick="openCategoryModal()"
                    class="bg-lime-600 hover:bg-lime-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                <i class="fas fa-plus"></i>
            </button>
            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                Nuova Categoria
                <div class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1 border-4 border-transparent border-t-gray-800"></div>
            </div>
        </div>
        @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($categories as $category)
        <a href="{{ route('admin.settings.categories.show', $category->slug) }}" 
           class="group bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <i class="fas fa-chevron-right text-gray-400 group-hover:text-lime-500 transition-colors"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2 group-hover:text-lime-600 transition-colors">
                    {{ $category->titolo }}
                </h3>
                <p class="text-gray-500 text-sm mb-4">
                    {{ $category->descrizione ?: 'Nessuna descrizione' }}
                </p>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-gray-400">
                        <i class="fas fa-database mr-1"></i> 
                        {{ $category->settings->count() }} impostazioni
                    </span>
                    @if($category->tabella_riferimento)
                    <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded">
                        {{ $category->tabella_riferimento }}
                    </span>
                    @endif
                </div>
            </div>
        </a>
        @endforeach
    </div>

    @if($categories->isEmpty())
    <div class="bg-white rounded-xl shadow-lg p-12 text-center">
        <i class="fas fa-folder-open text-gray-300 text-6xl mb-4"></i>
        <p class="text-gray-500">Nessuna categoria impostata</p>
        @if(auth()->guard('admin')->user()->hasPermission('edit_settings'))
        <button type="button" onclick="openCategoryModal()" class="mt-4 inline-block text-lime-600 hover:text-lime-700">
            <i class="fas fa-plus mr-1"></i> Crea la prima categoria
        </button>
        @endif
    </div>
    @endif
</div>

<!-- Modal per creazione categoria -->
<div id="categoryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-plus mr-2 text-lime-600"></i> Nuova Categoria
            </h3>
            <button onclick="closeCategoryModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="categoryForm" method="POST" action="{{ route('admin.settings.categories.store') }}">
            @csrf
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Titolo <span class="text-red-500">*</span></label>
                <input type="text" name="titolo" id="titolo" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                <span class="text-xs text-red-500 hidden" id="titoloError"></span>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Descrizione</label>
                <textarea name="descrizione" id="descrizione" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"></textarea>
                <span class="text-xs text-red-500 hidden" id="descrizioneError"></span>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Tabella di Riferimento</label>
                <input type="text" name="tabella_riferimento" id="tabella_riferimento"
                       placeholder="es: contacts, expiration, vehicle_documents"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                <p class="text-xs text-gray-500 mt-1">Nome della tabella associata a questa categoria</p>
                <span class="text-xs text-red-500 hidden" id="tabellaRiferimentoError"></span>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Ordinamento</label>
                <input type="number" name="ordinamento" id="ordinamento" value="0"
                       class="w-32 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                <span class="text-xs text-red-500 hidden" id="ordinamentoError"></span>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                <button type="button" onclick="closeCategoryModal()" 
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                    Annulla
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-lime-600 hover:bg-lime-700 text-white rounded-md transition-colors">
                    <i class="fas fa-save mr-2"></i> Crea Categoria
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCategoryModal() {
    document.getElementById('categoryModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden'; // Previene lo scroll della pagina
}

function closeCategoryModal() {
    document.getElementById('categoryModal').classList.add('hidden');
    document.body.style.overflow = 'auto'; // Riabilita lo scroll
    resetForm();
}

function resetForm() {
    document.getElementById('categoryForm').reset();
    // Nascondi tutti gli errori
    document.querySelectorAll('#categoryForm .text-red-500').forEach(el => {
        el.classList.add('hidden');
        el.textContent = '';
    });
}

// Chiudi il modal cliccando fuori
document.getElementById('categoryModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCategoryModal();
    }
});

// Gestione submit del form via AJAX
document.getElementById('categoryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch(this.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Ricarica la pagina o aggiungi dinamicamente la nuova categoria
            window.location.reload();
        } else {
            // Mostra errori di validazione
            if (data.errors) {
                for (const [field, errors] of Object.entries(data.errors)) {
                    const errorSpan = document.getElementById(`${field}Error`);
                    if (errorSpan) {
                        errorSpan.textContent = errors[0];
                        errorSpan.classList.remove('hidden');
                    }
                }
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Si è verificato un errore durante il salvataggio.');
    });
});
</script>
@endsection