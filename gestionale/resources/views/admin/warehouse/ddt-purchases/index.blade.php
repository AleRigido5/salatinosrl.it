@extends('admin.layouts.app')

@section('title', 'DDT di Acquisto')

@section('content')
    @livewire('admin.warehouse-ddt-table', ['type' => 'acquisto'])
@endsection