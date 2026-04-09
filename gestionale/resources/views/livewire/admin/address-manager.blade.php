<div>
    <div class="bg-gray-50 rounded-lg p-4">
        <div class="flex justify-between items-center mb-3 border-b pb-2">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-map-marker-alt mr-2 text-red-500"></i> Indirizzi
            </h3>
            @if(!$showForm)
            <button type="button" 
                    wire:click="showCreateForm"
                    class="text-emerald-600 hover:text-emerald-700 transition-colors text-sm">
                <i class="fas fa-plus mr-1"></i> Nuovo Indirizzo
            </button>
            @endif
        </div>
        
        <!-- Form di inserimento/modifica indirizzo -->
        @if($showForm)
        <div class="mb-4 p-3 bg-white rounded-lg border border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Sede</label>
                    <input type="text" 
                           wire:model="sede" 
                           placeholder="es: principale, secondaria, ecc."
                           class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Indirizzo</label>
                    <input type="text" 
                           wire:model="indirizzo" 
                           placeholder="Via/Piazza, numero civico"
                           class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Città</label>
                    <input type="text" 
                           wire:model="citta" 
                           class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Provincia</label>
                    <input type="text" 
                           wire:model="provincia" 
                           maxlength="5"
                           placeholder="es: BA, TA, ecc."
                           class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">CAP</label>
                    <input type="text" 
                           wire:model="cap" 
                           maxlength="10"
                           class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Nazione</label>
                    <input type="text" 
                           wire:model="nazione" 
                           placeholder="Italia"
                           class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Telefono</label>
                    <input type="text" 
                           wire:model="telefono" 
                           class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Cellulare</label>
                    <input type="text" 
                           wire:model="cellulare" 
                           class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Fax</label>
                    <input type="text" 
                           wire:model="fax" 
                           class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div class="flex justify-end space-x-2 mt-3">
                <button type="button" 
                        wire:click="cancelEdit"
                        class="px-3 py-1 text-sm bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                    Annulla
                </button>
                <button type="button" 
                        wire:click="saveAddress"
                        class="px-3 py-1 text-sm bg-emerald-600 hover:bg-emerald-700 text-white rounded-md transition-colors">
                    <i class="fas fa-save mr-1"></i> {{ $editingAddressId ? 'Aggiorna Indirizzo' : 'Salva Indirizzo' }}
                </button>
            </div>
        </div>
        @endif
        
        <!-- Tabella Indirizzi -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Sede</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Indirizzo</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Città</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">CAP</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Prov.</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nazione</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($addresses as $address)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2 text-gray-500">{{ $address['id_indirizzo'] }}</td>
                        <td class="px-3 py-2">
                            <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-700">
                                {{ $address['sede'] ?: 'principale' }}
                            </span>
                        </td>
                        <td class="px-3 py-2">{{ $address['indirizzo'] ?: '-' }}</td>
                        <td class="px-3 py-2">{{ $address['citta'] ?: '-' }}</td>
                        <td class="px-3 py-2">{{ $address['cap'] ?: '-' }}</td>
                        <td class="px-3 py-2">{{ $address['provincia'] ?: '-' }}</td>
                        <td class="px-3 py-2">{{ $address['nazione'] ?: '-' }}</td>
                        <td class="px-3 py-2">
                            <div class="flex space-x-2">
                                <button type="button" 
                                        wire:click="editAddress({{ $address['id_indirizzo'] }})"
                                        class="text-yellow-600 hover:text-yellow-800 transition-colors"
                                        title="Modifica Indirizzo">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" 
                                        wire:click="deleteAddress({{ $address['id_indirizzo'] }})"
                                        wire:confirm="Sei sicuro di voler eliminare questo indirizzo?"
                                        class="text-red-600 hover:text-red-800 transition-colors"
                                        title="Elimina Indirizzo">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-3 py-6 text-center text-gray-400">
                            <i class="fas fa-map-marker-alt text-2xl mb-2 block"></i>
                            Nessun indirizzo associato
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('address-saved', (message) => {
                alert(message);
            });
            Livewire.on('address-deleted', (message) => {
                alert(message);
            });
        });
    </script>
</div>