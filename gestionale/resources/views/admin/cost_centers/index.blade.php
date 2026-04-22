@extends('admin.layouts.app')

@section('title', 'Centri di Costo')

@section('content')
<div class="p-6">
    @livewire('admin.cost-centers-table')
</div>
@endsection