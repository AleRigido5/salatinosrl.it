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
            <i class="fas fa-address-card mr-2 text-purple-500"></i> Contatti
        </h3>
        <button type="button" 
                wire:click="showCreateForm"
                class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-md text-sm transition-colors">
            <i class="fas fa-plus mr-1"></i> Nuovo Contatto
        </button>
    </div>

    <!-- Tabella Contatti -->
    @if(count($contacts) > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Tipo</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Valore</th>
                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500">Principale</th>
                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500">Azioni</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($contacts as $contact)
                <tr>
                    <td class="px-3 py-2 text-gray-700">
                        @php
                            $tipo = $contact['setting']['valore'] ?? 'Contatto';
                            $icona = 'fa-address-card';
                            if($tipo == 'Telefono') $icona = 'fa-phone';
                            elseif($tipo == 'Cellulare') $icona = 'fa-mobile-alt';
                            elseif($tipo == 'Email') $icona = 'fa-envelope';
                            elseif($tipo == 'Fax') $icona = 'fa-fax';
                        @endphp
                        <i class="fas {{ $icona }} text-gray-500 mr-2"></i>
                        {{ $tipo }}
                    </td>
                    <td class="px-3 py-2">
                        @if(filter_var($contact['valore'], FILTER_VALIDATE_EMAIL))
                            <a href="mailto:{{ $contact['valore'] }}" class="text-blue-600 hover:text-blue-800">
                                {{ $contact['valore'] }}
                            </a>
                        @elseif(preg_match('/^[0-9+\-\s\(\)]+$/', $contact['valore']))
                            <a href="tel:{{ $contact['valore'] }}" class="text-gray-800 hover:text-blue-600">
                                {{ $contact['valore'] }}
                            </a>
                        @else
                            <span class="text-gray-800">{{ $contact['valore'] }}</span>
                        @endif
                    </td>
                    <td class="px-3 py-2 text-center">
                        @if($contact['principale'])
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i> Sì
                            </span>
                        @else
                            <span class="text-gray-400 text-xs">No</span>
                        @endif
                    </td>
                    <td class="px-3 py-2 text-center">
                        <button type="button" wire:click="editContact({{ $contact['id'] }})" class="text-blue-600 hover:text-blue-800 mr-2">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" wire:click="confirmDelete({{ $contact['id'] }})" class="text-red-600 hover:text-red-800">
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
        <i class="fas fa-address-card text-gray-400 mr-1"></i> Nessun contatto disponibile
    </p>
    @endif

    <!-- MODAL Contatto -->
    @if($showForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" 
         x-data="{ show: true }" 
         x-show="show"
         x-transition.opacity.duration.200ms>
        
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6" 
             x-on:click.away="show = false; $wire.cancelEdit()"
             x-transition.scale.origin.top>
            
            <div class="flex justify-between items-center mb-4 border-b pb-3">
                <h3 class="text-xl font-bold text-gray-800">
                    <i class="fas {{ $editingContactId ? 'fa-edit' : 'fa-plus-circle' }} mr-2 text-green-600"></i>
                    {{ $editingContactId ? 'Modifica Contatto' : 'Nuovo Contatto' }}
                </h3>
                <button type="button" wire:click="cancelEdit" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo Contatto <span class="text-red-500">*</span></label>
                    <select wire:model="id_settings" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="">Seleziona tipo...</option>
                        @foreach($contactTypes as $type)
                            <option value="{{ $type['id'] }}">{{ $type['valore'] }}</option>
                        @endforeach
                    </select>
                    @error('id_settings') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valore <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="valore" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                    @error('valore') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="inline-flex items-center">
                        <input type="checkbox" wire:model="principale" class="rounded border-gray-300 text-green-600">
                        <span class="ml-2 text-sm text-gray-700">Imposta come contatto principale</span>
                    </label>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                <button type="button" wire:click="cancelEdit" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md">
                    Annulla
                </button>
                <button type="button" wire:click="confirmSave" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md">
                    <i class="fas fa-save mr-2"></i> Salva
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL Conferma Eliminazione Contatto -->
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
            <p class="text-gray-700">Sei sicuro di voler eliminare il contatto:</p>
            <p class="text-gray-900 font-semibold mt-2">{{ $contactToDeleteName }}</p>
            <p class="text-red-600 text-sm mt-3">⚠️ Questa azione è irreversibile!</p>
            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                <button type="button" wire:click="cancelDelete" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md">
                    Annulla
                </button>
                <button type="button" wire:click="deleteContact" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md">
                    <i class="fas fa-trash-alt mr-2"></i> Elimina
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL Conferma Salvataggio -->
    @if($showConfirmModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
            <div class="flex justify-between items-center mb-4 border-b pb-3">
                <h3 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-question-circle mr-2 text-yellow-600"></i> Conferma Salvataggio
                </h3>
                <button type="button" wire:click="cancelConfirm" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <p class="text-gray-700">Confermi il salvataggio del contatto?</p>
            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                <button type="button" wire:click="cancelConfirm" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md">
                    Annulla
                </button>
                <button type="button" wire:click="saveContact" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md">
                    <i class="fas fa-check mr-2"></i> Conferma
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