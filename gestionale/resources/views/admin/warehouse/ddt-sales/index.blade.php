@extends('admin.layouts.app')

@section('title', 'DDT di Vendita')

@section('content')
    @livewire('admin.warehouse-ddt-table', ['type' => 'vendita'])
@endsection