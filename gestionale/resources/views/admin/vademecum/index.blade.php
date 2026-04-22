{{-- resources/views/admin/vademecum/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Gestione VADEMECUM')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fa-solid fa-circle-info mr-2 text-red-500"></i> Gestione VADEMECUM ASSUNZIONE
        </h1>
    </div>
    
    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200 p-6">
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">
                {{ session('success') }}
            </div>
        @endif
        
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg">
                {{ session('error') }}
            </div>
        @endif
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- PDF Attuale -->
            <div class="border rounded-lg p-4">
                <h2 class="text-lg font-semibold mb-3">PDF Attuale</h2>
                @if($currentPdf)
                    <div class="mb-3">
                        <p><strong>Nome file:</strong> {{ $currentPdf['filename'] }}</p>
                        <p><strong>Dimensione:</strong> {{ number_format($currentPdf['size'] / 1024, 2) }} KB</p>
                        <p><strong>Ultima modifica:</strong> {{ date('d/m/Y H:i:s', $currentPdf['last_modified']) }}</p>
                    </div>
                    <div class="flex gap-3">
                        <a href="{{ route('admin.vademecum.pdf') }}" target="_blank" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <i class="fas fa-eye mr-2"></i> Visualizza PDF
                        </a>
                        <a href="{{ route('admin.vademecum.pdf') }}" download class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                            <i class="fas fa-download mr-2"></i> Scarica
                        </a>
                    </div>
                @else
                    <p class="text-gray-500 italic">Nessun PDF caricato</p>
                @endif
            </div>
            
            <!-- Upload Nuovo PDF -->
            <div class="border rounded-lg p-4">
                <h2 class="text-lg font-semibold mb-3">Carica Nuovo PDF</h2>
                <form action="{{ route('admin.vademecum.upload') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Seleziona file PDF</label>
                        <input type="file" name="pdf_file" accept=".pdf" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <p class="text-xs text-gray-500 mt-1">Max 10MB, solo PDF. Il file verrà rinominato automaticamente mantenendo l'estensione .pdf</p>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        <i class="fas fa-upload mr-2"></i> Carica PDF
                    </button>
                </form>
            </div>
        </div>
        
        <div class="mt-6 pt-4 border-t text-sm text-gray-500">
            <i class="fas fa-info-circle mr-1"></i>
            Il file PDF viene letto dinamicamente dalla cartella <code>public/vademecum/</code>. 
            Basta caricare un nuovo file per sostituire quello esistente.
        </div>
    </div>
</div>
@endsection