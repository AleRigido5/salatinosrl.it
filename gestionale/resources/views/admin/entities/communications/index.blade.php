{{-- resources/views/admin/entities/communications/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Comunicazioni - ' . $entity->full_name)

@section('content')
<div class="p-4">

    {{-- HEADER --}}
    <div class="flex justify-between items-center mb-4">
        <div>
            <h1 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-envelope text-lime-600"></i>
                GESTIONE COMUNICAZIONI
            </h1>
            <p class="text-xs text-gray-400 mt-0.5">
                <i class="fas fa-building mr-1"></i>
                {{ $entity->full_name }}
            </p>
        </div>
        <a href="{{ route('admin.entities.index') }}"
           class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded-lg text-sm flex items-center gap-1.5 transition-colors">
            <i class="fas fa-arrow-left"></i>
        </a>
    </div>

    {{-- Pulsante Nuova Comunicazione --}}
    <div class="mb-4">
        <button onclick="openCreateModal()"
                class="bg-lime-600 hover:bg-lime-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition-colors shadow-sm">
            <i class="fas fa-plus"></i> Nuova Comunicazione
        </button>
    </div>

    {{-- TABELLA COMUNICAZIONI --}}
    <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="border-b-2 border-gray-200 bg-gray-50">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">DATA</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">TESTO</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">CONTATTO</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">MITTENTE</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">COMMENTI</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">ALLEGATO</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">INSERITO DA</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">AZIONI</th>
                    </tr>
                </thead>
                <tbody id="communicationsTableBody">
                    @forelse($communications as $comm)
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors" id="comm-row-{{ $comm->id }}">
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-700">
                            {{ $comm->data->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 max-w-xs truncate" title="{{ $comm->testo }}">
                            {{ Str::limit($comm->testo, 80) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            @if($comm->contatto)
                                @if(filter_var($comm->contatto, FILTER_VALIDATE_EMAIL))
                                    <a href="mailto:{{ $comm->contatto }}" class="text-blue-600 hover:text-blue-800">
                                        {{ $comm->contatto }}
                                    </a>
                                @else
                                    {{ $comm->contatto }}
                                @endif
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ $comm->mittente ?? 'Amministrazione' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button onclick="openCommentsModal({{ $comm->id }})"
                                    class="relative inline-flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-100 transition-colors">
                                <i class="fa-solid fa-comment text-blue-500"></i>
                                <span id="comments-badge-{{ $comm->id }}" 
                                      class="comments-badge absolute -top-1 -right-1 flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-red-500 rounded-full {{ $comm->comments_count > 0 ? '' : 'hidden' }}">
                                    {{ $comm->comments_count }}
                                </span>
                            </button>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($comm->allegato)
                                <a href="{{ route('admin.entities.communications.download', ['entityId' => $entity->id_cliente, 'id' => $comm->id]) }}"
                                   class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 transition-colors"
                                   title="{{ $comm->allegato_nome }}">
                                    <i class="{{ $comm->allegato_icon }} {{ $comm->allegato_color }}"></i>
                                    <span class="text-xs text-gray-500">{{ strtoupper($comm->allegato_tipo) }}</span>
                                </a>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">
                            <div class="flex flex-col">
                                <span>{{ $comm->createdBy->name ?? 'Sistema' }}</span>
                                <span class="text-[10px] text-gray-400">{{ $comm->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button onclick="editCommunication({{ $comm->id }})"
                                        class="text-yellow-600 hover:text-yellow-800 transition-colors"
                                        title="Modifica">
                                    <i class="fa-solid fa-pen-to-square text-yellow-600 hover:text-yellow-900"></i>
                                </button>
                                <button onclick="deleteCommunication({{ $comm->id }})"
                                        class="text-red-600 hover:text-red-800 transition-colors"
                                        title="Elimina">
                                    <i class="fa-solid fa-trash-can text-red-600 hover:text-red-900"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr id="no-communications-row">
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                            <i class="fa-solid fa-envelope text-gray-300 text-3xl block mb-2"></i>
                            Nessuna comunicazione registrata
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- MODAL CREAZIONE COMUNICAZIONE --}}
<div id="createModal" class="fixed inset-0 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="relative bg-white rounded-xl shadow-2xl max-w-2xl w-full p-6 transform transition-all">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900">
                    <i class="fa-solid fa-envelope text-lime-500 mr-2"></i>
                    Nuova Comunicazione
                </h3>
                <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form id="createForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="entity_id" value="{{ $entity->id_cliente }}">

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Data <span class="text-red-500">*</span></label>
                        <input type="date" name="data" required
                               value="{{ date('Y-m-d') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lime-500">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Testo <span class="text-red-500">*</span></label>
                        <textarea name="testo" rows="3" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lime-500"
                                  placeholder="Inserisci il testo della comunicazione..."></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Contatto</label>
                        <input type="text" name="contatto"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lime-500"
                               placeholder="Email o telefono del contatto">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Mittente</label>
                        <input type="text" name="mittente"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lime-500"
                               placeholder="Es: Amministrazione, Ufficio Acquisti...">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Allegato</label>
                        <input type="file" name="allegato"
                               accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.eml"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lime-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-lime-50 file:text-lime-700 hover:file:bg-lime-100">
                        <p class="text-xs text-gray-400 mt-1">Formati supportati: JPG, PNG, PDF, DOC, DOCX, XLS, XLSX, EML (max 5MB)</p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="closeCreateModal()"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors">
                        Annulla
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-lime-600 hover:bg-lime-700 text-white rounded-lg text-sm font-medium flex items-center gap-2 transition-colors shadow-sm">
                        <i class="fas fa-save"></i> Salva
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL COMMENTI --}}
<div id="commentsModal" class="fixed inset-0 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="relative bg-white rounded-xl shadow-2xl max-w-2xl w-full p-6 transform transition-all">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900">
                    <i class="fa-solid fa-comments text-blue-500 mr-2"></i>
                    Commenti
                </h3>
                <button onclick="closeCommentsModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div id="commentsList" class="max-h-96 overflow-y-auto mb-4 space-y-3">
                <!-- I commenti verranno caricati qui -->
            </div>

            <form id="commentForm" class="flex gap-2">
                @csrf
                <input type="hidden" name="communication_id" id="commentCommunicationId">
                <input type="text" name="testo" id="commentText"
                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lime-500"
                       placeholder="Scrivi un commento...">
                <button type="submit"
                        class="px-4 py-2 bg-lime-600 hover:bg-lime-700 text-white rounded-lg text-sm font-medium transition-colors">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>
</div>

{{-- MODAL MODIFICA --}}
<div id="editModal" class="fixed inset-0 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="relative bg-white rounded-xl shadow-2xl max-w-2xl w-full p-6 transform transition-all">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900">
                    <i class="fa-solid fa-pen-to-square text-yellow-500 mr-2"></i>
                    Modifica Comunicazione
                </h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form id="editForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="communication_id" id="editCommunicationId">

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Data <span class="text-red-500">*</span></label>
                        <input type="date" name="data" id="editData" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lime-500">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Testo <span class="text-red-500">*</span></label>
                        <textarea name="testo" id="editTesto" rows="3" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lime-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Contatto</label>
                        <input type="text" name="contatto" id="editContatto"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lime-500">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Mittente</label>
                        <input type="text" name="mittente" id="editMittente"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lime-500">
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="closeEditModal()"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors">
                        Annulla
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-lime-600 hover:bg-lime-700 text-white rounded-lg text-sm font-medium flex items-center gap-2 transition-colors shadow-sm">
                        <i class="fas fa-save"></i> Aggiorna
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL NOTIFICA (Tailwind CSS) --}}
<div id="notificationModal" class="fixed inset-0 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="relative bg-white rounded-xl shadow-2xl max-w-md w-full p-6 transform transition-all">
            <div class="flex items-start gap-4">
                <div id="notificationIcon" class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center bg-green-100">
                    <i id="notificationIconIcon" class="fas fa-check text-green-600"></i>
                </div>
                <div class="flex-1">
                    <h3 id="notificationTitle" class="text-lg font-semibold text-gray-900">Successo</h3>
                    <p id="notificationMessage" class="text-sm text-gray-500 mt-1"></p>
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <button onclick="closeNotification()"
                        class="px-4 py-2 bg-lime-600 hover:bg-lime-700 text-white rounded-lg text-sm font-medium transition-colors">
                    OK
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const entityId = {{ $entity->id_cliente }};
const baseUrl = '{{ route("admin.entities.communications.index", $entity->id_cliente) }}';
const currentUserId = {{ Auth::guard('admin')->id() }};

// ── NOTIFICATION ──────────────────────────────────────────────────────────
function showNotification(title, message, type = 'success') {
    const modal = document.getElementById('notificationModal');
    const icon = document.getElementById('notificationIcon');
    const iconIcon = document.getElementById('notificationIconIcon');
    
    icon.className = 'flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center';
    if (type === 'success') {
        icon.classList.add('bg-green-100');
        iconIcon.className = 'fas fa-check text-green-600';
    } else if (type === 'error') {
        icon.classList.add('bg-red-100');
        iconIcon.className = 'fas fa-times text-red-600';
    } else {
        icon.classList.add('bg-yellow-100');
        iconIcon.className = 'fas fa-exclamation-triangle text-yellow-600';
    }
    
    document.getElementById('notificationTitle').textContent = title;
    document.getElementById('notificationMessage').textContent = message;
    modal.classList.remove('hidden');
}

function closeNotification() {
    document.getElementById('notificationModal').classList.add('hidden');
}

// ── CREATE ──────────────────────────────────────────────────────────────
function openCreateModal() {
    document.getElementById('createModal').classList.remove('hidden');
}

function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
    document.getElementById('createForm').reset();
}

document.getElementById('createForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('{{ route("admin.entities.communications.store", $entity->id_cliente) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            closeCreateModal();
            showNotification('Successo', data.message || 'Comunicazione aggiunta con successo!');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification('Errore', data.message || 'Errore durante il salvataggio', 'error');
        }
    })
    .catch(() => showNotification('Errore', 'Errore di connessione', 'error'));
});

