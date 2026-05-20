@php
    $btnBase = 'rounded-lg shadow-md hover:shadow-lg transition-all duration-200 flex items-center gap-2 font-medium';
    $btnSize = $size === 'small' ? 'px-3 py-1.5 text-sm' : 'px-5 py-2.5';
@endphp

<div class="flex items-center gap-3">
    @if($pdfUrl)
    <a href="{{ $pdfUrl }}"
       class="{{ $btnBase }} {{ $btnSize }} bg-red-600 hover:bg-red-700 text-white">
        <i class="fas fa-file-pdf {{ $size === 'small' ? 'text-sm' : 'text-xl' }}"></i>
        <span>{{ $size === 'small' ? 'PDF' : 'Esporta PDF' }}</span>
    </a>
    @endif

    @if($excelUrl)
    <a href="{{ $excelUrl }}"
       class="{{ $btnBase }} {{ $btnSize }} bg-green-600 hover:bg-green-700 text-white">
        <i class="fas fa-file-excel {{ $size === 'small' ? 'text-sm' : 'text-xl' }}"></i>
        <span>{{ $size === 'small' ? 'Excel' : 'Esporta Excel' }}</span>
    </a>
    @endif
</div>