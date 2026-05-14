<div>
    <div class="flex flex-wrap items-center justify-between gap-3">

        <!-- SINISTRA: comandano tutto -->
        <div class="flex items-center gap-3">

            <button type="button"
                wire:click="previousMonth"
                class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 rounded-md transition-colors"
                title="Mese precedente">
                <i class="fas fa-chevron-left"></i>
            </button>

            <button type="button"
                wire:click="nextMonth"
                class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 rounded-md transition-colors"
                title="Mese successivo">
                <i class="fas fa-chevron-right"></i>
            </button>

            <div class="flex items-center gap-2">
                <select wire:model.live="selectedMonth"
                    class="px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-lime-500">
                    @foreach($months as $num => $name)
                        <option value="{{ $num }}">{{ $name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="selectedYear"
                    class="px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-lime-500">
                    @foreach($years as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- DESTRA: valore scritto direttamente da PHP, niente wire:model -->
        <div class="flex items-center gap-3">

            <input type="date"
                value="{{ $dateFrom }}"
                readonly
                class="text-sm px-3 py-1.5 border border-gray-300 rounded-md bg-gray-50 text-gray-600 cursor-default focus:outline-none">

            <span class="text-gray-400">
                <i class="fas fa-arrow-right text-xs"></i>
            </span>

            <input type="date"
                value="{{ $dateTo }}"
                readonly
                class="text-sm px-3 py-1.5 border border-gray-300 rounded-md bg-gray-50 text-gray-600 cursor-default focus:outline-none">

            <button type="button"
                wire:click="applyFilters"
                wire:loading.attr="disabled"
                wire:target="applyFilters"
                class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-5 py-1.5 rounded-lg shadow-md hover:shadow-lg hover:from-lime-600 hover:to-lime-700 transition-all duration-200 disabled:opacity-60">
                <span wire:loading.remove wire:target="applyFilters">Applica</span>
                <span wire:loading wire:target="applyFilters">
                    <i class="fas fa-spinner fa-spin text-sm"></i>
                </span>
            </button>

        </div>
    </div>
</div>