// ── COMMENTS ─────────────────────────────────────────────────────────────
let currentCommunicationId = null;

function openCommentsModal(commId) {
    currentCommunicationId = commId;
    document.getElementById('commentCommunicationId').value = commId;
    document.getElementById('commentsModal').classList.remove('hidden');
    loadComments(commId);
}

function closeCommentsModal() {
    document.getElementById('commentsModal').classList.add('hidden');
    document.getElementById('commentsList').innerHTML = '';
}

function loadComments(commId) {
    fetch(`{{ route("admin.entities.communications.show", ['entityId' => $entity->id_cliente, 'id' => '__ID__']) }}`.replace('__ID__', commId))
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderComments(data.communication.comments || []);
            }
        })
        .catch(() => showNotification('Errore', 'Errore nel caricamento dei commenti', 'error'));
}

function renderComments(comments) {
    const container = document.getElementById('commentsList');
    container.innerHTML = '';

    if (comments.length === 0) {
        container.innerHTML = '<p class="text-center text-gray-400 text-sm py-4">Nessun commento</p>';
        return;
    }

    comments.forEach(c => {
        const div = document.createElement('div');
        div.className = 'bg-gray-50 rounded-lg p-3';
        div.id = 'comment-' + c.id;
        div.innerHTML = `
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-gray-800">${c.created_by ? c.created_by.name : 'Sistema'}</p>
                    <p class="text-xs text-gray-400">${new Date(c.created_at).toLocaleString('it-IT')}</p>
                </div>
                ${c.created_by && c.created_by.id === currentUserId ? `
                    <button onclick="deleteComment(${c.id})" class="text-red-400 hover:text-red-600 text-sm">
                        <i class="fas fa-times"></i>
                    </button>
                ` : ''}
            </div>
            <p class="text-sm text-gray-700 mt-1">${c.testo}</p>
        `;
        container.appendChild(div);
    });
    
    // Scroll in fondo per vedere l'ultimo commento
    container.scrollTop = container.scrollHeight;
}

