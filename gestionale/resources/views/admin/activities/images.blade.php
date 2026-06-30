{{-- resources/views/admin/activities/images.blade.php --}}

@extends('admin.layouts.app')

@section('title', 'Gestione Immagini - ' . ($activity->service->Titolo ?? 'Attività'))

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-images mr-2 text-lime-600"></i>
                    Gestione Immagini
                </h1>
                <p class="text-gray-500 mt-1">
                    <i class="fas fa-calendar mr-1"></i> 
                    {{ $activity->data_activities ? $activity->data_activities->format('d/m/Y') : 'Data non impostata' }}
                    @if($activity->service)
                        <span class="mx-2">|</span>
                        <i class="fas fa-concierge-bell mr-1"></i> {{ $activity->service->Titolo }}
                    @endif
                    @if($activity->costCenter)
                        <span class="mx-2">|</span>
                        <i class="fas fa-building mr-1"></i> {{ $activity->costCenter->Nome }}
                    @endif
                </p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.activities.index') }}" 
                   class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center" title="Torna alle attività">
                    <i class="fas fa-arrow-left"></i> 
                </a>
            </div>
        </div>
    </div>

    <!-- Drag & Drop Upload -->
    <div class="bg-white rounded-lg shadow border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">
            <i class="fas fa-cloud-upload-alt mr-2 text-lime-600"></i>
            Carica Immagini
            <span class="text-sm font-normal text-gray-500 ml-2">(trascina qui i file o clicca per selezionare)</span>
        </h2>
        
        <div id="dropzone" 
             class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-lime-400 transition-colors cursor-pointer"
             style="min-height: 200px;">
            
            <div id="dropzone-content">
                <i class="fas fa-cloud-upload-alt text-5xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">Trascina qui le immagini o clicca per selezionarle</p>
                <p class="text-xs text-gray-400 mt-2">Formati supportati: JPG, JPEG, PNG, GIF, WEBP (max 10MB per file)</p>
                <input type="file" id="file-input" accept="image/*" multiple style="display: none;">
            </div>
            
            <div id="upload-preview" class="hidden">
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3" id="preview-grid"></div>
                <div class="mt-4 flex justify-end gap-3">
                    <button type="button" id="cancel-upload" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-md">
                        <i class="fas fa-times mr-2"></i> Annulla
                    </button>
                    <button type="button" id="upload-files" class="px-4 py-2 bg-lime-600 hover:bg-lime-700 text-white rounded-md">
                        <i class="fas fa-upload mr-2"></i> Carica (0 file)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Gallery Immagini -->
    <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-images mr-2 text-lime-600"></i>
                Galleria Immagini
                @if($images->count() > 0)
                    <span class="ml-2 px-2 py-0.5 text-xs bg-lime-100 text-lime-800 rounded-full">
                        {{ $images->count() }}
                    </span>
                @endif
            </h2>
            @if($images->count() > 0)
                <button type="button" 
                        onclick="openDeleteAllModal()"
                        class="text-red-600 hover:text-red-800 text-sm transition-colors">
                    <i class="fas fa-trash-alt mr-1"></i> Elimina tutte
                </button>
            @endif
        </div>
        
        @if($images->count() == 0)
            <div class="text-center py-12">
                <i class="fas fa-images text-gray-300 text-5xl mb-3"></i>
                <p class="text-gray-500">Nessuna immagine caricata</p>
                <p class="text-xs text-gray-400 mt-1">Trascina le immagini nell'area sopra per caricarli</p>
            </div>
        @else
            <div id="image-grid" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @foreach($images as $index => $image)
                    @php
                        // Debug: generiamo l'URL in vari modi per testare
                        $s3Path = str_replace('s3://', '', $image->path_doc);
                        $fullS3Path = $s3Path . '/' . $image->file_name;
                        $debugUrl = $image->url;
                        $directUrl = Storage::disk('s3')->url($fullS3Path);
                        $temporaryUrl = Storage::disk('s3')->temporaryUrl($fullS3Path, now()->addMinutes(60));
                    @endphp
                    
                    <div class="image-item group relative bg-gray-100 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow" 
                         data-id="{{ $image->id }}">
                        
                        <!-- Tentativo 1: URL generato da getUrlAttribute() -->
                        <img src="{{ $debugUrl }}" 
                             alt="{{ $image->titolo ?? 'Immagine' }}" 
                             class="w-full h-48 object-cover cursor-pointer mt-12"
                             onerror="handleImageError(this, '{{ $directUrl }}', '{{ $temporaryUrl }}', '{{ $fullS3Path }}')"
                             onclick="openImageModal('{{ $debugUrl }}', '{{ $image->titolo }}')"
                             id="img-{{ $image->id }}">
                        
                        <!-- Overlay info -->
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <p class="text-white text-xs truncate">{{ $image->titolo ?? 'Immagine' }}</p>
                        </div>
                        
                        <!-- Pulsanti azioni -->
                        <div class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity z-20">
                            <button type="button" 
                                    onclick="openDeleteModal({{ $image->id }})"
                                    class="bg-red-500 hover:bg-red-600 text-white p-1.5 rounded-full shadow-md transition-colors"
                                    title="Elimina">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                            <button type="button" 
                                    onclick="openImageModal('{{ $debugUrl }}', '{{ $image->titolo }}')"
                                    class="bg-blue-500 hover:bg-blue-600 text-white p-1.5 rounded-full shadow-md transition-colors"
                                    title="Visualizza">
                                <i class="fas fa-expand text-xs"></i>
                            </button>
                        </div>
                        
                        <!-- Badge ordine -->
                        <div class="absolute top-12 left-2 bg-black/50 text-white text-xs px-2 py-1 rounded z-20">
                            #{{ $loop->iteration }}
                        </div>
                    </div>
                @endforeach
            </div>
            
            @if($images->count() > 1)
                <p class="text-xs text-gray-400 mt-4 text-center">
                    <i class="fas fa-arrows-alt mr-1"></i>
                    Trascina le immagini per riordinarle (funzionalità drag & drop)
                </p>
            @endif
        @endif
    </div>
