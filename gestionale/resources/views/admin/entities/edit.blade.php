@extends('admin.layouts.app')

@section('title', 'Modifica Cliente / Fornitore')

@section('content')
<style>
    /* Tooltip personalizzato per l'email - CON Z-INDEX PIU' ALTO */
    .email-tooltip {
        position: relative;
        display: inline-block;
        cursor: help;
        margin-left: 5px;
    }
    
    .email-tooltip .tooltip-text {
        visibility: hidden;
        background-color: #1f2937;
        color: #fff;
        text-align: center;
        padding: 6px 12px;
        border-radius: 6px;
        position: absolute;
        z-index: 9999;
        bottom: 125%;
        left: 0;
        transform: translateX(0%);
        white-space: nowrap;
        font-size: 12px;
        font-weight: normal;
        font-family: system-ui, -apple-system, sans-serif;
        opacity: 0;
        transition: opacity 0.2s;
        pointer-events: none;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    
    .email-tooltip .tooltip-text::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 5%;
        margin-left: -5px;
        border-width: 5px;
        border-style: solid;
        border-color: #1f2937 transparent transparent transparent;
    }
    
    .email-tooltip:hover .tooltip-text {
        visibility: visible;
        opacity: 1;
    }
    
    .bg-white.rounded-xl {
        position: relative;
        z-index: 1;
    }

    /* Stelle rating */
    .star-icon {
        transition: color 0.15s ease-in-out, transform 0.1s ease-in-out;
    }
    .star-icon:hover {
        transform: scale(1.15);
    }
