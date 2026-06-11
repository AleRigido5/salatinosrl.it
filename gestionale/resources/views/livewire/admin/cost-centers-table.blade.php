<div>
    <!-- Header -->
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fa-solid fa-scale-unbalanced mr-2 text-lime-600"></i> Centri di Costo 
                </h1>
            </div>
            <div class="flex gap-3">
                @if(auth()->guard('admin')->user()->hasPermission('create_cost_centers'))
                <a href="{{ route('admin.cost_centers.create') }}" 
                   class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-5 py-2.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                    <i class="fas fa-plus"></i>
                </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Filtri e Ricerca -->
    <div class="bg-white rounded-lg shadow mb-6 p-4 border border-gray-200" wire:key="filters-{{ $search }}-{{ $typeFilter }}-{{ $statusFilter }}-{{ $referenceFilter }}">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <!-- Tipo Riferimento (col 2) -->
            <div class="md:col-span-2">
                <select wire:model.live="typeFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                    <option value="">Tutti i tipi</option>
                    <option value="ownership">Proprietà</option>
                    <option value="entities">Clienti/Fornitori</option>
                </select>
            </div>
            
            <!-- Riferimento Specifico (col 3) -->
            <div class="md:col-span-3">
                <select wire:model.live="referenceFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                    <option value="">Tutti i riferimenti</option>
                    @foreach($referenceList as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            
            <!-- Ricerca (col 5) -->
            <div class="relative md:col-span-5">
                <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       placeholder="Cerca per nome, contrada, località, coltura..." 
                       class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent">
            </div>
            
            <!-- Stato (col 2) - mantenuto solo per filtro -->
            <div class="md:col-span-2">
                <select wire:model.live="statusFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                    <option value="">Tutti gli stati</option>
                    <option value="1">Attivi</option>
                    <option value="0">Disattivi</option>
                </select>
            </div>
        </div>
        
        <div class="flex justify-between items-center mt-4">
            @if($search || $typeFilter || $statusFilter || $referenceFilter)
            <button wire:click="resetFilters" class="text-sm text-gray-600 hover:text-gray-900 transition-colors">
                <i class="fas fa-sync-alt mr-1"></i>
                Resetta filtri
            </button>
            @endif
        </div>
    </div>

    <!-- Tabella Centri di Costo -->
    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('id')">
                            <div class="flex items-center space-x-1">
                                <span>ID</span>
                                @if($sortField === 'id')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <span>Riferimento</span>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('nome')">
                            <div class="flex items-center space-x-1">
                                <span>Nome</span>
                                @if($sortField === 'nome')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('localita')">
                            <div class="flex items-center space-x-1">
                                <span>Località</span>
                                @if($sortField === 'localita')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('coltura')">
                            <div class="flex items-center space-x-1">
                                <span>Coltura</span>
                                @if($sortField === 'coltura')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('superficie')">
                            <div class="flex items-center space-x-1">
                                <span>Superficie</span>
                                @if($sortField === 'superficie')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('costo_h')">
                            <div class="flex items-center space-x-1">
                                <span>Costo/h</span>
                                @if($sortField === 'costo_h')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('competenza')">
                            <div class="flex items-center space-x-1">
                                <span>Competenza</span>
                                @if($sortField === 'competenza')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($costCenters as $center)
                    <tr wire:key="center-{{ $center->id }}" class="hover:bg-gray-50 transition-colors duration-150 border-t border-gray-200">
                        <!-- ID -->
                        <td class="px-4 py-4 whitespace-nowrap">
                            <span class="text-sm font-sans font-medium text-gray-400">
                                {{ $center->id }}
                            </span>
                        </td>

                        <!-- Riferimento -->
                        <td class="px-4 py-4 whitespace-nowrap">
                            <div class="flex flex-col">
                                <span class="text-xs text-gray-500">
                                    {{ $center->table_references === 'ownership' ? 'Proprietà' : 'Cliente/Fornitore' }}
                                </span>
                                <span class="text-sm text-gray-900 font-medium">
                                    @if($center->table_references === 'ownership' && $center->ownership)
                                        {{ $center->ownership->RagAbbrev ?? $center->ownership->Rag_Soc_intest ?? 'Proprietà' }}
                                    @elseif($center->table_references === 'entities' && $center->entity)
                                        {{ $center->entity->ragione_sociale ?? ($center->entity->nome . ' ' . $center->entity->cognome) }}
                                    @else
                                        N/D
                                    @endif
                                </span>
                            </div>
                        </td>
                        
                        <!-- Nome -->
                        <td class="px-4 py-4">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $center->Nome ?? '-' }}
                            </div>
                            @if($center->Contrada)
                            <div class="text-xs text-gray-500">
                                {{ $center->Contrada }}
                            </div>
                            @endif
                        </td>
                        
                        <!-- Località -->
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $center->Localita ?? '-' }}
                        </td>
                        
                        <!-- Coltura -->
                        <td class="px-4 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                {{ $center->Coltura ?? '-' }}
                            </span>
                        </td>
                        
                        <!-- Superficie -->
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ number_format($center->Superficie ?? 0, 2) }} ha
                        </td>
                        
                        <!-- Costo/h -->
                        <td class="px-4 py-4 whitespace-nowrap">
                            <span class="text-sm font-mono font-medium text-gray-900">
                                € {{ number_format($center->CostoH ?? 0, 2) }}
                            </span>
                        </td>
                        
                        <!-- Competenza -->
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $center->Competenza ?? '-' }}
                        </td>
                        
                        <!-- Azioni -->
                        <td class="px-4 py-4 text-sm font-medium whitespace-nowrap">
                            <div class="flex space-x-3">
                                {{-- Dettaglio fatture + attività --}}
                                <a href="{{ route('admin.cost_centers.detail', $center->id) }}"
                                    class="text-lime-600 hover:text-lime-800 transition-colors text-base"
                                    title="Fatture & Attività">
                                    <i class="fa-solid fa-scale-balanced"></i>
                                </a>

                                {{-- PDF Fatture di Vendita collegate --}}
                                {{-- @php
                                    $invoiceSentIds = \App\Models\InvoiceRow::where('id_cost_center', $center->id)
                                        ->where('document_type', 'invoice_sent')
                                        ->pluck('document_id')
                                        ->unique();
                                @endphp
                                @foreach($invoiceSentIds as $invoiceId)
                                    <a href="{{ route('admin.invoices-sent.preview', $invoiceId) }}"
                                    target="_blank"
                                    class="text-red-500 hover:text-red-700 transition-colors text-base"
                                    title="PDF Fattura di Vendita #{{ $invoiceId }}">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                @endforeach --}}

                                {{-- PDF Allegati Fatture di Acquisto da S3 --}}
                                {{-- @php
                                    $invoiceReceivedIds = \App\Models\InvoiceRow::where('id_cost_center', $center->id)
                                        ->where('document_type', 'invoice_received')
                                        ->pluck('document_id')
                                        ->unique();

                                    $s3Attachments = \App\Models\InvoiceReceived::whereIn('id', $invoiceReceivedIds)
                                        ->whereNotNull('attachment')
                                        ->get(['id', 'n_invoice', 'attachment']);
                                @endphp
                                @foreach($s3Attachments as $inv)
                                    @php
                                        $urls = json_decode($inv->attachment, true) ?? [];
                                    @endphp
                                    @foreach($urls as $url)
                                        <a href="{{ $url }}"
                                        target="_blank"
                                        class="text-orange-500 hover:text-orange-700 transition-colors text-base"
                                        title="Allegato Fattura Acquisto {{ $inv->n_invoice }}">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                    @endforeach
                                @endforeach --}}

                                @if(auth()->guard('admin')->user()->hasPermission('view_cost_centers'))
                                <button wire:click="viewCostCenter({{ $center->id }})" 
                                        wire:key="view-{{ $center->id }}"
                                        class="text-blue-600 hover:text-blue-900 transition-colors text-base"
                                        title="Visualizza">
                                    <i class="fa-regular fa-eye text-blue-600 hover:text-blue-900"></i>
                                </button>
                                @endif

                                @if(auth()->guard('admin')->user()->hasPermission('edit_cost_centers'))
                                <a href="{{ route('admin.cost_centers.edit', $center->id) }}" 
                                   wire:key="edit-{{ $center->id }}"
                                   class="text-yellow-600 hover:text-yellow-900 transition-colors text-base"
                                   title="Modifica">
                                    <i class="fa-solid fa-pen-to-square text-yellow-600 hover:text-yellow-900"></i>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center">
                            <div class="text-gray-500">
                                <i class="fa-solid fa-scale-unbalanced fa-3x text-gray-400 mb-3"></i>
                                <p class="mt-2 text-sm">Nessun centro di costo trovato</p>
                                @if($search || $typeFilter || $statusFilter || $referenceFilter)
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
    @if($costCenters->hasPages())
    <div class="mt-6">
        <div class="text-sm text-gray-500 mb-2">
            Mostrando {{ $costCenters->firstItem() ?? 0 }} - {{ $costCenters->lastItem() ?? 0 }} di {{ $costCenters->total() }} risultati
        </div>
        <div class="flex justify-center">
            {{ $costCenters->links() }}
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
    </style>

    <!-- MODAL VISUALIZZAZIONE DETTAGLIO CENTRO DI COSTO -->
    @if($showViewModal && $viewingCostCenter)
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
                            <i class="fa-solid fa-scale-unbalanced mr-2 text-lime-600"></i> Dettaglio Centro di Costo
                        </h2>
                        <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Tipo Riferimento</label>
                                <p class="text-gray-900">{{ $viewingCostCenter->table_references === 'ownership' ? 'Proprietà' : 'Cliente/Fornitore' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Riferimento</label>
                                <p class="text-gray-900">
                                    @if($viewingCostCenter->table_references === 'ownership' && $viewingCostCenter->ownership)
                                        {{ $viewingCostCenter->ownership->RagAbbrev ?? $viewingCostCenter->ownership->Rag_Soc_intest ?? 'Proprietà' }}
                                    @elseif($viewingCostCenter->table_references === 'entities' && $viewingCostCenter->entity)
                                        {{ $viewingCostCenter->entity->ragione_sociale ?? ($viewingCostCenter->entity->nome . ' ' . $viewingCostCenter->entity->cognome) }}
                                    @else
                                        N/D
                                    @endif
                                </p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Nome</label>
                                <p class="text-gray-900">{{ $viewingCostCenter->Nome ?? '-' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Contrada</label>
                                <p class="text-gray-900">{{ $viewingCostCenter->Contrada ?? '-' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Località</label>
                                <p class="text-gray-900">{{ $viewingCostCenter->Localita ?? '-' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Foglio</label>
                                <p class="text-gray-900">{{ $viewingCostCenter->Foglio ?? '-' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Particella</label>
                                <p class="text-gray-900">{{ $viewingCostCenter->Particella ?? '-' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Superficie</label>
                                <p class="text-gray-900">{{ number_format($viewingCostCenter->Superficie ?? 0, 2) }} ha</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Coltura</label>
                                <p class="text-gray-900">{{ $viewingCostCenter->Coltura ?? '-' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Costo Orario</label>
                                <p class="text-gray-900">€ {{ number_format($viewingCostCenter->CostoH ?? 0, 2) }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Ore Giornaliere</label>
                                <p class="text-gray-900">{{ $viewingCostCenter->NumH ?? 0 }} h</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Competenza</label>
                                <p class="text-gray-900">{{ $viewingCostCenter->Competenza ?? '-' }}</p>
                            </div>
                        </div>
                        
                        @if($viewingCostCenter->Note)
                        <div>
                            <label class="text-sm font-medium text-gray-500">Note</label>
                            <p class="text-gray-700 mt-1">{{ $viewingCostCenter->Note }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-col sm:flex-row sm:justify-end gap-3">
                    <button @click="open = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md">
                        <i class="fas fa-times mr-2"></i> Chiudi
                    </button>
                    @if(auth()->guard('admin')->user()->hasPermission('edit_cost_centers'))
                    <a href="{{ route('admin.cost_centers.edit', $viewingCostCenter->id) }}" 
                       @click="open = false"
                       class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-md">
                        <i class="fas fa-edit mr-2"></i> Modifica
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>