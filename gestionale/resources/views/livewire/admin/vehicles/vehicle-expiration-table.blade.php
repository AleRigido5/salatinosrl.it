<div>
    <!-- Header con breadcrumb -->
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                @php
                    // Estrai i dati del veicolo se disponibile
                    $vehicle = null;
                    $vehicleTitle = 'Gestione Scadenze Mezzi';
                    $vehicleSubtitle = '';
                    
                    if($vehicleId && $vehicleName) {
                        // Cerca il veicolo per ottenere i dettagli
                        $vehicle = \App\Models\Vehicles::find($vehicleId);
                        if($vehicle) {
                            // Titolo: Scadenze per mezzo MARCA - MODELLO
                            $marcaModello = trim(($vehicle->marca ?? '') . ' ' . ($vehicle->modello ?? ''));
                            if(!empty($marcaModello)) {
                                $vehicleTitle = 'Scadenze per mezzo ' . $marcaModello;
                            } else {
                                $vehicleTitle = 'Scadenze per mezzo ' . $vehicleName;
                            }
                            
                            // Sottotitolo: TIPOLOGIA - ANNO IMMATRICOLAZIONE
                            $tipologia = $vehicle->tipologia ?? '';
                            $anno = '';
                            if($vehicle->immatricolazione && $vehicle->immatricolazione != '0000-00-00') {
                                try {
                                    $date = date_create($vehicle->immatricolazione);
                                    if($date && $date->format('Y') > 1900 && $date->format('Y') <= date('Y') + 1) {
                                        $anno = $date->format('Y');
                                    }
                                } catch(Exception $e) {
                                    $anno = '';
                                }
                            }
                            
                            if($tipologia && $anno) {
                                $vehicleSubtitle = $tipologia . ' - ' . 'Immatricolazione ' . $anno;
                            } elseif($tipologia) {
                                $vehicleSubtitle = $tipologia;
                            } elseif($anno) {
                                $vehicleSubtitle = 'Immatricolazione ' . $anno;
                            }
                        }
                    }
                @endphp
                
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-truck mr-2 text-lime-600"></i> 
                    {{ $vehicleTitle }}
                </h1>
                
                @if($vehicleSubtitle)
                <p class="text-gray-500 mt-1">
                    <i class="fas fa-info-circle mr-1 text-gray-400"></i> 
                    {{ $vehicleSubtitle }}
                </p>
                @elseif($vehicleName)
                <p class="text-gray-500 mt-1">
                    <i class="fas fa-truck mr-1"></i> Scadenze per mezzo: <strong>{{ $vehicleName }}</strong>
                </p>
                @endif
            </div>
            <div class="flex gap-3">
                <div class="relative group">
                    <button wire:click="openCreateModal" 
                            class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                        <i class="fas fa-plus"></i>
                    </button>
                    <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                        Nuova scadenza
                    </div>
                </div>
                
                @if($vehicleId)
                <div class="relative group">
                    <button wire:click="backToVehicles" 
                            class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                    <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                        Torna ai mezzi
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Filtri e Ricerca -->
    <div class="bg-white rounded-lg shadow mb-6 p-4 border border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="relative md:col-span-2">
                <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       placeholder="Cerca per titolo, fornitore, codice polizza o note..." 
                       class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent">
            </div>
            
            <select wire:model.live="tipologiaFilter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                <option value="">Tutte le tipologie</option>
                @foreach($tipologie as $tipologia)
                    <option value="{{ $tipologia->id }}">{{ $tipologia->valore }}</option>
                @endforeach
            </select>
            
            <select wire:model.live="statusFilter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                <option value="">Tutti gli stati</option>
                <option value="active">Attive</option>
                <option value="expiring">In scadenza</option>
                <option value="expired">Scadute</option>
            </select>
        </div>
        
        <div class="flex justify-between items-center mt-4">
            @if($search || $tipologiaFilter || $statusFilter)
            <button wire:click="resetFilters" class="text-sm text-gray-600 hover:text-gray-900 transition-colors">
                <i class="fas fa-sync-alt mr-1"></i>
                Resetta filtri
            </button>
            @endif
        </div>
    </div>

    <!-- Tabella Scadenze -->
    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition w-[15%]" wire:click="sortBy('titolo')">
                            <div class="flex items-center space-x-1">
                                <span>Titolo</span>
                                @if($sortField === 'titolo')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[12%]">Fornitore</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[10%]">Polizza n.</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition w-[10%]" wire:click="sortBy('data_inizio')">
                            <div class="flex items-center space-x-1">
                                <span>Data Inizio</span>
                                @if($sortField === 'data_inizio')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition w-[10%]" wire:click="sortBy('data_fine')">
                            <div class="flex items-center space-x-1">
                                <span>Data Scadenza</span>
                                @if($sortField === 'data_fine')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[20%]">Note</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition w-[8%]" wire:click="sortBy('importo')">
                            <div class="flex items-center space-x-1">
                                <span>Importo</span>
                                @if($sortField === 'importo')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[8%]">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expirations as $expiration)
                    <tr wire:key="expiration-{{ $expiration->id }}" class="hover:bg-gray-50 transition-colors duration-150 border-t border-gray-200">
                        <td class="px-4 py-4 align-top">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $expiration->titolo }}
                            </div>
                            @if($expiration->subtitolo)
                            <div class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-briefcase mr-1"></i> {{ $expiration->subtitolo }}
                            </div>
                            @endif
                        </td>
                        
                        <td class="px-4 py-4 align-top whitespace-normal">
                            @php
                                $fornitoreNome = '';
                                $entityId = $expiration->id_entities ?? ($expiration->id_references ?? null);
                                if($entityId) {
                                    $entity = \App\Models\Entity::find($entityId);
                                    if($entity) {
                                        $fornitoreNome = $entity->ragione_sociale ?: ($entity->nome . ' ' . $entity->cognome);
                                    }
                                }
                            @endphp
                            @if($fornitoreNome)
                            <span class="text-sm text-gray-900">{{ Str::limit($fornitoreNome, 40) }}</span>
                            @else
                            <span class="text-sm text-gray-400">-</span>
                            @endif
                        </td>
                        
                        <td class="px-4 py-4 align-top whitespace-nowrap">
                            @if($expiration->codice)
                            <span class="text-sm font-mono text-gray-900">{{ $expiration->codice }}</span>
                            @else
                            <span class="text-sm text-gray-400">-</span>
                            @endif
                        </td>
                        
                        <td class="px-4 py-4 align-top whitespace-nowrap text-sm text-gray-500">
                            {{ $expiration->data_inizio ? $expiration->data_inizio->format('d/m/Y') : '-' }}
                        </td>
                        
                        <td class="px-4 py-4 align-top whitespace-nowrap text-sm text-gray-500">
                            {{ $expiration->data_fine ? $expiration->data_fine->format('d/m/Y') : '-' }}
                        </td>
                        
                        <td class="px-4 py-4 align-top">
                            @php
                                $cleanNote = '';
                                if($expiration->note) {
                                    // Rimuovi marker [Scadenza_id: ...]
                                    $cleanNote = preg_replace('/\[Scadenza_id: \d+\]/', '', $expiration->note);
                                    // Gestisci il testo "1° semestre" e "2° semestre"
                                    $cleanNote = preg_replace('/1Â° semestre/', '1° semestre', $cleanNote);
                                    $cleanNote = preg_replace('/2Â° semestre/', '2° semestre', $cleanNote);
                                    $cleanNote = preg_replace('/1� semestre/', '1° semestre', $cleanNote);
                                    $cleanNote = preg_replace('/2� semestre/', '2° semestre', $cleanNote);
                                    $cleanNote = preg_replace('/\s+/', ' ', $cleanNote);
                                    $cleanNote = html_entity_decode($cleanNote, ENT_QUOTES, 'UTF-8');
                                    $cleanNote = trim($cleanNote);
                                }
                            @endphp
                            @if($cleanNote && $cleanNote != '')
                            <div class="text-sm text-gray-600 max-w-md" title="{{ $cleanNote }}">
                                {{ Str::limit($cleanNote, 100) }}
                            </div>
                            @else
                            <span class="text-sm text-gray-400">-</span>
                            @endif
                        </td>
                        
                        <td class="px-4 py-4 align-top whitespace-nowrap">
                            @if($expiration->importo)
                            <span class="text-sm font-semibold text-green-600">
                                € {{ number_format($expiration->importo, 2, ',', '.') }}
                            </span>
                            @else
                            <span class="text-sm text-gray-400">-</span>
                            @endif
                        </td>
                        
                        <td class="px-4 py-4 align-top text-sm font-medium whitespace-nowrap">
                            <div class="flex space-x-3">
                                <button wire:click="viewExpiration({{ $expiration->id }})" 
                                        wire:key="view-{{ $expiration->id }}"
                                        class="text-blue-600 hover:text-blue-900 transition-colors"
                                        title="Visualizza">
                                    <i class="fa-regular fa-eye text-blue-600 hover:text-blue-900 text-base"></i>
                                </button>
                                
                                @if(auth()->guard('admin')->user()->hasPermission('edit_expiration'))
                                <button wire:click="openEditModal({{ $expiration->id }})" 
                                        wire:key="edit-{{ $expiration->id }}"
                                        class="text-yellow-600 hover:text-yellow-900 transition-colors"
                                        title="Modifica">
                                    <i class="fa-solid fa-pen-to-square text-yellow-600 hover:text-yellow-900 text-base"></i>
                                </button>
                                @endif

                                <a href="{{ route('admin.documents.index', ['expiration-vehicles', $expiration->id]) . '?vehicle_id=' . $vehicleId }}" 
                                   class="text-indigo-600 hover:text-indigo-900 transition-colors relative"
                                   title="Gestisci Documenti">
                                    <i class="fas fa-paperclip text-base"></i>
                                    @if($expiration->documents_count > 0)
                                    <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">
                                        {{ $expiration->documents_count }}
                                    </span>
                                    @endif
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center">
                            <div class="text-gray-500">
                                <i class="fas fa-calendar-times text-gray-400 text-5xl"></i>
                                <p class="mt-2 text-sm">Nessuna scadenza trovata per questo mezzo</p>
                                @if($search || $tipologiaFilter || $statusFilter)
                                <button wire:click="resetFilters" class="mt-2 text-sm text-lime-600 hover:text-lime-800">
                                    Resetta filtri
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginazione -->
    @if($expirations->hasPages())
    <div class="mt-6">
        <div class="text-sm text-gray-500 mb-2">
            Mostrando {{ $expirations->firstItem() ?? 0 }} - {{ $expirations->lastItem() ?? 0 }} di {{ $expirations->total() }} risultati
        </div>
        <div class="flex justify-center">
            {{ $expirations->links() }}
        </div>
    </div>
    @endif

    <style>
        nav[role="navigation"] div.flex-1 {
            display: none !important;
        }
        nav[role="navigation"] .relative.z-0 {
            justify-content: center !important;
            display: flex !important;
        }
        nav[role="navigation"] span[aria-current="page"] span,
        nav[role="navigation"] .relative.inline-flex.items-center {
            background-color: white !important;
            border-color: #e5e7eb !important;
            color: #374151 !important;
        }
        nav[role="navigation"] span[aria-current="page"] span {
            background-color: #84cc16 !important;
            border-color: #84cc16 !important;
            color: white !important;
        }
        nav[role="navigation"] .relative.inline-flex.items-center:hover {
            background-color: #f9fafb !important;
            border-color: #d1d5db !important;
        }
        nav[role="navigation"] p.text-sm {
            display: none !important;
        }
        nav[role="navigation"] > div:first-child {
            justify-content: center !important;
        }
        nav[role="navigation"] > div:first-child > div:first-child {
            display: none !important;
        }
        
        @media (min-width: 1920px) {
            .container, .p-6 {
                max-width: 100%;
            }
            table th, table td {
                padding-left: 1.5rem !important;
                padding-right: 1.5rem !important;
            }
        }
        
        @media (min-width: 2560px) {
            table th, table td {
                padding-left: 2rem !important;
                padding-right: 2rem !important;
            }
            .text-sm {
                font-size: 0.9rem !important;
            }
        }
    </style>

    <!-- MODAL CREAZIONE NUOVA SCADENZA -->
    @if($showCreateModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" 
        x-data="{ open: true }" 
        x-show="open"
        x-init="$watch('open', value => { if (!value) $wire.closeCreateModal() })"
        @keydown.escape.window="open = false">
        
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="open = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-center mb-4 border-b pb-3">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-plus-circle mr-2 text-lime-600"></i>
                            Nuova Scadenza
                            @if($vehicleName)
                            <span class="text-sm font-normal text-gray-500 ml-2">{{ $vehicleName }}</span>
                            @endif
                        </h2>
                        <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-2xl"></i>
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {{-- <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Tipologia Scadenza <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="createTipologiaId" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                    <option value="">Seleziona tipologia...</option>
                                    @foreach($tipologie as $tipologia)
                                        <option value="{{ $tipologia->id }}">{{ $tipologia->valore }}</option>
                                    @endforeach
                                </select>
                                @error('createTipologiaId') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ownership / Azienda</label>
                                <select wire:model="createOwnershipId" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                    <option value="">Seleziona ownership...</option>
                                    @foreach($ownerships as $ownership)
                                        <option value="{{ $ownership->id_proprieta }}">{{ $ownership->RagSocialePr }}</option>
                                    @endforeach
                                </select>
                            </div> --}}
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Titolo <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                wire:model="createTitolo" 
                                placeholder="es. Assicurazione, Revisione, Bollo..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent">
                            @error('createTitolo') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Codice Polizza</label>
                            <input type="text" 
                                wire:model="createCodice" 
                                placeholder="es. 123456789"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Data Inizio <span class="text-red-500">*</span>
                                </label>
                                <input type="date" 
                                    wire:model="createDataInizio" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                @error('createDataInizio') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data Scadenza</label>
                                <input type="date" 
                                    wire:model="createDataFine" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                <p class="text-xs text-gray-400 mt-1">Opzionale: lascia vuoto se non ha scadenza</p>
                                @error('createDataFine') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Importo</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-gray-500">€</span>
                                <input type="number" 
                                    wire:model="createImporto" 
                                    step="0.01"
                                    placeholder="0.00"
                                    class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fornitore / Cliente</label>
                                <div class="relative">
                                    <input type="text" 
                                        wire:model.live.debounce.300ms="createEntitySearch" 
                                        placeholder="Cerca cliente o fornitore..."
                                        class="w-full px-3 py-2 pl-9 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                                        autocomplete="off">
                                    <i class="fas fa-search absolute left-2.5 top-2.5 text-gray-400"></i>
                                    
                                    @if($createEntityNome)
                                    <button type="button" 
                                            wire:click="clearEntity" 
                                            class="absolute right-2 top-2 text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    @endif
                                </div>
                                
                                @if(count($createEntityResults) > 0)
                                <div class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto" style="position: relative; z-index: 60;">
                                    @foreach($createEntityResults as $index => $result)
                                    <div class="px-4 py-2 hover:bg-lime-50 cursor-pointer border-b border-gray-100 last:border-0"
                                        wire:click="selectEntity({{ $result->id_cliente }}, '{{ addslashes($result->ragione_sociale ?: $result->nome . ' ' . $result->cognome) }}')"
                                        wire:key="create-result-{{ $result->id_cliente }}-{{ $index }}">
                                        <div class="font-medium text-gray-900">
                                            {{ $result->ragione_sociale ?: $result->nome . ' ' . $result->cognome }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            @if($result->partita_iva) P.IVA: {{ $result->partita_iva }} @endif
                                            @if($result->codice_fiscale) | CF: {{ $result->codice_fiscale }} @endif
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                                
                                @if($createEntityNome)
                                <div class="mt-2 p-2 bg-green-50 rounded-md border border-green-200">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center text-sm text-green-700">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            <span class="font-medium">Associato a:</span>
                                            <span class="ml-1">{{ $createEntityNome }}</span>
                                        </div>
                                        <button type="button" wire:click="clearEntity" class="text-green-600 hover:text-green-800">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                @endif
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Qualifica / Sottotitolo</label>
                                <input type="text" 
                                    wire:model="createQualifica" 
                                    placeholder="es. RCA, Kasko, Tagliando..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                            <textarea wire:model="createNote" 
                                    rows="3"
                                    placeholder="Note aggiuntive..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-col sm:flex-row sm:justify-end gap-3">
                    <button @click="open = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md">
                        <i class="fas fa-times mr-2"></i> Annulla
                    </button>
                    <button wire:click="saveExpiration" 
                            class="px-4 py-2 bg-lime-600 hover:bg-lime-700 text-white rounded-md">
                        <i class="fas fa-save mr-2"></i> Crea Scadenza
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
    
    <!-- Modal Visualizzazione Dettaglio Scadenza -->
    @if($showViewModal && $viewingExpiration)
    <div class="fixed inset-0 z-50 overflow-y-auto" 
         x-data="{ open: true }" 
         x-show="open"
         x-init="$watch('open', value => { if (!value) $wire.closeViewModal() })"
         @keydown.escape.window="open = false">
        
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="open = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-center mb-4 border-b pb-3">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-calendar-alt mr-2 text-lime-600"></i> Dettaglio Scadenza
                        </h2>
                        <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-2xl"></i>
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        @php
                            $linkedEntity = $viewingExpiration->getLinkedEntityAttribute();
                            $linkedEntityName = $viewingExpiration->getLinkedEntityNameAttribute();
                            $linkedEntityType = $viewingExpiration->getLinkedEntityTypeAttribute();
                            $fornitoreNome = '';
                            if($viewingExpiration->id_entities) {
                                $entity = \App\Models\Entity::find($viewingExpiration->id_entities);
                                if($entity) {
                                    $fornitoreNome = $entity->ragione_sociale ?: ($entity->nome . ' ' . $entity->cognome);
                                }
                            }
                        @endphp
                        
                        @if($linkedEntity && $linkedEntityName != '-')
                        <div class="bg-blue-50 p-3 rounded-lg">
                            <p class="text-sm text-blue-700">
                                <i class="fas fa-building mr-1"></i> 
                                {{ $linkedEntityType }} Associato: <strong>{{ $linkedEntityName }}</strong>
                            </p>
                        </div>
                        @endif
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Titolo</label>
                                <p class="text-gray-900 font-medium">{{ $viewingExpiration->titolo }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Tipologia</label>
                                <p><span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">{{ $viewingExpiration->tipologiaName }}</span></p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Fornitore</label>
                                <p class="text-gray-900">{{ $fornitoreNome ?: '-' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Codice Polizza</label>
                                <p class="text-gray-900 font-mono">{{ $viewingExpiration->codice ?: '-' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Data Inizio</label>
                                <p class="text-gray-900">{{ $viewingExpiration->data_inizio ? $viewingExpiration->data_inizio->format('d/m/Y') : '-' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Data Scadenza</label>
                                <p class="text-gray-900">{{ $viewingExpiration->data_fine ? $viewingExpiration->data_fine->format('d/m/Y') : '-' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Importo</label>
                                <p class="text-gray-900 font-semibold text-green-600">
                                    {{ $viewingExpiration->importo ? '€ ' . number_format($viewingExpiration->importo, 2, ',', '.') : '-' }}
                                </p>
                            </div>
                            @if($viewingExpiration->subtitolo)
                            <div class="col-span-2">
                                <label class="text-sm font-medium text-gray-500">Qualifica</label>
                                <p class="text-gray-900">{{ $viewingExpiration->subtitolo }}</p>
                            </div>
                            @endif
                            <div>
                                <label class="text-sm font-medium text-gray-500">Stato</label>
                                <p>{!! $viewingExpiration->statusBadge !!}</p>
                            </div>
                        </div>
                        
                        @if($viewingExpiration->note)
                        <div>
                            <label class="text-sm font-medium text-gray-500">Note</label>
                            <p class="text-gray-700 mt-1">
                                @php
                                    $cleanViewNote = preg_replace('/\[Scadenza_id: \d+\]/', '', $viewingExpiration->note);
                                    $cleanViewNote = preg_replace('/1Â° semestre/', '1° semestre', $cleanViewNote);
                                    $cleanViewNote = preg_replace('/2Â° semestre/', '2° semestre', $cleanViewNote);
                                    $cleanViewNote = preg_replace('/1� semestre/', '1° semestre', $cleanViewNote);
                                    $cleanViewNote = preg_replace('/2� semestre/', '2° semestre', $cleanViewNote);
                                    $cleanViewNote = preg_replace('/\s+/', ' ', $cleanViewNote);
                                    $cleanViewNote = html_entity_decode($cleanViewNote, ENT_QUOTES, 'UTF-8');
                                    $cleanViewNote = trim($cleanViewNote);
                                @endphp
                                {{ $cleanViewNote ?: '-' }}
                            </p>
                        </div>
                        @endif
                        
                        <div class="border-t pt-4 mt-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Tracciamento</h4>
                            <div class="text-xs text-gray-500 space-y-1">
                                @if($viewingExpiration->created_at && $viewingExpiration->createdBy)
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-plus-circle text-green-500"></i>
                                    <span>Inserito da <strong>{{ $viewingExpiration->createdBy->name }}</strong> il {{ $viewingExpiration->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                                @endif
                                @if($viewingExpiration->updated_at && $viewingExpiration->updatedBy && $viewingExpiration->created_at != $viewingExpiration->updated_at)
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-edit text-blue-500"></i>
                                    <span>Modificato da <strong>{{ $viewingExpiration->updatedBy->name }}</strong> il {{ $viewingExpiration->updated_at->format('d/m/Y H:i') }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-col sm:flex-row sm:justify-end gap-3">
                    <button @click="open = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md">
                        <i class="fas fa-times mr-2"></i> Chiudi
                    </button>
                    @if(auth()->guard('admin')->user()->hasPermission('edit_expiration'))
                    <a href="{{ route('admin.expiration-vehicle.edit', ['id' => $viewingExpiration->id, 'vehicleId' => $vehicleId]) }}"
                       class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-md">
                        <i class="fas fa-edit mr-2"></i> Modifica
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL MODIFICA SCADENZA -->
    @if($showEditModal && $editingExpiration)
    <div class="fixed inset-0 z-50 overflow-y-auto" 
        x-data="{ open: true }" 
        x-show="open"
        x-init="$watch('open', value => { if (!value) $wire.closeEditModal() })"
        @keydown.escape.window="open = false">
        
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="open = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-center mb-4 border-b pb-3">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-edit mr-2 text-yellow-600"></i>
                            Modifica Scadenza
                            @if($vehicleName)
                            <span class="text-sm font-normal text-gray-500 ml-2">{{ $vehicleName }}</span>
                            @endif
                        </h2>
                        <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-2xl"></i>
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {{-- <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Tipologia Scadenza <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="editTipologiaId" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                    <option value="">Seleziona tipologia...</option>
                                    @foreach($tipologie as $tipologia)
                                        <option value="{{ $tipologia->id }}">{{ $tipologia->valore }}</option>
                                    @endforeach
                                </select>
                                @error('editTipologiaId') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div> --}}
                            
                            {{-- <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ownership / Azienda</label>
                                <select wire:model="editOwnershipId" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                    <option value="">Seleziona ownership...</option>
                                    @foreach($ownerships as $ownership)
                                        <option value="{{ $ownership->id_proprieta }}">{{ $ownership->RagSocialePr }}</option>
                                    @endforeach
                                </select>
                            </div> --}}
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Titolo <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                wire:model="editTitolo" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                            @error('editTitolo') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Codice Polizza</label>
                            <input type="text" 
                                wire:model="editCodice" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Data Inizio <span class="text-red-500">*</span>
                                </label>
                                <input type="date" wire:model="editDataInizio" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                @error('editDataInizio') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data Scadenza</label>
                                <input type="date" wire:model="editDataFine" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                <p class="text-xs text-gray-400 mt-1">Opzionale: lascia vuoto se non ha scadenza</p>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Importo</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-gray-500">€</span>
                                <input type="number" 
                                    wire:model="editImporto" 
                                    step="0.01"
                                    placeholder="0.00"
                                    class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fornitore / Cliente</label>
                                <div class="relative">
                                    <input type="text" 
                                        wire:model.live.debounce.300ms="editEntitySearch" 
                                        placeholder="Cerca cliente o fornitore..."
                                        class="w-full px-3 py-2 pl-9 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                                        autocomplete="off">
                                    <i class="fas fa-search absolute left-2.5 top-2.5 text-gray-400"></i>
                                    
                                    @if($editEntityNome)
                                    <button type="button" wire:click="clearEditEntity" class="absolute right-2 top-2 text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    @endif
                                </div>
                                
                                @if(count($editEntityResults) > 0)
                                <div class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto" style="position: relative; z-index: 60;">
                                    @foreach($editEntityResults as $index => $result)
                                    <div class="px-4 py-2 hover:bg-lime-50 cursor-pointer border-b border-gray-100 last:border-0"
                                        wire:click="selectEditEntity({{ $result->id_cliente }}, '{{ addslashes($result->ragione_sociale ?: $result->nome . ' ' . $result->cognome) }}')"
                                        wire:key="edit-result-{{ $result->id_cliente }}-{{ $index }}">
                                        <div class="font-medium text-gray-900">
                                            {{ $result->ragione_sociale ?: $result->nome . ' ' . $result->cognome }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            @if($result->partita_iva) P.IVA: {{ $result->partita_iva }} @endif
                                            @if($result->codice_fiscale) | CF: {{ $result->codice_fiscale }} @endif
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                                
                                @if($editEntityNome)
                                <div class="mt-2 p-2 bg-green-50 rounded-md border border-green-200">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center text-sm text-green-700">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            <span class="font-medium">Associato a:</span>
                                            <span class="ml-1">{{ $editEntityNome }}</span>
                                        </div>
                                        <button type="button" wire:click="clearEditEntity" class="text-green-600 hover:text-green-800">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                @endif
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Qualifica / Sottotitolo</label>
                                <input type="text" wire:model="editQualifica" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                            <textarea wire:model="editNote" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-col sm:flex-row sm:justify-end gap-3">
                    <button @click="open = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md">
                        <i class="fas fa-times mr-2"></i> Annulla
                    </button>
                    <button wire:click="updateExpiration" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-md">
                        <i class="fas fa-save mr-2"></i> Aggiorna
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:init', function () {
        Livewire.on('edit-entity-selected', (data) => {
            console.log('Entità selezionata per modifica:', data);
        });
        
        Livewire.on('edit-entity-cleared', () => {
            console.log('Entità deselezionata in modifica');
        });
        
        Livewire.on('entity-selected', () => {
            console.log('Entità selezionata per creazione');
        });
    });
</script>