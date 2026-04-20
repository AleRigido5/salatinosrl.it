@extends('admin.layouts.app')

@section('title', 'Gestione Scadenze')

@section('content')
<div class="p-6">
    @livewire('admin.expiration-table', [
        'staffId' => $staffId ?? null,
        'staffName' => $staffName ?? null,
        'entityId' => $entityId ?? null,
        'entityType' => $entityType ?? null,
        'entityName' => $entityName ?? null
    ])
</div>
@endsection