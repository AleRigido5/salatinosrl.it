<div>
    <script>
        document.addEventListener('livewire:init', function () {
            // Ascolta l'evento per nascondere la notifica dopo 3 secondi
            Livewire.on('hide-notification-after-3-seconds', () => {
                setTimeout(() => {
                    Livewire.dispatch('hideNotification');
                }, 3000);
            });
        });
    </script>
    
    <!-- Header con bottone -->
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-800">
            <i class="fas fa-map-marker-alt mr-2 text-orange-500"></i> Indirizzi
        </h3>
        <button type="button" 
                wire:click="showCreateForm"
                class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-md text-sm transition-colors">
            <i class="fas fa-plus mr-1"></i> Nuovo Indirizzo
        </button>
    </div>

    <!-- Tabella Indirizzi -->
    @if(count($addresses) > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Sede</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Indirizzo</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Città</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Provincia</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">CAP</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Nazione</th>
                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500">Azioni</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($addresses as $address)
                <tr>
                    <td class="px-3 py-2 text-gray-700">{{ $address['sede'] ?? '-' }}</td>
                    <td class="px-3 py-2 text-gray-700">{{ $address['indirizzo'] ?? '-' }}</td>
                    <td class="px-3 py-2 text-gray-700">{{ $address['citta'] ?? '-' }}</td>
                    <td class="px-3 py-2 text-gray-700">{{ $address['provincia'] ?? '-' }}</td>
                    <td class="px-3 py-2 text-gray-700">{{ $address['cap'] ?? '-' }}</td>
                    <td class="px-3 py-2 text-gray-700">{{ $address['nazione'] ?? 'Italia' }}</td>
                    <td class="px-3 py-2 text-center">
                        <button type="button" wire:click="editAddress({{ $address['id_indirizzo'] }})" class="text-blue-600 hover:text-blue-800 mr-2">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" wire:click="confirmDelete({{ $address['id_indirizzo'] }})" class="text-red-600 hover:text-red-800">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p class="text-gray-500 text-sm text-center py-4 bg-gray-50 rounded-lg">
        <i class="fas fa-map-marker-alt text-gray-400 mr-1"></i> Nessun indirizzo disponibile
    </p>
    @endif

    <!-- MODAL Indirizzo -->
    @if($showForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" 
         x-data="{ show: true }" 
         x-show="show"
         x-transition.opacity.duration.200ms>
        
        <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl p-6" 
             x-on:click.away="show = false; $wire.cancelEdit()"
             x-transition.scale.origin.top>
            
            <div class="flex justify-between items-center mb-4 border-b pb-3">
                <h3 class="text-xl font-bold text-gray-800">
                    <i class="fas {{ $editingAddressId ? 'fa-edit' : 'fa-plus-circle' }} mr-2 text-green-600"></i>
                    {{ $editingAddressId ? 'Modifica Indirizzo' : 'Nuovo Indirizzo' }}
                </h3>
                <button type="button" wire:click="cancelEdit" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- RIGA 1: Sede (col 4) + Indirizzo (col 8) -->
            <div class="grid grid-cols-12 gap-4 mb-4">
                <div class="col-span-12 md:col-span-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sede</label>
                    <select wire:model="sede" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="principale">Sede Principale</option>
                        <option value="legale">Sede Legale</option>
                        <option value="operativa">Sede Operativa</option>
                        <option value="amministrativa">Sede Amministrativa</option>
                        <option value="fiscale">Sede Fiscale</option>
                    </select>
                </div>
                
                <div class="col-span-12 md:col-span-8">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Indirizzo</label>
                    <input type="text" wire:model="indirizzo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Via/Piazza, numero">
                </div>
            </div>
            
            <!-- RIGA 2: Città (col 4) + Provincia (col 2) + CAP (col 3) + Nazione (col 3) -->
            <div class="grid grid-cols-12 gap-4 mb-4">
                <div class="col-span-12 md:col-span-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Città</label>
                    <input type="text" wire:model="citta" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Città">
                </div>
                
                <div class="col-span-12 md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Provincia</label>
                    <input type="text" wire:model="provincia" maxlength="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 uppercase" placeholder="Provincia">
                </div>
                
                <div class="col-span-12 md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">CAP</label>
                    <input type="text" wire:model="cap" maxlength="10" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="CAP">
                </div>
                
                <div class="col-span-12 md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nazione</label>
                    <input type="text" wire:model="nazione" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Nazione">
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                <button type="button" wire:click="cancelEdit" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md">
                    Annulla
                </button>
                <button type="button" wire:click="saveAddress" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md">
                    <i class="fas fa-save mr-2"></i> Salva
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL Conferma Eliminazione -->
    @if($showDeleteModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
            <div class="flex justify-between items-center mb-4 border-b pb-3">
                <h3 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-exclamation-triangle mr-2 text-red-600"></i> Conferma Eliminazione
                </h3>
                <button type="button" wire:click="cancelDelete" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <p class="text-gray-700">Sei sicuro di voler eliminare l'indirizzo:</p>
            <p class="text-gray-900 font-semibold mt-2">{{ $addressToDeleteName }}</p>
            <p class="text-red-600 text-sm mt-3">⚠️ Questa azione è irreversibile!</p>
            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                <button type="button" wire:click="cancelDelete" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md">
                    Annulla
                </button>
                <button type="button" wire:click="deleteAddress" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md">
                    <i class="fas fa-trash-alt mr-2"></i> Elimina
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- NOTIFICA -->
    @if($showNotification)
    <div class="fixed bottom-4 right-4 z-50 animate-slide-up">
        <div class="rounded-lg shadow-lg p-4 {{ $notificationType == 'success' ? 'bg-green-500' : 'bg-red-500' }} text-white">
            <div class="flex items-center">
                <i class="fas {{ $notificationType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' }} mr-2"></i>
                <span>{{ $notificationMessage }}</span>
            </div>
        </div>
    </div>
    @endif

    <style>
    @keyframes slideUp {
        from {
            transform: translateY(100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    .animate-slide-up {
        animation: slideUp 0.3s ease-out;
    }
    </style>
</div>