@extends('admin.layouts.app')

@section('title', 'Gestione Clienti / Fornitori')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div class="flex space-x-3">
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-building mr-2 text-lime-600"></i> Gestione Clienti / Fornitori
            </h1>
        </div>
        
        <div class="flex items-center space-x-3">
            @if(auth()->guard('admin')->user()->hasPermission('create_entities'))
            <div class="relative group">
                <button onclick="Livewire.dispatch('openCreateModal')"
                        class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-5 py-2.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                    <i class="fas fa-plus"></i>
                </button>
                <div class="absolute bottom-full transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                    Nuovo Cliente / Fornitore
                    <div class="absolute top-full transform -translate-x-1/2 -mt-1 border-4 border-transparent border-t-gray-800"></div>
                </div>
            </div>
            @endif
            
            <!-- Pulsante Cestino con badge contatore -->
            <div class="relative group">
                <button onclick="Livewire.dispatch('openTrashModal')"
                        id="trashButton"
                        class="relative px-5 py-2.5 rounded-lg shadow-md transition-all duration-200 bg-gray-200 text-gray-700 hover:bg-gray-300">
                    <i class="fas fa-trash-alt"></i>
                    <span id="trashCountBadge" 
                          class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center shadow-md"
                          style="{{ $trashCount == 0 ? 'display: none;' : '' }}">
                        {{ $trashCount }}
                    </span>
                </button>
                <div class="absolute bottom-full transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                    Cestino
                    <div class="absolute top-full transform -translate-x-1/2 -mt-1 border-4 border-transparent border-t-gray-800"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Componente Livewire -->
    @livewire('admin.entities-table')
</div>

<script>
    // Ascolta gli eventi per aggiornare il badge in tempo reale
    document.addEventListener('livewire:initialized', () => {
        // Evento per aggiornare il conteggio
        Livewire.on('trashCountUpdated', (data) => {
            const badge = document.getElementById('trashCountBadge');
            if (badge) {
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'flex';
                } else {
                    badge.textContent = '0';
                    badge.style.display = 'none';
                }
            }
        });
        
        // Aggiorna il badge anche quando il componente viene renderizzato
        Livewire.on('tableRefreshed', () => {
            Livewire.dispatch('updateTrashCount');
        });
    });
</script>
@endsection