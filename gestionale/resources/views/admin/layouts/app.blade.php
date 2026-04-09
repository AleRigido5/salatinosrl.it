<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Panel - @yield('title')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
            background: #10b981;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #059669;
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
            background-color: #f0fdf4;
            border-left-color: #10b981;
        }
        .dropdown-item i {
            width: 1.25rem;
            margin-right: 0.75rem;
            color: #6b7280;
            font-size: 1rem;
        }
        .dropdown-item:hover i {
            color: #10b981;
        }
        .dropdown-divider {
            height: 1px;
            background: linear-gradient(to right, #e5e7eb, #10b981, #e5e7eb);
            margin: 0.25rem 0;
        }
        .dropdown-header {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
        }
        
        /* Transizioni */
        .sidebar-link {
            transition: all 0.2s ease;
        }
        .sidebar-link:hover {
            padding-left: 1.75rem;
        }
        
        /* Badge personalizzati */
        .badge-success {
            background: linear-gradient(135deg, #10b981, #059669);
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
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100">
    @php
        $currentAdmin = Auth::guard('admin')->user();
    @endphp
    
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 bg-gradient-to-b from-gray-900 via-gray-800 to-gray-900 text-white flex flex-col shadow-xl">
            <!-- Logo -->
            <div class="p-6 border-b border-gray-700/50">
                <div class="flex items-center space-x-2">
                    <h1 class="text-xl font-bold bg-gradient-to-r from-emerald-400 to-green-400 bg-clip-text text-transparent">Gestionale Salatino</h1>
                </div>
                <p class="text-xs text-gray-500 mt-2">Pannello di Controllo</p>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 mt-6 overflow-y-auto px-3">
                <!-- Dashboard -->
                @if($currentAdmin && $currentAdmin->hasPermission('access_dashboard'))
                <div class="mb-6">
                    <div class="px-4 py-2 text-xs font-semibold text-emerald-400 uppercase tracking-wider">
                        <i class="fas fa-circle mr-1 text-[8px]"></i> Navigazione
                    </div>
                    <a href="{{ route('admin.dashboard') }}" 
                       class="sidebar-link flex items-center px-4 py-2.5 rounded-lg hover:bg-gray-700/50 transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-gray-700/70 text-emerald-400 border-r-2 border-emerald-500' : 'text-gray-300' }} mb-1">
                        <i class="fas fa-tachometer-alt w-5 h-5 mr-3 {{ request()->routeIs('admin.dashboard') ? 'text-emerald-400' : 'text-gray-500' }}"></i>
                        <span class="text-sm font-medium">Dashboard</span>
                    </a>
                </div>
                @endif
                
                <!-- Anagrafica -->
                @if($currentAdmin && $currentAdmin->hasPermission('view_entities'))
                <div class="mb-6">
                    <div class="px-4 py-2 text-xs font-semibold text-emerald-400 uppercase tracking-wider">
                        <i class="fas fa-circle mr-1 text-[8px]"></i> Anagrafica
                    </div>
                    <a href="{{ route('admin.entities.index') }}" 
                       class="sidebar-link flex items-center px-4 py-2.5 rounded-lg hover:bg-gray-700/50 transition-all duration-200 {{ request()->routeIs('admin.entities.*') ? 'bg-gray-700/70 text-emerald-400 border-r-2 border-emerald-500' : 'text-gray-300' }} mb-1">
                        <i class="fas fa-building w-5 h-5 mr-3 {{ request()->routeIs('admin.entities.*') ? 'text-emerald-400' : 'text-gray-500' }}"></i>
                        <span class="text-sm font-medium">Clienti / Fornitori</span>
                    </a>
                </div>
                @endif
            </nav>
            
            <!-- Footer Sidebar -->
            <div class="p-4 border-t border-gray-700/50 mt-auto">
                <div class="text-center">
                    <p class="text-xs text-gray-500">Versione 1.0.0</p>
                    <p class="text-xs text-gray-600 mt-1">© {{ date('Y') }} Gestionale Salatino</p>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <!-- Header -->
            <header class="bg-white/80 backdrop-blur-sm shadow-sm sticky top-0 z-10 border-b border-gray-100">
                <div class="flex justify-between items-center px-8 py-3">
                    <!-- Left side - empty or page title -->
                    <div class="text-sm text-gray-500">
                        <i class="fas fa-home text-emerald-500 mr-1"></i> 
                        @yield('title', 'Dashboard')
                    </div>
                    
                    <!-- Right side - Avatar e Dropdown -->
                    <div class="flex items-center space-x-4">
                        <!-- Settings Dropdown (Ingranaggio) -->
                        <div class="relative">
                            <button id="settingsMenuButton" class="settings-btn text-gray-500 hover:text-emerald-600 focus:outline-none transition-all duration-200">
                                <i class="fas fa-cog text-xl"></i>
                            </button>
                            
                            <!-- Settings Dropdown Menu -->
                            <div id="settingsDropdown" class="dropdown-menu" style="min-width: 220px; right: 0; left: auto;">
                                <div class="dropdown-header">
                                    <div class="flex items-center space-x-3">
                                        <i class="fas fa-sliders-h text-emerald-500 text-lg"></i>
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
                                <a href="{{ route('admin.settings.index') }}" class="dropdown-item">
                                    <i class="fas fa-cog"></i>
                                    <span>Settings</span>
                                </a>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Avatar e Dropdown Profilo -->
                        <div class="relative">
                            <button id="userMenuButton" class="flex items-center space-x-3 focus:outline-none hover:bg-gray-50 rounded-xl px-3 py-2 transition-all duration-200 group">
                                <div class="w-10 h-10 bg-gradient-to-r from-emerald-500 to-green-500 rounded-full flex items-center justify-center text-white font-semibold shadow-md group-hover:shadow-lg transition">
                                    {{ strtoupper(substr($currentAdmin->name ?? 'A', 0, 1)) }}
                                </div>
                                <div class="text-left">
                                    <p class="font-semibold text-gray-800 text-sm">{{ $currentAdmin->name ?? 'Admin' }}</p>
                                    <p class="text-gray-500 text-xs">
                                        <i class="fas {{ $currentAdmin->role && $currentAdmin->role->slug == 'super_admin' ? 'fa-lock text-gray-800' : 'fa-shield-alt' }} mr-1"></i>
                                        {{ $currentAdmin->role->name ?? 'Nessun ruolo' }}
                                    </p>
                                </div>
                                <i class="fas fa-chevron-down text-gray-400 text-xs transition-transform duration-200 group-hover:text-emerald-500" id="userMenuChevron"></i>
                            </button>
                            
                            <!-- Dropdown Menu Profilo -->
                            <div id="userDropdown" class="dropdown-menu">
                                <div class="dropdown-header">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-12 h-12 bg-gradient-to-r from-emerald-500 to-green-500 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-md">
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
                    <div class="mb-6 p-4 bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 text-green-700 rounded-lg shadow-sm animate-pulse">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 text-lg mr-3"></i>
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
        });
        
        // Tooltip auto-initialization
        document.querySelectorAll('[data-tooltip]').forEach(element => {
            if (element.classList.contains('disabled-link')) {
                element.setAttribute('data-tooltip', 'Non hai i permessi necessari');
            }
        });
    </script>
    
    @stack('scripts')
</body>
</html>