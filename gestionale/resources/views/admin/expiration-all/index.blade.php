@extends('admin.layouts.app')

@section('title', 'Tutte le Scadenze')

@section('content')
<div class="p-6">
    @livewire('admin.expiration-all-table')
</div>
@endsection