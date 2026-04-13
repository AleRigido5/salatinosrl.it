import './bootstrap';

import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

// Importa Alpine
import Alpine from 'alpinejs';

// Rendi Alpine disponibile globalmente
window.Alpine = Alpine;

// Avvia Livewire
Livewire.start();

// Inizializza Alpine
Alpine.start();
