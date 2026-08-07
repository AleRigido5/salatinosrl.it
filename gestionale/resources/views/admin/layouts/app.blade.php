<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Panel - @yield('title')</title>
    <link rel="shortcut icon" href="{{ asset('../../../../public/images/logo.png') }}" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Livewire Scripts -->
    @livewireStyles
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        /* Scrollbar personalizzata */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb {
            background: #84cc16;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #65a30d;
        }
        
        /* Link disabilitati */
        .disabled-link {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        /* Tooltip */
        [data-tooltip] {
            position: relative;
            cursor: help;
        }
        [data-tooltip]:before {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #1f2937;
            color: white;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            white-space: nowrap;
            display: none;
            z-index: 1000;
            font-weight: normal;
            letter-spacing: 0.3px;
        }
        [data-tooltip]:after {
            content: '';
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: #1f2937;
            display: none;
        }
        [data-tooltip]:hover:before,
        [data-tooltip]:hover:after {
            display: block;
        }
        
        /* Dropdown Menu */
        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 0.5rem;
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            min-width: 260px;
            z-index: 50;
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }
        .dropdown-menu.show {
            display: block;
            animation: fadeIn 0.2s ease-out;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .dropdown-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #374151;
            transition: all 0.2s;
            border-left: 3px solid transparent;
            font-size: 0.875rem;
        }
        .dropdown-item:hover {
            background-color: #fefce8;
            border-left-color: #84cc16;
        }
        .dropdown-item:hover i {
            color: #84cc16;
        }
        .dropdown-item i {
            width: 1.25rem;
            margin-right: 0.75rem;
            color: #6b7280;
            font-size: 1rem;
        }
        .dropdown-divider {
            height: 1px;
            background: linear-gradient(to right, #e5e7eb, #84cc16, #e5e7eb);
            margin: 0.25rem 0;
        }
        .dropdown-header {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(135deg, #fefce8 0%, #fef9c3 100%);
        }
        
        /* Transizioni sidebar */
        .sidebar-link {
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        .sidebar-link:hover {
            padding-left: 1.75rem;
        }
        
        /* Sidebar transitions - Hover effect */
        .sidebar {
            transition: width 0.3s ease;
            position: relative;
            z-index: 40;
        }
        
        /* Sidebar chiusa di default (80px) */
        .sidebar-closed {
            width: 80px;
        }
        
        /* Sidebar aperta al hover (264px) */
        .sidebar:hover {
            width: 264px !important;
        }
        
        /* Testi nascosti quando sidebar è chiusa */
        .sidebar:not(:hover) .sidebar-link-text {
            opacity: 0;
            width: 0;
            display: none;
        }
        
        .sidebar:not(:hover) .sidebar-link i {
            margin-right: 0;
        }
        
        .sidebar:not(:hover) .sidebar-link {
            justify-content: center;
        }
        
        .sidebar:not(:hover) .logo-text {
            display: none;
        }
        
        .sidebar:not(:hover) .logo-container {
            justify-content: center;
        }
        
        .sidebar:not(:hover) .nav-label span {
            display: none;
        }
        
        .sidebar:not(:hover) .nav-label {
            justify-content: center;
        }
        
        .sidebar:not(:hover) .footer-text {
            display: none;
        }
        
        /* Quando sidebar è hover, mostra tutto */
        .sidebar:hover .sidebar-link-text {
            display: inline-block !important;
            opacity: 1 !important;
        }
        
        .sidebar:hover .sidebar-link i {
            margin-right: 0.75rem !important;
        }
        
        .sidebar:hover .sidebar-link {
            justify-content: flex-start !important;
        }
        
        .sidebar:hover .logo-text {
            display: block !important;
        }
        
        .sidebar:hover .logo-container {
            justify-content: flex-start !important;
        }
        
        .sidebar:hover .nav-label span {
            display: inline !important;
        }
        
        .sidebar:hover .nav-label {
            justify-content: flex-start !important;
        }
        
        .sidebar:hover .footer-text {
            display: block !important;
        }
        
        /* Badge personalizzati */
        .badge-success {
            background: linear-gradient(135deg, #84cc16, #65a30d);
            color: white;
        }
        .badge-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }
        .badge-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        .badge-info {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }
        
        /* Pulsante impostazioni nella navbar */
        .settings-btn {
            transition: all 0.2s ease;
        }
        .settings-btn:hover {
            transform: rotate(30deg);
        }
        
        /* Tab styling */
        .tab-container {
            border-bottom: 2px solid #e5e7eb;
        }
        .tab-button {
            transition: all 0.2s ease;
            position: relative;
        }
        .tab-button.active {
            color: #84cc16;
            border-bottom-color: #84cc16;
        }
        .tab-button.active i {
            color: #84cc16;
        }
        .tab-button:hover:not(.active) {
            color: #65a30d;
            border-bottom-color: #d9f99d;
        }
        .tab-content {
            display: none;
            animation: fadeInTab 0.3s ease;
        }
        .tab-content.active {
            display: block;
        }
        @keyframes fadeInTab {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Toast animation */
        .toast-notification {
            animation: slideInRight 0.3s ease-out;
        }
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* Breadcrumb styling */
        .breadcrumb-item {
            display: inline-flex;
            align-items: center;
        }
        .breadcrumb-item:not(:last-child):after {
            content: '›';
            margin: 0 0.5rem;
            color: #9ca3af;
            font-size: 1.1rem;
        }
        .breadcrumb-link {
            transition: color 0.2s ease;
        }
        .breadcrumb-link:hover {
            color: #84cc16;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100">
    @php
        $currentAdmin = Auth::guard('admin')->user();
    @endphp
    
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar - si apre al hover, si chiude quando il mouse esce -->
        <aside id="sidebar" class="sidebar sidebar-closed bg-gradient-to-b from-gray-900 via-gray-800 to-gray-900 text-white flex flex-col shadow-xl transition-all duration-300" style="width: 80px;">
            <!-- Logo -->
            <div class="p-6 border-b border-gray-700/50">
                <div class="logo-container flex items-center space-x-3">
                    <!-- Logo Image -->
                    <div class="w-10 h-10 flex-shrink-0">
                        <img src="{{ asset('../../../../public/images/logo.png') }}" alt="Logo" class="w-full h-full object-contain">
                    </div>
                    <div class="logo-text">
                        <h1 class="text-xl font-bold bg-gradient-to-r from-lime-400 to-lime-600 bg-clip-text text-transparent">Gruppo Salatino</h1>
                    </div>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 mt-6 overflow-y-auto px-3">
                <!-- Dashboard -->
                @if($currentAdmin && $currentAdmin->hasPermission('access_dashboard'))
                <div>
                    <a href="{{ route('admin.dashboard') }}" 
                    class="sidebar-link flex items-center px-4 py-2.5 rounded-lg hover:bg-gray-700/50 transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-gray-700/70 text-lime-400 border-r-2 border-lime-500' : 'text-gray-300' }} mb-1">
                        <i class="fas fa-tachometer-alt w-5 h-5 {{ request()->routeIs('admin.dashboard') ? 'text-lime-400' : 'text-gray-500' }}"></i>
                        <span class="sidebar-link-text text-sm font-medium ml-3">Dashboard</span>
                    </a>
                </div>
                @endif
                
                <!-- Anagrafica -->
                @if($currentAdmin && ($currentAdmin->hasPermission('view_entities') || $currentAdmin->hasPermission('view_staff') || $currentAdmin->hasPermission('view_vehicles') || $currentAdmin->hasPermission('view_cost_centers') || $currentAdmin->hasPermission('view_activities')))
                <div class="mb-6">
                    <!-- Attività -->
                    @if($currentAdmin && $currentAdmin->hasPermission('view_activities'))
                    <a href="{{ route('admin.activities.index') }}" 
                    class="sidebar-link flex items-center px-4 py-2.5 rounded-lg hover:bg-gray-700/50 transition-all duration-200 {{ request()->routeIs('admin.activities.*') ? 'bg-gray-700/70 text-lime-400 border-r-2 border-lime-500' : 'text-gray-300' }} mb-1">
                        <i class="fa-solid fa-person-digging w-5 h-5 {{ request()->routeIs('admin.activities.*') ? 'text-lime-400' : 'text-gray-500' }}"></i>
                        <span class="sidebar-link-text text-sm font-medium ml-3">Attività</span>
                    </a>
                    @endif

                    <!-- Clienti / Fornitori con Dropdown -->
                    @if($currentAdmin && $currentAdmin->hasPermission('view_entities'))
                    <div x-data="{ 
                        openEntities: false,
                        init() {
                            window.addEventListener('sidebar-closed', () => {
                                this.openEntities = false;
                            });
                        }
                    }">
                        <a href="#" 
                        @click.prevent="$store.sidebar.isExpanded ? openEntities = !openEntities : null"
                        class="sidebar-link flex items-center justify-between px-4 py-2.5 rounded-lg hover:bg-gray-700/50 transition-all duration-200 {{ request()->routeIs('admin.entities.*') || request()->routeIs('admin.communications.*') ? 'bg-gray-700/70 text-lime-400 border-r-2 border-lime-500' : 'text-gray-300' }} mb-1">
                            <div class="flex items-center">
                                <i class="fas fa-building w-5 h-5 {{ request()->routeIs('admin.entities.*') || request()->routeIs('admin.communications.*') ? 'text-lime-400' : 'text-gray-500' }}"></i>
                                <span class="sidebar-link-text text-sm font-medium ml-3">Clienti / Fornitori</span>
                            </div>
                            <i class="fas fa-chevron-down text-xs transition-transform duration-200 ml-4 mr-1" 
                            :class="{ 'rotate-180': openEntities }" 
                            x-show="$store.sidebar.isExpanded"
                            style="min-width: 12px;"></i>
                        </a>
                        
                        <!-- Submenu Dropdown -->
                        <div x-show="openEntities && $store.sidebar.isExpanded" 
                            x-transition:enter="transition ease-out duration-200" 
                            x-transition:enter-start="opacity-0 transform -translate-y-2" 
                            x-transition:enter-end="opacity-100 transform translate-y-0"
                            x-transition:leave="transition ease-in duration-100" 
                            x-transition:leave-start="opacity-100 transform translate-y-0" 
                            x-transition:leave-end="opacity-0 transform -translate-y-2"
                            class="ml-6 mt-1 space-y-1">
                            
                            <!-- Elenco -->
                            <a href="{{ route('admin.entities.index') }}" 
                            class="flex items-center px-4 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('admin.entities.*') && !request()->routeIs('admin.communications.*') ? 'bg-gray-700/50 text-lime-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-700/30' }}">
                                <i class="fas fa-list-ul w-4 h-4 mr-2"></i>
                                <span>Elenco</span>
                            </a>

                            <!-- Comunicazioni (globali) -->
                            <a href="{{ route('admin.communications.index') }}" 
                            class="flex items-center px-4 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('admin.communications.*') ? 'bg-gray-700/50 text-lime-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-700/30' }}">
                                <i class="fas fa-envelope w-4 h-4 mr-2"></i>
                                <span>Comunicazioni</span>
                            </a>
                        </div>
                    </div>
                    @endif
                    
                    <!-- Personale con Dropdown -->
                    @if($currentAdmin && $currentAdmin->hasPermission('view_staff'))
                    <div x-data="{ 
                        openPersonale: false,
                        init() {
                            window.addEventListener('sidebar-closed', () => {
                                this.openPersonale = false;
                            });
                        }
                    }">
                        <a href="#" 
                        @click.prevent="$store.sidebar.isExpanded ? openPersonale = !openPersonale : null"
                        class="sidebar-link flex items-center justify-between px-4 py-2.5 rounded-lg hover:bg-gray-700/50 transition-all duration-200 {{ request()->routeIs('admin.staff.*') || request()->routeIs('admin.expiration-staff.*') ? 'bg-gray-700/70 text-lime-400 border-r-2 border-lime-500' : 'text-gray-300' }} mb-1">
                            <div class="flex items-center">
                                <i class="fas fa-users w-5 h-5 {{ request()->routeIs('admin.staff.*') || request()->routeIs('admin.expiration-staff.*') ? 'text-lime-400' : 'text-gray-500' }}"></i>
                                <span class="sidebar-link-text text-sm font-medium ml-3">Personale</span>
                            </div>
                            <i class="fas fa-chevron-down text-xs transition-transform duration-200 ml-4 mr-1" 
                            :class="{ 'rotate-180': openPersonale }" 
                            x-show="$store.sidebar.isExpanded"
                            style="min-width: 12px;"></i>
                        </a>
                        
                        <!-- Submenu Dropdown -->
                        <div x-show="openPersonale && $store.sidebar.isExpanded" 
                            x-transition:enter="transition ease-out duration-200" 
                            x-transition:enter-start="opacity-0 transform -translate-y-2" 
                            x-transition:enter-end="opacity-100 transform translate-y-0"
                            x-transition:leave="transition ease-in duration-100" 
                            x-transition:leave-start="opacity-100 transform translate-y-0" 
                            x-transition:leave-end="opacity-0 transform -translate-y-2"
                            class="ml-6 mt-1 space-y-1">
                            
                            <!-- Elenco Dipendenti -->
                            <a href="{{ route('admin.staff.index') }}" 
                            class="flex items-center px-4 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('admin.staff.index') ? 'bg-gray-700/50 text-lime-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-700/30' }}">
                                <i class="fas fa-list-ul w-4 h-4 mr-2"></i>
                                <span>Elenco Dipendenti</span>
                            </a>
                            
                            <!-- Gestione Scadenze -->
                            <a href="{{ route('admin.expiration-staff.index') }}" 
                            class="flex items-center px-4 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('admin.expiration-staff.*') ? 'bg-gray-700/50 text-lime-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-700/30' }}">
                                <i class="fas fa-calendar-alt w-4 h-4 mr-2"></i>
                                <span>Gestione Scadenze</span>
                            </a>
                            
                            <!-- Gestione Presenze (NEW) -->
                            <a href="{{ route('admin.staff.attendance.index') }}" 
                            class="flex items-center px-4 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('admin.staff.attendance.*') ? 'bg-gray-700/50 text-lime-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-700/30' }}">
                                <i class="fas fa-clock w-4 h-4 mr-2"></i>
                                <span>Gestione Presenze</span>
                            </a>
                        </div>
                    </div>
                    @endif
                    
                    <!-- Mezzi con Dropdown -->
                    @if($currentAdmin && $currentAdmin->hasPermission('view_vehicles'))
                    <div x-data="{ 
                        openVeicoli: false,
                        init() {
                            window.addEventListener('sidebar-closed', () => {
                                this.openVeicoli = false;
                            });
                        }
                    }">
                        <a href="#" 
                        @click.prevent="$store.sidebar.isExpanded ? openVeicoli = !openVeicoli : null"
                        class="sidebar-link flex items-center justify-between px-4 py-2.5 rounded-lg hover:bg-gray-700/50 transition-all duration-200 {{ request()->routeIs('admin.vehicles.*') || request()->routeIs('admin.expiration-vehicle.*') ? 'bg-gray-700/70 text-lime-400 border-r-2 border-lime-500' : 'text-gray-300' }} mb-1">
                            <div class="flex items-center">
                                <i class="fas fa-truck w-5 h-5 {{ request()->routeIs('admin.vehicles.*') || request()->routeIs('admin.expiration-vehicle.*') ? 'text-lime-400' : 'text-gray-500' }}"></i>
                                <span class="sidebar-link-text text-sm font-medium ml-3">Mezzi</span>
                            </div>
                            <i class="fas fa-chevron-down text-xs transition-transform duration-200 ml-4 mr-1" 
                            :class="{ 'rotate-180': openVeicoli }" 
                            x-show="$store.sidebar.isExpanded"
                            style="min-width: 12px;"></i>
                        </a>
                        
                        <!-- Submenu Dropdown -->
                        <div x-show="openVeicoli && $store.sidebar.isExpanded" 
                            x-transition:enter="transition ease-out duration-200" 
                            x-transition:enter-start="opacity-0 transform -translate-y-2" 
                            x-transition:enter-end="opacity-100 transform translate-y-0"
                            x-transition:leave="transition ease-in duration-100" 
                            x-transition:leave-start="opacity-100 transform translate-y-0" 
                            x-transition:leave-end="opacity-0 transform -translate-y-2"
                            class="ml-6 mt-1 space-y-1">
                            
                            <!-- Elenco Mezzi -->
                            <a href="{{ route('admin.vehicles.index') }}" 
                            class="flex items-center px-4 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('admin.vehicles.index') ? 'bg-gray-700/50 text-lime-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-700/30' }}">
                                <i class="fas fa-list-ul w-4 h-4 mr-2"></i>
                                <span>Elenco Mezzi</span>
                            </a>

                            <!-- Gestione Scadenze -->
                            <a href="{{ route('admin.vehicles.calendar.index') }}" 
                            class="flex items-center px-4 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('admin.vehicles.calendar.*') ? 'bg-gray-700/50 text-lime-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-700/30' }}">
                                <i class="fas fa-calendar-alt w-4 h-4 mr-2"></i>
                                <span>Gestione Scadenze</span>
                            </a>
                        </div>
                    </div>
                    @endif

                    <!-- Centri di Costo -->
                    @if($currentAdmin && $currentAdmin->hasPermission('view_cost_centers'))
                    <a href="{{ route('admin.cost_centers.index') }}" 
                    class="sidebar-link flex items-center px-4 py-2.5 rounded-lg hover:bg-gray-700/50 transition-all duration-200 {{ request()->routeIs('admin.cost_centers.*') ? 'bg-gray-700/70 text-lime-400 border-r-2 border-lime-500' : 'text-gray-300' }} mb-1">
                        <i class="fa-solid fa-scale-unbalanced w-5 h-5 {{ request()->routeIs('admin.cost_centers.*') ? 'text-lime-400' : 'text-gray-500' }}"></i>
                        <span class="sidebar-link-text text-sm font-medium ml-3">Centri di Costo</span>
                    </a>
                    @endif

                    <!-- Acquisti -->
                    @if($currentAdmin && $currentAdmin->hasPermission('view_purchases'))
                    <!-- Acquisti con Dropdown -->
                    <div x-data="{ 
                        openAcquisti: {{ (request()->routeIs('admin.invoices-received.*') || request()->routeIs('admin.invoice-payments.*')) ? 'true' : 'false' }},
                        init() {
                            // Ascolta l'evento sidebar-chiuso
                            window.addEventListener('sidebar-closed', () => {
                                this.openAcquisti = false;
                            });
                        }
                    }">
                        <a href="#" 
                        @click.prevent="$store.sidebar.isExpanded ? openAcquisti = !openAcquisti : null"
                        class="sidebar-link flex items-center justify-between px-4 py-2.5 rounded-lg hover:bg-gray-700/50 transition-all duration-200 {{ request()->routeIs('admin.invoices-received.*') || request()->routeIs('admin.invoice-payments.*') ? 'bg-gray-700/70 text-lime-400 border-r-2 border-lime-500' : 'text-gray-300' }}">
                            <div class="flex items-center">
                                <i class="fa-solid fa-dolly w-5 h-5 {{ request()->routeIs('admin.invoices-received.*') || request()->routeIs('admin.invoice-payments.*') ? 'text-lime-400' : 'text-gray-500' }}"></i>
                                <span class="sidebar-link-text text-sm font-medium ml-3">Acquisti</span>
                            </div>
                            <!-- Freccia più staccata - con margin-left maggiore -->
                            <i class="fas fa-chevron-down text-xs transition-transform duration-200 ml-4 mr-1" 
                            :class="{ 'rotate-180': openAcquisti }" 
                            x-show="$store.sidebar.isExpanded"
                            style="min-width: 12px;"></i>
                        </a>
                        
                        <!-- Submenu Dropdown -->
                        <div x-show="openAcquisti && $store.sidebar.isExpanded" 
                            x-transition:enter="transition ease-out duration-200" 
                            x-transition:enter-start="opacity-0 transform -translate-y-2" 
                            x-transition:enter-end="opacity-100 transform translate-y-0"
                            x-transition:leave="transition ease-in duration-100" 
                            x-transition:leave-start="opacity-100 transform translate-y-0" 
                            x-transition:leave-end="opacity-0 transform -translate-y-2"
                            class="ml-6 mt-1 space-y-1">
                            
                            <a href="{{ route('admin.invoices-received.index') }}" 
                            class="flex items-center px-4 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('admin.invoices-received.*') && !request()->routeIs('admin.invoice-payments.*') && !request()->routeIs('admin.invoices-received.statistics') ? 'bg-gray-700/50 text-lime-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-700/30' }}">
                                <i class="fas fa-file-invoice w-4 h-4 mr-2"></i>
                                <span>Elenco fatture</span>
                            </a>
                            
                            <a href="{{ route('admin.invoice-payments.index') }}" 
                            class="flex items-center px-4 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('admin.invoice-payments.*') ? 'bg-gray-700/50 text-lime-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-700/30' }}">
                                <i class="fas fa-calendar-alt w-4 h-4 mr-2"></i>
                                <span>Elenco scadenze</span>
                            </a>

                            <a href="{{ route('admin.invoices-received.statistics') }}" 
                            class="flex items-center px-4 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('admin.invoices-received.statistics') ? 'bg-gray-700/50 text-lime-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-700/30' }}">
                                <i class="fas fa-chart-pie w-4 h-4 mr-2"></i>
                                <span>Statistiche</span>
                            </a>
                        </div>
                    </div>
                    @endif

                    <!-- Vendite -->
                    @if($currentAdmin && $currentAdmin->hasPermission('view_sent'))
                    <div x-data="{ 
                        openVendite: false,
                        init() {
                            window.addEventListener('sidebar-closed', () => {
                                this.openVendite = false;
                            });
                        }
                    }">
                        <a href="#" 
                        @click.prevent="$store.sidebar.isExpanded ? openVendite = !openVendite : null"
                        class="sidebar-link flex items-center justify-between px-4 py-2.5 rounded-lg hover:bg-gray-700/50 transition-all duration-200 {{ request()->routeIs('admin.invoices-sent.*') || request()->routeIs('admin.invoice-payments-sent.*') ? 'bg-gray-700/70 text-lime-400 border-r-2 border-lime-500' : 'text-gray-300' }} mb-1">
                            <div class="flex items-center">
                                <i class="fa-solid fa-briefcase w-5 h-5 {{ request()->routeIs('admin.invoices-sent.*') || request()->routeIs('admin.invoice-payments-sent.*') ? 'text-lime-400' : 'text-gray-500' }}"></i>
                                <span class="sidebar-link-text text-sm font-medium ml-3">Vendite</span>
                            </div>
                            <i class="fas fa-chevron-down text-xs transition-transform duration-200 ml-4 mr-1" 
                            :class="{ 'rotate-180': openVendite }" 
                            x-show="$store.sidebar.isExpanded"
                            style="min-width: 12px;"></i>
                        </a>
                        
                        <div x-show="openVendite && $store.sidebar.isExpanded" 
                            x-transition:enter="transition ease-out duration-200" 
                            x-transition:enter-start="opacity-0 transform -translate-y-2" 
                            x-transition:enter-end="opacity-100 transform translate-y-0"
                            x-transition:leave="transition ease-in duration-100" 
                            x-transition:leave-start="opacity-100 transform translate-y-0" 
                            x-transition:leave-end="opacity-0 transform -translate-y-2"
                            class="ml-6 mt-1 space-y-1">
                            
                            <a href="{{ route('admin.invoices-sent.index') }}" 
                            class="flex items-center px-4 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('admin.invoices-sent.*') && !request()->routeIs('admin.invoice-payments-sent.*') && !request()->routeIs('admin.invoices-sent.statistics') ? 'bg-gray-700/50 text-lime-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-700/30' }}">
                                <i class="fas fa-file-invoice-dollar w-4 h-4 mr-2"></i>
                                <span>Elenco fatture</span>
                            </a>
                            
                            <a href="{{ route('admin.invoice-payments-sent.index') }}" 
                            class="flex items-center px-4 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('admin.invoice-payments-sent.*') ? 'bg-gray-700/50 text-lime-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-700/30' }}">
                                <i class="fas fa-calendar-alt w-4 h-4 mr-2"></i>
                                <span>Elenco scadenze</span>
                            </a>

                            @if(Route::has('admin.invoices-sent.statistics'))
                            <a href="{{ route('admin.invoices-sent.statistics') }}" 
                            class="flex items-center px-4 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('admin.invoices-sent.statistics') ? 'bg-gray-700/50 text-lime-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-700/30' }}">
                                <i class="fas fa-chart-pie w-4 h-4 mr-2"></i>
                                <span>Statistiche</span>
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Prima Nota -->
                    @if($currentAdmin && $currentAdmin->hasPermission('view_accounting_entries'))
                    <a href="{{ route('admin.accounting-entries.index') }}" 
                    class="sidebar-link flex items-center px-4 py-2.5 rounded-lg hover:bg-gray-700/50 transition-all duration-200 {{ request()->routeIs('admin.accounting-entries.*') ? 'bg-gray-700/70 text-lime-400 border-r-2 border-lime-500' : 'text-gray-300' }} mb-1">
                        <i class="fa-solid fa-book w-5 h-5 {{ request()->routeIs('admin.accounting-entries.*') ? 'text-lime-400' : 'text-gray-500' }}"></i>
                        <span class="sidebar-link-text text-sm font-medium ml-3">Prima Nota</span>
                    </a>
                    @endif
                </div>
                @endif
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <!-- Header - Con breadcrumb -->
            <header class="bg-white/80 backdrop-blur-sm shadow-sm sticky top-0 z-10 border-b border-gray-100">
                <div class="flex justify-between items-center px-8 py-3">
                    <!-- Breadcrumb -->
                    <div class="text-sm">
                        <div class="flex items-center text-gray-500">
                            <i class="fas fa-home text-lime-500 mr-2"></i>
                            @php
                                $breadcrumbs = [];
                                $currentRoute = request()->route()->getName();
                                $currentParams = request()->route()->parameters();
                                
                                // Dashboard
                                if ($currentRoute === 'admin.dashboard') {
                                    $breadcrumbs[] = ['name' => 'Dashboard', 'url' => null, 'clickable' => false];
                                }
                                // Attività
                                elseif (str_starts_with($currentRoute, 'admin.activities.')) {
                                    $breadcrumbs[] = ['name' => 'Attività', 'url' => route('admin.activities.index'), 'clickable' => true];
                                    
                                    if ($currentRoute === 'admin.activities.show' || $currentRoute === 'admin.activities.edit') {
                                        $activity = $currentParams['activity'] ?? null;
                                        if ($activity && is_object($activity)) {
                                            // CORREZIONE: usa data_activities o un'altra proprietà esistente
                                            $activityName = 'Attività del ' . ($activity->data_activities ?? '');
                                            $breadcrumbs[] = ['name' => $activityName, 'url' => null, 'clickable' => false];
                                        }
                                        if ($currentRoute === 'admin.activities.edit') {
                                            $breadcrumbs[] = ['name' => 'Modifica', 'url' => null, 'clickable' => false];
                                        } elseif ($currentRoute === 'admin.activities.show') {
                                            $breadcrumbs[] = ['name' => 'Dettaglio', 'url' => null, 'clickable' => false];
                                        }
                                    } elseif ($currentRoute === 'admin.activities.create') {
                                        $breadcrumbs[] = ['name' => 'Nuova Attività', 'url' => null, 'clickable' => false];
                                    } elseif ($currentRoute === 'admin.activities.sub-activities') {
                                        $breadcrumbs[] = ['name' => 'Sotto-attività per Cliente', 'url' => null, 'clickable' => false];
                                    }
                                }
                                // Clienti / Fornitori
                                elseif (str_starts_with($currentRoute, 'admin.entities.')) {
                                    $breadcrumbs[] = ['name' => 'Clienti / Fornitori', 'url' => route('admin.entities.index'), 'clickable' => true];
                                    
                                    if ($currentRoute === 'admin.entities.show' || $currentRoute === 'admin.entities.edit') {
                                        $entity = $currentParams['entity'] ?? null;
                                        if ($entity) {
                                            $entityName = $entity->ragione_sociale ?: ($entity->nome . ' ' . $entity->cognome);
                                            $breadcrumbs[] = ['name' => $entityName, 'url' => null, 'clickable' => false];
                                        }
                                        if ($currentRoute === 'admin.entities.edit') {
                                            $breadcrumbs[] = ['name' => 'Modifica', 'url' => null, 'clickable' => false];
                                        } elseif ($currentRoute === 'admin.entities.show') {
                                            $breadcrumbs[] = ['name' => 'Dettaglio', 'url' => null, 'clickable' => false];
                                        }
                                    } elseif ($currentRoute === 'admin.entities.create') {
                                        $breadcrumbs[] = ['name' => 'Nuovo', 'url' => null, 'clickable' => false];
                                    }
                                }
                                // Personale (Staff)
                                elseif (str_starts_with($currentRoute, 'admin.staff.')) {
                                    $breadcrumbs[] = ['name' => 'Personale', 'url' => route('admin.staff.index'), 'clickable' => true];
                                    
                                    if ($currentRoute === 'admin.staff.show' || $currentRoute === 'admin.staff.edit') {
                                        $staff = $currentParams['staff'] ?? null;
                                        if ($staff && is_object($staff)) {
                                            $breadcrumbs[] = ['name' => $staff->full_name, 'url' => null, 'clickable' => false];
                                        }
                                        if ($currentRoute === 'admin.staff.edit') {
                                            $breadcrumbs[] = ['name' => 'Modifica', 'url' => null, 'clickable' => false];
                                        }
                                    } elseif ($currentRoute === 'admin.staff.create') {
                                        $breadcrumbs[] = ['name' => 'Nuovo', 'url' => null, 'clickable' => false];
                                    } elseif ($currentRoute === 'admin.staff.activity-report') {
                                        $staffId = $currentParams['staff'] ?? null;
                                        $breadcrumbs[] = ['name' => 'Report Attività', 'url' => null, 'clickable' => false];
                                        if ($staffId) {
                                            $staffModel = \App\Models\Staff::find($staffId);
                                            if ($staffModel) {
                                                $breadcrumbs[] = ['name' => $staffModel->full_name, 'url' => null, 'clickable' => false];
                                            }
                                        }
                                    } elseif ($currentRoute === 'admin.staff.attendance.index') {
                                        $breadcrumbs[] = ['name' => 'Gestione Presenze', 'url' => null, 'clickable' => false];
                                    } elseif ($currentRoute === 'admin.staff.attendance.show') {
                                        $staffId = $currentParams['staffId'] ?? null;
                                        $breadcrumbs[] = ['name' => 'Gestione Presenze', 'url' => route('admin.staff.attendance.index'), 'clickable' => true];
                                        if ($staffId) {
                                            $staffModel = \App\Models\Staff::find($staffId);
                                            if ($staffModel) {
                                                $breadcrumbs[] = [
                                                    'name' => $staffModel->NomePers . ' ' . $staffModel->CognomePers,
                                                    'url'  => null,
                                                    'clickable' => false
                                                ];
                                            }
                                        }
                                    }
                                }
                                // Comunicazioni (globali)
                                elseif (str_starts_with($currentRoute, 'admin.communications.')) {
                                    $breadcrumbs[] = ['name' => 'Clienti / Fornitori', 'url' => route('admin.entities.index'), 'clickable' => true];
                                    $breadcrumbs[] = ['name' => 'Comunicazioni', 'url' => null, 'clickable' => false];
                                }
                                // Mezzi (Vehicles)
                                elseif (str_starts_with($currentRoute, 'admin.vehicles.')) {
                                    $breadcrumbs[] = ['name' => 'Mezzi', 'url' => route('admin.vehicles.index'), 'clickable' => true];
                                    
                                    if ($currentRoute === 'admin.vehicles.show' || $currentRoute === 'admin.vehicles.edit') {
                                        $vehicle = $currentParams['vehicle'] ?? null;
                                        if ($vehicle) {
                                            $vehicleName = $vehicle->full_name ?? $vehicle->targa ?? 'Mezzo';
                                            $breadcrumbs[] = ['name' => $vehicleName, 'url' => null, 'clickable' => false];
                                        }
                                        if ($currentRoute === 'admin.vehicles.edit') {
                                            $breadcrumbs[] = ['name' => 'Modifica', 'url' => null, 'clickable' => false];
                                        } elseif ($currentRoute === 'admin.vehicles.show') {
                                            $breadcrumbs[] = ['name' => 'Dettaglio', 'url' => null, 'clickable' => false];
                                        }
                                    } elseif ($currentRoute === 'admin.vehicles.create') {
                                        $breadcrumbs[] = ['name' => 'Nuovo Mezzo', 'url' => null, 'clickable' => false];
                                    } elseif ($currentRoute === 'admin.vehicles.invoices-received') {
                                        $vehicle = $currentParams['vehicle'] ?? null;
                                        if ($vehicle) {
                                            $vehicleName = $vehicle->full_name ?? $vehicle->targa ?? 'Mezzo';
                                            $breadcrumbs[] = ['name' => $vehicleName, 'url' => null, 'clickable' => false];
                                        }
                                        $breadcrumbs[] = ['name' => 'Fatture di Acquisto', 'url' => null, 'clickable' => false];
                                    }
                                }
                                // Scadenze Personale
                                elseif (str_starts_with($currentRoute, 'admin.expiration-staff.')) {
                                    $staffId = request()->get('staffId');
                                    $breadcrumbs[] = ['name' => 'Personale', 'url' => route('admin.staff.index'), 'clickable' => true];
                                    
                                    if ($staffId) {
                                        $staff = \App\Models\Staff::find($staffId);
                                        if ($staff) {
                                            $breadcrumbs[] = ['name' => $staff->full_name, 'url' => route('admin.staff.show', $staffId), 'clickable' => false];
                                        }
                                    }
                                    $breadcrumbs[] = ['name' => 'Scadenze Personale', 'url' => null, 'clickable' => false];
                                    
                                    if ($currentRoute === 'admin.expiration-staff.edit') {
                                        $expiration = $currentParams['id'] ?? null;
                                        if ($expiration && is_object($expiration)) {
                                            $breadcrumbs[] = ['name' => 'Modifica: ' . $expiration->titolo, 'url' => null, 'clickable' => false];
                                        } else {
                                            $breadcrumbs[] = ['name' => 'Modifica', 'url' => null, 'clickable' => false];
                                        }
                                    } elseif ($currentRoute === 'admin.expiration-staff.create') {
                                        $breadcrumbs[] = ['name' => 'Nuova Scadenza', 'url' => null, 'clickable' => false];
                                    }
                                }
                                // Scadenze Veicoli
                                elseif (str_starts_with($currentRoute, 'admin.expiration-vehicle.')) {
                                    $vehicleId = request()->get('vehicleId');
                                    $breadcrumbs[] = ['name' => 'Mezzi', 'url' => route('admin.vehicles.index'), 'clickable' => true];
                                    
                                    if ($vehicleId) {
                                        $vehicle = \App\Models\Vehicles::find($vehicleId);
                                        if ($vehicle) {
                                            $vehicleName = $vehicle->full_name ?? $vehicle->targa ?? 'Mezzo';
                                            $breadcrumbs[] = ['name' => $vehicleName, 'url' => route('admin.vehicles.show', $vehicleId), 'clickable' => false];
                                        }
                                    }
                                    $breadcrumbs[] = ['name' => 'Scadenze Veicoli', 'url' => null, 'clickable' => false];
                                    
                                    if ($currentRoute === 'admin.expiration-vehicle.edit') {
                                        $expiration = $currentParams['id'] ?? null;
                                        if ($expiration && is_object($expiration)) {
                                            $breadcrumbs[] = ['name' => 'Modifica: ' . $expiration->titolo, 'url' => null, 'clickable' => false];
                                        } else {
                                            $breadcrumbs[] = ['name' => 'Modifica', 'url' => null, 'clickable' => false];
                                        }
                                    } elseif ($currentRoute === 'admin.expiration-vehicle.create') {
                                        $breadcrumbs[] = ['name' => 'Nuova Scadenza', 'url' => null, 'clickable' => false];
                                    }
                                }
                                // Documenti
                                elseif (str_starts_with($currentRoute, 'admin.documents.')) {
                                    $tableRef = $currentParams['tableRef'] ?? null;
                                    $idRef = $currentParams['idRef'] ?? null;
                                    
                                    if ($tableRef === 'staff') {
                                        $staff = \App\Models\Staff::find($idRef);
                                        if ($staff) {
                                            $breadcrumbs[] = ['name' => 'Personale', 'url' => route('admin.staff.index'), 'clickable' => true];
                                            $breadcrumbs[] = ['name' => $staff->full_name, 'url' => route('admin.staff.show', $idRef), 'clickable' => true];
                                            $breadcrumbs[] = ['name' => 'Documenti', 'url' => null, 'clickable' => false];
                                        }
                                    } 
                                    elseif ($tableRef === 'expiration-staff') {
                                        $expiration = \App\Models\Expiration::find($idRef);
                                        if ($expiration && $expiration->id_references) {
                                            $staff = \App\Models\Staff::find($expiration->id_references);
                                            if ($staff) {
                                                $breadcrumbs[] = ['name' => 'Personale', 'url' => route('admin.staff.index'), 'clickable' => true];
                                                $breadcrumbs[] = ['name' => $staff->full_name, 'url' => route('admin.staff.show', $staff->id_personale), 'clickable' => false];
                                                $breadcrumbs[] = ['name' => 'Scadenze Personale', 'url' => route('admin.expiration-staff.index', ['staffId' => $staff->id_personale]), 'clickable' => true];
                                                $breadcrumbs[] = ['name' => 'Documenti', 'url' => null, 'clickable' => false];
                                            }
                                        }
                                    }
                                    elseif ($tableRef === 'expiration-vehicles') {
                                        $expiration = \App\Models\Expiration::find($idRef);
                                        if ($expiration && $expiration->vehicles()->count() > 0) {
                                            $firstVehicle = $expiration->vehicles->first();
                                            if ($firstVehicle) {
                                                $breadcrumbs[] = ['name' => 'Mezzi', 'url' => route('admin.vehicles.index'), 'clickable' => true];
                                                $breadcrumbs[] = ['name' => $firstVehicle->full_name ?? $firstVehicle->targa, 'url' => route('admin.vehicles.show', $firstVehicle->id), 'clickable' => false];
                                                $breadcrumbs[] = ['name' => 'Scadenze Veicoli', 'url' => route('admin.expiration-vehicle.index', ['vehicleId' => $firstVehicle->id]), 'clickable' => true];
                                                $breadcrumbs[] = ['name' => 'Documenti', 'url' => null, 'clickable' => false];
                                            }
                                        }
                                    }
                                    elseif ($tableRef === 'vehicles') {
                                        $vehicle = \App\Models\Vehicles::find($idRef);
                                        if ($vehicle) {
                                            $breadcrumbs[] = ['name' => 'Mezzi', 'url' => route('admin.vehicles.index'), 'clickable' => true];
                                            $breadcrumbs[] = ['name' => $vehicle->full_name ?? $vehicle->targa, 'url' => route('admin.vehicles.show', $idRef), 'clickable' => true];
                                            $breadcrumbs[] = ['name' => 'Documenti', 'url' => null, 'clickable' => false];
                                        }
                                    }
                                }
                                // Centri di Costo
                                elseif (str_starts_with($currentRoute, 'admin.cost_centers.')) {
                                    $breadcrumbs[] = ['name' => 'Centri di Costo', 'url' => route('admin.cost_centers.index'), 'clickable' => true];
                                    
                                    if ($currentRoute === 'admin.cost_centers.show') {
                                        $costCenter = $currentParams['cost_center'] ?? null;
                                        if ($costCenter && is_object($costCenter)) {
                                            $breadcrumbs[] = ['name' => $costCenter->Nome ?: 'Centro di Costo', 'url' => null, 'clickable' => false];
                                        }
                                        $breadcrumbs[] = ['name' => 'Dettaglio', 'url' => null, 'clickable' => false];
                                    } elseif ($currentRoute === 'admin.cost_centers.edit') {
                                        $costCenter = $currentParams['cost_center'] ?? null;
                                        if ($costCenter && is_object($costCenter)) {
                                            $breadcrumbs[] = ['name' => $costCenter->Nome ?: 'Centro di Costo', 'url' => null, 'clickable' => false];
                                        }
                                        $breadcrumbs[] = ['name' => 'Modifica', 'url' => null, 'clickable' => false];
                                    } elseif ($currentRoute === 'admin.cost_centers.create') {
                                        $breadcrumbs[] = ['name' => 'Nuovo Centro di Costo', 'url' => null, 'clickable' => false];
                                    }
                                }
                                // Acquisti - Fatture di Acquisto
                                elseif (str_starts_with($currentRoute, 'admin.invoices-received.')) {
                                    $breadcrumbs[] = ['name' => 'Acquisti', 'url' => null, 'clickable' => false];
                                    $breadcrumbs[] = ['name' => 'Fatture di Acquisto', 'url' => route('admin.invoices-received.index'), 'clickable' => true];
                                    
                                    if ($currentRoute === 'admin.invoices-received.create') {
                                        $breadcrumbs[] = ['name' => 'Nuova Fattura', 'url' => null, 'clickable' => false];
                                    } elseif ($currentRoute === 'admin.invoices-received.edit') {
                                        $invoice = $currentParams['invoice'] ?? null;
                                        if ($invoice && is_object($invoice)) {
                                            $breadcrumbs[] = ['name' => 'Fattura n. ' . ($invoice->n_invoice ?? ''), 'url' => null, 'clickable' => false];
                                        }
                                        $breadcrumbs[] = ['name' => 'Modifica', 'url' => null, 'clickable' => false];
                                    } elseif ($currentRoute === 'admin.invoices-received.xml-import') {
                                        $breadcrumbs[] = ['name' => 'Importa XML', 'url' => null, 'clickable' => false];
                                    } elseif ($currentRoute === 'admin.invoices-received.xml-view') {
                                        $invoice = $currentParams['invoice'] ?? null;
                                        if ($invoice && is_object($invoice)) {
                                            $breadcrumbs[] = ['name' => 'Fattura n. ' . ($invoice->n_invoice ?? ''), 'url' => null, 'clickable' => false];
                                        }
                                        $breadcrumbs[] = ['name' => 'Visualizza XML', 'url' => null, 'clickable' => false];
                                    } elseif ($currentRoute === 'admin.invoices-received.statistics') {
                                        $breadcrumbs[] = ['name' => 'Statistiche', 'url' => null, 'clickable' => false];
                                    }
                                }

                                // Scadenze Pagamento (Acquisti)
                                elseif (str_starts_with($currentRoute, 'admin.invoice-payments.')) {
                                    $breadcrumbs[] = ['name' => 'Acquisti', 'url' => null, 'clickable' => false];
                                    $breadcrumbs[] = ['name' => 'Scadenze Pagamento', 'url' => route('admin.invoice-payments.index'), 'clickable' => true];
                                    
                                    if ($currentRoute === 'admin.invoice-payments.edit') {
                                        $payment = $currentParams['payment'] ?? null;
                                        if ($payment && is_object($payment)) {
                                            $breadcrumbs[] = ['name' => 'Pagamento del ' . ($payment->due_date ? date('d/m/Y', strtotime($payment->due_date)) : ''), 'url' => null, 'clickable' => false];
                                        }
                                        $breadcrumbs[] = ['name' => 'Modifica', 'url' => null, 'clickable' => false];
                                    }
                                }
                                // Vendite - Fatture di Vendita e Scadenze Incasso
                                elseif (str_starts_with($currentRoute, 'admin.invoices-sent.') || str_starts_with($currentRoute, 'admin.invoice-payments-sent.')) {
                                    $breadcrumbs[] = ['name' => 'Vendite', 'url' => null, 'clickable' => false];
                                    
                                    if (str_starts_with($currentRoute, 'admin.invoices-sent.')) {
                                        $breadcrumbs[] = ['name' => 'Fatture di Vendita', 'url' => route('admin.invoices-sent.index'), 'clickable' => true];
                                        
                                        if ($currentRoute === 'admin.invoices-sent.create') {
                                            $breadcrumbs[] = ['name' => 'Nuova Fattura', 'url' => null, 'clickable' => false];
                                        } elseif ($currentRoute === 'admin.invoices-sent.edit') {
                                            $invoice = $currentParams['id'] ?? null;
                                            if ($invoice && is_object($invoice)) {
                                                $breadcrumbs[] = ['name' => 'Fattura n. ' . ($invoice->n_fattura ?? ''), 'url' => null, 'clickable' => false];
                                            }
                                            $breadcrumbs[] = ['name' => 'Modifica', 'url' => null, 'clickable' => false];
                                        } elseif ($currentRoute === 'admin.invoices-sent.show') {
                                            $invoice = $currentParams['id'] ?? null;
                                            if ($invoice && is_object($invoice)) {
                                                $breadcrumbs[] = ['name' => 'Fattura n. ' . ($invoice->n_fattura ?? ''), 'url' => null, 'clickable' => false];
                                            }
                                            $breadcrumbs[] = ['name' => 'Dettaglio', 'url' => null, 'clickable' => false];
                                        }
                                    } elseif (str_starts_with($currentRoute, 'admin.invoice-payments-sent.')) {
                                        $breadcrumbs[] = ['name' => 'Scadenze Vendita', 'url' => route('admin.invoice-payments-sent.index'), 'clickable' => true];
                                        
                                        if ($currentRoute === 'admin.invoice-payments-sent.edit') {
                                            $payment = $currentParams['payment'] ?? null;
                                            if ($payment && is_object($payment)) {
                                                $breadcrumbs[] = ['name' => 'Scadenza del ' . ($payment->due_date ? date('d/m/Y', strtotime($payment->due_date)) : ''), 'url' => null, 'clickable' => false];
                                            }
                                            $breadcrumbs[] = ['name' => 'Modifica', 'url' => null, 'clickable' => false];
                                        }
                                    }
                                }
                                // Prima Nota (Accounting Entries)
                                elseif (str_starts_with($currentRoute, 'admin.accounting-entries.')) {
                                    $breadcrumbs[] = ['name' => 'Prima Nota', 'url' => route('admin.accounting-entries.index'), 'clickable' => true];
                                    
                                    if ($currentRoute === 'admin.accounting-entries.create') {
                                        $breadcrumbs[] = ['name' => 'Nuova Scrittura', 'url' => null, 'clickable' => false];
                                    } elseif ($currentRoute === 'admin.accounting-entries.edit') {
                                        $entry = $currentParams['accounting_entry'] ?? null;
                                        if ($entry && is_object($entry)) {
                                            $breadcrumbs[] = ['name' => 'Scrittura del ' . ($entry->entry_date ? date('d/m/Y', strtotime($entry->entry_date)) : ''), 'url' => null, 'clickable' => false];
                                        }
                                        $breadcrumbs[] = ['name' => 'Modifica', 'url' => null, 'clickable' => false];
                                    } elseif ($currentRoute === 'admin.accounting-entries.show') {
                                        $entry = $currentParams['accounting_entry'] ?? null;
                                        if ($entry && is_object($entry)) {
                                            $breadcrumbs[] = ['name' => 'Scrittura del ' . ($entry->entry_date ? date('d/m/Y', strtotime($entry->entry_date)) : ''), 'url' => null, 'clickable' => false];
                                        }
                                        $breadcrumbs[] = ['name' => 'Dettaglio', 'url' => null, 'clickable' => false];
                                    }
                                }
                                // Impostazioni
                                elseif (str_starts_with($currentRoute, 'admin.settings.')) {
                                    $breadcrumbs[] = ['name' => 'Impostazioni', 'url' => null, 'clickable' => false];
                                    
                                    if ($currentRoute === 'admin.settings.index') {
                                        $breadcrumbs[] = ['name' => 'Categorie Settings', 'url' => null, 'clickable' => false];
                                    }
                                    elseif ($currentRoute === 'admin.settings.categories.index') {
                                        // Recupera category_id sia da parametro route che da query string
                                        $categoryId = $currentParams['category_id'] ?? request()->get('category_id');
                                        
                                        if ($categoryId) {
                                            // Siamo in una categoria specifica (es. Contatti)
                                            try {
                                                $category = \App\Models\SettingsCategories::find($categoryId);
                                                if ($category) {
                                                    $breadcrumbs[] = ['name' => 'Categorie Settings', 'url' => route('admin.settings.index'), 'clickable' => true];
                                                    $breadcrumbs[] = ['name' => $category->valore, 'url' => null, 'clickable' => false];
                                                } else {
                                                    $breadcrumbs[] = ['name' => 'Categorie Settings', 'url' => null, 'clickable' => false];
                                                }
                                            } catch (\Exception $e) {
                                                $breadcrumbs[] = ['name' => 'Categorie Settings', 'url' => null, 'clickable' => false];
                                            }
                                        } else {
                                            // Lista di tutte le categorie
                                            $breadcrumbs[] = ['name' => 'Categorie Settings', 'url' => null, 'clickable' => false];
                                        }
                                    }
                                    elseif ($currentRoute === 'admin.settings.categories.create') {
                                        $categoryId = $currentParams['category_id'] ?? request()->get('category_id');
                                        
                                        $breadcrumbs[] = ['name' => 'Categorie Settings', 'url' => route('admin.settings.index'), 'clickable' => true];
                                        
                                        if ($categoryId) {
                                            try {
                                                $category = \App\Models\SettingsCategories::find($categoryId);
                                                if ($category) {
                                                    $breadcrumbs[] = ['name' => $category->valore, 'url' => route('admin.settings.categories.index', ['category_id' => $categoryId]), 'clickable' => true];
                                                }
                                            } catch (\Exception $e) {}
                                        }
                                        $breadcrumbs[] = ['name' => 'Nuova Impostazione', 'url' => null, 'clickable' => false];
                                    }
                                    elseif ($currentRoute === 'admin.settings.categories.edit') {
                                        $categoryId = $currentParams['category_id'] ?? request()->get('category_id');
                                        
                                        $breadcrumbs[] = ['name' => 'Categorie Settings', 'url' => route('admin.settings.index'), 'clickable' => true];
                                        
                                        if ($categoryId) {
                                            try {
                                                $category = \App\Models\SettingsCategories::find($categoryId);
                                                if ($category) {
                                                    $breadcrumbs[] = ['name' => $category->valore, 'url' => route('admin.settings.categories.index', ['category_id' => $categoryId]), 'clickable' => true];
                                                }
                                            } catch (\Exception $e) {}
                                        }
                                        $breadcrumbs[] = ['name' => 'Modifica Impostazione', 'url' => null, 'clickable' => false];
                                    }
                                    elseif ($currentRoute === 'admin.settings.edit') {
                                        $breadcrumbs[] = ['name' => 'Categorie Settings', 'url' => route('admin.settings.index'), 'clickable' => true];
                                        $breadcrumbs[] = ['name' => 'Modifica Impostazione', 'url' => null, 'clickable' => false];
                                    }
                                    elseif ($currentRoute === 'admin.settings.create') {
                                        $breadcrumbs[] = ['name' => 'Categorie Settings', 'url' => route('admin.settings.index'), 'clickable' => true];
                                        $breadcrumbs[] = ['name' => 'Nuova Impostazione', 'url' => null, 'clickable' => false];
                                    }
                                }
                                // Servizi (separato dalle Impostazioni)
                                elseif (str_starts_with($currentRoute, 'admin.services.')) {
                                    $breadcrumbs[] = ['name' => 'Impostazioni', 'url' => null, 'clickable' => false];
                                    $breadcrumbs[] = ['name' => 'Servizi', 'url' => route('admin.services.index'), 'clickable' => true];
                                    
                                    if ($currentRoute === 'admin.services.create') {
                                        $breadcrumbs[] = ['name' => 'Nuovo Servizio', 'url' => null, 'clickable' => false];
                                    } elseif ($currentRoute === 'admin.services.edit') {
                                        $service = $currentParams['service'] ?? null;
                                        if ($service && is_object($service)) {
                                            $breadcrumbs[] = ['name' => $service->Titolo ?? 'Servizio', 'url' => null, 'clickable' => false];
                                        }
                                        $breadcrumbs[] = ['name' => 'Modifica', 'url' => null, 'clickable' => false];
                                    } elseif ($currentRoute === 'admin.services.show') {
                                        $service = $currentParams['service'] ?? null;
                                        if ($service && is_object($service)) {
                                            $breadcrumbs[] = ['name' => $service->Titolo ?? 'Servizio', 'url' => null, 'clickable' => false];
                                        }
                                        $breadcrumbs[] = ['name' => 'Dettaglio', 'url' => null, 'clickable' => false];
                                    }
                                }
                                // Amministratori
                                elseif (str_starts_with($currentRoute, 'admin.administrators.')) {
                                    $breadcrumbs[] = ['name' => 'Impostazioni', 'url' => null, 'clickable' => false];
                                    $breadcrumbs[] = ['name' => 'Amministratori', 'url' => route('admin.administrators.index'), 'clickable' => true];
                                    
                                    if ($currentRoute === 'admin.administrators.create') {
                                        $breadcrumbs[] = ['name' => 'Nuovo Amministratore', 'url' => null, 'clickable' => false];
                                    } elseif ($currentRoute === 'admin.administrators.edit') {
                                        $breadcrumbs[] = ['name' => 'Modifica Amministratore', 'url' => null, 'clickable' => false];
                                    } elseif ($currentRoute === 'admin.administrators.show') {
                                        $breadcrumbs[] = ['name' => 'Dettaglio Amministratore', 'url' => null, 'clickable' => false];
                                    }
                                }
                                // Ruoli
                                elseif (str_starts_with($currentRoute, 'admin.roles.')) {
                                    $breadcrumbs[] = ['name' => 'Impostazioni', 'url' => null, 'clickable' => false];
                                    $breadcrumbs[] = ['name' => 'Ruoli', 'url' => route('admin.roles.index'), 'clickable' => true];
                                    
                                    if ($currentRoute === 'admin.roles.create') {
                                        $breadcrumbs[] = ['name' => 'Nuovo Ruolo', 'url' => null, 'clickable' => false];
                                    } elseif ($currentRoute === 'admin.roles.edit') {
                                        $breadcrumbs[] = ['name' => 'Modifica Ruolo', 'url' => null, 'clickable' => false];
                                    } elseif ($currentRoute === 'admin.roles.show') {
                                        $breadcrumbs[] = ['name' => 'Permessi', 'url' => null, 'clickable' => false];
                                    }
                                }
                                // Profilo
                                elseif ($currentRoute === 'admin.profile.edit') {
                                    $breadcrumbs[] = ['name' => 'Profilo', 'url' => null, 'clickable' => false];
                                }
                                // Cestino
                                elseif (str_starts_with($currentRoute, 'admin.trash.')) {
                                    $breadcrumbs[] = ['name' => 'Cestino', 'url' => null, 'clickable' => false];
                                    
                                    $type = $currentParams['type'] ?? null;
                                    if ($type) {
                                        $typeName = match($type) {
                                            'entities' => 'Clienti / Fornitori',
                                            'staff' => 'Personale',
                                            'vehicles' => 'Mezzi',
                                            default => ucfirst($type)
                                        };
                                        $breadcrumbs[] = ['name' => $typeName, 'url' => null, 'clickable' => false];
                                    }
                                }
                                // Default
                                else {
                                    $breadcrumbs[] = ['name' => 'Dashboard', 'url' => route('admin.dashboard'), 'clickable' => true];
                                }
                            @endphp
                            
                            @foreach($breadcrumbs as $index => $crumb)
                                <span class="breadcrumb-item">
                                    @if(isset($crumb['clickable']) && $crumb['clickable'] === true && $crumb['url'])
                                        <a href="{{ $crumb['url'] }}" class="breadcrumb-link text-gray-500 hover:text-lime-600 transition">
                                            {{ $crumb['name'] }}
                                        </a>
                                    @else
                                        <span class="text-gray-800 font-medium">{{ $crumb['name'] }}</span>
                                    @endif
                                </span>
                            @endforeach
                        </div>
                    </div>
                    
                    <!-- Right side - Avatar e Dropdown -->
                    <div class="flex items-center space-x-4">
                        <!-- Settings Dropdown (Ingranaggio) -->
                        <div class="relative">
                            <button id="settingsMenuButton" class="settings-btn text-gray-500 hover:text-lime-600 focus:outline-none transition-all duration-200">
                                <i class="fas fa-cog text-xl"></i>
                            </button>
                            
                            <!-- Settings Dropdown Menu -->
                            <div id="settingsDropdown" class="dropdown-menu" style="min-width: 220px; right: 0; left: auto;">
                                <div class="dropdown-header">
                                    <div class="flex items-center space-x-3">
                                        <i class="fas fa-sliders-h text-lime-500 text-lg"></i>
                                        <div>
                                            <p class="font-bold text-gray-900">Impostazioni</p>
                                            <p class="text-xs text-gray-500">Gestisci il sistema</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="dropdown-divider"></div>
                                
                                <!-- Gestione Utenti (Lista Admin) -->
                                @if($currentAdmin && $currentAdmin->hasPermission('view_administrators'))
                                <a href="{{ route('admin.administrators.index') }}" class="dropdown-item">
                                    <i class="fas fa-users"></i>
                                    <span>Gestione Utenti</span>
                                </a>
                                @endif
                                
                                <!-- Ruoli e Permessi -->
                                @if($currentAdmin && $currentAdmin->hasPermission('view_roles'))
                                <a href="{{ route('admin.roles.index') }}" class="dropdown-item">
                                    <i class="fas fa-shield-alt"></i>
                                    <span>Ruoli e Permessi</span>
                                </a>
                                @endif
                                
                                <!-- Settings -->
                                @if($currentAdmin && $currentAdmin->hasPermission('access_settings'))
                                <div class="dropdown-divider"></div>
                                <a href="{{ route('admin.settings.categories.index') }}" class="dropdown-item">
                                    <i class="fas fa-cog"></i>
                                    <span>Settings</span>
                                </a>
                                @endif

                                <!-- Servizi -->
                                @if($currentAdmin && $currentAdmin->hasPermission('view_services'))
                                <a href="{{ route('admin.services.index') }}" class="dropdown-item">
                                    <i class="fas fa-concierge-bell"></i>
                                    <span>Servizi</span>
                                </a>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Avatar e Dropdown Profilo -->
                        <div class="relative">
                            <button id="userMenuButton" class="flex items-center space-x-3 focus:outline-none hover:bg-gray-50 rounded-xl px-3 py-2 transition-all duration-200 group">
                                <div class="w-10 h-10 bg-gradient-to-r from-lime-500 to-lime-600 rounded-full flex items-center justify-center text-white font-semibold shadow-md group-hover:shadow-lg transition">
                                    {{ strtoupper(substr($currentAdmin->name ?? 'A', 0, 1)) }}
                                </div>
                                <div class="text-left">
                                    <p class="font-semibold text-gray-800 text-sm">{{ $currentAdmin->name ?? 'Admin' }}</p>
                                    <p class="text-gray-500 text-xs">
                                        <i class="fas {{ $currentAdmin->role && $currentAdmin->role->slug == 'super_admin' ? 'fa-lock text-gray-800' : 'fa-shield-alt' }} mr-1"></i>
                                        {{ $currentAdmin->role->name ?? 'Nessun ruolo' }}
                                    </p>
                                </div>
                                <i class="fas fa-chevron-down text-gray-400 text-xs transition-transform duration-200 group-hover:text-lime-500" id="userMenuChevron"></i>
                            </button>
                            
                            <!-- Dropdown Menu Profilo -->
                            <div id="userDropdown" class="dropdown-menu">
                                <div class="dropdown-header">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-12 h-12 bg-gradient-to-r from-lime-500 to-lime-600 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-md">
                                            {{ strtoupper(substr($currentAdmin->name ?? 'A', 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-900">{{ $currentAdmin->name ?? 'Admin' }}</p>
                                            <p class="text-xs text-gray-500">{{ $currentAdmin->email ?? '' }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="dropdown-divider"></div>
                                <a href="{{ route('admin.profile.edit') }}" class="dropdown-item">
                                    <i class="fas fa-user-circle"></i>
                                    <span>Il mio profilo</span>
                                </a>
                                <div class="dropdown-divider"></div>
                                <form method="POST" action="{{ route('admin.logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item w-full text-left">
                                        <i class="fas fa-sign-out-alt"></i>
                                        <span>Logout</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="p-6">
                <!-- Alert Messages -->
                @if(session('success'))
                    <div class="mb-6 p-4 bg-gradient-to-r from-green-50 to-lime-50 border-l-4 border-lime-500 text-green-700 rounded-lg shadow-sm animate-pulse">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-lime-500 text-lg mr-3"></i>
                            <span class="font-medium">{{ session('success') }}</span>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 p-4 bg-gradient-to-r from-red-50 to-rose-50 border-l-4 border-red-500 text-red-700 rounded-lg shadow-sm">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-500 text-lg mr-3"></i>
                            <span class="font-medium">{{ session('error') }}</span>
                        </div>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="mb-6 p-4 bg-gradient-to-r from-amber-50 to-yellow-50 border-l-4 border-amber-500 text-amber-700 rounded-lg shadow-sm">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle text-amber-500 text-lg mr-3"></i>
                            <span class="font-medium">{{ session('warning') }}</span>
                        </div>
                    </div>
                @endif

                @if(session('info'))
                    <div class="mb-6 p-4 bg-gradient-to-r from-blue-50 to-cyan-50 border-l-4 border-blue-500 text-blue-700 rounded-lg shadow-sm">
                        <div class="flex items-center">
                            <i class="fas fa-info-circle text-blue-500 text-lg mr-3"></i>
                            <span class="font-medium">{{ session('info') }}</span>
                        </div>
                    </div>
                @endif

                <!-- Page Content -->
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Livewire Scripts -->
    @livewireScripts

    <script>
        // ============================================
        // SIDEBAR STATE MANAGEMENT (Alpine.js Store)
        // ============================================
        document.addEventListener('alpine:init', () => {
            Alpine.store('sidebar', {
                isExpanded: false,
                init() {
                    const sidebar = document.getElementById('sidebar');
                    if (sidebar) {
                        // Funzione per aggiornare lo stato
                        const updateState = () => {
                            const wasExpanded = this.isExpanded;
                            // La sidebar è espansa quando NON ha la classe 'sidebar-closed' 
                            // o quando la larghezza è maggiore di 100px
                            this.isExpanded = !sidebar.classList.contains('sidebar-closed') || sidebar.offsetWidth > 100;
                            
                            // Se la sidebar si è chiusa (da espansa a non espansa)
                            if (wasExpanded && !this.isExpanded) {
                                // Emetti un evento globale per chiudere tutti i dropdown
                                window.dispatchEvent(new CustomEvent('sidebar-closed'));
                            }
                        };
                        
                        // Osserva i cambiamenti delle classi
                        const classObserver = new MutationObserver(updateState);
                        classObserver.observe(sidebar, { attributes: true, attributeFilter: ['class'] });
                        
                        // Osserva i cambiamenti di stile (per la larghezza)
                        const resizeObserver = new ResizeObserver(updateState);
                        resizeObserver.observe(sidebar);
                        
                        // Osserva anche i cambiamenti CSS (nel caso)
                        const styleObserver = new MutationObserver(updateState);
                        styleObserver.observe(sidebar, { attributes: true, attributeFilter: ['style'] });
                        
                        // Stato iniziale
                        updateState();
                    }
                }
            });
        });

        // ============================================
        // DROPDOWN MENU FUNCTIONALITY
        // ============================================
        document.addEventListener('DOMContentLoaded', function() {
            // User dropdown
            const userMenuButton = document.getElementById('userMenuButton');
            const userDropdown = document.getElementById('userDropdown');
            const userMenuChevron = document.getElementById('userMenuChevron');
            
            if (userMenuButton) {
                userMenuButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userDropdown.classList.toggle('show');
                    if (userMenuChevron) {
                        userMenuChevron.style.transform = userDropdown.classList.contains('show') ? 'rotate(180deg)' : 'rotate(0deg)';
                    }
                });
            }
            
            // Settings dropdown
            const settingsMenuButton = document.getElementById('settingsMenuButton');
            const settingsDropdown = document.getElementById('settingsDropdown');
            
            if (settingsMenuButton) {
                settingsMenuButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    settingsDropdown.classList.toggle('show');
                });
            }
            
            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                if (userMenuButton && !userMenuButton.contains(e.target)) {
                    userDropdown.classList.remove('show');
                    if (userMenuChevron) {
                        userMenuChevron.style.transform = 'rotate(0deg)';
                    }
                }
                if (settingsMenuButton && !settingsMenuButton.contains(e.target)) {
                    settingsDropdown.classList.remove('show');
                }
            });
            
            // Quando la sidebar si chiude, chiudi tutti i dropdown di Acquisti
            window.addEventListener('sidebar-closed', function() {
                // Chiudi tutti i dropdown di Acquisti (Alpine.js)
                const acquistiElements = document.querySelectorAll('[x-data]');
                acquistiElements.forEach(el => {
                    if (el.__x && el.__x.$data && typeof el.__x.$data.openAcquisti !== 'undefined') {
                        el.__x.$data.openAcquisti = false;
                    }
                });
                
                // Chiudi anche eventuali altri dropdown
                const allDropdowns = document.querySelectorAll('.dropdown-menu.show');
                allDropdowns.forEach(dropdown => {
                    dropdown.classList.remove('show');
                });
            });
            
            // Auto-hide success message after 5 seconds
            const successAlert = document.querySelector('.animate-pulse');
            if (successAlert) {
                setTimeout(function() {
                    successAlert.style.opacity = '0';
                    successAlert.style.transition = 'opacity 0.5s ease';
                    setTimeout(function() {
                        if (successAlert && successAlert.remove) {
                            successAlert.remove();
                        }
                    }, 500);
                }, 5000);
            }
        });
        
        // ============================================
        // TOOLTIP AUTO-INITIALIZATION
        // ============================================
        document.querySelectorAll('[data-tooltip]').forEach(element => {
            if (element.classList.contains('disabled-link')) {
                element.setAttribute('data-tooltip', 'Non hai i permessi necessari');
            }
        });
        
        // ============================================
        // TAB FUNCTIONALITY HELPER
        // ============================================
        window.initializeTabs = function(containerId) {
            const container = document.getElementById(containerId);
            if (!container) return;
            
            const tabButtons = container.querySelectorAll('.tab-button');
            const tabContents = container.querySelectorAll('.tab-content');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const tabId = button.getAttribute('data-tab');
                    
                    // Remove active class from all buttons and contents
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // Add active class to current button and content
                    button.classList.add('active');
                    const activeContent = document.getElementById(tabId);
                    if (activeContent) activeContent.classList.add('active');
                });
            });
        };
        
        // ============================================
        // LIVE WIRE EVENT HANDLERS
        // ============================================
        document.addEventListener('livewire:init', function () {
            // Redirect to edit page
            Livewire.on('redirectToEdit', ({ id }) => {
                window.location.href = '/admin/entities/' + id + '/edit';
            });
            
            // Redirect to edit staff
            Livewire.on('redirectToEditStaff', ({ id }) => {
                window.location.href = '/admin/staff/' + id + '/edit';
            });
            
            // Redirect to edit vehicle
            Livewire.on('redirectToEditVehicle', ({ id }) => {
                window.location.href = '/admin/vehicles/' + id + '/edit';
            });
            
            // Show success message as toast
            Livewire.on('showSuccess', ({ message }) => {
                showToast(message, 'success');
            });
            
            // Show error message as toast
            Livewire.on('showError', ({ message }) => {
                showToast(message, 'error');
            });
            
            // Show info message as toast
            Livewire.on('showInfo', ({ message }) => {
                showToast(message, 'info');
            });
            
            // Show warning message as toast
            Livewire.on('showWarning', ({ message }) => {
                showToast(message, 'warning');
            });
            
            // Refresh table event
            Livewire.on('tableRefreshed', () => {
                console.log('Tabella aggiornata');
            });
            
            // Clear input events
            Livewire.on('clearOwnershipInput', () => {
                const input = document.getElementById('ownership_input');
                if (input) input.value = '';
            });
            
            Livewire.on('clearSupplierInput', () => {
                const input = document.getElementById('supplier_input');
                if (input) input.value = '';
            });
            
            Livewire.on('clearCostCenterInput', () => {
                const input = document.getElementById('cost_center_input');
                if (input) input.value = '';
            });
            
            Livewire.on('resetAllFilters', () => {
                const ownershipInput = document.getElementById('ownership_input');
                if (ownershipInput) ownershipInput.value = '';
                
                const supplierInput = document.getElementById('supplier_input');
                if (supplierInput) supplierInput.value = '';
                
                const costCenterInput = document.getElementById('cost_center_input');
                if (costCenterInput) costCenterInput.value = '';
            });
            
            // Scroll to top when page changes
            Livewire.on('scrollToTop', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
        
        // ============================================
        // TOAST NOTIFICATION FUNCTION
        // ============================================
        function showToast(message, type = 'success') {
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                info: 'bg-blue-500',
                warning: 'bg-yellow-500'
            };
            
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                info: 'fa-info-circle',
                warning: 'fa-exclamation-triangle'
            };
            
            const toast = document.createElement('div');
            toast.className = `toast-notification fixed top-4 right-4 z-50 p-4 ${colors[type]} text-white rounded-lg shadow-lg`;
            toast.style.animation = 'slideInRight 0.3s ease-out';
            toast.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${icons[type]} mr-2"></i>
                    <span>${message}</span>
                    <button class="ml-4 text-white hover:text-gray-200 focus:outline-none" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (toast && toast.parentElement) {
                    toast.style.opacity = '0';
                    toast.style.transition = 'opacity 0.5s ease';
                    setTimeout(() => {
                        if (toast && toast.remove) {
                            toast.remove();
                        }
                    }, 500);
                }
            }, 5000);
        }
        
        // ============================================
        // SIDEBAR HELPER FUNCTION (per uso in Alpine)
        // ============================================
        window.sidebarExpanded = function() {
            const sidebar = document.getElementById('sidebar');
            if (!sidebar) return false;
            return !sidebar.classList.contains('sidebar-closed') || sidebar.offsetWidth > 100;
        };
        
        // ============================================
        // MODAL HELPER FUNCTIONS
        // ============================================
        window.openModal = function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.overflow = 'hidden';
            }
        };
        
        window.closeModal = function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = '';
            }
        };
        
        // ============================================
        // CONFIRM DIALOG HELPER
        // ============================================
        window.confirmAction = function(message, callback) {
            if (confirm(message)) {
                callback();
            }
        };
        
        // ============================================
        // FORMAT NUMBER HELPER
        // ============================================
        window.formatNumber = function(number, decimals = 2, decPoint = ',', thousandsSep = '.') {
            number = parseFloat(number);
            if (isNaN(number)) return '0,00';
            
            let parts = number.toFixed(decimals).split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandsSep);
            return parts.join(decPoint);
        };
        
        // ============================================
        // FORMAT DATE HELPER
        // ============================================
        window.formatDate = function(dateString, format = 'dd/mm/yyyy') {
            if (!dateString) return '-';
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return '-';
            
            const day = date.getDate().toString().padStart(2, '0');
            const month = (date.getMonth() + 1).toString().padStart(2, '0');
            const year = date.getFullYear();
            
            return format.replace('dd', day).replace('mm', month).replace('yyyy', year);
        };
        
        // ============================================
        // PRINT ELEMENT HELPER
        // ============================================
        window.printElement = function(elementId) {
            const element = document.getElementById(elementId);
            if (element) {
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <html>
                    <head>
                        <title>Stampa</title>
                        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
                        <style>
                            body { font-family: Arial, sans-serif; padding: 20px; }
                            @media print {
                                .no-print { display: none; }
                            }
                        </style>
                    </head>
                    <body>
                        ${element.innerHTML}
                    </body>
                    </html>
                `);
                printWindow.document.close();
                printWindow.print();
            }
        };
        
        // ============================================
        // EXPORT TABLE TO CSV HELPER
        // ============================================
        window.exportToCSV = function(tableId, filename = 'export.csv') {
            const table = document.getElementById(tableId);
            if (!table) return;
            
            const rows = table.querySelectorAll('tr');
            const csvData = [];
            
            rows.forEach(row => {
                const cols = row.querySelectorAll('td, th');
                const rowData = [];
                cols.forEach(col => {
                    let text = col.innerText.replace(/,/g, ';');
                    rowData.push(text);
                });
                csvData.push(rowData.join(','));
            });
            
            const blob = new Blob([csvData.join('\n')], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            a.click();
            URL.revokeObjectURL(url);
        };
    </script>
    
    @stack('scripts')
</body>
</html>