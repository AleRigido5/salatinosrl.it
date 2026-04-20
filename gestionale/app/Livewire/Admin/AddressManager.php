<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Address;
use Illuminate\Support\Facades\Auth;

class AddressManager extends Component
{
    public $entityId;
    public $addresses = [];
    public $editingAddressId = null;
    public $showForm = false;
    
    // Form fields
    public $sede = '';
    public $indirizzo = '';
    public $citta = '';
    public $provincia = '';
    public $nazione = '';
    public $cap = '';
    public $telefono = '';
    public $cellulare = '';
    public $fax = '';
    
    // Modal di conferma eliminazione
    public $showDeleteModal = false;
    public $addressToDelete = null;
    public $addressToDeleteName = '';
    
    // Modal di notifica
    public $showNotification = false;
    public $notificationMessage = '';
    public $notificationType = 'success';
    
    protected $listeners = [
        'refreshAddresses' => 'loadAddresses',
        'hideNotification' => 'hideNotification'
    ];
    
    public function mount($entityId)
    {
        $this->entityId = $entityId;
        $this->loadAddresses();
    }
    
    public function loadAddresses()
    {
        $this->addresses = Address::where('clienti_id_cliente', $this->entityId)
            ->orderBy('id_indirizzo')
            ->get()
            ->toArray();
    }
    
    public function resetForm()
    {
        $this->reset([
            'sede', 'indirizzo', 'citta', 'provincia', 'nazione',
            'cap', 'telefono', 'cellulare', 'fax', 'editingAddressId'
        ]);
        $this->sede = 'principale';
        $this->showForm = false;
    }
    
    public function showCreateForm()
    {
        $this->resetForm();
        $this->showForm = true;
    }
    
    public function editAddress($id)
    {
        $address = Address::where('clienti_id_cliente', $this->entityId)
            ->where('id_indirizzo', $id)
            ->first();
            
        if ($address) {
            $this->editingAddressId = $id;
            $this->sede = $address->sede;
            $this->indirizzo = $address->indirizzo;
            $this->citta = $address->citta;
            $this->provincia = $address->provincia;
            $this->nazione = $address->nazione;
            $this->cap = $address->cap;
            $this->telefono = $address->telefono;
            $this->cellulare = $address->cellulare;
            $this->fax = $address->fax;
            $this->showForm = true;
        }
    }
    
    public function cancelEdit()
    {
        $this->resetForm();
    }
    
    /**
     * Verifica se esiste già un indirizzo duplicato
     */
    private function checkDuplicateAddress($excludeId = null)
    {
        $query = Address::where('clienti_id_cliente', $this->entityId)
            ->where('indirizzo', $this->indirizzo)
            ->where('citta', $this->citta)
            ->where('provincia', $this->provincia);
        
        // Se è in modifica, escludi l'indirizzo corrente
        if ($excludeId) {
            $query->where('id_indirizzo', '!=', $excludeId);
        }
        
        return $query->exists();
    }
    
    public function saveAddress()
    {
        // Validazione base
        $this->validate([
            'indirizzo' => 'nullable|string|max:255',
            'citta' => 'nullable|string|max:100',
            'provincia' => 'nullable|string|max:5',
            'cap' => 'nullable|string|max:10',
        ]);
        
        // Controllo duplicati per nuovo indirizzo
        if (!$this->editingAddressId) {
            if ($this->checkDuplicateAddress()) {
                $this->showNotificationMessage('Esiste già un indirizzo identico per questo cliente/fornitore!', 'error');
                return;
            }
        } else {
            // Controllo duplicati per modifica (escludendo se stesso)
            if ($this->checkDuplicateAddress($this->editingAddressId)) {
                $this->showNotificationMessage('Esiste già un indirizzo identico per questo cliente/fornitore!', 'error');
                return;
            }
        }
        
        $data = [
            'clienti_id_cliente' => $this->entityId,
            'sede' => $this->sede ?: 'principale',
            'indirizzo' => $this->indirizzo,
            'citta' => $this->citta,
            'provincia' => strtoupper($this->provincia),
            'nazione' => $this->nazione ?: 'Italia',
            'cap' => $this->cap,
            'telefono' => $this->telefono,
            'cellulare' => $this->cellulare,
            'fax' => $this->fax,
        ];
        
        try {
            if ($this->editingAddressId) {
                Address::where('clienti_id_cliente', $this->entityId)
                    ->where('id_indirizzo', $this->editingAddressId)
                    ->update($data);
                $message = 'Indirizzo aggiornato con successo!';
            } else {
                Address::create($data);
                $message = 'Indirizzo aggiunto con successo!';
            }
            
            $this->resetForm();
            $this->loadAddresses();
            $this->showNotificationMessage($message, 'success');
            
        } catch (\Exception $e) {
            $this->showNotificationMessage('Errore: ' . $e->getMessage(), 'error');
        }
    }
    
    public function confirmDelete($id)
    {
        $address = Address::where('clienti_id_cliente', $this->entityId)
            ->where('id_indirizzo', $id)
            ->first();
            
        if ($address) {
            $this->addressToDelete = $id;
            // Crea un nome descrittivo per l'indirizzo
            $this->addressToDeleteName = $address->sede ?: 'Indirizzo';
            if ($address->indirizzo) {
                $this->addressToDeleteName .= ' - ' . $address->indirizzo;
            }
            if ($address->citta) {
                $this->addressToDeleteName .= ' (' . $address->citta . ')';
            }
            $this->showDeleteModal = true;
        }
    }
    
    public function deleteAddress()
    {
        try {
            if ($this->addressToDelete) {
                Address::where('clienti_id_cliente', $this->entityId)
                    ->where('id_indirizzo', $this->addressToDelete)
                    ->delete();
                    
                $this->loadAddresses();
                $this->showNotificationMessage('Indirizzo eliminato con successo!', 'success');
            }
            
            $this->showDeleteModal = false;
            $this->addressToDelete = null;
            $this->addressToDeleteName = '';
            
        } catch (\Exception $e) {
            $this->showNotificationMessage('Errore durante l\'eliminazione', 'error');
            $this->showDeleteModal = false;
        }
    }
    
    public function cancelDelete()
    {
        $this->showDeleteModal = false;
        $this->addressToDelete = null;
        $this->addressToDeleteName = '';
    }
    
    public function showNotificationMessage($message, $type = 'success')
    {
        $this->notificationMessage = $message;
        $this->notificationType = $type;
        $this->showNotification = true;
        
        // Dispatch event per nascondere la notifica dopo 3 secondi
        $this->dispatch('hide-notification-after-3-seconds');
    }
    
    public function hideNotification()
    {
        $this->showNotification = false;
        $this->notificationMessage = '';
    }
    
    public function render()
    {
        return view('livewire.admin.address-manager');
    }
}