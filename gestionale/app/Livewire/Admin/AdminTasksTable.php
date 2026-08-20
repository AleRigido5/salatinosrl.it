<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\AdminTask;
use App\Models\Setting;
use App\Models\AdminTaskTag;
use App\Models\AdminTaskComment;
use App\Models\Entity;
use App\Models\Ownership;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminTasksTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Filtri
    public string $search = '';
    public string $categoryFilter = '';
    public string $statusFilter = '';
    public string $priorityFilter = '';
    public string $tagFilter = '';
    public int $perPage = 100;

    // Filtro data
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $dateFilterMode = 'task_date';

    public array $categories = [];

    // ==================== FORM CREAZIONE/MODIFICA ====================
    public bool $showFormModal = false;
    public ?int $editingId = null;

    public string $title = '';
    public string $description = '';
    public string $practice_number = '';
    public string $channel = '';
    public string $task_date = '';
    public string $due_date = '';
    public string $id_category = '';
    public int $priority = 3;
    public string $status = 'waiting';
    public string $amount = '';

    // Cliente/Proprietà
    public string $entitySearch = '';
    public Collection $entityResults;
    public string $selectedEntityId = '';
    public string $selectedEntityName = '';
    public bool $showEntityDropdown = false;

    public string $ownershipSearch = '';
    public Collection $ownershipResults;
    public string $selectedOwnershipId = '';
    public string $selectedOwnershipName = '';
    public bool $showOwnershipDropdown = false;

    // Tag
    public string $tagInput = '';
    public array $selectedTags = [];
    public Collection $tagSuggestions;

    // ==================== DETTAGLIO / COMMENTI ====================
    public bool $showDetailModal = false;
    public ?AdminTask $viewingTask = null;
    public string $newComment = '';

    public bool $showCommentsModal = false;
    public ?int $commentsTaskId = null;
    public string $commentsTaskTitle = '';
    public $taskComments = null;

    // Conferma eliminazione
    public ?int $deletingId = null;

    protected $listeners = [
        'refreshTasks' => '$refresh',
        'dateRangeUpdated' => 'updateDateRange',
    ];

    public function mount(): void
    {
        $this->entityResults = new Collection();
        $this->ownershipResults = new Collection();
        $this->tagSuggestions = new Collection();
        $this->task_date = now()->format('Y-m-d');

        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');

        $this->loadCategories();
    }

    public function updateDateRange(array $data): void
    {
        $this->dateFrom = $data['date_from'] ?? '';
        $this->dateTo = $data['date_to'] ?? '';
        $this->resetPage();
    }

    public function updatedDateFilterMode(): void
    {
        $this->resetPage();
    }

    protected function loadCategories(): void
    {
        $this->categories = Setting::where('tabella_riferimento', 'admin_tasks')
            ->where('valid', 1)
            ->orderBy('ordinamento')
            ->orderBy('valore')
            ->get(['id', 'valore'])
            ->toArray();
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingCategoryFilter(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }
    public function updatingPriorityFilter(): void { $this->resetPage(); }
    public function updatingTagFilter(): void { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->categoryFilter = '';
        $this->statusFilter = '';
        $this->priorityFilter = '';
        $this->tagFilter = '';
        $this->dateFilterMode = 'task_date';

        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
        $this->resetPage();
        $this->dispatch('resetDates');
    }

    // ==================== ELENCO ====================
    public function getTasksProperty()
    {
        $query = AdminTask::with(['category', 'entity', 'ownership', 'tags', 'creator'])
            ->withCount(['comments', 'documents']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('practice_number', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->categoryFilter) {
            $query->where('id_category', $this->categoryFilter);
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->priorityFilter) {
            $query->where('priority', $this->priorityFilter);
        }

        if ($this->tagFilter) {
            $query->whereHas('tags', function ($q) {
                $q->where('name', 'like', '%' . $this->tagFilter . '%');
            });
        }

        $dateColumn = $this->dateFilterMode === 'due_date' ? 'due_date' : 'task_date';
        if ($this->dateFrom) {
            $query->whereDate($dateColumn, '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate($dateColumn, '<=', $this->dateTo);
        }

        $query->orderByRaw("CASE WHEN due_date IS NULL THEN 1 ELSE 0 END")
              ->orderBy('due_date')
              ->orderByDesc('created_at');

        return $query->paginate($this->perPage);
    }

    // ==================== AUTOCOMPLETE CLIENTE/FORNITORE ====================
    public function updatedEntitySearch(): void
    {
        if (!empty($this->selectedEntityId) && $this->entitySearch === $this->selectedEntityName) {
            $this->showEntityDropdown = false;
            return;
        }
        if (!empty($this->selectedEntityId) && $this->entitySearch !== $this->selectedEntityName) {
            $this->selectedEntityId = '';
            $this->selectedEntityName = '';
        }
        if (strlen(trim($this->entitySearch)) < 2) {
            $this->entityResults = new Collection();
            $this->showEntityDropdown = false;
            return;
        }

        $this->entityResults = Entity::where('valid', 1)
            ->where(function ($q) {
                $q->where('ragione_sociale', 'like', '%' . $this->entitySearch . '%')
                  ->orWhere('nome', 'like', '%' . $this->entitySearch . '%')
                  ->orWhere('cognome', 'like', '%' . $this->entitySearch . '%');
            })
            ->limit(10)
            ->get(['id_cliente as id', 'ragione_sociale as name']);

        $this->showEntityDropdown = $this->entityResults->isNotEmpty();
    }

    public function selectEntity($id, string $name): void
    {
        $this->selectedEntityId = (string) $id;
        $this->selectedEntityName = $name;
        $this->entitySearch = $name;
        $this->showEntityDropdown = false;
    }

    public function clearEntity(): void
    {
        $this->selectedEntityId = '';
        $this->selectedEntityName = '';
        $this->entitySearch = '';
        $this->showEntityDropdown = false;
    }

    // ==================== AUTOCOMPLETE PROPRIETÀ ====================
    public function updatedOwnershipSearch(): void
    {
        if (!empty($this->selectedOwnershipId) && $this->ownershipSearch === $this->selectedOwnershipName) {
            $this->showOwnershipDropdown = false;
            return;
        }
        if (!empty($this->selectedOwnershipId) && $this->ownershipSearch !== $this->selectedOwnershipName) {
            $this->selectedOwnershipId = '';
            $this->selectedOwnershipName = '';
        }
        if (strlen(trim($this->ownershipSearch)) < 2) {
            $this->ownershipResults = new Collection();
            $this->showOwnershipDropdown = false;
            return;
        }

        $this->ownershipResults = Ownership::where('valid', 1)
            ->where(function ($q) {
                $q->where('RagAbbrev', 'like', '%' . $this->ownershipSearch . '%')
                  ->orWhere('Rag_Soc_intest', 'like', '%' . $this->ownershipSearch . '%');
            })
            ->limit(10)
            ->get(['id_proprieta as id', 'RagAbbrev as name', 'Rag_Soc_intest as ragione_sociale']);

        $this->showOwnershipDropdown = $this->ownershipResults->isNotEmpty();
    }

    public function selectOwnership($id, string $name): void
    {
        $this->selectedOwnershipId = (string) $id;
        $this->selectedOwnershipName = $name;
        $this->ownershipSearch = $name;
        $this->showOwnershipDropdown = false;
    }

    public function clearOwnership(): void
    {
        $this->selectedOwnershipId = '';
        $this->selectedOwnershipName = '';
        $this->ownershipSearch = '';
        $this->showOwnershipDropdown = false;
    }

    // ==================== TAG ====================
    public function updatedTagInput(): void
    {
        $query = trim($this->tagInput);
        if (strlen($query) < 1) {
            $this->tagSuggestions = new Collection();
            return;
        }
        $this->tagSuggestions = AdminTaskTag::where('name', 'like', '%' . $query . '%')
            ->whereNotIn('name', $this->selectedTags)
            ->limit(8)
            ->get(['id', 'name']);
    }

    public function addTagFromInput(): void
    {
        $name = trim($this->tagInput);
        if ($name === '') {
            return;
        }
        $this->addTag($name);
    }

    public function addTag(string $name): void
    {
        $name = trim($name);
        if ($name === '' || in_array($name, $this->selectedTags, true)) {
            $this->tagInput = '';
            $this->tagSuggestions = new Collection();
            return;
        }
        $this->selectedTags[] = $name;
        $this->tagInput = '';
        $this->tagSuggestions = new Collection();
    }

    public function removeTag(string $name): void
    {
        $this->selectedTags = array_values(array_filter($this->selectedTags, fn ($t) => $t !== $name));
    }

    // ==================== CREAZIONE / MODIFICA TASK ====================
    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(int $id): void
    {
        $task = AdminTask::with('tags')->findOrFail($id);

        $this->editingId = $task->id;
        $this->title = $task->title;
        $this->description = $task->description ?? '';
        $this->practice_number = $task->practice_number ?? '';
        $this->channel = $task->channel ?? '';
        $this->task_date = optional($task->task_date)->format('Y-m-d') ?? '';
        $this->due_date = optional($task->due_date)->format('Y-m-d') ?? '';
        $this->id_category = (string) ($task->id_category ?? '');
        $this->priority = $task->priority;
        $this->status = $task->status;
        $this->amount = $task->amount !== null ? number_format((float) $task->amount, 2, '.', '') : '';

        $this->selectedEntityId = (string) ($task->id_entities ?? '');
        $this->selectedEntityName = $task->entity ? ($task->entity->ragione_sociale ?? trim($task->entity->nome . ' ' . $task->entity->cognome)) : '';
        $this->entitySearch = $this->selectedEntityName;

        $this->selectedOwnershipId = (string) ($task->id_ownership ?? '');
        $this->selectedOwnershipName = $task->ownership ? ($task->ownership->RagAbbrev ?? $task->ownership->Rag_Soc_intest) : '';
        $this->ownershipSearch = $this->selectedOwnershipName;

        $this->selectedTags = $task->tags->pluck('name')->toArray();

        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->description = '';
        $this->practice_number = '';
        $this->channel = '';
        $this->task_date = now()->format('Y-m-d');
        $this->due_date = '';
        $this->id_category = '';
        $this->priority = 3;
        $this->status = 'waiting';
        $this->amount = '';
        $this->clearEntity();
        $this->clearOwnership();
        $this->selectedTags = [];
        $this->tagInput = '';
        $this->resetErrorBag();
    }

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'practice_number' => 'nullable|string|max:100',
            'channel' => 'nullable|string|max:100',
            'task_date' => 'required|date',
            'due_date' => 'nullable|date',
            'id_category' => 'nullable|exists:settings,id',
            'priority' => 'required|integer|min:1|max:5',
            'status' => 'required|in:waiting,associated,completed,expired',
            'amount' => 'nullable|numeric|min:0|max:99999999.99',
        ];
    }

    public function save(): void
    {
        // Commit automatico del tag se presente nel campo input
        if (trim($this->tagInput) !== '') {
            $this->addTag($this->tagInput);
        }

        $this->validate();

        $adminId = Auth::guard('admin')->id();

        $data = [
            'title' => $this->title,
            'description' => $this->description,
            'practice_number' => $this->practice_number ?: null,
            'channel' => $this->channel ?: null,
            'task_date' => $this->task_date,
            'due_date' => $this->due_date ?: null,
            'id_category' => $this->id_category ?: null,
            'id_entities' => $this->selectedEntityId ?: null,
            'id_ownership' => $this->selectedOwnershipId ?: null,
            'priority' => $this->priority,
            'status' => $this->status,
            'amount' => $this->amount !== '' ? round((float) str_replace(',', '.', $this->amount), 2) : null,
            'updated_by' => $adminId,
        ];

        DB::beginTransaction();
        try {
            if ($this->editingId) {
                $task = AdminTask::findOrFail($this->editingId);
                $task->update($data);
            } else {
                $data['created_by'] = $adminId;
                $task = AdminTask::create($data);
            }

            // Sincronizza i tag
            $tagIds = [];
            foreach ($this->selectedTags as $tagName) {
                $tagName = trim($tagName);
                if ($tagName !== '') {
                    $tag = AdminTaskTag::firstOrCreate(
                        ['slug' => Str::slug($tagName)],
                        ['name' => $tagName]
                    );
                    $tagIds[] = $tag->id;
                }
            }
            
            $task->tags()->sync($tagIds);

            DB::commit();

            $this->dispatch('showSuccess', message: "Task '{$task->title}' " . ($this->editingId ? 'aggiornato' : 'creato') . " con successo.");
            $this->closeFormModal();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Errore salvataggio task', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }

    // ==================== ELIMINAZIONE ====================
    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
    }

    public function cancelDelete(): void
    {
        $this->deletingId = null;
    }

    public function delete(): void
    {
        if (!$this->deletingId) {
            return;
        }
        $task = AdminTask::find($this->deletingId);
        if ($task) {
            $title = $task->title;
            $task->delete();
            $this->dispatch('showSuccess', message: "Task '{$title}' eliminato.");
        }
        $this->deletingId = null;
    }

    // ==================== DETTAGLIO ====================
    public function openDetailModal(int $id): void
    {
        $this->viewingTask = AdminTask::with(['category', 'entity', 'ownership', 'tags', 'creator', 'updater'])
            ->withCount(['comments', 'documents'])
            ->findOrFail($id);
        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->viewingTask = null;
    }

    // ==================== MODAL COMMENTI ====================
    public function openCommentsModal(int $id): void
    {
        $task = AdminTask::with('comments.author')->findOrFail($id);
        $this->commentsTaskId = $task->id;
        $this->commentsTaskTitle = $task->title;
        $this->taskComments = $task->comments;
        $this->newComment = '';
        $this->showCommentsModal = true;
    }

    public function closeCommentsModal(): void
    {
        $this->showCommentsModal = false;
        $this->commentsTaskId = null;
        $this->commentsTaskTitle = '';
        $this->taskComments = null;
        $this->newComment = '';
    }

    public function addComment(): void
    {
        if (!$this->commentsTaskId || trim($this->newComment) === '') {
            return;
        }

        AdminTaskComment::create([
            'admin_task_id' => $this->commentsTaskId,
            'comment' => trim($this->newComment),
            'created_by' => Auth::guard('admin')->id(),
        ]);

        $this->newComment = '';
        $this->refreshTaskComments();
    }

    public function deleteComment(int $commentId): void
    {
        $comment = AdminTaskComment::find($commentId);

        if (!$comment || $comment->admin_task_id !== $this->commentsTaskId) {
            return;
        }

        if ($comment->created_by !== Auth::guard('admin')->id()) {
            $this->dispatch('showError', message: 'Puoi eliminare solo i tuoi commenti.');
            return;
        }

        $comment->delete();
        $this->refreshTaskComments();
    }

    protected function refreshTaskComments(): void
    {
        if (!$this->commentsTaskId) {
            return;
        }
        $this->taskComments = AdminTaskComment::where('admin_task_id', $this->commentsTaskId)
            ->with('author')
            ->orderBy('created_at')
            ->get();
    }

    public function quickChangeStatus(int $id, string $newStatus): void
    {
        $task = AdminTask::find($id);
        if (!$task) {
            return;
        }
        $task->update([
            'status' => $newStatus,
            'updated_by' => Auth::guard('admin')->id(),
        ]);
        $this->dispatch('showSuccess', message: "Stato aggiornato a '" . (AdminTask::statusOptions()[$newStatus]['label'] ?? $newStatus) . "'.");

        if ($this->viewingTask && $this->viewingTask->id === $id) {
            $this->viewingTask->refresh();
        }
    }

    public function render()
    {
        return view('livewire.admin.admin-tasks-table', [
            'tasks' => $this->tasks,
            'statuses' => AdminTask::statusOptions(),
            'priorityColors' => AdminTask::priorityColors(),
        ]);
    }
}