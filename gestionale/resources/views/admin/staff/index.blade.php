@extends('admin.layouts.app')

@section('title', 'Gestione Personale')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-users mr-2 text-lime-600"></i> Gestione Personale
        </h1>
        
        @if(auth()->guard('admin')->user()->hasPermission('create_staff'))
        <button onclick="Livewire.dispatch('openCreateModal')"
                class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-5 py-2.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
            <i class="fas fa-plus mr-2"></i> Nuovo Personale
        </button>
        @endif
    </div>

    <!-- Componente Livewire -->
    @livewire('admin.staff-table')
</div>

<script>
    // Event listener per i messaggi di successo/errore
    document.addEventListener('livewire:init', () => {
        Livewire.on('showSuccess', ({ message }) => {
            showNotification(message, 'success');
        });
        
        Livewire.on('showError', ({ message }) => {
            showNotification(message, 'error');
        });
    });
    
    function showNotification(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg text-white ${type === 'success' ? 'bg-lime-500' : 'bg-red-500'} toast-notification`;
        toast.innerHTML = `
            <div class="flex items-center">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-2"></i>
                <span>${message}</span>
            </div>
        `;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.5s ease';
            setTimeout(() => toast.remove(), 500);
        }, 3000);
    }
</script>

<style>
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
</style>
@endsection