<?php

namespace App\View\Components;

use Illuminate\View\Component;

class ExportButtons extends Component
{
    public function __construct(
        public ?string $pdfUrl = null,
        public ?string $excelUrl = null,
        public string $size = 'normal',
    ) {}

    public function render()
    {
        return view('components.export-buttons');
    }
}