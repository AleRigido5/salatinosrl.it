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
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
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
                @if($currentAdmin && $currentAdmin->hasPermission('view_entities'))
                <div class="mb-6">
                    
                    <!-- Clienti / Fornitori -->
                    <a href="{{ route('admin.entities.index') }}" 
                       class="sidebar-link flex items-center px-4 py-2.5 rounded-lg hover:bg-gray-700/50 transition-all duration-200 {{ request()->routeIs('admin.entities.*') ? 'bg-gray-700/70 text-lime-400 border-r-2 border-lime-500' : 'text-gray-300' }} mb-1">
                        <i class="fas fa-building w-5 h-5 {{ request()->routeIs('admin.entities.*') ? 'text-lime-400' : 'text-gray-500' }}"></i>
                        <span class="sidebar-link-text text-sm font-medium ml-3">Clienti / Fornitori</span>
                    </a>
                    
                    <!-- Personale -->
                    <a href="{{ route('admin.staff.index') }}" 
                       class="sidebar-link flex items-center px-4 py-2.5 rounded-lg hover:bg-gray-700/50 transition-all duration-200 {{ request()->routeIs('admin.staff.*') ? 'bg-gray-700/70 text-lime-400 border-r-2 border-lime-500' : 'text-gray-300' }} mb-1">
                        <i class="fas fa-users w-5 h-5 {{ request()->routeIs('admin.staff.*') ? 'text-lime-400' : 'text-gray-500' }}"></i>
                        <span class="sidebar-link-text text-sm font-medium ml-3">Personale</span>
                    </a>
                    
                    <!-- Mezzi -->
                    <a href="{{ route('admin.vehicles.index') }}" 
                       class="sidebar-link flex items-center px-4 py-2.5 rounded-lg hover:bg-gray-700/50 transition-all duration-200 {{ request()->routeIs('admin.vehicles.*') ? 'bg-gray-700/70 text-lime-400 border-r-2 border-lime-500' : 'text-gray-300' }} mb-1">
                        <i class="fas fa-truck w-5 h-5 {{ request()->routeIs('admin.vehicles.*') ? 'text-lime-400' : 'text-gray-500' }}"></i>
                        <span class="sidebar-link-text text-sm font-medium ml-3">Mezzi</span>
                    </a>

                    <!-- Centri di Costo -->
                    <a href="#" 
                       class="sidebar-link flex items-center px-4 py-2.5 rounded-lg hover:bg-gray-700/50 transition-all duration-200 {{ request()->routeIs('admin.cost_centers.*') ? 'bg-gray-700/70 text-lime-400 border-r-2 border-lime-500' : 'text-gray-300' }} mb-1">
                        <i class="fa-solid fa-scale-unbalanced w-5 h-5 {{ request()->routeIs('admin.cost_centers.*') ? 'text-lime-400' : 'text-gray-500' }}"></i>
                        <span class="sidebar-link-text text-sm font-medium ml-3">Centri di Costo</span>
                    </a>
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
                                // Clienti / Fornitori
                                elseif (str_starts_with($currentRoute, 'admin.entities.')) {
                                    $breadcrumbs[] = ['name' => 'Anagrafica', 'url' => null, 'clickable' => false];
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
                                    $breadcrumbs[] = ['name' => 'Anagrafica', 'url' => null, 'clickable' => false];
                                    $breadcrumbs[] = ['name' => 'Personale', 'url' => route('admin.staff.index'), 'clickable' => true];
                                    
                                    if ($currentRoute === 'admin.staff.show' || $currentRoute === 'admin.staff.edit') {
                                        $staff = $currentParams['staff'] ?? null;
                                        if ($staff) {
                                            $breadcrumbs[] = ['name' => $staff->full_name, 'url' => null, 'clickable' => false];
                                        }
                                        if ($currentRoute === 'admin.staff.edit') {
                                            $breadcrumbs[] = ['name' => 'Modifica', 'url' => null, 'clickable' => false];
                                        }
                                    } elseif ($currentRoute === 'admin.staff.create') {
                                        $breadcrumbs[] = ['name' => 'Nuovo', 'url' => null, 'clickable' => false];
                                    }
                                }
                                // Mezzi (Vehicles)
                                elseif (str_starts_with($currentRoute, 'admin.vehicles.')) {
                                    $breadcrumbs[] = ['name' => 'Anagrafica', 'url' => null, 'clickable' => false];
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
                                    }
                                }
                                // Scadenze
                                elseif (str_starts_with($currentRoute, 'admin.expiration.')) {
                                    // Controlla se è per i mezzi (vehicle)
                                    if (request()->has('entityType') && request()->get('entityType') === 'vehicle') {
                                        $entityId = request()->get('entityId');
                                        $breadcrumbs[] = ['name' => 'Anagrafica', 'url' => null, 'clickable' => false];
                                        $breadcrumbs[] = ['name' => 'Mezzi', 'url' => route('admin.vehicles.index'), 'clickable' => true];
                                        
                                        if ($entityId) {
                                            $vehicle = \App\Models\Vehicles::find($entityId);
                                            if ($vehicle) {
                                                $vehicleName = $vehicle->full_name ?? $vehicle->targa ?? 'Mezzo';
                                                $breadcrumbs[] = ['name' => $vehicleName, 'url' => null, 'clickable' => false];
                                            }
                                        }
                                        $breadcrumbs[] = ['name' => 'Scadenze', 'url' => null, 'clickable' => false];
                                    }
                                    // Controlla se è per il personale (staff)
                                    elseif (request()->has('staff_id') && request()->get('staff_id')) {
                                        $staffId = request()->get('staff_id');
                                        $breadcrumbs[] = ['name' => 'Anagrafica', 'url' => null, 'clickable' => false];
                                        $breadcrumbs[] = ['name' => 'Personale', 'url' => route('admin.staff.index'), 'clickable' => true];
                                        
                                        $staff = \App\Models\Staff::find($staffId);
                                        if ($staff) {
                                            $breadcrumbs[] = ['name' => $staff->full_name, 'url' => null, 'clickable' => false];
                                        }
                                        $breadcrumbs[] = ['name' => 'Scadenze', 'url' => null, 'clickable' => false];
                                    }
                                    // Default (se non ci sono parametri)
                                    else {
                                        $breadcrumbs[] = ['name' => 'Personale', 'url' => route('admin.staff.index'), 'clickable' => true];
                                        $breadcrumbs[] = ['name' => 'Scadenze', 'url' => null, 'clickable' => false];
                                    }
                                    
                                    if ($currentRoute === 'admin.expiration.edit') {
                                        $expiration = $currentParams['id'] ?? null;
                                        if ($expiration && is_object($expiration)) {
                                            $breadcrumbs[] = ['name' => 'Modifica: ' . $expiration->titolo, 'url' => null, 'clickable' => false];
                                        } else {
                                            $breadcrumbs[] = ['name' => 'Modifica', 'url' => null, 'clickable' => false];
                                        }
                                    } elseif ($currentRoute === 'admin.expiration.create') {
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
                                            $breadcrumbs[] = ['name' => 'Anagrafica', 'url' => null, 'clickable' => false];
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
                                                $breadcrumbs[] = ['name' => 'Anagrafica', 'url' => null, 'clickable' => false];
                                                $breadcrumbs[] = ['name' => 'Personale', 'url' => route('admin.staff.index'), 'clickable' => true];
                                                $breadcrumbs[] = ['name' => $staff->full_name, 'url' => route('admin.staff.show', $staff->id_personale), 'clickable' => true];
                                                $breadcrumbs[] = ['name' => 'Scadenze', 'url' => route('admin.expiration-staff.index', ['staffId' => $staff->id_personale]), 'clickable' => true];
                                                $breadcrumbs[] = ['name' => 'Documenti', 'url' => null, 'clickable' => false];
                                            }
                                        }
                                    }
                                    elseif ($tableRef === 'expiration-vehicles') {
                                        $expiration = \App\Models\Expiration::find($idRef);
                                        if ($expiration && $expiration->vehicles()->count() > 0) {
                                            $firstVehicle = $expiration->vehicles->first();
                                            if ($firstVehicle) {
                                                $breadcrumbs[] = ['name' => 'Anagrafica', 'url' => null, 'clickable' => false];
                                                $breadcrumbs[] = ['name' => 'Mezzi', 'url' => route('admin.vehicles.index'), 'clickable' => true];
                                                $breadcrumbs[] = ['name' => $firstVehicle->full_name ?? $firstVehicle->targa, 'url' => route('admin.vehicles.show', $firstVehicle->id), 'clickable' => true];
                                                $breadcrumbs[] = ['name' => 'Scadenze', 'url' => route('admin.expiration-vehicle.index', ['vehicleId' => $firstVehicle->id]), 'clickable' => true];
                                                $breadcrumbs[] = ['name' => 'Documenti', 'url' => null, 'clickable' => false];
                                            }
                                        }
                                    }
                                    elseif ($tableRef === 'vehicles') {
                                        $vehicle = \App\Models\Vehicles::find($idRef);
                                        if ($vehicle) {
                                            $breadcrumbs[] = ['name' => 'Anagrafica', 'url' => null, 'clickable' => false];
                                            $breadcrumbs[] = ['name' => 'Mezzi', 'url' => route('admin.vehicles.index'), 'clickable' => true];
                                            $breadcrumbs[] = ['name' => $vehicle->full_name ?? $vehicle->targa, 'url' => route('admin.vehicles.show', $idRef), 'clickable' => true];
                                            $breadcrumbs[] = ['name' => 'Documenti', 'url' => null, 'clickable' => false];
                                        }
                                    }
                                }
                                // Impostazioni
                                elseif (str_starts_with($currentRoute, 'admin.settings.')) {
                                    $breadcrumbs[] = ['name' => 'Impostazioni', 'url' => null, 'clickable' => false];
                                    $breadcrumbs[] = ['name' => 'Settings', 'url' => route('admin.settings.index'), 'clickable' => true];
                                    
                                    if ($currentRoute === 'admin.settings.categories.index') {
                                        $breadcrumbs[] = ['name' => 'Categorie', 'url' => null, 'clickable' => false];
                                    } elseif ($currentRoute === 'admin.settings.categories.create') {
                                        $breadcrumbs[] = ['name' => 'Categorie', 'url' => route('admin.settings.categories.index'), 'clickable' => true];
                                        $breadcrumbs[] = ['name' => 'Nuova Categoria', 'url' => null, 'clickable' => false];
                                    } elseif ($currentRoute === 'admin.settings.categories.edit') {
                                        $breadcrumbs[] = ['name' => 'Categorie', 'url' => route('admin.settings.categories.index'), 'clickable' => true];
                                        $breadcrumbs[] = ['name' => 'Modifica Categoria', 'url' => null, 'clickable' => false];
                                    } elseif ($currentRoute === 'admin.settings.edit') {
                                        $breadcrumbs[] = ['name' => 'Modifica Impostazione', 'url' => null, 'clickable' => false];
                                    } elseif ($currentRoute === 'admin.settings.create') {
                                        $breadcrumbs[] = ['name' => 'Nuova Impostazione', 'url' => null, 'clickable' => false];
                                    }
                                }
                                // Servizi
                                elseif (str_starts_with($currentRoute, 'admin.services.')) {
                                    $breadcrumbs[] = ['name' => 'Servizi', 'url' => route('admin.services.index'), 'clickable' => true];
                                    
                                    if ($currentRoute === 'admin.services.create') {
                                        $breadcrumbs[] = ['name' => 'Nuovo Servizio', 'url' => null, 'clickable' => false];
                                    } elseif ($currentRoute === 'admin.services.edit') {
                                        $breadcrumbs[] = ['name' => 'Modifica Servizio', 'url' => null, 'clickable' => false];
                                    } elseif ($currentRoute === 'admin.services.show') {
                                        $breadcrumbs[] = ['name' => 'Dettaglio Servizio', 'url' => null, 'clickable' => false];
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
        // Dropdown menu functionality
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
            
            // Sidebar hover effect è gestito completamente da CSS
            // Non serve più JavaScript per il toggle
        });
        
        // Tooltip auto-initialization
        document.querySelectorAll('[data-tooltip]').forEach(element => {
            if (element.classList.contains('disabled-link')) {
                element.setAttribute('data-tooltip', 'Non hai i permessi necessari');
            }
        });
        
        // Tab functionality helper
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

        // Livewire Event Handlers
        document.addEventListener('livewire:init', function () {
            // Redirect to edit page
            Livewire.on('redirectToEdit', ({ id }) => {
                window.location.href = '/admin/entities/' + id + '/edit';
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
        });

        // Toast notification function
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
                </div>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.5s ease';
                setTimeout(() => {
                    if (toast && toast.remove) {
                        toast.remove();
                    }
                }, 500);
            }, 5000);
        }
    </script>
    
    @stack('scripts')
</body>
</html>