</div>

<!-- ============================ -->
<!-- MODALI TAILWINDCSS -->
<!-- ============================ -->

<!-- Modal di conferma eliminazione singola -->
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
                                Sei sicuro di voler eliminare questa immagine? Questa azione non può essere annullata.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" 
                        id="confirmDeleteBtn"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Elimina
                </button>
                <button type="button" 
                        onclick="closeDeleteModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-lime-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Annulla
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal di conferma eliminazione tutte -->
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
                            Conferma eliminazione tutte
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Sei sicuro di voler eliminare <strong>TUTTE</strong> le immagini? Questa azione non può essere annullata.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" 
                        id="confirmDeleteAllBtn"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Elimina tutte
                </button>
                <button type="button" 
                        onclick="closeDeleteAllModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-lime-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Annulla
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal di errore -->
<div id="errorModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-times-circle text-red-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Errore
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500" id="errorModalMessage">
                                Si è verificato un errore.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" 
                        onclick="closeErrorModal()"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                    OK
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal di successo -->
<div id="successModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-check-circle text-green-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Successo
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500" id="successModalMessage">
                                Operazione completata con successo.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" 
                        onclick="closeSuccessModal()"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                    OK
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal visualizzazione immagine -->
<div id="image-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 py-8">
        <div class="fixed inset-0 bg-black/90 transition-opacity" onclick="closeImageModal()"></div>
        
        <div class="relative bg-white rounded-lg max-w-5xl w-full mx-auto max-h-[90vh] overflow-hidden">
            <button onclick="closeImageModal()" 
                    class="absolute top-4 right-4 z-10 bg-white/20 hover:bg-white/30 text-white p-2 rounded-full transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
            
            <div class="p-4">
                <img id="modal-image" src="" alt="Immagine" class="w-full h-auto max-h-[70vh] object-contain">
                <p id="modal-title" class="text-center text-gray-700 mt-3 font-medium"></p>
            </div>
        </div>
    </div>
