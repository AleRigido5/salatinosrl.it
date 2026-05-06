@extends('admin.layouts.app')

@section('title', 'Modifica Fattura di Acquisto')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-edit text-yellow-500 mr-2"></i>
            Modifica Fattura di Acquisto
        </h1>
        <div>
            <a href="{{ route('admin.invoices-received.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition">
                <i class="fas fa-arrow-left mr-2"></i> Torna indietro
            </a>
        </div>
    </div>

    @livewire('admin.invoices-received-form', ['mode' => 'edit', 'invoiceId' => $invoice->id])
</div>
@endsection