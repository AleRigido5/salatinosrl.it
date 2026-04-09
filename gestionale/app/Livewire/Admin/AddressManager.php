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
    
    protected $listeners = ['refreshAddresses' => 'loadAddresses', 'addressSaved' => 'handleAddressSaved'];
    
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
    
    public function saveAddress()
    {
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
        
        if ($this->editingAddressId) {
            // Update
            Address::where('clienti_id_cliente', $this->entityId)
                ->where('id_indirizzo', $this->editingAddressId)
                ->update($data);
            $message = 'Indirizzo aggiornato con successo!';
        } else {
            // Create
            Address::create($data);
            $message = 'Indirizzo aggiunto con successo!';
        }
        
        $this->resetForm();
        $this->loadAddresses();
        
        // Invia notifica di successo
        $this->dispatch('address-saved', $message);
    }
    
    public function deleteAddress($id)
    {
        Address::where('clienti_id_cliente', $this->entityId)
            ->where('id_indirizzo', $id)
            ->delete();
            
        $this->loadAddresses();
        $this->dispatch('address-deleted', 'Indirizzo eliminato con successo!');
    }
    
    public function handleAddressSaved($message)
    {
        // Questo metodo viene chiamato quando un indirizzo viene salvato
        // Puoi aggiungere qui eventuali azioni aggiuntive
    }
    
    public function render()
    {
        return view('livewire.admin.address-manager');
    }
}