{{-- resources/views/admin/documents/index.blade.php --}}

@extends('admin.layouts.app')

@section('title', 'Gestione Documenti - ' . ($title ?? 'Documenti'))

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-paperclip mr-2 text-purple-600"></i>
                    Gestione Documenti
                </h1>
                @if(!empty($title) && $title != 'Documenti Personale' && $title != 'Documenti Scadenza')
                <p class="text-gray-500 mt-1">
                    <i class="fas fa-user mr-1"></i> Personale: <strong>{{ $title }}</strong>
                </p>
                @elseif(!empty($title))
                <p class="text-gray-500 mt-1">
                    <i class="fas fa-folder mr-1"></i> {{ $title }}
                </p>
                @endif
            </div>
            <div class="flex gap-3">
                <a href="{{ $backUrl }}" 
                   class="text-gray-500 hover:text-gray-600 px-4 py-2 rounded-lg transition-colors flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>
                </a>
            </div>
        </div>
    </div>
    

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form di upload multiplo -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-cloud-upload-alt mr-2 text-purple-600"></i>
                    Carica Documenti Multipli
                </h2>
                
                <form action="{{ route('admin.documents.store', [$tableRef, $idRef]) }}" 
                      method="POST" 
                      enctype="multipart/form-data"
                      id="upload-form">
                    @csrf
                    
                    <!-- Passa staff_id come campo hidden -->
                    <input type="hidden" name="staff_id" value="{{ $staffId ?? request()->get('staff_id') }}">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                File <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="file" 
                                    name="document_files[]" 
                                    accept=".pdf,.jpg,.jpeg"
                                    multiple
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 file:mr-4 file:py-2 file:px-4 file:rounded-l-md file:border-0 file:text-sm file:font-semibold file:bg-purple-600 file:text-white file:hover:bg-purple-700 file:cursor-pointer"
                                    required>
                                <p class="text-xs text-gray-400 mt-1">Max 5MB per file. Formati supportati: PDF, JPG, JPEG. Puoi selezionare più file contemporaneamente.</p>
                            </div>
                            @error('document_files')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Titolo (opzionale)</label>
                            <input type="text" 
                                   name="titolo" 
                                   placeholder="Titolo per tutti i documenti (verrà usato come prefisso)"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <p class="text-xs text-gray-400 mt-1">Se lasciato vuoto, verrà usato il nome del file</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Note (opzionale)</label>
                            <textarea name="note" 
                                      rows="3"
                                      placeholder="Note per tutti i documenti..."
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"></textarea>
                        </div>
                        
                        <button type="submit" 
                                class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md transition-colors flex items-center justify-center">
                            <i class="fas fa-upload mr-2"></i>
                            Carica Documenti
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Lista documenti -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow border border-gray-200">
                <div class="border-b px-6 py-4 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-list mr-2 text-purple-600"></i>
                        Documenti Allegati
                        @if(count($documents) > 0)
                        <span class="ml-2 px-2 py-0.5 text-xs bg-purple-100 text-purple-800 rounded-full">
                            {{ count($documents) }}
                        </span>
                        @endif
                    </h2>
                    @if(count($documents) > 0)
                    <button type="button" 
                            onclick="openDeleteAllModal()"
                            class="text-red-600 hover:text-red-800 text-sm transition-colors">
                        <i class="fas fa-trash-alt mr-1"></i> Elimina tutti
                    </button>
                    @endif
                </div>
                
                <div class="p-6">
                    @if(count($documents) == 0)
                    <div class="text-center py-8">
                        <i class="fas fa-folder-open text-gray-400 text-5xl mb-3"></i>
                        <p class="text-gray-500">Nessun documento allegato</p>
                        <p class="text-xs text-gray-400 mt-1">Utilizza il form a sinistra per caricare documenti (puoi selezionare più file alla volta)</p>
                    </div>
                    @else
                    <div class="space-y-3">
                        @foreach($documents as $doc)
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200 hover:bg-gray-100 transition">
                            <div class="flex items-center space-x-4">
                                <i class="fas {{ $doc->icon }} text-2xl"></i>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $doc->titolo }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $doc->file_name }}</p>
                                    @if($doc->note)
                                    <p class="text-xs text-gray-400 mt-1">{{ $doc->note }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <a href="{{ route('admin.documents.download', [$tableRef, $idRef, $doc->id]) . '?staff_id=' . ($staffId ?? request()->get('staff_id')) }}" 
                                   class="text-blue-600 hover:text-blue-800 transition-colors p-2"
                                   title="Scarica">
                                    <i class="fas fa-download"></i>
                                </a>
                                <button type="button" 
                                        onclick="openDeleteModal('{{ $tableRef }}', '{{ $idRef }}', '{{ $doc->id }}', '{{ addslashes($doc->titolo) }}')"
                                        class="text-red-600 hover:text-red-800 transition-colors p-2"
                                        title="Elimina">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODALE CONFERMA ELIMINAZIONE SINGOLA -->
<div id="deleteModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Conferma eliminazione
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500" id="deleteModalMessage">
                                Sei sicuro di voler eliminare questo documento? Questa azione non può essere annullata.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <form id="deleteForm" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="staff_id" value="{{ $staffId ?? request()->get('staff_id') }}">
                    <button type="submit" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Elimina
                    </button>
                </form>
                <button type="button" 
                        onclick="closeDeleteModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Annulla
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODALE CONFERMA ELIMINAZIONE TUTTI -->
<div id="deleteAllModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Conferma eliminazione tutti
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Sei sicuro di voler eliminare <strong>TUTTI</strong> i documenti? Questa azione non può essere annullata.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <form id="deleteAllForm" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="staff_id" value="{{ $staffId ?? request()->get('staff_id') }}">
                    <button type="submit" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Elimina tutti
                    </button>
                </form>
                <button type="button" 
                        onclick="closeDeleteAllModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Annulla
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Modale per eliminazione singola
function openDeleteModal(tableRef, idRef, documentId, documentTitle) {
    const modal = document.getElementById('deleteModal');
    const message = document.getElementById('deleteModalMessage');
    const form = document.getElementById('deleteForm');
    
    message.innerHTML = `Sei sicuro di voler eliminare il documento "<strong>${documentTitle}</strong>"? Questa azione non può essere annullata.`;
    form.action = `/admin/documents/${tableRef}/${idRef}/${documentId}`;
    
    modal.classList.remove('hidden');
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.classList.add('hidden');
}

// Modale per eliminazione tutti
function openDeleteAllModal() {
    const modal = document.getElementById('deleteAllModal');
    const form = document.getElementById('deleteAllForm');
    form.action = `/admin/documents/{{ $tableRef }}/{{ $idRef }}/all`;
    modal.classList.remove('hidden');
}

function closeDeleteAllModal() {
    const modal = document.getElementById('deleteAllModal');
    modal.classList.add('hidden');
}

// Chiudi modale cliccando fuori
document.addEventListener('click', function(event) {
    const deleteModal = document.getElementById('deleteModal');
    const deleteAllModal = document.getElementById('deleteAllModal');
    
    if (event.target === deleteModal) {
        closeDeleteModal();
    }
    if (event.target === deleteAllModal) {
        closeDeleteAllModal();
    }
});

// Chiudi modale con tasto ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeDeleteModal();
        closeDeleteAllModal();
    }
});
</script>

<style>
    /* Animazione per il fade in del modal */
    .fixed.inset-0 {
        transition: opacity 0.3s ease;
    }
</style>
@endsection