@extends('admin.layouts.app')

@section('title', 'Gestione Ruoli')

@section('content')
<div class="p-6">
    @livewire('admin.roles-table')
</div>
@endsection