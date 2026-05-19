@extends('admin.layouts.app')

@section('content')
    @livewire('admin.invoice-received-edit', ['id' => $id])
@endsection