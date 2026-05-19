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
                    wire:key="month-select-{{ $selectedMonth }}-{{ $selectedYear }}"
                    class="px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-lime-500">
                    @foreach($months as $num => $name)
                        <option value="{{ $num }}" {{ $selectedMonth == $num ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>

                <!-- ANNO: campo non editabile che si aggiorna con le frecce -->
                <input type="text" 
                    readonly
                    value="{{ $selectedYear }}"
                    wire:key="year-input-{{ $selectedYear }}"
                    class="px-3 py-1.5 border border-gray-300 rounded-md text-sm bg-gray-50 text-gray-700 w-20 text-center cursor-default focus:outline-none">
            </div>
        </div>

        <!-- CENTRO: Data singola e Stagione (label affiancate) -->
        <div class="flex items-center gap-4">
            <!-- Data singola -->
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700">Data</label>
                <input type="date"
                    wire:model.live="singleDate"
                    class="text-sm px-3 py-1.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
            </div>

            <!-- Stagione (select con ultimi 10 anni) -->
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700">Stagione</label>
                <select wire:model.live="selectedSeason"
                    class="text-sm px-3 py-1.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                    <option value="">Seleziona anno</option>
                    @foreach($seasonYears as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- DESTRA: valore scritto direttamente da PHP, niente wire:model -->
        <div class="flex items-center gap-3">

            <input type="date"
                value="{{ $dateFrom }}"
                class="text-sm px-3 py-1.5 border border-gray-300 rounded-md bg-gray-50 text-gray-600 cursor-default focus:outline-none">

            <span class="text-gray-400">
                <i class="fas fa-arrow-right text-xs"></i>
            </span>

            <input type="date"
                value="{{ $dateTo }}"
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

@push('scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        // Forza l'aggiornamento della select quando cambia il mese/anno
        Livewire.on('monthYearUpdated', (data) => {
            const select = document.querySelector('select[wire\\:model\\:live="selectedMonth"]');
            if (select && select.value != data.month) {
                select.value = data.month;
                // Trigger manuale dell'evento change
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    });
</script>
@endpush