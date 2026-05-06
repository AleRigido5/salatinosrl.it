@extends('admin.layouts.app')

@section('title', 'Nuova Fattura di Acquisto')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-plus-circle text-lime-500 mr-2"></i>
            Nuova Fattura di Acquisto
        </h1>
        <div>
            <a href="{{ route('admin.invoices-received.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition">
                <i class="fas fa-arrow-left mr-2"></i> Torna indietro
            </a>
        </div>
    </div>

    <!-- Componente per upload XML -->
    @livewire('admin.invoice-xml-upload')
</div>
@endsection