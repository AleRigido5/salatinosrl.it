@extends('layouts.admin')

@section('title', 'Dettaglio Fattura di Vendita')

@section('content')
    <div>
        @livewire('admin.invoice-sales-show', ['invoiceId' => $id])
    </div>
@endsection