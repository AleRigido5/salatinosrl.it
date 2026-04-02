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
    <style>
        body { font-family: 'Inter', sans-serif; }
        .disabled-link {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }
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
            background: #333;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            white-space: nowrap;
            display: none;
            z-index: 1000;
        }
        [data-tooltip]:hover:before {
            display: block;
        }
        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 0.5rem;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            min-width: 240px;
            z-index: 50;
        }
        .dropdown-menu.show {
            display: block;
        }
        .dropdown-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #374151;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        .dropdown-item:hover {
            background-color: #F3F4F6;
            border-left-color: #3B82F6;
        }
        .dropdown-item i {
            width: 1.25rem;
            margin-right: 0.75rem;
            color: #6B7280;
        }
        .dropdown-item:hover i {
            color: #3B82F6;
        }
        .dropdown-divider {
            height: 1px;
            background-color: #E5E7EB;
            margin: 0.25rem 0;
        }
        .dropdown-header {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #E5E7EB;
        }
    </style>
</head>
<body class="bg-gray-100">
    @php
        $currentAdmin = Auth::guard('admin')->user();
    @endphp
    
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-900 text-white flex flex-col">
            <div class="p-6 border-b border-gray-800">
                <h1 class="text-2xl font-bold">AdminLTE</h1>
                <p class="text-sm text-gray-400 mt-1">Pannello di Controllo</p>
            </div>
            
            <nav class="flex-1 mt-6 overflow-y-auto">
                <!-- Dashboard -->
                @if($currentAdmin && $currentAdmin->hasPermission('access_dashboard'))
                <a href="{{ route('admin.dashboard') }}" 
                   class="flex items-center px-6 py-3 hover:bg-gray-800 transition {{ request()->routeIs('admin.dashboard') ? 'bg-gray-800 border-l-4 border-blue-500' : '' }}">
                    <i class="fas fa-tachometer-alt w-5 h-5 mr-3"></i>
                    <span>Dashboard</span>
                </a>
                @endif
                
                <!-- Amministratori -->
                @if($currentAdmin && $currentAdmin->hasPermission('view_administrators'))
                <div class="mt-4">
                    <div class="px-6 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        Amministratori
                    </div>
                    <a href="#" class="flex items-center px-6 py-3 hover:bg-gray-800 transition">
                        <i class="fas fa-users w-5 h-5 mr-3"></i>
                        <span>Lista Admin</span>
                    </a>
                </div>
                @endif
                
                <!-- Ruoli e Permessi -->
                @if($currentAdmin && $currentAdmin->hasPermission('view_roles'))
                <div class="mt-4">
                    <div class="px-6 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        Sicurezza
                    </div>
                    <a href="{{ route('admin.roles.index') }}" 
                       class="flex items-center px-6 py-3 hover:bg-gray-800 transition {{ request()->routeIs('admin.roles.*') ? 'bg-gray-800 border-l-4 border-blue-500' : '' }}">
                        <i class="fas fa-shield-alt w-5 h-5 mr-3"></i>
                        <span>Ruoli & Permessi</span>
                    </a>
                </div>
                @endif
                
                <!-- Utenti -->
                @if($currentAdmin && $currentAdmin->hasPermission('view_users'))
                <div class="mt-4">
                    <div class="px-6 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        Gestione
                    </div>
                    <a href="{{ route('admin.users.index') }}" 
                       class="flex items-center px-6 py-3 hover:bg-gray-800 transition {{ request()->routeIs('admin.users.*') ? 'bg-gray-800 border-l-4 border-blue-500' : '' }}">
                        <i class="fas fa-user w-5 h-5 mr-3"></i>
                        <span>Utenti</span>
                    </a>
                </div>
                @endif
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <!-- Header con avatar e dropdown -->
            <header class="bg-white shadow-sm sticky top-0 z-10">
                <div class="flex justify-end items-center px-8 py-4">
                    <!-- Avatar e Dropdown -->
                    <div class="relative">
                        <button id="userMenuButton" class="flex items-center space-x-3 focus:outline-none hover:bg-gray-50 rounded-lg px-3 py-2 transition">
                            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold">
                                {{ strtoupper(substr($currentAdmin->name ?? 'A', 0, 1)) }}
                            </div>
                            <div class="text-left">
                                <p class="font-medium text-gray-700">{{ $currentAdmin->name ?? 'Admin' }}</p>
                                <p class="text-gray-500 text-xs">{{ $currentAdmin->role->name ?? 'Nessun ruolo' }}</p>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 text-sm transition-transform duration-200" id="userMenuChevron"></i>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div id="userDropdown" class="dropdown-menu">
                            <div class="dropdown-header">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold">
                                        {{ strtoupper(substr($currentAdmin->name ?? 'A', 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $currentAdmin->name ?? 'Admin' }}</p>
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
            </header>

            <main>
                @if(session('success'))
                    <div class="m-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded">
                        <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="m-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded">
                        <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script>
        // Dropdown menu functionality
        document.addEventListener('DOMContentLoaded', function() {
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
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (userMenuButton && !userMenuButton.contains(e.target)) {
                    userDropdown.classList.remove('show');
                    if (userMenuChevron) {
                        userMenuChevron.style.transform = 'rotate(0deg)';
                    }
                }
            });
        });
    </script>
</body>
</html>