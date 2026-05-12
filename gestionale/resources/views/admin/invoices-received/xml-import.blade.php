@extends('admin.layouts.app')

@section('title', 'Importa Fattura Elettronica')

@section('content')
<div class="p-6">
    <livewire:admin.invoices-xml-import />
</div>
@endsection