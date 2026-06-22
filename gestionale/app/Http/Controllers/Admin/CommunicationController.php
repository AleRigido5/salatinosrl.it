<?php
// app/Http/Controllers/Admin/CommunicationController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Communication;
use App\Models\Entity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CommunicationController extends Controller
{
    public function index($entityId)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_entities')) {
            abort(403);
        }

        $entity = Entity::findOrFail($entityId);
        
        $communications = Communication::where('id_entities', $entityId)
            ->with(['createdBy', 'updatedBy'])
            ->orderBy('data', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.entities.communications.index', compact('entity', 'communications'));
    }

    public function store(Request $request, $entityId)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_entities')) {
            abort(403);
        }

        $request->validate([
            'data' => 'required|date',
            'testo' => 'required|string',
            'contatto' => 'nullable|string|max:255',
            'mittente' => 'nullable|string|max:255',
            'allegato' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,eml',
        ]);

        $allegatoPath = null;
        $allegatoTipo = null;

        if ($request->hasFile('allegato')) {
            $file = $request->file('allegato');
            $allegatoPath = $file->store('communications/' . $entityId, 'public');
            $allegatoTipo = $file->getClientOriginalExtension();
        }

        $communication = Communication::create([
            'data' => $request->data,
            'testo' => $request->testo,
            'contatto' => $request->contatto,
            'id_entities' => $entityId,
            'mittente' => $request->mittente,
            'allegato' => $allegatoPath,
            'allegato_tipo' => $allegatoTipo,
            'created_by' => Auth::guard('admin')->id(),
            'updated_by' => Auth::guard('admin')->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Comunicazione aggiunta con successo!',
            'communication' => $communication->load(['createdBy', 'updatedBy'])
        ]);
    }

    public function show($entityId, $id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_entities')) {
            abort(403);
        }

        $communication = Communication::where('id_entities', $entityId)
            ->with(['createdBy', 'updatedBy', 'comments' => function($q) {
                $q->with('createdBy')->orderBy('created_at', 'asc');
            }])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'communication' => $communication
        ]);
    }

    public function update(Request $request, $entityId, $id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_entities')) {
            abort(403);
        }

        $communication = Communication::where('id_entities', $entityId)->findOrFail($id);

        $request->validate([
            'data' => 'required|date',
            'testo' => 'required|string',
            'contatto' => 'nullable|string|max:255',
            'mittente' => 'nullable|string|max:255',
        ]);

        $communication->update([
            'data' => $request->data,
            'testo' => $request->testo,
            'contatto' => $request->contatto,
            'mittente' => $request->mittente,
            'updated_by' => Auth::guard('admin')->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Comunicazione aggiornata con successo!',
            'communication' => $communication->load(['createdBy', 'updatedBy'])
        ]);
    }

    public function destroy($entityId, $id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('delete_entities')) {
            abort(403);
        }

        $communication = Communication::where('id_entities', $entityId)->findOrFail($id);

        // Elimina allegato se esiste
        if ($communication->allegato && Storage::disk('public')->exists($communication->allegato)) {
            Storage::disk('public')->delete($communication->allegato);
        }

        $communication->delete();

        return response()->json([
            'success' => true,
            'message' => 'Comunicazione eliminata con successo!'
        ]);
    }

    public function download($entityId, $id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_entities')) {
            abort(403);
        }

        $communication = Communication::where('id_entities', $entityId)->findOrFail($id);

        if (!$communication->allegato || !Storage::disk('public')->exists($communication->allegato)) {
            abort(404, 'File non trovato');
        }

        $filename = basename($communication->allegato);
        // Storage::download may not be available depending on filesystem adapter/version.
        // Resolve the absolute path and use response()->download to ensure compatibility.
        $filePath = Storage::disk('public')->path($communication->allegato);
        return response()->download($filePath, $filename);
    }

    // ==================== COMMENTI ====================

    public function storeComment(Request $request, $entityId, $communicationId)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_entities')) {
            abort(403);
        }

        $request->validate([
            'testo' => 'required|string'
        ]);

        $comment = \App\Models\CommunicationComment::create([
            'communication_id' => $communicationId,
            'testo' => $request->testo,
            'created_by' => Auth::guard('admin')->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Commento aggiunto con successo!',
            'comment' => $comment->load('createdBy')
        ]);
    }

    public function deleteComment($entityId, $communicationId, $commentId)
    {
        if (!Auth::guard('admin')->user()->hasPermission('delete_entities')) {
            abort(403);
        }

        $comment = \App\Models\CommunicationComment::where('communication_id', $communicationId)
            ->findOrFail($commentId);

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Commento eliminato con successo!'
        ]);
    }
}