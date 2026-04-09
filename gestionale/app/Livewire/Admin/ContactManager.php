<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Contact;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

class ContactManager extends Component
{
    public $entityId;
    public $contacts = [];
    public $contactTypes = [];
    public $editingContactId = null;
    public $showForm = false;
    
    // Form fields
    public $id_settings = '';
    public $valore = '';
    public $principale = false;
    
    // Modal di conferma per creazione/modifica
    public $showConfirmModal = false;
    public $confirmAction = ''; // 'create' o 'update'
    public $confirmData = [];
    
    // Modal di notifica
    public $showNotification = false;
    public $notificationMessage = '';
    public $notificationType = 'success';
    
    // Modal di conferma eliminazione
    public $showDeleteModal = false;
    public $contactToDelete = null;
    public $contactToDeleteName = '';
    
    protected $listeners = ['refreshContacts' => 'loadContacts'];
    
    public function mount($entityId)
    {
        $this->entityId = $entityId;
        $this->loadContactTypes();
        $this->loadContacts();
    }
    
    public function loadContactTypes()
    {
        $this->contactTypes = Setting::where('tabella_riferimento', 'contacts')
            ->where('valid', true)
            ->orderBy('ordinamento')
            ->get()
            ->toArray();
    }
    
    public function loadContacts()
    {
        $this->contacts = Contact::where('id_entities', $this->entityId)
            ->with('setting')
            ->orderBy('id_settings')
            ->get()
            ->toArray();
    }
    
    public function resetForm()
    {
        $this->reset([
            'id_settings', 'valore', 'principale', 'editingContactId'
        ]);
        $this->principale = false;
        $this->showForm = false;
    }
    
    public function showCreateForm()
    {
        $this->resetForm();
        $this->showForm = true;
    }
    
    public function editContact($id)
    {
        $contact = Contact::where('id_entities', $this->entityId)
            ->where('id', $id)
            ->first();
            
        if ($contact) {
            $this->editingContactId = $id;
            $this->id_settings = $contact->id_settings;
            $this->valore = $contact->valore;
            $this->principale = $contact->principale == 1;
            $this->showForm = true;
        }
    }
    
    public function cancelEdit()
    {
        $this->resetForm();
    }
    
    public function confirmSave()
    {
        $this->validate([
            'id_settings' => 'required|exists:settings,id',
            'valore' => 'required|string|max:255',
        ]);
        
        $this->confirmAction = $this->editingContactId ? 'update' : 'create';
        $this->confirmData = [
            'id_settings' => $this->id_settings,
            'valore' => $this->valore,
            'principale' => $this->principale,
        ];
        $this->showConfirmModal = true;
    }
    
    public function saveContact()
    {
        try {
            $data = [
                'id_entities' => $this->entityId,
                'id_settings' => $this->confirmData['id_settings'],
                'valore' => $this->confirmData['valore'],
                'principale' => $this->confirmData['principale'] ? 1 : 0,
            ];
            
            // Se questo contatto è impostato come principale, rimuovi principale dagli altri dello stesso tipo
            if ($this->confirmData['principale']) {
                Contact::where('id_entities', $this->entityId)
                    ->where('id_settings', $this->confirmData['id_settings'])
                    ->update(['principale' => 0]);
            }
            
            if ($this->confirmAction == 'update') {
                Contact::where('id_entities', $this->entityId)
                    ->where('id', $this->editingContactId)
                    ->update($data);
                $message = 'Contatto aggiornato con successo!';
            } else {
                Contact::create($data);
                $message = 'Contatto aggiunto con successo!';
            }
            
            $this->showConfirmModal = false;
            $this->resetForm();
            $this->loadContacts();
            $this->showNotificationMessage($message, 'success');
            
        } catch (\Exception $e) {
            $this->showConfirmModal = false;
            $this->showNotificationMessage('Errore: ' . $e->getMessage(), 'error');
        }
    }
    
    public function cancelConfirm()
    {
        $this->showConfirmModal = false;
        $this->confirmAction = '';
        $this->confirmData = [];
    }
    
    public function confirmDelete($id)
    {
        $contact = Contact::where('id_entities', $this->entityId)
            ->where('id', $id)
            ->with('setting')
            ->first();
            
        if ($contact) {
            $this->contactToDelete = $contact->id;
            $tipo = $contact->setting->valore ?? 'Contatto';
            $this->contactToDeleteName = $tipo . ': ' . $contact->valore;
            $this->showDeleteModal = true;
        }
    }
    
    public function deleteContact()
    {
        try {
            if ($this->contactToDelete) {
                Contact::where('id_entities', $this->entityId)
                    ->where('id', $this->contactToDelete)
                    ->delete();
                    
                $this->loadContacts();
                $this->showNotificationMessage('Contatto eliminato con successo!', 'success');
            }
            
            $this->showDeleteModal = false;
            $this->contactToDelete = null;
            $this->contactToDeleteName = '';
            
        } catch (\Exception $e) {
            $this->showNotificationMessage('Errore durante l\'eliminazione', 'error');
            $this->showDeleteModal = false;
        }
    }
    
    public function cancelDelete()
    {
        $this->showDeleteModal = false;
        $this->contactToDelete = null;
        $this->contactToDeleteName = '';
    }
    
    public function showNotificationMessage($message, $type = 'success')
    {
        $this->notificationMessage = $message;
        $this->notificationType = $type;
        $this->showNotification = true;
        
        // Auto-hide dopo 3 secondi
        $this->dispatch('hide-notification');
    }
    
    public function hideNotification()
    {
        $this->showNotification = false;
        $this->notificationMessage = '';
    }
    
    public function render()
    {
        return view('livewire.admin.contact-manager');
    }
}