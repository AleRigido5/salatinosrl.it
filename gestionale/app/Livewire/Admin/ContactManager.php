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
            ->where('id_contatto', $id)
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
    
    public function saveContact()
    {
        $this->validate([
            'id_settings' => 'required|exists:settings,id_settings',
            'valore' => 'required|string|max:255',
        ]);
        
        // Se questo contatto è impostato come principale, rimuovi principale dagli altri dello stesso tipo
        if ($this->principale) {
            Contact::where('id_entities', $this->entityId)
                ->where('id_settings', $this->id_settings)
                ->update(['principale' => 0]);
        }
        
        $data = [
            'id_entities' => $this->entityId,
            'id_settings' => $this->id_settings,
            'valore' => $this->valore,
            'principale' => $this->principale ? 1 : 0,
        ];
        
        if ($this->editingContactId) {
            // Update
            Contact::where('id_entities', $this->entityId)
                ->where('id_contatto', $this->editingContactId)
                ->update($data);
            $message = 'Contatto aggiornato con successo!';
        } else {
            // Create
            Contact::create($data);
            $message = 'Contatto aggiunto con successo!';
        }
        
        $this->resetForm();
        $this->loadContacts();
        $this->dispatch('contact-saved', $message);
    }
    
    public function deleteContact($id)
    {
        Contact::where('id_entities', $this->entityId)
            ->where('id_contatto', $id)
            ->delete();
            
        $this->loadContacts();
        $this->dispatch('contact-deleted', 'Contatto eliminato con successo!');
    }
    
    public function getTypeName($idSettings)
    {
        foreach ($this->contactTypes as $type) {
            if ($type['id_settings'] == $idSettings) {
                return $type['nome'];
            }
        }
        return 'Tipo sconosciuto';
    }
    
    public function render()
    {
        return view('livewire.admin.contact-manager');
    }
}