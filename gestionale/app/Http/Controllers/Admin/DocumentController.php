<?php
// app/Http/Controllers/Admin/DocumentController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Staff;
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
        
        // Determina il titolo della pagina in base al tipo
        $title = '';
        $backUrl = '';
        
        if ($tableRef === 'staff') {
            $staff = Staff::find($idRef);
            $title = $staff ? $staff->full_name : 'Documenti Personale';
            $backUrl = route('admin.staff.show', $idRef);
        } else {
            $backUrl = route('admin.expiration.index');
        }
        
        return view('admin.documents.index', compact('documents', 'tableRef', 'idRef', 'title', 'backUrl'));
    }
    
    public function store(Request $request, $tableRef, $idRef)
    {
        $request->validate([
            'document_file' => 'required|file|max:5120|mimes:pdf,jpeg,jpg,png|mimetypes:image/jpeg,image/jpg,image/png,application/pdf',
            'titolo' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:500',
        ]);
        
        $file = $request->file('document_file');
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        
        // Pulisci il nome originale da caratteri speciali
        $cleanOriginalName = preg_replace('/[^a-zA-Z0-9-_]/', '_', $originalName);
        
        // Crea il nome file: nome_originale_timestamp.estensione
        $timestamp = time();
        $savedName = $cleanOriginalName . '_' . $timestamp . '.' . $extension;
        
        // Crea cartella: public/upload/staff/{id_ref}/
        $folderPath = "upload/staff/{$idRef}";
        $fullPath = public_path($folderPath);
        
        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
        
        $file->move($fullPath, $savedName);
        
        Document::create([
            'titolo' => $request->titolo ?: $originalName,
            'note' => $request->note,
            'path_doc' => $folderPath,
            'file_name' => $savedName,
            'table_ref' => $tableRef,
            'id_ref' => $idRef
        ]);
        
        $redirectUrl = route('admin.documents.index', [$tableRef, $idRef]);
        if ($request->staff_id) {
            $redirectUrl .= '?staff_id=' . $request->staff_id;
        }
        
        return redirect($redirectUrl)->with('success', 'Documento caricato con successo!');
    }
    
    public function destroy($tableRef, $idRef, $documentId, Request $request)
    {
        $document = Document::where('table_ref', $tableRef)
            ->where('id_ref', $idRef)
            ->where('id', $documentId)
            ->firstOrFail();
        
        // Elimina file fisico
        $filePath = public_path($document->path_doc . '/' . $document->file_name);
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        $document->delete();
        
        $redirectUrl = route('admin.documents.index', [$tableRef, $idRef]);
        if ($request->staff_id) {
            $redirectUrl .= '?staff_id=' . $request->staff_id;
        }
        
        return redirect($redirectUrl)->with('success', 'Documento eliminato con successo!');
    }
    
    public function download($tableRef, $idRef, $documentId)
    {
        $document = Document::where('table_ref', $tableRef)
            ->where('id_ref', $idRef)
            ->where('id', $documentId)
            ->firstOrFail();
        
        $filePath = public_path($document->path_doc . '/' . $document->file_name);
        
        if (file_exists($filePath)) {
            // Estrai il nome originale dal file_name (prima del timestamp)
            $originalName = preg_replace('/_\d+\./', '.', $document->file_name);
            return response()->download($filePath, $originalName);
        }
        
        return back()->with('error', 'File non trovato sul server');
    }
}