@extends('layouts.admin')

@section('title', 'Dettaglio Fattura di Vendita')

@section('content')
    <div>
        @livewire('admin.invoice-sent-show', ['invoiceId' => $id])
    </div>
@endsection