@extends('admin.layouts.app')

@section('title', 'Modifica Cliente / Fornitore')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-edit mr-2 text-yellow-600"></i> Modifica Cliente / Fornitore
            </h1>
            <p class="text-gray-600 mt-1">Modifica le informazioni di {{ $entity->full_name }}</p>
        </div>
        <a href="{{ route('admin.entities.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Torna alla lista
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
        <form method="POST" action="{{ route('admin.entities.update', $entity->id_cliente) }}" id="entityForm">
            @csrf
            @method('PUT')
            
            <div class="p-6">
                <!-- PRIMA RIGA: Ragione Sociale e Tipologia -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ragione Sociale</label>
                        <input type="text" 
                               name="ragione_sociale" 
                               id="ragione_sociale"
                               value="{{ old('ragione_sociale', $entity->ragione_sociale) }}"
                               oninput="enableUpdateButton()"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Tipologia <span class="text-red-500">*</span>
                        </label>
                        <select name="entity_type" 
                                id="entity_type"
                                onchange="enableUpdateButton()"
                                required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="cliente" {{ old('entity_type', $entity->entity_type) == 'cliente' ? 'selected' : '' }}>Cliente</option>
                            <option value="fornitore" {{ old('entity_type', $entity->entity_type) == 'fornitore' ? 'selected' : '' }}>Fornitore</option>
                            <option value="entrambi" {{ old('entity_type', $entity->entity_type) == 'entrambi' ? 'selected' : '' }}>Entrambi</option>
                        </select>
                        @error('entity_type') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- SECONDA RIGA: Cognome e Nome -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cognome</label>
                        <input type="text" 
                               name="cognome"
                               id="cognome"
                               value="{{ old('cognome', $entity->cognome) }}"
                               oninput="enableUpdateButton()"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                        <input type="text" 
                               name="nome"
                               id="nome"
                               value="{{ old('nome', $entity->nome) }}"
                               oninput="enableUpdateButton()"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <!-- TERZA RIGA: Persona Riferimento e P.IVA -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Persona di Riferimento</label>
                        <input type="text" 
                               name="persona_riferimento"
                               id="persona_riferimento"
                               value="{{ old('persona_riferimento', $entity->persona_riferimento) }}"
                               oninput="enableUpdateButton()"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Partita IVA</label>
                        <input type="text" 
                               name="partita_iva"
                               id="partita_iva"
                               value="{{ old('partita_iva', $entity->partita_iva) }}"
                               oninput="enableUpdateButton()"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <!-- SEPARATORE -->
                <div class="border-t border-gray-200 my-6"></div>

                <!-- DATI FATTURA ELETTRONICA -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-file-invoice-dollar mr-2 text-green-500"></i> Dati Fattura Elettronica
                    </h3>
                    
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">PEC (Posta Elettronica Certificata)</label>
                            <input type="email" 
                                   name="pec"
                                   id="pec"
                                   value="{{ old('pec', $entity->pec) }}"
                                   oninput="enableUpdateButton()"
                                   placeholder="esempio@pec.it"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Indirizzo PEC per l'invio delle fatture elettroniche</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Codice SDI (Sistema di Interscambio)</label>
                            <input type="text" 
                                   name="codice_sdi"
                                   id="codice_sdi"
                                   value="{{ old('codice_sdi', $entity->codice_sdi) }}"
                                   oninput="enableUpdateButton()"
                                   placeholder="es: XXXXXXX"
                                   maxlength="7"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase">
                            <p class="text-xs text-gray-500 mt-1">Codice a 7 caratteri per la ricezione delle fatture elettroniche</p>
                        </div>
                    </div>
                    
                    <div class="mt-4 p-3 bg-blue-50 rounded-md border border-blue-200">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-blue-500 mt-0.5 mr-2"></i>
                            <div class="text-sm text-blue-700">
                                <p class="font-medium mb-1">Informazione:</p>
                                <p>Il codice SDI (codice destinatario) è obbligatorio per ricevere fatture elettroniche dalla Pubblica Amministrazione. Se non specificato, verrà utilizzata la PEC.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STATO ACCOUNT -->
                <div class="mb-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-md font-semibold text-gray-800 mb-3">
                            <i class="fas fa-toggle-on mr-2 text-purple-500"></i> Stato Account
                        </h3>
                        <label class="inline-flex items-center">
                            <input type="checkbox" 
                                   name="valid" 
                                   id="valid"
                                   value="1" 
                                   onchange="enableUpdateButton()"
                                   {{ old('valid', $entity->valid) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-green-600 shadow-sm focus:border-green-300 focus:ring focus:ring-green-200">
                            <span class="ml-2 text-sm text-gray-700">Account attivo</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- BOTTONE AGGIORNA -->
            <div class="px-6 pb-4">
                <div class="flex justify-end">
                    <button type="submit" 
                            id="updateButton"
                            disabled
                            class="px-6 py-2.5 bg-yellow-600 hover:bg-yellow-700 text-white rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-save mr-2"></i> Aggiorna Anagrafica
                    </button>
                </div>
            </div>
            
            <!-- Linea di divisione -->
            <div class="border-t border-gray-200"></div>
            
            <!-- Sezione Indirizzi -->
            <div class="px-6 pb-6 pt-4">
                @livewire('admin.address-manager', ['entityId' => $entity->id_cliente])
            </div>
            
            <!-- Sezione Contatti -->
            <div class="px-6 pb-6 pt-2">
                @livewire('admin.contact-manager', ['entityId' => $entity->id_cliente])
            </div>
        </form>
    </div>
</div>

<script>
// Salva i valori originali del form
const originalValues = {
    ragione_sociale: document.getElementById('ragione_sociale')?.value || '',
    entity_type: document.getElementById('entity_type')?.value || '',
    cognome: document.getElementById('cognome')?.value || '',
    nome: document.getElementById('nome')?.value || '',
    persona_riferimento: document.getElementById('persona_riferimento')?.value || '',
    partita_iva: document.getElementById('partita_iva')?.value || '',
    pec: document.getElementById('pec')?.value || '',
    codice_sdi: document.getElementById('codice_sdi')?.value || '',
    valid: document.getElementById('valid')?.checked || false
};

function enableUpdateButton() {
    const updateButton = document.getElementById('updateButton');
    
    const isChanged = 
        document.getElementById('ragione_sociale')?.value !== originalValues.ragione_sociale ||
        document.getElementById('entity_type')?.value !== originalValues.entity_type ||
        document.getElementById('cognome')?.value !== originalValues.cognome ||
        document.getElementById('nome')?.value !== originalValues.nome ||
        document.getElementById('persona_riferimento')?.value !== originalValues.persona_riferimento ||
        document.getElementById('partita_iva')?.value !== originalValues.partita_iva ||
        document.getElementById('pec')?.value !== originalValues.pec ||
        document.getElementById('codice_sdi')?.value !== originalValues.codice_sdi ||
        document.getElementById('valid')?.checked !== originalValues.valid;
    
    updateButton.disabled = !isChanged;
}

document.getElementById('entityForm')?.addEventListener('submit', function() {
    const updateButton = document.getElementById('updateButton');
    updateButton.disabled = true;
    updateButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Aggiornamento in corso...';
});
</script>

@endsection