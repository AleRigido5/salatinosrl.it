@extends('admin.layouts.app')

@section('title', 'Fatture di Acquisto - Mezzo')

@section('content')
    @livewire('admin.vehicle-invoices-received-table', ['vehicleId' => $vehicle->id])
@endsection