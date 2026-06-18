{{-- resources/views/admin/vehicles/calendar.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Calendario Scadenze Veicoli')

@section('content')
<div class="p-6">
    @livewire('admin.vehicle-calendar')
</div>
@endsection