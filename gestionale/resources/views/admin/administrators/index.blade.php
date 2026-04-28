@extends('admin.layouts.app')

@section('title', 'Gestione Amministratori')

@section('content')
<div class="p-6">
    @livewire('admin.administrator-table')
</div>
@endsection