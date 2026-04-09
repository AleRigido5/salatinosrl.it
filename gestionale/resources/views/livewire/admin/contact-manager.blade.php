<div>
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
                            <option value="{{ $type['id_settings'] }}">{{ $type['nome'] }}</option>
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
                        wire:click="saveContact"
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
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Valore</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Principale</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($contacts as $contact)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2 text-gray-500">{{ $contact['id_contatto'] }}</td>
                        <td class="px-3 py-2">
                            <span class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-800">
                                {{ $contact['setting']['nome'] ?? 'Tipo sconosciuto' }}
                            </span>
                        </td>
                        <td class="px-3 py-2">
                            @php
                                $settingSlug = $contact['setting']['slug'] ?? '';
                            @endphp
                            @if($settingSlug == 'email' || $settingSlug == 'pec')
                                <a href="mailto:{{ $contact['valore'] }}" class="text-blue-600 hover:text-blue-800">
                                    {{ $contact['valore'] }}
                                </a>
                            @elseif($settingSlug == 'telefono' || $settingSlug == 'cellulare')
                                <a href="tel:{{ $contact['valore'] }}" class="text-gray-800 hover:text-emerald-600">
                                    {{ $contact['valore'] }}
                                </a>
                            @else
                                {{ $contact['valore'] }}
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
                                        wire:click="editContact({{ $contact['id_contatto'] }})"
                                        class="text-yellow-600 hover:text-yellow-800 transition-colors"
                                        title="Modifica Contatto">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" 
                                        wire:click="deleteContact({{ $contact['id_contatto'] }})"
                                        wire:confirm="Sei sicuro di voler eliminare questo contatto?"
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
    
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('contact-saved', (message) => {
                alert(message);
            });
            Livewire.on('contact-deleted', (message) => {
                alert(message);
            });
        });
    </script>
</div>