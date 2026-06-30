<?php
// app/Http/Controllers/Admin/CommunicationController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Communication;
use App\Models\Entity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CommunicationController extends Controller
{
    /**
     * Pulisce una stringa per renderla utilizzabile come nome cartella
     */
    private function sanitizeFolderName($name)
    {
        if (empty($name)) {
            return 'comunicazione';
        }
        
        $name = preg_replace('/[^a-zA-Z0-9À-ÿ\s-]/u', '', $name);
        $name = preg_replace('/[\s-]+/', '_', $name);
        $name = substr($name, 0, 50);
        $name = preg_replace('/_+/', '_', $name);
        $name = trim($name, '_');
        
        return $name ?: 'comunicazione';
    }

    /**
     * Genera il percorso su S3 per gli allegati delle comunicazioni
     */
    private function getCommunicationS3Path($entityId, $originalName)
    {
        $entity = Entity::find($entityId);
        $entityName = $entity ? $this->sanitizeFolderName($entity->ragione_sociale ?? ($entity->nome . ' ' . $entity->cognome)) : 'entita_' . $entityId;
        
        // Genera un nome file pulito
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $cleanBaseName = preg_replace('/[^a-zA-Z0-9-_]/', '_', $baseName);
        $timestamp = time() . '_' . Str::random(4);
        $savedName = $cleanBaseName . '_' . $timestamp . '.' . $extension;
        
        return "s3://communications/{$entityName}/" . $savedName;
    }

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
            'allegato' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,eml',
        ]);

        $allegatoPath = null;
        $allegatoTipo = null;

        if ($request->hasFile('allegato')) {
            $file = $request->file('allegato');
            $originalName = $file->getClientOriginalName();
            $allegatoTipo = $file->getClientOriginalExtension();
            
            // Genera il percorso su S3
            $s3PathWithPrefix = $this->getCommunicationS3Path($entityId, $originalName);
            $s3Path = str_replace('s3://', '', $s3PathWithPrefix);
            
            // Leggi il contenuto del file
            $content = file_get_contents($file->getRealPath());
            
            // Carica su S3
            $saved = Storage::disk('s3')->put($s3Path, $content);
            
            if (!$saved) {
                throw new \Exception("Errore durante il caricamento su S3: {$originalName}");
            }
            
            // Salva il path con prefisso s3:// nel database
            $allegatoPath = $s3PathWithPrefix;
            
            Log::info('Allegato comunicazione caricato su S3', [
                'entity_id' => $entityId,
                'path' => $s3Path,
                'file' => $originalName
            ]);
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

        // Elimina allegato da S3 se esiste
        if ($communication->allegato) {
            if (str_starts_with($communication->allegato, 's3://')) {
                $s3Path = str_replace('s3://', '', $communication->allegato);
                Storage::disk('s3')->delete($s3Path);
                Log::info('Allegato comunicazione eliminato da S3', [
                    'path' => $s3Path,
                    'communication_id' => $communication->id
                ]);
            } elseif (Storage::disk('public')->exists($communication->allegato)) {
                // Fallback per file locali (compatibilità con vecchi allegati)
                Storage::disk('public')->delete($communication->allegato);
            }
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

        if (!$communication->allegato) {
            abort(404, 'File non trovato');
        }

        // Verifica se è su S3
        if (str_starts_with($communication->allegato, 's3://')) {
            $s3Path = str_replace('s3://', '', $communication->allegato);
            
            // Verifica che il file esista su S3
            if (!Storage::disk('s3')->exists($s3Path)) {
                abort(404, 'File non trovato su S3');
            }
            
            $content = Storage::disk('s3')->get($s3Path);
            $filename = basename($s3Path);
            
            return response($content)
                ->header('Content-Type', 'application/octet-stream')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        } else {
            // Fallback per file locali (compatibilità con vecchi allegati)
            if (!Storage::disk('public')->exists($communication->allegato)) {
                abort(404, 'File non trovato');
            }
            
            $filename = basename($communication->allegato);
            $filePath = Storage::disk('public')->path($communication->allegato);
            return response()->download($filePath, $filename);
        }
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