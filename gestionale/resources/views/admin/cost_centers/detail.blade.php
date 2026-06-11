@extends('admin.layouts.app')

@section('title', 'Dettaglio Centro di Costo')

@section('content')
    @livewire('admin.cost-center-detail-table', ['costCenterId' => $costCenterId])
@endsection