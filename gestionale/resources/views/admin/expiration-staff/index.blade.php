@extends('admin.layouts.app')

@section('title', 'Gestione Scadenze Personale')

@section('content')
<div class="p-6">
    @livewire('admin.staff-expiration-table', [
        'staffId' => $staffId ?? null,
        'staffName' => $staffName ?? null
    ])
</div>
@endsection