</div>

<script>
    // ============================
    // HANDLER PER ERRORI IMMAGINI
    // ============================
    
    function handleImageError(img, directUrl, temporaryUrl, s3Path) {
        console.log('Errore caricamento immagine:', {
            currentSrc: img.src,
            directUrl: directUrl,
            temporaryUrl: temporaryUrl,
            s3Path: s3Path
        });
        
        // Prova con URL diretto
        if (img.src !== directUrl && directUrl) {
            console.log('Tentativo con URL diretto:', directUrl);
            img.src = directUrl;
            return;
        }
        
        // Prova con URL temporaneo
        if (img.src !== temporaryUrl && temporaryUrl) {
            console.log('Tentativo con URL temporaneo:', temporaryUrl);
            img.src = temporaryUrl;
            return;
        }
        
        // Se tutto fallisce, mostra un'immagine di placeholder
        img.src = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22%3E%3Crect fill=%22%23f3f4f6%22 width=%22200%22 height=%22200%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%239ca3af%22 font-size=%2214%22 font-family=%22sans-serif%22%3EImmagine non disponibile%3C/text%3E%3C/svg%3E';
        img.style.objectFit = 'contain';
        img.style.padding = '20px';
        
        // Mostra il path nell'immagine per debug
        console.error('Tutti i tentativi falliti per:', s3Path);
    }
    
    // ============================
    // FUNZIONI MODALI
    // ============================
    
    // Modale eliminazione singola
    let deleteImageId = null;
    
    function openDeleteModal(imageId) {
        deleteImageId = imageId;
        document.getElementById('deleteModal').classList.remove('hidden');
    }
    
    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
        deleteImageId = null;
    }
    
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (deleteImageId !== null) {
            performDelete(deleteImageId);
        }
        closeDeleteModal();
    });
    
    // Modale eliminazione tutte
    function openDeleteAllModal() {
        document.getElementById('deleteAllModal').classList.remove('hidden');
    }
    
    function closeDeleteAllModal() {
        document.getElementById('deleteAllModal').classList.add('hidden');
    }
    
    document.getElementById('confirmDeleteAllBtn').addEventListener('click', function() {
        closeDeleteAllModal();
        performDeleteAll();
    });
    
    // Modale errore
    function showErrorModal(message) {
        document.getElementById('errorModalMessage').textContent = message || 'Si è verificato un errore.';
        document.getElementById('errorModal').classList.remove('hidden');
    }
    
    function closeErrorModal() {
        document.getElementById('errorModal').classList.add('hidden');
    }
    
    // Modale successo
    function showSuccessModal(message) {
        document.getElementById('successModalMessage').textContent = message || 'Operazione completata con successo.';
        document.getElementById('successModal').classList.remove('hidden');
    }
    
    function closeSuccessModal() {
        document.getElementById('successModal').classList.add('hidden');
    }
    
    // Chiudi modali cliccando fuori
    document.addEventListener('click', function(event) {
        const deleteModal = document.getElementById('deleteModal');
        const deleteAllModal = document.getElementById('deleteAllModal');
        const errorModal = document.getElementById('errorModal');
        const successModal = document.getElementById('successModal');
        const imageModal = document.getElementById('image-modal');
        
        if (event.target === deleteModal) {
            closeDeleteModal();
        }
        if (event.target === deleteAllModal) {
            closeDeleteAllModal();
        }
        if (event.target === errorModal) {
            closeErrorModal();
        }
        if (event.target === successModal) {
            closeSuccessModal();
        }
        if (event.target === imageModal && !event.target.closest('.relative.bg-white')) {
            closeImageModal();
        }
    });
    
    // Chiudi con ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeDeleteModal();
            closeDeleteAllModal();
            closeErrorModal();
            closeSuccessModal();
            closeImageModal();
        }
    });
    
    // ============================
    // FUNZIONI ELIMINAZIONE
    // ============================
    
    async function performDelete(imageId) {
        try {
            const response = await fetch('{{ route("admin.activities.images.destroy", ["activityId" => $activity->id, "imageId" => ":imageId"]) }}'.replace(':imageId', imageId), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                const item = document.querySelector(`.image-item[data-id="${imageId}"]`);
                if (item) {
                    item.style.opacity = '0';
                    item.style.transform = 'scale(0.8)';
                    setTimeout(() => {
                        item.remove();
                        if (document.querySelectorAll('.image-item').length === 0) {
                            window.location.reload();
                        } else {
                            showSuccessModal('Immagine eliminata con successo!');
                        }
                    }, 300);
                }
            } else {
                showErrorModal(result.message || 'Errore durante l\'eliminazione.');
            }
        } catch (error) {
            showErrorModal('Errore: ' + error.message);
        }
    }
    
    async function performDeleteAll() {
        try {
            const response = await fetch('{{ route("admin.activities.images.destroyAll", $activity->id) }}', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                showSuccessModal(result.message || 'Tutte le immagini sono state eliminate!');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showErrorModal(result.message || 'Errore durante l\'eliminazione.');
            }
        } catch (error) {
            showErrorModal('Errore: ' + error.message);
        }
    }
    
    // ============================
    // DRAG & DROP UPLOAD
    // ============================
    
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('file-input');
    const previewGrid = document.getElementById('preview-grid');
    const uploadPreview = document.getElementById('upload-preview');
    const dropzoneContent = document.getElementById('dropzone-content');
    const uploadBtn = document.getElementById('upload-files');
    const cancelBtn = document.getElementById('cancel-upload');
    
    let selectedFiles = [];
    
    // Clic sulla dropzone apre il file picker
    dropzone.addEventListener('click', (e) => {
        if (e.target.closest('button') || e.target.closest('#upload-preview')) return;
        fileInput.click();
    });
    
    // Drag & Drop events
    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('border-lime-500', 'bg-lime-50');
    });
    
    dropzone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        dropzone.classList.remove('border-lime-500', 'bg-lime-50');
    });
    
    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('border-lime-500', 'bg-lime-50');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFiles(files);
        }
    });
    
    // File input change
    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleFiles(e.target.files);
        }
        e.target.value = '';
    });
    
    // Gestione file selezionati
    function handleFiles(files) {
        const validFiles = Array.from(files).filter(file => 
            file.type.startsWith('image/') && file.size <= 10 * 1024 * 1024
        );
        
        if (validFiles.length === 0) {
            showErrorModal('Solo immagini (max 10MB) sono permesse.');
            return;
        }
        
        selectedFiles = [...selectedFiles, ...validFiles];
        updatePreview();
    }
    
    // Aggiorna anteprima
    function updatePreview() {
        previewGrid.innerHTML = '';
        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const div = document.createElement('div');
                div.className = 'relative bg-gray-100 rounded-lg overflow-hidden shadow-sm';
                div.innerHTML = `
                    <img src="${e.target.result}" class="w-full h-32 object-cover">
                    <button type="button" 
                            onclick="removeFile(${index})"
                            class="absolute top-1 right-1 bg-red-500 hover:bg-red-600 text-white p-1 rounded-full shadow-md">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                    <div class="absolute bottom-0 left-0 right-0 bg-black/50 text-white text-xs p-1 truncate">
                        ${file.name}
                    </div>
                `;
                previewGrid.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
        
        if (selectedFiles.length > 0) {
            dropzoneContent.classList.add('hidden');
            uploadPreview.classList.remove('hidden');
            uploadBtn.innerHTML = `<i class="fas fa-upload mr-2"></i> Carica (${selectedFiles.length} file)`;
        } else {
            dropzoneContent.classList.remove('hidden');
            uploadPreview.classList.add('hidden');
        }
    }
    
    // Rimuovi file
    window.removeFile = function(index) {
        selectedFiles.splice(index, 1);
        updatePreview();
    };
    
    // Annulla upload
    cancelBtn.addEventListener('click', () => {
        selectedFiles = [];
        updatePreview();
    });
    
    // Carica file
    uploadBtn.addEventListener('click', async () => {
        if (selectedFiles.length === 0) return;
        
        uploadBtn.disabled = true;
        uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Caricamento...';
        
        const formData = new FormData();
        selectedFiles.forEach(file => {
            formData.append('images[]', file);
        });
        formData.append('_token', '{{ csrf_token() }}');
        
        try {
            const response = await fetch('{{ route("admin.activities.images.store", $activity->id) }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            const text = await response.text();
            
            try {
                const result = JSON.parse(text);
                
                if (result.success) {
                    selectedFiles = [];
                    updatePreview();
                    showSuccessModal(result.message || 'Immagini caricate con successo!');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showErrorModal(result.message || 'Errore durante il caricamento');
                }
            } catch (jsonError) {
                console.error('Risposta non JSON:', text);
                showErrorModal('Errore del server: ' + text.substring(0, 200));
            }
        } catch (error) {
            showErrorModal('Errore di rete: ' + error.message);
        } finally {
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = `<i class="fas fa-upload mr-2"></i> Carica (${selectedFiles.length} file)`;
        }
    });
    
    // ============================
    // MODALE VISUALIZZAZIONE IMMAGINE
    // ============================
    
    function openImageModal(url, title) {
        const modal = document.getElementById('image-modal');
        const img = document.getElementById('modal-image');
        const titleEl = document.getElementById('modal-title');
        
        img.src = url;
        titleEl.textContent = title || 'Immagine';
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    
    function closeImageModal() {
        const modal = document.getElementById('image-modal');
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
    
    // ============================
    // DRAG & DROP PER RIORDINARE (SORTABLE)
    // ============================
    
    @if($images->count() > 1)
    document.addEventListener('DOMContentLoaded', function() {
        const grid = document.getElementById('image-grid');
        if (!grid) return;
        
        let dragItem = null;
        
        grid.addEventListener('dragstart', (e) => {
            const item = e.target.closest('.image-item');
            if (!item) return;
            dragItem = item;
            item.style.opacity = '0.5';
        });
        
        grid.addEventListener('dragend', (e) => {
            const item = e.target.closest('.image-item');
            if (!item) return;
            item.style.opacity = '1';
        });
        
        grid.addEventListener('dragover', (e) => {
            e.preventDefault();
            const target = e.target.closest('.image-item');
            if (!target || target === dragItem) return;
            
            const rect = target.getBoundingClientRect();
            const after = e.clientY > rect.top + rect.height / 2;
            
            if (after) {
                target.parentNode.insertBefore(dragItem, target.nextSibling);
            } else {
                target.parentNode.insertBefore(dragItem, target);
            }
        });
        
        grid.addEventListener('dragend', async () => {
            const items = grid.querySelectorAll('.image-item');
            const order = Array.from(items).map(item => parseInt(item.dataset.id));
            
            try {
                const response = await fetch('{{ route("admin.activities.images.update-order", $activity->id) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ order })
                });
                
                if (!response.ok) {
                    console.error('Errore aggiornamento ordine');
                }
            } catch (error) {
                console.error('Errore: ' + error.message);
            }
        });
    });
    @endif
</script>

<style>
    .image-item {
        transition: all 0.3s ease;
        cursor: grab;
    }
    .image-item:active {
        cursor: grabbing;
    }
    #dropzone {
        transition: all 0.3s ease;
    }
    #preview-grid img {
        pointer-events: none;
    }
    
    /* Animazione modali */
    .fixed.inset-0 {
        transition: opacity 0.3s ease;
    }
    
    .inline-block.align-bottom {
        animation: modalFadeIn 0.3s ease;
    }
    
    @keyframes modalFadeIn {
        from {
            opacity: 0;
            transform: scale(0.95) translateY(20px);
        }
        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }
</style>
@endsection