@extends('admin.layouts.app')

@section('title', 'Cestino - ' . ucfirst($type))

@section('content')
<div x-data="trashManager()" x-init="init()" class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-trash-alt mr-2 text-amber-600"></i> Cestino - {{ ucfirst($type) }}
            </h1>
            <p class="text-gray-500 mt-1">
                <i class="fas fa-info-circle mr-1"></i> 
                Gli elementi nel cestino vengono eliminati automaticamente dopo 30 giorni
            </p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.' . $type . '.index') }}" 
               class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition">
                <i class="fas fa-arrow-left mr-2"></i> Torna indietro
            </a>
            
            @if($items->total() > 0)
            <button @click="openEmptyModal()" 
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition">
                <i class="fas fa-trash-alt mr-2"></i> Svuota cestino
            </button>
            @endif
        </div>
    </div>

    <!-- Statistiche -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-r from-amber-50 to-yellow-50 rounded-xl p-4 border border-amber-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-amber-600">Totale nel cestino</p>
                    <p class="text-2xl font-bold text-amber-800">{{ $items->total() }}</p>
                </div>
                <i class="fas fa-trash-alt text-3xl text-amber-400"></i>
            </div>
        </div>
        
        <div class="bg-gradient-to-r from-red-50 to-orange-50 rounded-xl p-4 border border-red-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-red-600">In scadenza (&lt;7 giorni)</p>
                    <p class="text-2xl font-bold text-red-800">
                        {{ $items->filter(function($item) {
                            $daysInTrash = (int) $item->deleted_at->diffInDays(now());
                            return $daysInTrash >= 23;
                        })->count() }}
                    </p>
                </div>
                <i class="fas fa-clock text-3xl text-red-400"></i>
            </div>
        </div>
        
        <div class="bg-gradient-to-r from-emerald-50 to-green-50 rounded-xl p-4 border border-emerald-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-emerald-600">Eliminazione automatica</p>
                    <p class="text-sm text-emerald-700">Dopo 30 giorni</p>
                </div>
                <i class="fas fa-calendar-alt text-3xl text-emerald-400"></i>
            </div>
        </div>
        
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-blue-600">Selezionati</p>
                    <p class="text-2xl font-bold text-blue-800" x-text="selectedCount">0</p>
                </div>
                <i class="fas fa-check-square text-3xl text-blue-400"></i>
            </div>
        </div>
    </div>

    <!-- Barra azioni bulk -->
    <div x-show="selectedCount > 0" x-cloak class="bg-blue-50 rounded-lg p-4 mb-4 flex justify-between items-center border border-blue-200">
        <div>
            <i class="fas fa-check-circle text-blue-600 mr-2"></i>
            <span x-text="selectedCount" class="font-medium text-blue-800"></span> elementi selezionati
        </div>
        <div class="flex space-x-3">
            <button @click="openBulkRestoreModal()" 
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition">
                <i class="fas fa-trash-restore mr-2"></i> Ripristina selezionati
            </button>
            <button @click="openBulkForceDeleteModal()" 
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition">
                <i class="fas fa-trash-alt mr-2"></i> Elimina selezionati
            </button>
            <button @click="clearSelection()" 
                    class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition">
                <i class="fas fa-times mr-2"></i> Annulla
            </button>
        </div>
    </div>

    <!-- Tabella elementi nel cestino -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-amber-100">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-amber-50 to-yellow-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-amber-700 uppercase tracking-wider">
                            <input type="checkbox" id="selectAll" @change="toggleSelectAll()" class="rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-amber-700 uppercase tracking-wider">Nome</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-amber-700 uppercase tracking-wider">Dettagli</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-amber-700 uppercase tracking-wider">Data eliminazione</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-amber-700 uppercase tracking-wider">Eliminazione automatica</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-amber-700 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($items as $item)
                    @php
                        $deletedAt = $item->deleted_at;
                        $now = now();
                        $daysInTrash = (int) $deletedAt->diffInDays($now);
                        $daysRemaining = 30 - $daysInTrash;
                        if ($daysRemaining < 0) $daysRemaining = 0;
                        
                        $isExpiring = $daysRemaining <= 7 && $daysRemaining > 0;
                        $isExpired = $daysRemaining <= 0;
                        $percentage = (int) round(($daysInTrash / 30) * 100);
                        if ($percentage > 100) $percentage = 100;
                        
                        $expiryDate = $deletedAt->copy()->addDays(30);
                        
                        if($type == 'administrators') {
                            $displayName = $item->name;
                            $displayEmail = $item->email;
                            $displayRole = $item->role->name ?? 'N/A';
                            $icon = 'fa-user-shield';
                            $itemId = $item->id;
                        } elseif($type == 'users') {
                            $displayName = trim($item->first_name . ' ' . $item->last_name);
                            $displayEmail = $item->email;
                            $displayRole = $item->role ?? 'user';
                            $icon = 'fa-user';
                            $itemId = $item->id;
                        } elseif($type == 'roles') {
                            $displayName = $item->name;
                            $displayEmail = $item->slug;
                            $displayRole = null;
                            $icon = 'fa-shield-alt';
                            $itemId = $item->id;
                        } elseif($type == 'entities') {
                            if($item->ragione_sociale) {
                                $displayName = $item->ragione_sociale;
                            } else {
                                $displayName = trim($item->nome . ' ' . $item->cognome);
                            }
                            $displayEmail = $item->email;
                            $displayRole = $item->entity_type ?? null;
                            $icon = 'fa-building';
                            $itemId = $item->id_cliente;
                        } else {
                            $displayName = 'ID: ' . $item->id;
                            $displayEmail = '';
                            $displayRole = null;
                            $icon = 'fa-file';
                            $itemId = $item->id;
                        }
                    @endphp
                    <tr class="hover:bg-amber-50/30 transition-colors duration-150 {{ $isExpiring ? 'bg-red-50/30' : '' }}">
                        <td class="px-6 py-4">
                            <input type="checkbox" class="itemCheckbox rounded border-gray-300 text-amber-600 focus:ring-amber-500" value="{{ $itemId }}" @change="updateSelectedCount()">
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-amber-400 to-orange-500 rounded-full flex items-center justify-center shadow-md">
                                    <i class="fas {{ $icon }} text-white"></i>
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-800">
                                        {{ $displayName ?: 'N/A' }}
                                        @if($isExpiring)
                                            <span class="ml-2 text-xs text-red-500 animate-pulse">⚠️ In scadenza</span>
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <i class="fas fa-envelope mr-1"></i> {{ $displayEmail ?: 'Nessuna email' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($displayRole)
                                <span class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-emerald-100 text-emerald-700">
                                    <i class="fas fa-tag mr-1"></i> 
                                    @if($type == 'entities')
                                        {{ $displayRole == 'cliente' ? 'Cliente' : ($displayRole == 'fornitore' ? 'Fornitore' : 'Entrambi') }}
                                    @else
                                        {{ ucfirst($displayRole) }}
                                    @endif
                                </span>
                            @else
                                <span class="text-gray-400 text-xs">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <i class="far fa-calendar-alt mr-1 text-amber-500"></i>
                            {{ $deletedAt->format('d/m/Y H:i') }}
                            <div class="text-xs text-gray-400 mt-1">
                                <i class="far fa-clock mr-1"></i> {{ $deletedAt->diffForHumans() }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($isExpired)
                                <span class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-red-100 text-red-700">
                                    <i class="fas fa-skull-crosswalk mr-1"></i> Scaduto
                                </span>
                            @else
                                <div class="flex flex-col min-w-[180px]">
                                    <div class="text-sm font-medium {{ $isExpiring ? 'text-red-600' : 'text-gray-600' }}">
                                        <i class="fas fa-hourglass-half mr-1"></i>
                                        {{ number_format($daysRemaining, 0, ',', '.') }} giorni rimanenti
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-1.5 mt-2">
                                        <div class="bg-gradient-to-r from-amber-500 to-red-500 h-1.5 rounded-full transition-all duration-500" 
                                             style="width: {{ $percentage }}%"></div>
                                    </div>
                                    <div class="text-xs text-gray-400 mt-1.5 flex justify-between">
                                        <span><i class="far fa-calendar-check mr-1"></i> {{ $deletedAt->format('d/m/Y') }}</span>
                                        <span><i class="far fa-hourglass mr-1"></i> {{ $expiryDate->format('d/m/Y') }}</span>
                                    </div>
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <div class="flex space-x-2">
                                @if(!$isExpired)
                                <button @click="openRestoreModal('{{ $type }}', {{ $itemId }}, '{{ addslashes($displayName) }}')" 
                                        class="text-green-600 hover:text-green-800 transition-colors p-1.5 rounded-lg hover:bg-green-50"
                                        title="Ripristina">
                                    <i class="fas fa-trash-restore"></i>
                                </button>
                                @endif
                                
                                <button @click="openForceDeleteModal('{{ $type }}', {{ $itemId }}, '{{ addslashes($displayName) }}')" 
                                        class="text-red-600 hover:text-red-800 transition-colors p-1.5 rounded-lg hover:bg-red-50"
                                        title="Elimina permanentemente">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center text-gray-400">
                                <i class="fas fa-trash-alt text-5xl mb-3"></i>
                                <p class="text-lg">Il cestino è vuoto</p>
                                <p class="text-sm mt-1">Nessun elemento eliminato</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-6 py-4 border-t border-amber-100 bg-amber-50/20">
            {{ $items->links() }}
        </div>
    </div>

    <!-- MODALE CONFERMA RIPRISTINO -->
    <div x-show="showRestoreModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" @click="showRestoreModal = false">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-trash-restore text-green-600 text-xl"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                Conferma ripristino
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Sei sicuro di voler ripristinare <span class="font-semibold text-gray-700" x-text="itemName"></span>?
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <form id="restoreForm" method="POST">
                        @csrf
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                            <i class="fas fa-check mr-2"></i> Ripristina
                        </button>
                    </form>
                    <button @click="showRestoreModal = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Annulla
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODALE CONFERMA ELIMINAZIONE PERMANENTE -->
    <div x-show="showForceDeleteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" @click="showForceDeleteModal = false">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                Eliminazione permanente
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Questa azione è <span class="font-bold text-red-600">IRREVERSIBILE</span>!
                                </p>
                                <p class="text-sm text-gray-500 mt-2">
                                    Sei sicuro di voler eliminare permanentemente <span class="font-semibold text-gray-700" x-text="itemName"></span>?
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <form id="forceDeleteForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            <i class="fas fa-trash-alt mr-2"></i> Elimina permanentemente
                        </button>
                    </form>
                    <button @click="showForceDeleteModal = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Annulla
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODALE CONFERMA RIPRISTINO BULK -->
    <div x-show="showBulkRestoreModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" @click="showBulkRestoreModal = false">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-trash-restore text-green-600 text-xl"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                Conferma ripristino massivo
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Sei sicuro di voler ripristinare <span class="font-semibold text-gray-700" x-text="selectedCount"></span> elementi?
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button @click="bulkRestore()" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                        <i class="fas fa-check mr-2"></i> Ripristina tutti
                    </button>
                    <button @click="showBulkRestoreModal = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Annulla
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODALE CONFERMA ELIMINAZIONE BULK -->
    <div x-show="showBulkForceDeleteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" @click="showBulkForceDeleteModal = false">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                Eliminazione permanente massiva
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Questa azione è <span class="font-bold text-red-600">IRREVERSIBILE</span>!
                                </p>
                                <p class="text-sm text-gray-500 mt-2">
                                    Sei sicuro di voler eliminare permanentemente <span class="font-semibold text-gray-700" x-text="selectedCount"></span> elementi?
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button @click="bulkForceDelete()" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        <i class="fas fa-trash-alt mr-2"></i> Elimina tutti
                    </button>
                    <button @click="showBulkForceDeleteModal = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Annulla
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODALE CONFERMA SVUOTA CESTINO -->
    <div x-show="showEmptyModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" @click="showEmptyModal = false">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                Svuota cestino
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Questa azione è <span class="font-bold text-red-600">IRREVERSIBILE</span>!
                                </p>
                                <p class="text-sm text-gray-500 mt-2">
                                    Sei sicuro di voler eliminare permanentemente <span class="font-semibold text-gray-700">{{ $items->total() }}</span> elementi dal cestino?
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <form id="emptyTrashForm" method="POST">
                        @csrf
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            <i class="fas fa-trash-alt mr-2"></i> Svuota cestino
                        </button>
                    </form>
                    <button @click="showEmptyModal = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Annulla
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function trashManager() {
        return {
            selectedItems: new Set(),
            selectedCount: 0,
            showRestoreModal: false,
            showForceDeleteModal: false,
            showBulkRestoreModal: false,
            showBulkForceDeleteModal: false,
            showEmptyModal: false,
            itemType: '',
            itemId: null,
            itemName: '',
            
            init() {
                this.updateSelectedCount();
            },
            
            updateSelectedCount() {
                this.selectedItems.clear();
                document.querySelectorAll('.itemCheckbox:checked').forEach(checkbox => {
                    this.selectedItems.add(checkbox.value);
                });
                this.selectedCount = this.selectedItems.size;
                
                const selectAllCheckbox = document.getElementById('selectAll');
                if (selectAllCheckbox) {
                    const allCheckboxes = document.querySelectorAll('.itemCheckbox');
                    selectAllCheckbox.checked = allCheckboxes.length > 0 && allCheckboxes.length === this.selectedCount;
                }
            },
            
            toggleSelectAll() {
                const selectAllCheckbox = document.getElementById('selectAll');
                const allCheckboxes = document.querySelectorAll('.itemCheckbox');
                allCheckboxes.forEach(checkbox => {
                    checkbox.checked = selectAllCheckbox.checked;
                });
                this.updateSelectedCount();
            },
            
            clearSelection() {
                const allCheckboxes = document.querySelectorAll('.itemCheckbox');
                allCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
                this.updateSelectedCount();
            },
            
            openRestoreModal(type, id, name) {
                this.itemType = type;
                this.itemId = id;
                this.itemName = name;
                this.showRestoreModal = true;
                
                const form = document.getElementById('restoreForm');
                form.action = `/admin/trash/${type}/${id}/restore`;
            },
            
            openForceDeleteModal(type, id, name) {
                this.itemType = type;
                this.itemId = id;
                this.itemName = name;
                this.showForceDeleteModal = true;
                
                const form = document.getElementById('forceDeleteForm');
                form.action = `/admin/trash/${type}/${id}/force-delete`;
            },
            
            openBulkRestoreModal() {
                if (this.selectedCount === 0) return;
                this.showBulkRestoreModal = true;
            },
            
            openBulkForceDeleteModal() {
                if (this.selectedCount === 0) return;
                this.showBulkForceDeleteModal = true;
            },
            
            openEmptyModal() {
                this.showEmptyModal = true;
                const form = document.getElementById('emptyTrashForm');
                form.action = `/admin/trash/{{ $type }}/empty`;
            },
            
            async bulkRestore() {
                if (this.selectedCount === 0) return;
                
                const ids = Array.from(this.selectedItems);
                
                try {
                    const response = await fetch(`/admin/trash/{{ $type }}/bulk-restore`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ ids: ids })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.showBulkRestoreModal = false;
                        location.reload();
                    } else {
                        alert('Errore: ' + result.message);
                    }
                } catch (error) {
                    alert('Errore durante il ripristino');
                }
            },
            
            async bulkForceDelete() {
                if (this.selectedCount === 0) return;
                
                const ids = Array.from(this.selectedItems);
                
                try {
                    const response = await fetch(`/admin/trash/{{ $type }}/bulk-force-delete`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ ids: ids })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.showBulkForceDeleteModal = false;
                        location.reload();
                    } else {
                        alert('Errore: ' + result.message);
                    }
                } catch (error) {
                    alert('Errore durante l\'eliminazione');
                }
            }
        }
    }
</script>

<style>
    [x-cloak] { display: none !important; }
</style>
@endsection