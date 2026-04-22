<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;

class VademecumController extends Controller
{
    /**
     * Restituisce il PDF dalla cartella public/vademecum
     */
    public function getPdf()
    {
        $vademecumPath = public_path('vademecum');
        
        if (!File::exists($vademecumPath)) {
            abort(404, 'Cartella vademecum non trovata');
        }
        
        $pdfFiles = File::glob($vademecumPath . '/*.pdf');
        
        if (!empty($pdfFiles)) {
            $pdfFile = $pdfFiles[0];
            $filename = basename($pdfFile);
            
            return Response::file($pdfFile, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"'
            ]);
        }
        
        abort(404, 'Nessun file PDF trovato nella cartella vademecum');
    }
    
    /**
     * Restituisce informazioni sul PDF
     */
    public function info()
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_staff')) {
            return response()->json(['success' => false, 'message' => 'Permesso negato'], 403);
        }
        
        $vademecumPath = public_path('vademecum');
        
        if (!File::exists($vademecumPath)) {
            return response()->json(['success' => false, 'message' => 'Cartella non trovata']);
        }
        
        $pdfFiles = File::glob($vademecumPath . '/*.pdf');
        
        if (!empty($pdfFiles)) {
            $pdfFile = $pdfFiles[0];
            return response()->json([
                'success' => true,
                'filename' => basename($pdfFile),
                'url' => route('admin.vademecum.pdf'),
                'size' => File::size($pdfFile),
                'last_modified' => File::lastModified($pdfFile)
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Nessun PDF trovato'
        ]);
    }
    
    /**
     * Carica un nuovo PDF
     */
    public function upload(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_staff')) {
            return redirect()->back()->with('error', 'Non hai i permessi necessari.');
        }
        
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:10240',
        ]);
        
        try {
            $file = $request->file('pdf_file');
            
            // Prende il nome originale senza estensione
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            
            // Pulisce il nome (rimuove caratteri speciali)
            $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
            
            // Mantiene l'estensione .pdf
            $filename = $cleanName . '.pdf';
            
            // Percorso della cartella public/vademecum
            $destinationPath = public_path('vademecum');
            
            // Crea la cartella se non esiste
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            
            // Elimina tutti i PDF esistenti nella cartella
            $existingPdfs = glob($destinationPath . '/*.pdf');
            foreach ($existingPdfs as $existingPdf) {
                unlink($existingPdf);
            }
            
            // Sposta il nuovo file
            $file->move($destinationPath, $filename);
            
            // Prepara i dati per la vista
            $currentPdf = [
                'filename' => $filename,
                'size' => File::size($destinationPath . '/' . $filename),
                'last_modified' => File::lastModified($destinationPath . '/' . $filename)
            ];
            
            return redirect()->route('admin.vademecum.index')->with('success', 'PDF VADEMECUM ASSUNZIONE caricato con successo!')->with('currentPdf', $currentPdf);
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Errore durante l\'upload: ' . $e->getMessage());
        }
    }
}