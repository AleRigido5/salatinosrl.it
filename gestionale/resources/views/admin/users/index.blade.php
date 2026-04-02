@extends('admin.layouts.app')

@section('title', 'Gestione Utenti')

@section('content')
    {{-- Chiamata al componente Livewire --}}
    <livewire:admin.user-table />
@endsection