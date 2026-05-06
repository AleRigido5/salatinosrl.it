@extends('admin.layouts.app')

@section('title', 'Fatture di Acquisto')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-file-invoice-dollar text-lime-500 mr-2"></i>
                Fatture di Acquisto
            </h1>
            <p class="text-gray-500 mt-1">Gestione fatture ricevute dai fornitori</p>
        </div>
        <div class="flex gap-3">
            @if(auth()->guard('admin')->user()->hasPermission('create_purchases'))
            <a href="{{ route('admin.invoices-received.create') }}" 
               class="bg-lime-500 hover:bg-lime-600 text-white px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
                <i class="fas fa-plus"></i>
                <span>Nuova Fattura</span>
            </a>
            @endif
        </div>
    </div>

    <!-- Livewire Component -->
    @livewire('admin.invoices-received-table')
    
    <!-- Statistiche -->
    <div class="mt-6 bg-white rounded-lg shadow p-4">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Statistiche Rapide</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @php
                $totalInvoices = \App\Models\InvoiceReceived::count();
                $totalAmount = \App\Models\InvoiceReceived::sum('importo_totale');
                $draftCount = \App\Models\InvoiceReceived::where('status', 'draft')->count();
            @endphp
            <div class="bg-gray-50 rounded-lg p-3 text-center">
                <p class="text-2xl font-bold text-gray-800">{{ $totalInvoices }}</p>
                <p class="text-xs text-gray-500">Totale Fatture</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3 text-center">
                <p class="text-2xl font-bold text-green-600">€ {{ number_format($totalAmount, 2) }}</p>
                <p class="text-xs text-gray-500">Importo Totale</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3 text-center">
                <p class="text-2xl font-bold text-orange-600">{{ $draftCount }}</p>
                <p class="text-xs text-gray-500">Bozze in sospeso</p>
            </div>
        </div>
    </div>
</div>
@endsection