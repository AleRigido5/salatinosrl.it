import './bootstrap';

import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

// Importa Alpine
import Alpine from 'alpinejs';

// Rendi Alpine disponibile globalmente
window.Alpine = Alpine;

// Avvia Livewire (che inizializzerà anche Alpine)
Livewire.start();

// Se hai componenti Alpine personali, inizializzali dopo
document.addEventListener('alpine:init', () => {
    // I tuoi componenti Alpine qui
});
