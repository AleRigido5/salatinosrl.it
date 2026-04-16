{{-- resources/views/admin/documents/index.blade.php --}}

@extends('admin.layouts.app')

@section('title', 'Gestione Documenti - ' . $title)

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
                <p class="text-gray-500 mt-1">
                    Personale: <strong>{{ $title }}</strong>
                </p>
            </div>
            <div class="flex gap-3">
                <a href="{{ $backUrl }}" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Torna indietro
                </a>
            </div>
        </div>
    </div>

    <!-- Messaggi - Versione con JavaScript per evitare duplicati -->
    <div id="alert-messages" class="mb-4"></div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form di upload -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-cloud-upload-alt mr-2 text-purple-600"></i>
                    Carica Nuovo Documento
                </h2>
                
                <form action="{{ route('admin.documents.store', [$tableRef, $idRef]) }}" 
                      method="POST" 
                      enctype="multipart/form-data"
                      id="upload-form">
                    @csrf
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                File <span class="text-red-500">*</span>
                            </label>
                            <input type="file" 
                                   name="document_file" 
                                   accept=".pdf,.jpg,.jpeg"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                                   required>
                            <p class="text-xs text-gray-400 mt-1">Max 5MB. Formati supportati: PDF, JPG, JPEG</p>
                            @error('document_file')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Titolo</label>
                            <input type="text" 
                                   name="titolo" 
                                   placeholder="Titolo del documento (opzionale)"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                            <textarea name="note" 
                                      rows="3"
                                      placeholder="Note sul documento..."
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"></textarea>
                        </div>
                        
                        <button type="submit" 
                                class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md transition-colors flex items-center justify-center">
                            <i class="fas fa-upload mr-2"></i>
                            Carica Documento
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Lista documenti -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow border border-gray-200">
                <div class="border-b px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-list mr-2 text-purple-600"></i>
                        Documenti Allegati
                        @if(count($documents) > 0)
                        <span class="ml-2 px-2 py-0.5 text-xs bg-purple-100 text-purple-800 rounded-full">
                            {{ count($documents) }}
                        </span>
                        @endif
                    </h2>
                </div>
                
                <div class="p-6">
                    @if(count($documents) == 0)
                    <div class="text-center py-8">
                        <i class="fas fa-folder-open text-gray-400 text-5xl mb-3"></i>
                        <p class="text-gray-500">Nessun documento allegato</p>
                        <p class="text-xs text-gray-400 mt-1">Utilizza il form a sinistra per caricare documenti</p>
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
                                <a href="{{ route('admin.documents.download', [$tableRef, $idRef, $doc->id]) }}" 
                                   class="text-blue-600 hover:text-blue-800 transition-colors p-2"
                                   title="Scarica">
                                    <i class="fas fa-download"></i>
                                </a>
                                <form action="{{ route('admin.documents.destroy', [$tableRef, $idRef, $doc->id]) }}" 
                                      method="POST" 
                                      class="inline"
                                      onsubmit="return confirm('Sei sicuro di voler eliminare questo documento?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="text-red-600 hover:text-red-800 transition-colors p-2"
                                            title="Elimina">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
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

<script>
// Mostra i messaggi di sessione una sola volta
document.addEventListener('DOMContentLoaded', function() {
    // Rimuovi eventuali messaggi duplicati
    const messages = document.querySelectorAll('.bg-green-100, .bg-red-100');
    const uniqueMessages = new Map();
    
    messages.forEach(msg => {
        const text = msg.innerText;
        if (uniqueMessages.has(text)) {
            msg.remove();
        } else {
            uniqueMessages.set(text, msg);
        }
    });
    
    // Auto-scomparsa dopo 5 secondi
    setTimeout(function() {
        document.querySelectorAll('.bg-green-100, .bg-red-100').forEach(function(el) {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity = '0';
            setTimeout(function() {
                el.remove();
            }, 500);
        });
    }, 5000);
});
</script>
@endsection