</style>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-edit mr-2 text-yellow-600"></i> Modifica Cliente / Fornitore
            </h1>
            <p class="text-gray-600 mt-1">Modifica le informazioni di {{ $entity->full_name }}</p>
        </div>
        <div class="relative group">
            <a href="{{ route('admin.entities.index') }}" 
               onclick="event.preventDefault(); window.history.length > 1 ? window.history.back() : window.location.href = '{{ route('admin.entities.index') }}';"
               class="bg-gray-600 hover:bg-gray-900 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="absolute bottom-full transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                Torna alla lista
                <div class="absolute top-full transform -translate-x-1/2 -mt-1 border-4 border-transparent border-t-gray-800"></div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-200">
        <form method="POST" action="{{ route('admin.entities.update', $entity->id_cliente) }}" id="entityForm">
            @csrf
            @method('PUT')
            
            <div class="p-6">
                <!-- RIGA 1: Ragione Sociale (col 6), Tipologia (col 4), Stato account (col 2) -->
                <div class="grid grid-cols-12 gap-4 mb-6">
                    <div class="col-span-12 md:col-span-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ragione Sociale</label>
                        <input type="text" 
                               name="ragione_sociale" 
                               id="ragione_sociale"
                               value="{{ old('ragione_sociale', $entity->ragione_sociale) }}"
                               oninput="enableUpdateButton()"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="col-span-12 md:col-span-4">
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
                    
                    <div class="col-span-12 md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stato Account</label>
                        <div class="mt-1">
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

                <!-- RIGA Valutazione -->
                <div class="grid grid-cols-12 gap-4 mb-6">
                    <div class="col-span-12 md:col-span-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Valutazione</label>
                        <input type="hidden" name="rating" id="rating" value="{{ old('rating', $entity->rating ?? 3) }}">
                        <div class="flex items-center space-x-1 text-2xl">
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="star-icon fas fa-star cursor-pointer text-gray-300" data-value="{{ $i }}" onclick="setRating({{ $i }})"></i>
                            @endfor
                            <span id="ratingValueLabel" class="ml-3 text-sm text-gray-500 align-middle"></span>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>
                            Clicca sulla prima stella già selezionata per azzerare la valutazione
                        </p>
                    </div>
                </div>

                <!-- RIGA 2: Cognome (col 3), Nome (col 3), Persona di Riferimento (col 6) -->
                <div class="grid grid-cols-12 gap-4 mb-6">
                    <div class="col-span-12 md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cognome</label>
                        <input type="text" 
                               name="cognome"
                               id="cognome"
                               value="{{ old('cognome', $entity->cognome) }}"
                               oninput="enableUpdateButton()"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="col-span-12 md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                        <input type="text" 
                               name="nome"
                               id="nome"
                               value="{{ old('nome', $entity->nome) }}"
                               oninput="enableUpdateButton()"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="col-span-12 md:col-span-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Persona di Riferimento</label>
                        <input type="text" 
                               name="persona_riferimento"
                               id="persona_riferimento"
                               value="{{ old('persona_riferimento', $entity->persona_riferimento) }}"
                               oninput="enableUpdateButton()"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <!-- RIGA 3: Email (4) con tooltip, Partita IVA (4), Codice Fiscale (4) -->
                <div class="grid grid-cols-12 gap-4 mb-8">
                    <div class="col-span-12 md:col-span-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Email
                            <div class="email-tooltip inline-block">
                                <i class="fas fa-info-circle text-gray-400 hover:text-gray-600"></i>
                                <span class="tooltip-text">Email per comunicazioni automatiche da gestionale</span>
                            </div>
                        </label>
                        <input type="email" 
                               name="email"
                               id="email"
                               value="{{ old('email', $entity->email) }}"
                               oninput="enableUpdateButton()"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="col-span-12 md:col-span-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Partita IVA</label>
                        <input type="text" 
                               name="partita_iva"
                               id="partita_iva"
                               value="{{ old('partita_iva', $entity->partita_iva) }}"
                               oninput="enableUpdateButton()"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="col-span-12 md:col-span-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Codice Fiscale</label>
                        <input type="text" 
                               name="codice_fiscale"
                               id="codice_fiscale"
                               value="{{ old('codice_fiscale', $entity->codice_fiscale) }}"
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
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                </div>
            </div>
            
            <!-- BOTTONE AGGIORNA al centro -->
            <div class="px-6 pb-4">
                <div class="flex justify-center">
                    <button type="submit" 
                            id="updateButton"
                            disabled
                            class="px-8 py-2.5 bg-yellow-600 hover:bg-yellow-700 text-white rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
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
    codice_fiscale: document.getElementById('codice_fiscale')?.value || '',
    email: document.getElementById('email')?.value || '',
    pec: document.getElementById('pec')?.value || '',
    codice_sdi: document.getElementById('codice_sdi')?.value || '',
    rating: document.getElementById('rating')?.value || '3',
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
        document.getElementById('codice_fiscale')?.value !== originalValues.codice_fiscale ||
        document.getElementById('email')?.value !== originalValues.email ||
        document.getElementById('pec')?.value !== originalValues.pec ||
        document.getElementById('codice_sdi')?.value !== originalValues.codice_sdi ||
        document.getElementById('rating')?.value !== originalValues.rating ||
        document.getElementById('valid')?.checked !== originalValues.valid;
    
    updateButton.disabled = !isChanged;
}

// ==================== GESTIONE STELLE RATING ====================
function paintStars(value) {
    value = parseInt(value);
    document.querySelectorAll('.star-icon').forEach(function (star) {
        const starValue = parseInt(star.dataset.value);
        star.classList.remove('text-gray-300', 'text-yellow-400', 'text-red-500', 'text-green-500');
        if (starValue <= value) {
            star.classList.add(value === 0 ? 'text-red-500' : (value === 5 ? 'text-green-500' : 'text-yellow-400'));
        } else {
            star.classList.add('text-gray-300');
        }
    });
    const label = document.getElementById('ratingValueLabel');
    if (label) {
        label.textContent = value + '/5';
    }
}

function setRating(value) {
    const ratingInput = document.getElementById('rating');
    const current = parseInt(ratingInput.value);
    if (current === value && value === 1) {
        value = 0; // clic sulla prima stella già attiva -> azzera
    }
    ratingInput.value = value;
    paintStars(value);
    enableUpdateButton();
}

// Colora le stelle al caricamento della pagina
paintStars(document.getElementById('rating')?.value || 3);

document.getElementById('entityForm')?.addEventListener('submit', function() {
    const updateButton = document.getElementById('updateButton');
    updateButton.disabled = true;
    updateButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Aggiornamento in corso...';
});
</script>

@endsection