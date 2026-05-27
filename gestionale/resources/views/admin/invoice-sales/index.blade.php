@extends('admin.layouts.app')

@section('title', 'Fatture di Vendita')

@section('content')
    <div>
        @livewire('admin.invoice-sales-table')
    </div>
@endsection