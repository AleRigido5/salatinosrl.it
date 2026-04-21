@extends('admin.layouts.app')

@section('title', 'Gestione Scadenze Mezzi')

@section('content')
<div class="p-6">
    @livewire('admin.vehicle-expiration-table', [
        'vehicleId' => $vehicleId ?? null,
        'vehicleName' => $vehicleName ?? null
    ])
</div>
@endsection