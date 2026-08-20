<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fa-solid fa-bell mr-3 text-lime-600"></i>
            Gestione Task Amministrativi
        </h1>
        <button wire:click="openCreateModal" class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-4 py-2 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
            <i class="fas fa-plus mr-2"></i> Nuovo Task
        </button>
    </div>

    <!-- Card Filtri -->
    <div class="bg-white rounded-lg shadow p-4 mb-4 border border-gray-200">

        <!-- Barra Data -->
        <div class="mb-4 pb-4 border-b border-gray-200">
            <div class="flex justify-end gap-6 mb-2">
                <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 cursor-pointer">
                    <input type="radio" wire:model.live="dateFilterMode" value="task_date"
                        class="text-lime-600 focus:ring-lime-500">
                    Data Task Amministrativo
                </label>
                <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 cursor-pointer">
                    <input type="radio" wire:model.live="dateFilterMode" value="due_date"
                        class="text-lime-600 focus:ring-lime-500">
                    Scadenza Task Amministrativo
                </label>
            </div>
            @livewire('components.date-range-filter', ['dateFrom' => $dateFrom, 'dateTo' => $dateTo], key('admin-tasks-date-filter-' . $dateFrom . $dateTo))
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Cerca</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Titolo, n.pratica, descrizione..."
                        class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Categoria</label>
                <select wire:model.live="categoryFilter" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md">
                    <option value="">Tutte</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat['id'] }}">{{ $cat['valore'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Stato</label>
                <select wire:model.live="statusFilter" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md">
                    <option value="">Tutti</option>
                    @foreach($statuses as $key => $s)
                        <option value="{{ $key }}">{{ $s['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Priorità</label>
                <select wire:model.live="priorityFilter" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md">
                    <option value="">Tutte</option>
                    @foreach($priorityColors as $level => $p)
                        <option value="{{ $level }}">{{ str_repeat('★', $level) }} {{ $p['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Tag</label>
                <input type="text" wire:model.live.debounce.300ms="tagFilter" placeholder="Cerca parola chiave..."
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md">
            </div>
        </div>
        @if($search || $categoryFilter || $statusFilter || $priorityFilter || $tagFilter)
        <div class="mt-3 pt-3 border-t border-gray-200 flex flex-wrap items-center gap-2">
            <button wire:click="clearFilters" class="text-xs text-gray-400 hover:text-red-500">
                <i class="fas fa-trash-alt mr-1"></i> Rimuovi filtri
            </button>
        </div>
        @endif
    </div>

    <!-- Tabella -->
    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500">Data</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500">Titolo</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500">Proprietà</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500">Categoria</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500">TAG</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500">Scadenza</th>
                        <th class="px-3 py-3 text-right text-xs font-medium text-gray-500">Importo</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500">Canale</th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500">Priorità</th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500">Stato</th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500">Allegati</th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500">Commenti</th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($tasks as $task)
                    @php $overdue = $task->is_overdue; @endphp
                    <tr class="hover:bg-gray-50 {{ $overdue ? 'bg-red-50' : '' }}" wire:key="task-{{ $task->id }}">
                        <td class="px-3 py-3 text-sm whitespace-nowrap">{{ $task->task_date?->format('d/m/Y') }}</td>
                        <td class="px-3 py-3 text-sm font-medium text-gray-900 max-w-xs truncate" title="{{ $task->title }}">
                            <button wire:click="openDetailModal({{ $task->id }})" class="hover:text-lime-600 hover:underline text-left">
                                {{ $task->title }}
                            </button>
                        </td>
                        <td class="px-3 py-3 text-sm">{{ $task->ownership->RagAbbrev ?? $task->ownership->Rag_Soc_intest ?? '-' }}</td>
                        <td class="px-3 py-3 text-sm">
                            @if($task->category)
                                <span class="inline-flex px-2 py-0.5 rounded text-xs bg-blue-50 text-blue-700 font-medium">
                                    {{ $task->category->valore }}
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-sm">
                            <div class="flex flex-wrap gap-1 max-w-[180px]">
                                @forelse($task->tags as $tag)
                                    <span class="inline-flex px-1.5 py-0.5 rounded text-xs bg-gray-100 text-gray-700">{{ $tag->name }}</span>
                                @empty
                                    <span class="text-gray-400">-</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-3 py-3 text-sm whitespace-nowrap {{ $overdue ? 'text-red-600 font-bold' : '' }}">
                            {{ $task->due_date?->format('d/m/Y') ?? '-' }}
                            @if($overdue) <i class="fas fa-exclamation-triangle text-red-500 ml-1" title="Scaduto"></i> @endif
                        </td>
                        <td class="px-3 py-3 text-sm text-right whitespace-nowrap">
                            {{ $task->amount !== null ? number_format((float) $task->amount, 2, ',', '.') . ' €' : '-' }}
                        </td>
                        <td class="px-3 py-3 text-sm">{{ $task->channel ?: '-' }}</td>
                        <td class="px-3 py-3 text-center whitespace-nowrap">
                            <span style="color: {{ $priorityColors[$task->priority]['color'] ?? '#84cc16' }}" title="{{ $priorityColors[$task->priority]['label'] ?? '' }}">
                                {{ str_repeat('★', $task->priority) }}{{ str_repeat('☆', 5 - $task->priority) }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium {{ $task->status_badge_class }}">
                                {{ $task->status_label }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <a href="{{ route('admin.documents.index', ['tableRef' => 'admin_tasks', 'idRef' => $task->id]) }}" class="text-blue-600 hover:text-blue-900" title="Allegati">
                                <i class="fas fa-paperclip"></i> {{ $task->documents_count }}
                            </a>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <button wire:click="openCommentsModal({{ $task->id }})" class="relative inline-flex items-center justify-center text-blue-500 hover:text-blue-700" title="Commenti">
                                <i class="fa-solid fa-comment text-lg"></i>
                                @if($task->comments_count > 0)
                                <span class="absolute -top-2 -right-2 flex items-center justify-center w-4 h-4 text-[10px] font-bold text-white bg-red-500 rounded-full">{{ $task->comments_count }}</span>
                                @endif
                            </button>
                        </td>
                        <td class="px-3 py-3 text-center whitespace-nowrap">
                            <button wire:click="openDetailModal({{ $task->id }})" class="text-blue-600 hover:text-blue-900 mr-2" title="Dettaglio">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                            <button wire:click="openEditModal({{ $task->id }})" class="text-yellow-600 hover:text-yellow-900 mr-2" title="Modifica">
                                <i class="fas fa-pen-to-square"></i>
                            </button>
                            <button wire:click="confirmDelete({{ $task->id }})" class="text-red-600 hover:text-red-900" title="Elimina">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="13" class="text-center py-8 text-gray-500">
                            <i class="fa-solid fa-bell text-4xl mb-2 text-gray-300 block"></i>
                            Nessun task trovato
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($tasks->hasPages())
    <div class="mt-4">{{ $tasks->links() }}</div>
    @endif

    <!-- ==================== MODAL CREA/MODIFICA TASK ==================== -->
    @if($showFormModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50">
        <div class="flex items-start justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl my-8">
                <div class="flex justify-between items-center px-6 py-4 border-b sticky top-0 bg-white rounded-t-lg">
                    <h3 class="text-lg font-semibold text-gray-800">
                        {{ $editingId ? 'Modifica Task' : 'Nuovo Task' }}
                    </h3>
                    <button wire:click="closeFormModal" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-xl"></i></button>
                </div>

                <div class="px-6 py-4 space-y-4 max-h-[75vh] overflow-y-auto">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data <span class="text-red-500">*</span></label>
                            <input type="date" wire:model="task_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            @error('task_date') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Scadenza</label>
                            <input type="date" wire:model="due_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Canale</label>
                            <input type="text" wire:model="channel" placeholder="PEC / Email / Raccomandata..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                            <select wire:model="id_category" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                                <option value="">Nessuna</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat['id'] }}">{{ $cat['valore'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">N. Pratica</label>
                            <input type="text" wire:model="practice_number" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Importo €</label>
                            <input type="text" inputmode="decimal" wire:model="amount" placeholder="0,00"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            @error('amount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Stato</label>
                            <select wire:model="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                                @foreach($statuses as $key => $s)
                                    <option value="{{ $key }}">{{ $s['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Titolo <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="title" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        @error('title') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- DESCRIZIONE - TEXTAREA (NON SI PERDE MAI) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descrizione</label>
                        <textarea wire:model="description" 
                                  rows="6"
                                  placeholder="Inserisci la descrizione..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 text-sm resize-y"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Cliente/Fornitore -->
                        <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cliente / Fornitore</label>
                            <div class="relative">
                                <input type="text" wire:model.live.debounce.300ms="entitySearch"
                                    x-on:focus="open = true"
                                    placeholder="Cerca cliente/fornitore..."
                                    class="w-full pl-3 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                                @if($selectedEntityId)
                                <button type="button" wire:click="clearEntity" class="absolute right-2 top-2.5 text-gray-400 hover:text-red-500">
                                    <i class="fas fa-times-circle text-sm"></i>
                                </button>
                                @endif
                            </div>
                            <div x-show="open && @entangle('showEntityDropdown')" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto">
                                @forelse($entityResults as $item)
                                    <div x-on:click="open = false; @this.call('selectEntity', {{ $item->id }}, '{{ addslashes($item->name) }}')"
                                        class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                                        {{ $item->name }}
                                    </div>
                                @empty
                                    <div class="px-3 py-2 text-sm text-gray-500 text-center">Nessun risultato</div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Proprietà -->
                        <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Proprietà</label>
                            <div class="relative">
                                <input type="text" wire:model.live.debounce.300ms="ownershipSearch"
                                    x-on:focus="open = true"
                                    placeholder="Cerca proprietà..."
                                    class="w-full pl-3 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                                @if($selectedOwnershipId)
                                <button type="button" wire:click="clearOwnership" class="absolute right-2 top-2.5 text-gray-400 hover:text-red-500">
                                    <i class="fas fa-times-circle text-sm"></i>
                                </button>
                                @endif
                            </div>
                            <div x-show="open && @entangle('showOwnershipDropdown')" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto">
                                @forelse($ownershipResults as $item)
                                    <div x-on:click="open = false; @this.call('selectOwnership', {{ $item->id }}, '{{ addslashes($item->name) }}')"
                                        class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                                        {{ $item->name }}
                                    </div>
                                @empty
                                    <div class="px-3 py-2 text-sm text-gray-500 text-center">Nessun risultato</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Tag -->
                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Parole chiave (Tag)</label>
                        <div class="flex flex-wrap gap-1.5 mb-2">
                            @foreach($selectedTags as $tag)
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-lime-100 text-lime-800">
                                    {{ $tag }}
                                    <button type="button" wire:click="removeTag('{{ $tag }}')" class="hover:text-red-600"><i class="fas fa-times"></i></button>
                                </span>
                            @endforeach
                        </div>
                        <input type="text" wire:model.live.debounce.200ms="tagInput"
                            wire:keydown.enter.prevent="addTagFromInput"
                            placeholder="Scrivi e premi Invio per aggiungere..."
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        @if($tagSuggestions->isNotEmpty())
                        <div class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-40 overflow-y-auto">
                            @foreach($tagSuggestions as $suggestion)
                                <div wire:click="addTag('{{ addslashes($suggestion->name) }}')" class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm">
                                    {{ $suggestion->name }}
                                </div>
                            @endforeach
                        </div>
                        @endif
                    </div>

                    <!-- Priorità -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Priorità</label>
                        <div class="flex gap-2">
                            @foreach($priorityColors as $level => $p)
                                <button type="button" wire:click="$set('priority', {{ $level }})"
                                    class="px-3 py-1.5 rounded-md text-sm font-medium border-2 transition-all {{ $priority == $level ? 'text-white' : 'bg-white text-gray-600' }}"
                                    style="{{ $priority == $level ? 'background-color:' . $p['color'] . '; border-color:' . $p['color'] . ';' : 'border-color:#e5e7eb;' }}">
                                    {{ $level }} {{ str_repeat('★', $level) }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50 rounded-b-lg">
                    <button wire:click="closeFormModal" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">Annulla</button>
                    <button wire:click="save" class="px-4 py-2 bg-lime-600 hover:bg-lime-700 text-white rounded-md transition-colors">
                        <i class="fas fa-save mr-1"></i> Salva Task
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- ==================== MODAL DETTAGLIO ==================== -->
    @if($showDetailModal && $viewingTask)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50">
        <div class="flex items-start justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl my-8">
                <div class="flex justify-between items-center px-6 py-4 border-b">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">{{ $viewingTask->title }}</h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $viewingTask->category->valore ?? 'Nessuna categoria' }}
                            @if($viewingTask->practice_number) — Pratica n. {{ $viewingTask->practice_number }} @endif
                        </p>
                    </div>
                    <button wire:click="closeDetailModal" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-xl"></i></button>
                </div>

                <div class="px-6 py-4 space-y-4 max-h-[75vh] overflow-y-auto">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="bg-gray-50 p-2 rounded-lg">
                            <label class="text-xs text-gray-500 uppercase font-semibold">Data</label>
                            <p class="text-sm font-medium">{{ $viewingTask->task_date?->format('d/m/Y') }}</p>
                        </div>
                        <div class="bg-gray-50 p-2 rounded-lg">
                            <label class="text-xs text-gray-500 uppercase font-semibold">Scadenza</label>
                            <p class="text-sm font-medium {{ $viewingTask->is_overdue ? 'text-red-600' : '' }}">{{ $viewingTask->due_date?->format('d/m/Y') ?? '-' }}</p>
                        </div>
                        <div class="bg-gray-50 p-2 rounded-lg">
                            <label class="text-xs text-gray-500 uppercase font-semibold">Canale</label>
                            <p class="text-sm font-medium">{{ $viewingTask->channel ?: '-' }}</p>
                        </div>
                        <div class="bg-gray-50 p-2 rounded-lg">
                            <label class="text-xs text-gray-500 uppercase font-semibold">Priorità</label>
                            <p class="text-sm font-medium" style="color: {{ $viewingTask->priority_color }}">{{ str_repeat('★', $viewingTask->priority) }}</p>
                        </div>
                        <div class="bg-gray-50 p-2 rounded-lg">
                            <label class="text-xs text-gray-500 uppercase font-semibold">Cliente/Fornitore</label>
                            <p class="text-sm font-medium">{{ $viewingTask->entity->ragione_sociale ?? '-' }}</p>
                        </div>
                        <div class="bg-gray-50 p-2 rounded-lg">
                            <label class="text-xs text-gray-500 uppercase font-semibold">Proprietà</label>
                            <p class="text-sm font-medium">{{ $viewingTask->ownership->RagAbbrev ?? '-' }}</p>
                        </div>
                        <div class="bg-gray-50 p-2 rounded-lg">
                            <label class="text-xs text-gray-500 uppercase font-semibold">Importo</label>
                            <p class="text-sm font-medium">{{ $viewingTask->amount !== null ? number_format((float) $viewingTask->amount, 2, ',', '.') . ' €' : '-' }}</p>
                        </div>
                        <div class="bg-gray-50 p-2 rounded-lg col-span-2">
                            <label class="text-xs text-gray-500 uppercase font-semibold">Stato</label>
                            <div class="mt-1">
                                <select wire:change="quickChangeStatus({{ $viewingTask->id }}, $event.target.value)" class="text-sm border border-gray-300 rounded-md px-2 py-1">
                                    @foreach($statuses as $key => $s)
                                        <option value="{{ $key }}" {{ $viewingTask->status === $key ? 'selected' : '' }}>{{ $s['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    @if($viewingTask->tags->isNotEmpty())
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($viewingTask->tags as $tag)
                            <span class="inline-flex px-2 py-1 rounded-full text-xs bg-lime-100 text-lime-800">{{ $tag->name }}</span>
                        @endforeach
                    </div>
                    @endif

                    @if($viewingTask->description)
                    <div>
                        <label class="text-xs text-gray-500 uppercase font-semibold block mb-1">Descrizione</label>
                        <div class="prose prose-sm max-w-none text-gray-700 bg-gray-50 rounded-lg p-3">{{ nl2br(e($viewingTask->description)) }}</div>
                    </div>
                    @endif

                    <div class="text-xs text-gray-400 border-t pt-3">
                        Inserito da {{ $viewingTask->creator->name ?? 'Sistema' }} il {{ $viewingTask->created_at?->format('d/m/Y H:i') }}
                        @if($viewingTask->updated_at && $viewingTask->updated_at != $viewingTask->created_at)
                            — Ultima modifica di {{ $viewingTask->updater->name ?? 'Sistema' }} il {{ $viewingTask->updated_at->format('d/m/Y H:i') }}
                        @endif
                    </div>

                    <!-- ALLEGATI -->
                    <div class="border-t pt-4">
                        <label class="text-xs text-gray-500 uppercase font-semibold block mb-2">
                            <i class="fas fa-paperclip mr-1"></i> Allegati ({{ $viewingTask->documents_count ?? $viewingTask->documents->count() }})
                        </label>
                        <a href="{{ route('admin.documents.index', ['tableRef' => 'admin_tasks', 'idRef' => $viewingTask->id]) }}"
                            class="inline-flex items-center gap-2 px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-md text-sm transition-colors">
                            <i class="fas fa-folder-open"></i> Gestisci allegati
                        </a>
                        <p class="text-xs text-gray-400 mt-1">Si apre la pagina documenti — gli allegati vengono salvati su Amazon S3.</p>
                    </div>

                    <!-- COMMENTI -->
                    <div class="border-t pt-4">
                        <button wire:click="openCommentsModal({{ $viewingTask->id }})"
                            class="inline-flex items-center gap-2 px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-md text-sm transition-colors">
                            <i class="fa-solid fa-comments"></i> Commenti ({{ $viewingTask->comments_count ?? 0 }})
                        </button>
                    </div>
                </div>

                <div class="flex justify-end px-6 py-4 border-t bg-gray-50 rounded-b-lg">
                    <button wire:click="closeDetailModal" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">Chiudi</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- ==================== MODAL COMMENTI ==================== -->
    @if($showCommentsModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 px-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900">
                    <i class="fa-solid fa-comments text-blue-500 mr-2"></i>
                    Commenti
                </h3>
                <button wire:click="closeCommentsModal" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            @if($commentsTaskTitle)
            <p class="text-xs text-gray-400 mb-3 -mt-2 truncate">{{ $commentsTaskTitle }}</p>
            @endif

            <div class="max-h-96 overflow-y-auto mb-4 space-y-3">
                @forelse($taskComments ?? [] as $comment)
                    <div class="bg-gray-50 rounded-lg p-3" wire:key="comment-{{ $comment->id }}">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ $comment->author->name ?? 'Utente' }}</p>
                                <p class="text-xs text-gray-400">{{ $comment->created_at->format('d/m/Y, H:i:s') }}</p>
                            </div>
                            @if($comment->created_by === Auth::guard('admin')->id())
                            <button wire:click="deleteComment({{ $comment->id }})" class="text-red-400 hover:text-red-600 text-sm">
                                <i class="fas fa-times"></i>
                            </button>
                            @endif
                        </div>
                        <p class="text-sm text-gray-700 mt-1">{{ $comment->comment }}</p>
                    </div>
                @empty
                    <p class="text-center text-gray-400 text-sm py-4">Nessun commento</p>
                @endforelse
            </div>

            <div class="flex gap-2">
                <input type="text" wire:model="newComment" wire:keydown.enter="addComment"
                    placeholder="Scrivi un commento..."
                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lime-500">
                <button wire:click="addComment" class="px-4 py-2 bg-lime-600 hover:bg-lime-700 text-white rounded-lg text-sm font-medium transition-colors">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL CONFERMA ELIMINAZIONE -->
    @if($deletingId)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4"><i class="fas fa-exclamation-triangle text-red-600 text-xl"></i></div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Conferma eliminazione</h3>
            <p class="text-sm text-gray-500 mb-4">Il task verrà eliminato definitivamente. Continuare?</p>
            <div class="flex justify-center gap-3">
                <button wire:click="cancelDelete" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">Annulla</button>
                <button wire:click="delete" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md transition-colors">Elimina</button>
            </div>
        </div>
    </div>
    @endif

    @push('scripts')
    <script>
        document.addEventListener('livewire:initialized', () => {
            // Gestione click sui pulsanti della toolbar WYSIWYG (se presenti)
            const toolbar = document.getElementById('wysiwygToolbar');
            if (toolbar) {
                toolbar.addEventListener('click', function (e) {
                    const btn = e.target.closest('.wysiwyg-btn');
                    if (!btn) return;
                    e.preventDefault();
                    const cmd = btn.dataset.cmd;
                    const editor = document.getElementById('wysiwygEditor');
                    if (editor) editor.focus();
                    if (cmd === 'createLink') {
                        const url = prompt('Inserisci URL:');
                        if (url) document.execCommand(cmd, false, url);
                    } else {
                        document.execCommand(cmd, false, null);
                    }
                    if (editor) {
                        editor.dispatchEvent(new Event('input'));
                    }
                });
            }

            // Gestione autocomplete dropdown
            document.addEventListener('click', function(e) {
                const dropdowns = document.querySelectorAll('[x-data]');
                dropdowns.forEach(el => {
                    if (el._x_dataStack && el._x_dataStack.length > 0) {
                        const data = el._x_dataStack[0];
                        if (data.open !== undefined && !el.contains(e.target)) {
                            data.open = false;
                        }
                    }
                });
            });
        });
    </script>
    @endpush
</div>