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

    <!-- Tabella Contatti con layout: offset-md-2 - Tipo (2) - Valore (2) - Note (2) - Principale (1) - Azioni (1) - offset-md-2 -->
    @if(count($contacts) > 0)
    <div class="overflow-x-auto">
        <div class="flex justify-center">
            <div class="w-full" style="max-width: 60%;">
                <table class="min-w-full divide-y divide-gray-200 text-sm relative">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 w-1/6">Tipo</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 w-1/4">Valore</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 w-1/4">Note</th>
                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 w-1/12">Principale</th>
                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 w-1/6">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($contacts as $contact)
                        <tr class="hover:bg-gray-50 transition-colors relative">
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
                            <td class="px-3 py-2 text-gray-600 relative">
                                @php
                                    $useTextarea = $contact['note'] && strlen($contact['note']) > 50;
                                @endphp
                                <div x-data="{ 
                                    noteValue: {{ json_encode($contact['note'] ?? '') }},
                                    showTooltip: false,
                                    isEditing: false,
                                    editedValue: {{ json_encode($contact['note'] ?? '') }},
                                    
                                    saveNote() {
                                        this.isEditing = true;
                                        @this.call('updateNote', {{ $contact['id'] }}, this.editedValue)
                                            .then(() => {
                                                this.isEditing = false;
                                                this.showTooltip = false;
                                                this.noteValue = this.editedValue;
                                                // Aggiorna il DOM senza reload
                                                const span = this.$el.closest('td').querySelector('.note-text');
                                                if (span) {
                                                    const displayText = this.editedValue.length > 50 ? this.editedValue.substring(0, 47) + '...' : this.editedValue;
                                                    span.innerText = displayText || '-';
                                                }
                                            })
                                            .catch(() => {
                                                this.isEditing = false;
                                            });
                                    }
                                }">
                                    <div class="cursor-pointer hover:text-lime-600 note-text" 
                                        x-on:click="showTooltip = true; editedValue = noteValue"
                                        :class="{ 'text-gray-400 italic': !noteValue }">
                                        <span x-show="!noteValue">-</span>
                                        <span x-show="noteValue" x-text="noteValue.length > 50 ? noteValue.substring(0, 47) + '...' : noteValue">{{ Str::limit($contact['note'] ?? '-', 50) }}</span>
                                    </div>
                                    
                                    <!-- Tooltip NOTE - con z-index alto per stare sopra alla tabella -->
                                    <div x-show="showTooltip" 
                                        x-on:click.away="showTooltip = false"
                                        x-cloak
                                        class="fixed z-[9999] bg-white border border-gray-300 rounded-lg shadow-2xl p-3 min-w-[300px]"
                                        x-init="() => {
                                            $watch('showTooltip', (value) => {
                                                if (value) {
                                                    const rect = $el.previousElementSibling.getBoundingClientRect();
                                                    $el.style.top = (rect.bottom + window.scrollY + 5) + 'px';
                                                    $el.style.left = (rect.left + window.scrollX) + 'px';
                                                }
                                            });
                                        }">
                                        <div class="absolute -top-2 left-4 w-4 h-4 bg-white border-l border-t border-gray-300 transform rotate-45" style="z-index: -1;"></div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Note</label>
                                        @if($useTextarea || (strlen($contact['note'] ?? '') > 50))
                                            <textarea x-model="editedValue" 
                                                class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500"
                                                placeholder="Inserisci note..."
                                                rows="3"
                                                x-on:keydown.ctrl.enter="saveNote()"></textarea>
                                        @else
                                            <input type="text" 
                                                x-model="editedValue" 
                                                class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500"
                                                placeholder="Inserisci note..."
                                                x-on:keydown.enter="saveNote()">
                                        @endif
                                        <div class="flex justify-end gap-2 mt-2">
                                            <button type="button" 
                                                    x-on:click="showTooltip = false"
                                                    class="px-2 py-1 text-xs bg-gray-200 hover:bg-gray-300 rounded">
                                                <i class="fas fa-times"></i> Annulla
                                            </button>
                                            <button type="button" 
                                                    x-on:click="saveNote()"
                                                    x-bind:disabled="isEditing"
                                                    class="px-2 py-1 text-xs bg-lime-500 hover:bg-lime-600 text-white rounded disabled:opacity-50">
                                                <i class="fas fa-check" x-show="!isEditing"></i>
                                                <i class="fas fa-spinner fa-spin" x-show="isEditing"></i>
                                                <span x-show="!isEditing"> Salva</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-2 text-center relative">
                                <div x-data="{ 
                                    isPrimary: {{ $contact['principale'] ? 'true' : 'false' }},
                                    showTooltip: false,
                                    isEditing: false,
                                    editedValue: {{ $contact['principale'] ? 'true' : 'false' }},
                                    
                                    savePrimary() {
                                        this.isEditing = true;
                                        @this.call('togglePrimary', {{ $contact['id'] }}, this.editedValue)
                                            .then(() => {
                                                this.isEditing = false;
                                                this.showTooltip = false;
                                                this.isPrimary = this.editedValue;
                                                // Aggiorna il DOM senza reload
                                                const span = this.$el.closest('td').querySelector('.primary-text');
                                                if (span) {
                                                    span.innerText = this.editedValue ? 'Sì' : 'No';
                                                }
                                            })
                                            .catch(() => {
                                                this.isEditing = false;
                                            });
                                    }
                                }">
                                    <div class="cursor-pointer hover:text-lime-600 primary-text inline-flex items-center justify-center px-2 py-1 rounded"
                                        x-on:click="showTooltip = true; editedValue = isPrimary"
                                        x-bind:class="{ 'bg-green-100 text-green-800': isPrimary, 'text-gray-400': !isPrimary }">
                                        <span x-show="isPrimary">
                                            <i class="fas fa-check-circle mr-1"></i> Sì
                                        </span>
                                        <span x-show="!isPrimary">No</span>
                                    </div>
                                    
                                    <!-- Tooltip PRINCIPALE - con z-index alto per stare sopra alla tabella -->
                                    <div x-show="showTooltip" 
                                        x-on:click.away="showTooltip = false"
                                        x-cloak
                                        class="fixed z-[9999] bg-white border border-gray-300 rounded-lg shadow-2xl p-3 min-w-[200px]"
                                        x-init="() => {
                                            $watch('showTooltip', (value) => {
                                                if (value) {
                                                    const rect = $el.previousElementSibling.getBoundingClientRect();
                                                    $el.style.top = (rect.bottom + window.scrollY + 5) + 'px';
                                                    $el.style.left = (rect.left + window.scrollX) + 'px';
                                                }
                                            });
                                        }">
                                        <div class="absolute -top-2 left-5 w-4 h-4 bg-white border-l border-t border-gray-300 transform rotate-45 -translate-x-1/2" style="z-index: -1;"></div>
                                        <label class="block text-xs font-medium text-gray-700 mb-2 text-center">Contatto Principale</label>
                                        <div class="flex justify-center gap-3">
                                            <button type="button" 
                                                    x-on:click="editedValue = true; savePrimary()"
                                                    x-bind:disabled="isEditing"
                                                    class="px-3 py-1 text-xs rounded transition-colors"
                                                    :class="{ 'bg-green-500 text-white': editedValue === true, 'bg-gray-200 text-gray-700 hover:bg-gray-300': editedValue !== true }">
                                                <i class="fas fa-check-circle mr-1"></i> Sì
                                            </button>
                                            <button type="button" 
                                                    x-on:click="editedValue = false; savePrimary()"
                                                    x-bind:disabled="isEditing"
                                                    class="px-3 py-1 text-xs rounded transition-colors"
                                                    :class="{ 'bg-red-500 text-white': editedValue === false, 'bg-gray-200 text-gray-700 hover:bg-gray-300': editedValue !== false }">
                                                <i class="fas fa-times-circle mr-1"></i> No
                                            </button>
                                        </div>
                                        <div class="flex justify-center mt-2 pt-2 border-t">
                                            <button type="button" 
                                                    x-on:click="showTooltip = false"
                                                    class="px-2 py-1 text-xs bg-gray-200 hover:bg-gray-300 rounded">
                                                <i class="fas fa-times"></i> Annulla
                                            </button>
                                        </div>
                                    </div>
                                </div>
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
        </div>
    </div>
    @else
    <p class="text-gray-500 text-sm text-center py-4 bg-gray-50 rounded-lg">
        <i class="fas fa-address-card text-gray-400 mr-1"></i> Nessun contatto disponibile
    </p>
    @endif

    <!-- MODAL Contatto con campo Note -->
    @if($showForm)
    <div class="fixed inset-0 z-[10000] flex items-center justify-center bg-black bg-opacity-50" 
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                    <textarea wire:model="note" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Note aggiuntive (opzionale)..."></textarea>
                    @error('note') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
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
    <div class="fixed inset-0 z-[10000] flex items-center justify-center bg-black bg-opacity-50">
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
    <div class="fixed inset-0 z-[10000] flex items-center justify-center bg-black bg-opacity-50">
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
    <div class="fixed bottom-4 right-4 z-[10001] animate-slide-up">
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
    [x-cloak] {
        display: none !important;
    }
    </style>
</div>