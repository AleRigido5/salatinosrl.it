@extends('admin.layouts.app')

@section('title', 'Attività')

@section('content')
<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-tasks text-lime-500 mr-2"></i> Attività
        </h1>
        @if(auth()->guard('admin')->user()->hasPermission('create_activities'))
        <div class="relative group">
            <a href="{{ route('admin.activities.create') }}"
                    class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-5 py-2.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                <i class="fas fa-plus"></i>
            </a>
            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                Nuova Attività
                <div class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1 border-4 border-transparent border-t-gray-800"></div>
            </div>
        </div>
        @endif
    </div>
    
    @livewire('admin.activities-table')
</div>
@endsection