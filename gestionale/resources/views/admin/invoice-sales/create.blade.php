@extends('admin.layouts.app')

@section('title', 'Nuova Fattura di Vendita')

@section('content')
    <div>
        @livewire('admin.invoice-sales-create')
    </div>
@endsection