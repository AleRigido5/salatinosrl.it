@props(['model', 'showIcon' => true, 'showLabel' => true, 'class' => ''])

@php
    $trackingInfo = $model->trackingInfo ?? [];
@endphp

@if(!empty($trackingInfo))
<div {{ $attributes->merge(['class' => 'text-xs text-gray-500 space-y-1 ' . $class]) }}>
    {{-- CREAZIONE --}}
    @if(isset($trackingInfo['created']))
    <div class="flex items-center gap-1.5">
        @if($showIcon)
        <svg class="w-3.5 h-3.5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        @endif
        @if($showLabel)
        <span class="font-medium text-gray-600">Inserito:</span>
        @endif
        <span class="text-gray-700">{{ $trackingInfo['created']['by'] }}</span>
        <span class="text-gray-400">•</span>
        <span class="text-gray-500">{{ $trackingInfo['created']['at'] }}</span>
    </div>
    @endif
    
    {{-- MODIFICA --}}
    @if(isset($trackingInfo['updated']))
    <div class="flex items-center gap-1.5">
        @if($showIcon)
        <svg class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
        </svg>
        @endif
        @if($showLabel)
        <span class="font-medium text-gray-600">Modificato:</span>
        @endif
        <span class="text-gray-700">{{ $trackingInfo['updated']['by'] }}</span>
        <span class="text-gray-400">•</span>
        <span class="text-gray-500">{{ $trackingInfo['updated']['at'] }}</span>
    </div>
    @endif
</div>
@endif