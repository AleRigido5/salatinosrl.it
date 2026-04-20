@extends('admin.layouts.app')

@section('title', 'Gestione Mezzi')

@section('content')
<div class="container mx-auto px-4 py-6">
    @livewire('admin.vehicles-table')
</div>
@endsection