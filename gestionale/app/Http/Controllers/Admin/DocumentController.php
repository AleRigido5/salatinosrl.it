<?php
// app/Http/Controllers/Admin/DocumentController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Staff;
use App\Models\Expiration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function index($tableRef, $idRef, Request $request)
    {
        $documents = Document::where('table_ref', $tableRef)
            ->where('id_ref', $idRef)
            ->orderBy('created_at', 'desc')
            ->get();
        
        $title = '';
        $backUrl = '';
        $staffId = $request->get('staff_id');
        
        if ($tableRef === 'staff') {
            $staff = Staff::find($idRef);
            $title = $staff ? $staff->full_name : 'Documenti Personale';
            $backUrl = route('admin.staff.show', $idRef) . ($staffId ? '?staff_id=' . $staffId : '');
        } elseif ($tableRef === 'expiration-staff') {
            $expiration = Expiration::find($idRef);
            if ($expiration && $expiration->id_references) {
                $staff = Staff::find($expiration->id_references);
                $title = $staff ? $staff->full_name : 'Documenti Scadenza';
            } else {
                $title = 'Documenti Scadenza';
            }
            $backUrl = route('admin.expiration.index') . ($staffId ? '?staff_id=' . $staffId : '');
        } else {
            $backUrl = route('admin.expiration.index');
        }
        
        return view('admin.documents.index', compact('documents', 'tableRef', 'idRef', 'title', 'backUrl', 'staffId'));
    }
    
    public function store(Request $request, $tableRef, $idRef)
    {
        $request->validate([
            'document_files' => 'required|array',
            'document_files.*' => 'required|file|max:5120|mimes:pdf,jpeg,jpg,png',
            'titolo' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:500',
        ]);
        
        $files = $request->file('document_files');
        $commonTitle = $request->titolo;
        $commonNote = $request->note;
        $staffId = $request->staff_id;
        
        $successCount = 0;
        $errors = [];
        
        foreach ($files as $file) {
            try {
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                
                $cleanOriginalName = preg_replace('/[^a-zA-Z0-9-_]/', '_', $originalName);
                $timestamp = time() . '_' . Str::random(4);
                $savedName = $cleanOriginalName . '_' . $timestamp . '.' . $extension;
                
                $folderPath = "upload/staff/{$idRef}";
                $fullPath = public_path($folderPath);
                
                if (!file_exists($fullPath)) {
                    mkdir($fullPath, 0755, true);
                }
                
                $file->move($fullPath, $savedName);
                
                $title = $commonTitle ? $commonTitle . ' - ' . $originalName : $originalName;
                
                Document::create([
                    'titolo' => $title,
                    'note' => $commonNote,
                    'path_doc' => $folderPath,
                    'file_name' => $savedName,
                    'table_ref' => $tableRef,
                    'id_ref' => $idRef
                ]);
                
                $successCount++;
                
            } catch (\Exception $e) {
                $errors[] = "Errore per il file '{$originalName}': " . $e->getMessage();
            }
        }
        
        $redirectUrl = route('admin.documents.index', [$tableRef, $idRef]);
        if ($staffId) {
            $redirectUrl .= '?staff_id=' . $staffId;
        }
        
        $message = "{$successCount} documento/i caricato/i con successo!";
        
        if (!empty($errors)) {
            return redirect($redirectUrl)->with('errors', $errors)->with('warning', $message);
        }
        
        return redirect($redirectUrl)->with('success', $message);
    }
    
    public function destroy($tableRef, $idRef, $documentId, Request $request)
    {
        try {
            $document = Document::where('table_ref', $tableRef)
                ->where('id_ref', $idRef)
                ->where('id', $documentId)
                ->firstOrFail();
            
            // Elimina file fisico
            $filePath = public_path($document->path_doc . '/' . $document->file_name);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Elimina record dal database
            $document->forceDelete(); // Usa forceDelete() per eliminare definitivamente
            
            $redirectUrl = route('admin.documents.index', [$tableRef, $idRef]);
            if ($request->staff_id) {
                $redirectUrl .= '?staff_id=' . $request->staff_id;
            }
            
            return redirect($redirectUrl)->with('success', 'Documento eliminato con successo!');
            
        } catch (\Exception $e) {
            $redirectUrl = route('admin.documents.index', [$tableRef, $idRef]);
            if ($request->staff_id) {
                $redirectUrl .= '?staff_id=' . $request->staff_id;
            }
            return redirect($redirectUrl)->with('error', 'Errore durante l\'eliminazione: ' . $e->getMessage());
        }
    }
    
    public function destroyAll($tableRef, $idRef, Request $request)
    {
        try {
            $documents = Document::where('table_ref', $tableRef)
                ->where('id_ref', $idRef)
                ->get();
            
            $count = 0;
            foreach ($documents as $document) {
                $filePath = public_path($document->path_doc . '/' . $document->file_name);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $document->forceDelete();
                $count++;
            }
            
            $redirectUrl = route('admin.documents.index', [$tableRef, $idRef]);
            if ($request->staff_id) {
                $redirectUrl .= '?staff_id=' . $request->staff_id;
            }
            
            if ($count > 0) {
                return redirect($redirectUrl)->with('success', "{$count} documento/i eliminato/i con successo!");
            } else {
                return redirect($redirectUrl)->with('warning', 'Nessun documento da eliminare.');
            }
            
        } catch (\Exception $e) {
            $redirectUrl = route('admin.documents.index', [$tableRef, $idRef]);
            if ($request->staff_id) {
                $redirectUrl .= '?staff_id=' . $request->staff_id;
            }
            return redirect($redirectUrl)->with('error', 'Errore durante l\'eliminazione: ' . $e->getMessage());
        }
    }
    
    public function download($tableRef, $idRef, $documentId, Request $request)
    {
        $document = Document::where('table_ref', $tableRef)
            ->where('id_ref', $idRef)
            ->where('id', $documentId)
            ->firstOrFail();
        
        $filePath = public_path($document->path_doc . '/' . $document->file_name);
        
        if (file_exists($filePath)) {
            $originalName = preg_replace('/_\d+_\w+\./', '.', $document->file_name);
            return response()->download($filePath, $originalName);
        }
        
        $redirectUrl = route('admin.documents.index', [$tableRef, $idRef]);
        if ($request->staff_id) {
            $redirectUrl .= '?staff_id=' . $request->staff_id;
        }
        
        return redirect($redirectUrl)->with('error', 'File non trovato sul server');
    }
}