document.getElementById('commentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const commId = document.getElementById('commentCommunicationId').value;
    const testo = document.getElementById('commentText').value;

    if (!testo.trim()) return;

    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    fetch(`{{ route("admin.entities.communications.comments.store", ['entityId' => $entity->id_cliente, 'communicationId' => '__ID__']) }}`.replace('__ID__', commId), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ testo: testo })
    })
    .then(r => r.json())
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
        
        if (data.success) {
            document.getElementById('commentText').value = '';
            // 🔥 Ricarica i commenti in tempo reale
            loadComments(commId);
            // 🔥 Aggiorna il badge dei commenti
            updateCommentsBadge(commId);
        } else {
            showNotification('Errore', data.message || 'Errore durante il salvataggio', 'error');
        }
    })
    .catch(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
        showNotification('Errore', 'Errore di connessione', 'error');
    });
});

function deleteComment(commentId) {
    if (!confirm('Eliminare questo commento?')) return;

    fetch(`{{ route("admin.entities.communications.comments.delete", ['entityId' => $entity->id_cliente, 'communicationId' => '__CID__', 'commentId' => '__MID__']) }}`
        .replace('__CID__', currentCommunicationId)
        .replace('__MID__', commentId), {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // 🔥 Ricarica i commenti in tempo reale
            loadComments(currentCommunicationId);
            // 🔥 Aggiorna il badge dei commenti
            updateCommentsBadge(currentCommunicationId);
            showNotification('Successo', 'Commento eliminato con successo!');
        } else {
            showNotification('Errore', data.message || 'Errore durante l\'eliminazione', 'error');
        }
    })
    .catch(() => showNotification('Errore', 'Errore di connessione', 'error'));
}

