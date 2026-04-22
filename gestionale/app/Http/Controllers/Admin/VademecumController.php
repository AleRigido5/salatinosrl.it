<?php
// app/Http/Controllers/Admin/VademecumController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class VademecumController extends Controller
{
    /**
     * Restituisce il primo file PDF trovato nella cartella vademecum
     */
    public function getPdf()
    {
        // Percorso della cartella public/vademecum
        $vademecumPath = public_path('vademecum');
        
        // Cerca tutti i file PDF nella cartella
        $pdfFiles = File::glob($vademecumPath . '/*.pdf');
        
        if (!empty($pdfFiles)) {
            // Prende il primo file PDF trovato
            $pdfFile = $pdfFiles[0];
            $filename = basename($pdfFile);
            
            // Restituisce il file PDF
            return response()->file($pdfFile, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"'
            ]);
        }
        
        // Se non trova nessun PDF
        abort(404, 'Nessun file VADEMECUM trovato');
    }
    
    /**
     * Restituisce le informazioni sul PDF disponibile (per l'interfaccia)
     */
    public function info()
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_staff')) {
            return response()->json(['success' => false, 'message' => 'Permesso negato'], 403);
        }
        
        $vademecumPath = public_path('vademecum');
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
     * Permette di caricare un nuovo PDF (sovrascrivendo quello esistente)
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
            
            return redirect()->back()->with('success', 'PDF VADEMECUM ASSUNZIONE caricato con successo!');
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Errore durante l\'upload: ' . $e->getMessage());
        }
    }
}