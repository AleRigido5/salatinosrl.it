<div>
    <!-- Modal di notifica -->
    @if($showNotification)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 transition-opacity duration-300"
         x-data="{ show: true }"
         x-show="show"
         x-transition.opacity.duration.200ms
         @hide-notification.window="setTimeout(() => show = false, 3000)">
        
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6 transform transition-all duration-300"
             x-show="show"
             x-transition.scale.origin.top>
            
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    @if($notificationType == 'success')
                        <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    @elseif($notificationType == 'error')
                        <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                    @else
                        <div class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                    @endif
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-gray-900">
                        {{ $notificationType == 'success' ? 'Successo!' : ($notificationType == 'error' ? 'Errore!' : 'Attenzione!') }}
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">{{ $notificationMessage }}</p>
                </div>
            </div>
            
            <div class="mt-4 flex justify-end">
                <button type="button"
                        wire:click="hideNotification"
                        class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-sm transition-colors">
                    Chiudi
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal di conferma per creazione/modifica -->
    @if($showConfirmModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 transition-opacity duration-300"
         x-data="{ show: true }"
         x-show="show"
         x-transition.opacity.duration.200ms>
        
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6 transform transition-all duration-300"
             x-show="show"
             x-transition.scale.origin.top>
            
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-gray-900">
                        {{ $confirmAction == 'update' ? 'Conferma Modifica' : 'Conferma Creazione' }}
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ $confirmAction == 'update' ? 'Vuoi modificare questo contatto?' : 'Vuoi aggiungere questo contatto?' }}
                    </p>
                    <div class="mt-3 bg-gray-50 p-3 rounded-md">
                        <p class="text-sm text-gray-600">
                            <span class="font-medium">Tipo:</span> 
                            @foreach($contactTypes as $type)
                                @if($type['id'] == $confirmData['id_settings'])
                                    {{ $type['valore'] }}
                                @endif
                            @endforeach
                        </p>
                        <p class="text-sm text-gray-600 mt-1">
                            <span class="font-medium">Valore:</span> {{ $confirmData['valore'] }}
                        </p>
                        @if($confirmData['principale'])
                            <p class="text-sm text-emerald-600 mt-1">
                                <i class="fas fa-star mr-1"></i> Impostato come contatto principale
                            </p>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button"
                        wire:click="cancelConfirm"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                    Annulla
                </button>
                <button type="button"
                        wire:click="saveContact"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md transition-colors">
                    Conferma
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal di conferma eliminazione -->
    @if($showDeleteModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 transition-opacity duration-300"
         x-data="{ show: true }"
         x-show="show"
         x-transition.opacity.duration.200ms>
        
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6 transform transition-all duration-300"
             x-show="show"
             x-transition.scale.origin.top>
            
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-gray-900">Conferma Eliminazione</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Sei sicuro di voler eliminare il seguente contatto?
                    </p>
                    <p class="text-sm font-semibold text-gray-700 mt-2 bg-gray-100 p-2 rounded">
                        {{ $contactToDeleteName }}
                    </p>
                    <p class="text-xs text-gray-400 mt-2">
                        Questa azione non può essere annullata.
                    </p>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button"
                        wire:click="cancelDelete"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                    Annulla
                </button>
                <button type="button"
                        wire:click="deleteContact"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md transition-colors">
                    Elimina
                </button>
            </div>
        </div>
    </div>
    @endif

    <div class="bg-gray-50 rounded-lg p-4 mt-4">
        <div class="flex justify-between items-center mb-3 border-b pb-2">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-address-card mr-2 text-orange-500"></i> Contatti
            </h3>
            @if(!$showForm)
            <button type="button" 
                    wire:click="showCreateForm"
                    class="text-emerald-600 hover:text-emerald-700 transition-colors text-sm">
                <i class="fas fa-plus mr-1"></i> Nuovo Contatto
            </button>
            @endif
        </div>
        
        <!-- Form di inserimento/modifica contatto -->
        @if($showForm)
        <div class="mb-4 p-3 bg-white rounded-lg border border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Tipo Contatto <span class="text-red-500">*</span></label>
                    <select wire:model="id_settings" 
                            class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleziona tipo</option>
                        @foreach($contactTypes as $type)
                            <option value="{{ $type['id'] }}">{{ $type['valore'] }}</option>
                        @endforeach
                    </select>
                    @error('id_settings') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Valore <span class="text-red-500">*</span></label>
                    <input type="text" 
                           wire:model="valore" 
                           placeholder="Inserisci il valore"
                           class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('valore') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div class="col-span-2">
                    <label class="inline-flex items-center">
                        <input type="checkbox" 
                               wire:model="principale" 
                               class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="ml-2 text-sm text-gray-700">Contatto principale</span>
                    </label>
                </div>
            </div>
            <div class="flex justify-end space-x-2 mt-3">
                <button type="button" 
                        wire:click="cancelEdit"
                        class="px-3 py-1 text-sm bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                    Annulla
                </button>
                <button type="button" 
                        wire:click="confirmSave"
                        class="px-3 py-1 text-sm bg-emerald-600 hover:bg-emerald-700 text-white rounded-md transition-colors">
                    <i class="fas fa-save mr-1"></i> {{ $editingContactId ? 'Aggiorna Contatto' : 'Salva Contatto' }}
                </button>
            </div>
        </div>
        @endif
        
        <!-- Tabella Contatti -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipologia</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Valore</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Principale</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($contacts as $contact)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2 text-gray-500">{{ $contact['id'] }}</td>
                        <td class="px-3 py-2">
                            <span class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-800">
                                {{ $contact['setting']['valore'] ?? ($contact['tipo_nome'] ?? 'Tipo sconosciuto') }}
                            </span>
                        </td>
                        <td class="px-3 py-2">
                            @php
                                $settingValore = $contact['setting']['valore'] ?? ($contact['tipo_nome'] ?? '');
                                $valore = $contact['valore'];
                            @endphp
                            @if($settingValore == 'Email' || $settingValore == 'PEC' || filter_var($valore, FILTER_VALIDATE_EMAIL))
                                <a href="mailto:{{ $valore }}" class="text-blue-600 hover:text-blue-800">
                                    {{ $valore }}
                                </a>
                            @elseif($settingValore == 'Telefono' || $settingValore == 'Cellulare' || preg_match('/^[0-9+\s\(\)\-]+$/', $valore))
                                <a href="tel:{{ $valore }}" class="text-gray-800 hover:text-emerald-600">
                                    {{ $valore }}
                                </a>
                            @elseif($settingValore == 'Sito Web' || str_starts_with($valore, 'http'))
                                <a href="{{ $valore }}" target="_blank" class="text-blue-600 hover:text-blue-800">
                                    {{ $valore }}
                                </a>
                            @else
                                {{ $valore }}
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            @if($contact['principale'])
                                <span class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i> Principale
                                </span>
                            @else
                                <span class="text-gray-400 text-xs">-</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex space-x-2">
                                <button type="button" 
                                        wire:click="editContact({{ $contact['id'] }})"
                                        class="text-yellow-600 hover:text-yellow-800 transition-colors"
                                        title="Modifica Contatto">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" 
                                        wire:click="confirmDelete({{ $contact['id'] }})"
                                        class="text-red-600 hover:text-red-800 transition-colors"
                                        title="Elimina Contatto">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-3 py-6 text-center text-gray-400">
                            <i class="fas fa-address-card text-2xl mb-2 block"></i>
                            Nessun contatto associato
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>