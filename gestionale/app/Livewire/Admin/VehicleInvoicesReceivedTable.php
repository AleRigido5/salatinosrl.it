<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Vehicles;
use App\Models\InvoiceReceived;
use App\Models\InvoiceRow;

class VehicleInvoicesReceivedTable extends Component
{
    public $vehicleId;
    public $vehicleName;

    public function mount($vehicleId)
    {
        $this->vehicleId = $vehicleId;

        $vehicle = Vehicles::find($vehicleId);
        $this->vehicleName = $vehicle ? $vehicle->full_name : 'Mezzo';
    }

    public function getInvoicesProperty()
    {
        $documentIds = InvoiceRow::where('id_vehicle', $this->vehicleId)
            ->where('document_type', 'invoice_received')
            ->pluck('document_id')
            ->unique();

        return InvoiceReceived::whereIn('id', $documentIds)
            ->with([
                'ownership',
                'entity',
                'rows' => function ($q) {
                    $q->where('id_vehicle', $this->vehicleId);
                },
            ])
            ->orderBy('data_invoice', 'desc')
            ->get();
    }

    public function backToVehicles()
    {
        return redirect()->route('admin.vehicles.index');
    }

    public function render()
    {
        return view('livewire.admin.vehicles.vehicle-invoices-received-table', [
            'invoices' => $this->invoices,
        ]);
    }
}