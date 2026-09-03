@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="p-6">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800">
            Ciao, {{ $admin->name }}!
        </h1>
        <p class="text-gray-600 mt-1">Ecco cosa sta succedendo oggi nel tuo pannello di controllo.</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 p-6 border-l-4 border-emerald-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Totale Admin</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['total_admins']) }}</p>
                    <div class="flex items-center mt-2">
                        <span class="text-xs text-emerald-600 bg-emerald-100 px-2 py-0.5 rounded-full">
                            <i class="fas fa-chart-line mr-1"></i> +12%
                        </span>
                    </div>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-emerald-400 to-green-500 rounded-xl flex items-center justify-center shadow-md">
                    <i class="fas fa-users text-white text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Admin Attivi</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['active_admins']) }}</p>
                    <div class="flex items-center mt-2">
                        <span class="text-xs text-green-600 bg-green-100 px-2 py-0.5 rounded-full">
                            <i class="fas fa-check-circle mr-1"></i> Attivi
                        </span>
                    </div>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl flex items-center justify-center shadow-md">
                    <i class="fas fa-user-check text-white text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 p-6 border-l-4 border-lime-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Totale Clienti</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['total_entities'] ?? 0) }}</p>
                    <div class="flex items-center mt-2">
                        <span class="text-xs text-lime-600 bg-lime-100 px-2 py-0.5 rounded-full">
                            <i class="fas fa-users mr-1"></i> Registrati
                        </span>
                    </div>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-lime-400 to-green-500 rounded-xl flex items-center justify-center shadow-md">
                    <i class="fas fa-building text-white text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 p-6 border-l-4 border-teal-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Totale Ruoli</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['total_roles']) }}</p>
                    <div class="flex items-center mt-2">
                        <span class="text-xs text-teal-600 bg-teal-100 px-2 py-0.5 rounded-full">
                            <i class="fas fa-tags mr-1"></i> Configurati
                        </span>
                    </div>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-teal-400 to-emerald-500 rounded-xl flex items-center justify-center shadow-md">
                    <i class="fas fa-shield-alt text-white text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Ultimi Pagamenti + Ultime Comunicazioni -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

        <!-- Ultimi Pagamenti (incassi) -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-emerald-100 flex flex-col">
            <div class="bg-gradient-to-r from-emerald-50 to-green-50 px-6 py-4 border-b border-emerald-100">
                <h3 class="text-lg font-semibold text-emerald-800">
                    <i class="fas fa-euro-sign mr-2"></i> Ultimi Pagamenti Registrati
                </h3>
            </div>
            <div class="p-6 flex-1">
                <div class="space-y-3">
                    @forelse($recentPayments as $payment)
                    <div class="flex items-center justify-between py-2.5 hover:bg-emerald-50/30 px-3 rounded-lg transition-all duration-200">
                        <div class="flex items-center space-x-3 min-w-0">
                            <div class="w-9 h-9 bg-gradient-to-br from-emerald-400 to-green-500 rounded-full flex items-center justify-center shadow-md shrink-0">
                                <i class="fas fa-arrow-down text-white text-sm"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-800 truncate max-w-[220px]" title="{{ $payment->description }}">
                                    {{ $payment->entity_name ?? Str::limit($payment->description, 30) }}
                                </p>
                                <p class="text-xs text-gray-500 truncate max-w-[220px]">
                                    {{ $payment->paymentMethod->name ?? 'Metodo non specificato' }}
                                    @if($payment->bankAccount) — {{ $payment->bankAccount->name }} @endif
                                </p>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="font-bold text-emerald-600">{{ number_format($payment->amount, 2, ',', '.') }} €</p>
                            <p class="text-xs text-gray-400">{{ $payment->entry_date->format('d/m/Y') }}</p>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8">
                        <i class="fas fa-inbox text-4xl text-gray-300 mb-2"></i>
                        <p class="text-gray-500">Nessun pagamento registrato</p>
                    </div>
                    @endforelse
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                <a href="{{ route('admin.accounting-entries.index') }}"
                   class="w-full inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-lg font-medium transition-colors">
                    <i class="fas fa-book"></i> Vai a Prima Nota
                </a>
            </div>
        </div>

        <!-- Ultime Comunicazioni -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-blue-100 flex flex-col">
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-blue-100">
                <h3 class="text-lg font-semibold text-blue-800">
                    <i class="fas fa-comments mr-2"></i> Ultime Comunicazioni
                </h3>
            </div>
            <div class="p-6 flex-1">
                <div class="space-y-3">
                    @forelse($recentCommunications as $comm)
                    <div class="flex items-center justify-between py-2.5 hover:bg-blue-50/30 px-3 rounded-lg transition-all duration-200">
                        <div class="flex items-center space-x-3 min-w-0">
                            <div class="w-9 h-9 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-full flex items-center justify-center shadow-md shrink-0">
                                <i class="fas fa-envelope text-white text-sm"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-800 truncate max-w-[220px]">
                                    {{ optional($comm->entity)->full_name ?? 'Comunicazione generica' }}
                                </p>
                                <p class="text-xs text-gray-500 truncate max-w-[260px]" title="{{ $comm->testo }}">
                                    {{ Str::limit(strip_tags($comm->testo ?? ''), 60) }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-xs text-gray-400">
                                {{ $comm->data?->format('d/m/Y') ?? '-' }}
                            </p>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8">
                        <i class="fas fa-comment-slash text-4xl text-gray-300 mb-2"></i>
                        <p class="text-gray-500">Nessuna comunicazione registrata</p>
                    </div>
                    @endforelse
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                <a href="{{ route('admin.communications.index') }}"
                   class="w-full inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg font-medium transition-colors">
                    <i class="fas fa-comments"></i> Vai a Comunicazioni
                </a>
            </div>
        </div>

    </div>

    @php
        $mesi = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
    @endphp

    <!-- Grafici Agricola Salatino SRL -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-lime-100 mb-8">
        <div class="bg-gradient-to-r from-lime-50 to-green-50 px-6 py-4 border-b border-lime-100">
            <h3 class="text-lg font-semibold text-lime-800">
                <i class="fas fa-seedling mr-2"></i>
                {{ $agricolaOwnership->RagAbbrev ?? $agricolaOwnership->Rag_Soc_intest ?? 'Agricola Salatino SRL' }}
                <span class="text-sm font-normal text-gray-500">— {{ $year }}</span>
            </h3>
        </div>
        <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <p class="text-sm font-medium text-gray-600 mb-3">
                    <i class="fas fa-calendar text-lime-500 mr-1"></i> Fatturato Mensile
                </p>
                <canvas id="chartAgricolaSales" height="220"></canvas>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600 mb-3">
                    <i class="fas fa-calendar text-orange-500 mr-1"></i> Fatture di Acquisto Mensili
                </p>
                <canvas id="chartAgricolaPurchases" height="220"></canvas>
            </div>
        </div>
    </div>

    <!-- Grafici Vitivinicola Salatino SS -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-purple-100 mb-8">
        <div class="bg-gradient-to-r from-purple-50 to-indigo-50 px-6 py-4 border-b border-purple-100">
            <h3 class="text-lg font-semibold text-purple-800">
                <i class="fas fa-wine-bottle mr-2"></i>
                {{ $vitivinicolaOwnership->RagAbbrev ?? $vitivinicolaOwnership->Rag_Soc_intest ?? 'Vitivinicola Salatino SS' }}
                <span class="text-sm font-normal text-gray-500">— {{ $year }}</span>
            </h3>
            @if(!$vitivinicolaOwnership)
            <p class="text-xs text-gray-400 mt-1">Proprietà non ancora trovata in anagrafica — i grafici sotto sono vuoti.</p>
            @endif
        </div>
        <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <p class="text-sm font-medium text-gray-600 mb-3">
                    <i class="fas fa-calendar text-purple-500 mr-1"></i> Fatturato Mensile
                </p>
                <canvas id="chartVitivinicolaSales" height="220"></canvas>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600 mb-3">
                    <i class="fas fa-calendar text-orange-500 mr-1"></i> Fatture di Acquisto Mensili
                </p>
                <canvas id="chartVitivinicolaPurchases" height="220"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Entities -->
    {{-- <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-emerald-100">
        <div class="bg-gradient-to-r from-emerald-50 to-green-50 px-6 py-4 border-b border-emerald-100">
            <h3 class="text-lg font-semibold text-emerald-800">
                <i class="fas fa-history mr-2"></i> Ultimi Clienti / Fornitori Registrati
            </h3>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                @forelse($recentEntities ?? [] as $entityItem)
                <div class="flex items-center justify-between py-3 hover:bg-emerald-50/30 px-3 rounded-lg transition-all duration-200">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-green-500 rounded-full flex items-center justify-center shadow-md">
                            <i class="fas {{ $entityItem->entity_type == 'cliente' ? 'fa-user-tie' : 'fa-truck' }} text-white"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800">{{ $entityItem->full_name }}</p>
                            <p class="text-sm text-gray-500">
                                <i class="fas fa-envelope mr-1 text-gray-400"></i> {{ $entityItem->email ?? 'Nessuna email' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full 
                            @if($entityItem->entity_type == 'cliente') bg-emerald-100 text-emerald-700
                            @elseif($entityItem->entity_type == 'fornitore') bg-blue-100 text-blue-700
                            @else bg-purple-100 text-purple-700
                            @endif">
                            <i class="fas 
                                @if($entityItem->entity_type == 'cliente') fa-user-tie
                                @elseif($entityItem->entity_type == 'fornitore') fa-truck
                                @else fa-handshake
                                @endif mr-1"></i>
                            {{ $entityTypes[$entityItem->entity_type] ?? $entityItem->entity_type }}
                        </span>
                        <span class="text-sm text-gray-400">
                            <i class="far fa-clock mr-1"></i> {{ $entityItem->created_at ? $entityItem->created_at->diffForHumans() : ($entityItem->data_inserimento ? date('d/m/Y', strtotime($entityItem->data_inserimento)) : '-') }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="text-center py-8">
                    <i class="fas fa-inbox text-4xl text-gray-300 mb-2"></i>
                    <p class="text-gray-500">Nessun cliente/fornitore trovato</p>
                </div>
                @endforelse
            </div>
        </div>
    </div> --}}
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-datalabels/2.2.0/chartjs-plugin-datalabels.min.js"></script>
<script>
    const monthLabels = @json($mesi);

    function formatEuro(value) {
        return new Intl.NumberFormat('it-IT', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(value) + ' €';
    }

    function buildBarChart(canvasId, data, colorFrom, colorTo, labelColor) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return;

        const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 220);
        gradient.addColorStop(0, colorFrom);
        gradient.addColorStop(1, colorTo);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: monthLabels,
                datasets: [{
                    data: data,
                    backgroundColor: gradient,
                    borderRadius: 6,
                    maxBarThickness: 42,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        color: labelColor,
                        font: { size: 10, weight: 'bold' },
                        formatter: (value) => value > 0 ? formatEuro(value) : '',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        display: false,
                        grace: '15%',
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 }, color: '#6b7280' }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        buildBarChart('chartAgricolaSales', @json($agricolaSalesMonthly), '#a3e635', '#65a30d', '#4d7c0f');
        buildBarChart('chartAgricolaPurchases', @json($agricolaPurchasesMonthly), '#fdba74', '#ea580c', '#c2410c');
        buildBarChart('chartVitivinicolaSales', @json($vitivinicolaSalesMonthly), '#c4b5fd', '#7c3aed', '#5b21b6');
        buildBarChart('chartVitivinicolaPurchases', @json($vitivinicolaPurchasesMonthly), '#fdba74', '#ea580c', '#c2410c');
    });
</script>
@endpush
@endsection