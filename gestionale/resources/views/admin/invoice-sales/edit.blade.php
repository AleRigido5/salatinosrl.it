@extends('layouts.admin')

@section('title', 'Modifica Fattura di Vendita')

@section('content')
    <div>
        @livewire('admin.invoice-sales-edit', ['invoiceId' => $id])
    </div>
@endsection