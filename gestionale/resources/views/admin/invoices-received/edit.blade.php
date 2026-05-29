@extends('admin.layouts.app')

@section('title', 'Modifica Fattura')

@section('content')
    @livewire('admin.invoice-received-edit', ['id' => $id])
@endsection