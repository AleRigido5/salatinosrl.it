@extends('admin.layouts.app')

@section('title', 'Modifica Fattura di Vendita')

@section('content')
    <div>
        @livewire('admin.invoice-sent-edit', ['invoiceId' => $id])
    </div>
@endsection