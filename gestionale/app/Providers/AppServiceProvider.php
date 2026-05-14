<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use App\Livewire\Admin\InvoicesReceivedTable;
use App\Livewire\Admin\InvoicesXmlImport;
use App\Livewire\Components\DateRangeFilter;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Registra il componente StaffTable
        Livewire::component('admin.staff.staff-table', \App\Livewire\Admin\StaffTable::class);
        Livewire::component('admin.invoices-received-table', InvoicesReceivedTable::class);
        Livewire::component('admin.invoices-xml-import', InvoicesXmlImport::class);
        Livewire::component('components.date-range-filter', DateRangeFilter::class);

        Blade::directive('unescape', function ($expression) {
            return "<?php echo html_entity_decode($expression, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>";
        });
    }
}