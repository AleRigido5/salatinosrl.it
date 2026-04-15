@extends('admin.layouts.app')

@section('title', $category->titolo . ' - Impostazioni')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.settings.categories.index') }}" 
                   class="text-gray-500 hover:text-gray-700 transition-colors">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ $category->titolo }}</h1>
                    <p class="text-gray-500 text-sm">{{ $category->descrizione }}</p>
                </div>
            </div>
        </div>
        @if(auth()->guard('admin')->user()->hasPermission('edit_settings'))
        <button type="button" 
                onclick="openCreateModal()"
                class="bg-lime-600 hover:bg-lime-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
            <i class="fas fa-plus mr-2"></i> Nuova Impostazione
        </button>
        @endif
    </div>

    <!-- Tabella Impostazioni -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valore</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrizione</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ordinamento</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stato</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($settings as $setting)
                    <tr id="setting-row-{{ $setting->id }}">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $setting->id }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-800 font-medium">
                                {{ $setting->valore ?: '-' }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-500">
                                {{ $setting->descrizione ?: '-' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $setting->ordinamento }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $setting->valid ? 'bg-lime-100 text-lime-800' : 'bg-red-100 text-red-800' }}">
                                {{ $setting->valid ? 'Attivo' : 'Disattivo' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button type="button" 
                                    onclick="openEditModal({{ $setting->id }}, '{{ addslashes($setting->valore) }}', '{{ addslashes($setting->descrizione) }}', {{ $setting->ordinamento }}, {{ $setting->valid ? 'true' : 'false' }})"
                                    class="text-lime-600 hover:text-lime-900 transition-colors mr-3">
                                <i class="fas fa-edit"></i> Modifica
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                            <i class="fas fa-cog text-4xl mb-2 block"></i>
                            Nessuna impostazione trovata in questa categoria
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal CREA Impostazione -->
<div id="createSettingModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 transform transition-all">
        <div class="flex justify-between items-center mb-4 border-b pb-3">
            <h2 class="text-xl font-bold text-gray-800">
                <i class="fas fa-plus-circle mr-2 text-lime-600"></i> Nuova Impostazione
            </h2>
            <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="createSettingForm" method="POST" action="{{ route('admin.settings.store') }}">
            @csrf
            <input type="hidden" name="category_id" value="{{ $category->id }}">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valore <span class="text-red-500">*</span></label>
                    <input type="text" id="create_valore" name="valore" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrizione</label>
                    <textarea id="create_descrizione" name="descrizione" rows="3"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ordinamento</label>
                    <input type="number" id="create_ordinamento" name="ordinamento" value="0"
                           class="w-32 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                </div>
                
                <div>
                    <label class="inline-flex items-center">
                        <input type="checkbox" id="create_valid" name="valid" value="1" checked
                               class="rounded border-gray-300 text-lime-600 shadow-sm focus:ring-lime-500">
                        <span class="ml-2 text-sm text-gray-700">Impostazione attiva</span>
                    </label>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                <button type="button" onclick="closeCreateModal()" 
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                    Annulla
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-lime-600 hover:bg-lime-700 text-white rounded-md transition-colors">
                    <i class="fas fa-save mr-2"></i> Crea
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal MODIFICA Impostazione -->
<div id="editSettingModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 transform transition-all">
        <div class="flex justify-between items-center mb-4 border-b pb-3">
            <h2 class="text-xl font-bold text-gray-800">
                <i class="fas fa-edit mr-2 text-lime-600"></i> Modifica Impostazione
            </h2>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="editSettingForm" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" id="edit_setting_id" name="setting_id">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valore <span class="text-red-500">*</span></label>
                    <input type="text" id="edit_valore" name="valore" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrizione</label>
                    <textarea id="edit_descrizione" name="descrizione" rows="3"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ordinamento</label>
                    <input type="number" id="edit_ordinamento" name="ordinamento"
                           class="w-32 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                </div>
                
                <div>
                    <label class="inline-flex items-center">
                        <input type="checkbox" id="edit_valid" name="valid" value="1"
                               class="rounded border-gray-300 text-lime-600 shadow-sm focus:ring-lime-500">
                        <span class="ml-2 text-sm text-gray-700">Impostazione attiva</span>
                    </label>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                <button type="button" onclick="closeEditModal()" 
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                    Annulla
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-lime-600 hover:bg-lime-700 text-white rounded-md transition-colors">
                    <i class="fas fa-save mr-2"></i> Aggiorna
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal CONFERMA ELIMINAZIONE -->
<div id="deleteConfirmModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 transform transition-all">
        <div class="flex justify-between items-center mb-4 border-b pb-3">
            <h2 class="text-xl font-bold text-gray-800">
                <i class="fas fa-exclamation-triangle mr-2 text-red-600"></i> Conferma Eliminazione
            </h2>
            <button onclick="closeDeleteModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="py-4">
            <p class="text-gray-700">Sei sicuro di voler eliminare l'impostazione:</p>
            <p class="text-gray-900 font-semibold mt-2" id="delete_item_name"></p>
            <p class="text-red-600 text-sm mt-3">⚠️ Questa azione è irreversibile!</p>
        </div>
        
        <div class="flex justify-end space-x-3 mt-4 pt-4 border-t">
            <button type="button" onclick="closeDeleteModal()" 
                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                Annulla
            </button>
            <form id="deleteForm" method="POST" style="display: inline;">
                @csrf
                @method('DELETE')
                <button type="submit" 
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md transition-colors">
                    <i class="fas fa-trash-alt mr-2"></i> Elimina
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    // Variabili per tenere traccia dell'elemento da eliminare
    let deleteSettingId = null;
    let deleteSettingName = null;
    
    // ==================== FUNZIONI CREATE ====================
    function openCreateModal() {
        const modal = document.getElementById('createSettingModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        
        // Focus sul primo campo
        setTimeout(() => {
            document.getElementById('create_valore').focus();
        }, 100);
    }
    
    function closeCreateModal() {
        const modal = document.getElementById('createSettingModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
        
        // Resetta il form
        document.getElementById('createSettingForm').reset();
        document.getElementById('create_ordinamento').value = '0';
        document.getElementById('create_valid').checked = true;
    }
    
    // ==================== FUNZIONI EDIT ====================
    function openEditModal(id, valore, descrizione, ordinamento, valid) {
        // Imposta i valori nel form
        document.getElementById('edit_setting_id').value = id;
        document.getElementById('edit_valore').value = valore;
        document.getElementById('edit_descrizione').value = descrizione || '';
        document.getElementById('edit_ordinamento').value = ordinamento;
        document.getElementById('edit_valid').checked = valid;
        
        // Imposta l'action del form
        const form = document.getElementById('editSettingForm');
        form.action = '/admin/settings/' + id;
        
        // Mostra il modal
        const modal = document.getElementById('editSettingModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }
    
    function closeEditModal() {
        const modal = document.getElementById('editSettingModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
        
        // Resetta il form
        document.getElementById('editSettingForm').reset();
    }
    
    // ==================== FUNZIONI DELETE ====================
    function confirmDelete(id, name) {
        deleteSettingId = id;
        deleteSettingName = name;
        
        document.getElementById('delete_item_name').textContent = name;
        
        const form = document.getElementById('deleteForm');
        form.action = '/admin/settings/' + id;
        
        const modal = document.getElementById('deleteConfirmModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }
    
    function closeDeleteModal() {
        const modal = document.getElementById('deleteConfirmModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
        
        deleteSettingId = null;
        deleteSettingName = null;
    }
    
    // ==================== SUBMIT FORMS CON AJAX ====================
    
    // Submit del form di creazione
    document.getElementById('createSettingForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });
            
            const data = await response.json();
            
            if (response.ok && data.success) {
                showToast('Impostazione creata con successo!', 'success');
                closeCreateModal();
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                showToast(data.message || 'Errore durante la creazione', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Errore durante la creazione', 'error');
        }
    });
    
    // Submit del form di modifica
    document.getElementById('editSettingForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        const settingId = document.getElementById('edit_setting_id').value;
        
        // Aggiungi _method per PUT
        formData.append('_method', 'PUT');
        
        try {
            const response = await fetch('/admin/settings/' + settingId, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });
            
            const data = await response.json();
            
            if (response.ok && data.success) {
                showToast('Impostazione aggiornata con successo!', 'success');
                closeEditModal();
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                showToast(data.message || 'Errore durante l\'aggiornamento', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Errore durante l\'aggiornamento', 'error');
        }
    });
    
    // Submit del form di eliminazione
    document.getElementById('deleteForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const form = e.target;
        const settingId = deleteSettingId;
        
        try {
            const response = await fetch('/admin/settings/' + settingId, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (response.ok && data.success) {
                showToast('Impostazione eliminata con successo!', 'success');
                closeDeleteModal();
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                showToast(data.message || 'Errore durante l\'eliminazione', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Errore durante l\'eliminazione', 'error');
        }
    });
    
    // ==================== FUNZIONI UTILITY ====================
    
    function showToast(message, type = 'success') {
        // Rimuovi toast esistenti
        const existingToasts = document.querySelectorAll('.toast-notification');
        existingToasts.forEach(toast => toast.remove());
        
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg text-white ${type === 'success' ? 'bg-lime-500' : 'bg-red-500'} toast-notification`;
        toast.innerHTML = `
            <div class="flex items-center">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-2"></i>
                <span>${message}</span>
            </div>
        `;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.5s ease';
            setTimeout(() => toast.remove(), 500);
        }, 3000);
    }
    
    // Chiudi modali con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeCreateModal();
            closeEditModal();
            closeDeleteModal();
        }
    });
    
    // Chiudi modali cliccando fuori
    document.getElementById('createSettingModal').addEventListener('click', function(e) {
        if (e.target === this) closeCreateModal();
    });
    document.getElementById('editSettingModal').addEventListener('click', function(e) {
        if (e.target === this) closeEditModal();
    });
    document.getElementById('deleteConfirmModal').addEventListener('click', function(e) {
        if (e.target === this) closeDeleteModal();
    });
</script>

<style>
    .toast-notification {
        animation: slideInRight 0.3s ease-out;
    }
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
</style>
@endsection