function updateCommentsBadge(commId) {
    // Aggiorna il badge nella tabella senza ricaricare la pagina
    fetch(`{{ route("admin.entities.communications.show", ['entityId' => $entity->id_cliente, 'id' => '__ID__']) }}`.replace('__ID__', commId))
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const count = data.communication.comments ? data.communication.comments.length : 0;
                const badge = document.getElementById('comments-badge-' + commId);
                if (badge) {
                    badge.textContent = count;
                    if (count > 0) {
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                }
            }
        });
}

// ── EDIT ──────────────────────────────────────────────────────────────────
function editCommunication(commId) {
    fetch(`{{ route("admin.entities.communications.show", ['entityId' => $entity->id_cliente, 'id' => '__ID__']) }}`.replace('__ID__', commId))
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const c = data.communication;
                document.getElementById('editCommunicationId').value = c.id;
                document.getElementById('editData').value = c.data;
                document.getElementById('editTesto').value = c.testo;
                document.getElementById('editContatto').value = c.contatto || '';
                document.getElementById('editMittente').value = c.mittente || '';
                document.getElementById('editModal').classList.remove('hidden');
            }
        });
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

document.getElementById('editForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const commId = document.getElementById('editCommunicationId').value;
    const formData = new FormData(this);

    fetch(`{{ route("admin.entities.communications.update", ['entityId' => $entity->id_cliente, 'id' => '__ID__']) }}`.replace('__ID__', commId), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            closeEditModal();
            showNotification('Successo', data.message || 'Comunicazione aggiornata con successo!');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification('Errore', data.message || 'Errore durante l\'aggiornamento', 'error');
        }
    })
    .catch(() => showNotification('Errore', 'Errore di connessione', 'error'));
});

// ── DELETE ───────────────────────────────────────────────────────────────
function deleteCommunication(commId) {
    if (!confirm('Eliminare questa comunicazione?')) return;

    fetch(`{{ route("admin.entities.communications.destroy", ['entityId' => $entity->id_cliente, 'id' => '__ID__']) }}`.replace('__ID__', commId), {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showNotification('Successo', data.message || 'Comunicazione eliminata con successo!');
            // Rimuovi la riga dalla tabella senza ricaricare
            const row = document.getElementById('comm-row-' + commId);
            if (row) {
                row.remove();
                // Controlla se la tabella è vuota
                const tbody = document.getElementById('communicationsTableBody');
                if (tbody.children.length === 0) {
                    tbody.innerHTML = `
                        <tr id="no-communications-row">
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                <i class="fa-solid fa-envelope text-gray-300 text-3xl block mb-2"></i>
                                Nessuna comunicazione registrata
                            </td>
                        </tr>
                    `;
                }
            }
        } else {
            showNotification('Errore', data.message || 'Errore durante l\'eliminazione', 'error');
        }
    })
    .catch(() => showNotification('Errore', 'Errore di connessione', 'error'));
}

// Chiudi modali cliccando fuori
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('fixed') && e.target.classList.contains('inset-0') && e.target.classList.contains('bg-gray-500')) {
        document.querySelectorAll('.fixed.inset-0.z-50:not(.hidden)').forEach(m => {
            if (m.id !== 'notificationModal') {
                m.classList.add('hidden');
            }
        });
    }
});

// Chiudi notifica con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeNotification();
    }
});
</script>
@endsection