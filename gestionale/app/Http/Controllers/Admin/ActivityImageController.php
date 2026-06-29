<?php
// app/Http/Controllers/Admin/ActivityImageController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ActivityImageController extends Controller
{
    /**
     * Mostra la pagina di gestione immagini
     */
    public function index($activityId)
    {
        $activity = Activity::with('images')->findOrFail($activityId);
        $images = $activity->images;
        
        return view('admin.activities.images', compact('activity', 'images'));
    }

    /**
     * Carica immagini su S3
     */
    public function store(Request $request, $activityId)
    {
        try {
            $request->validate([
                'images' => 'required|array',
                'images.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,webp',
                'titolo' => 'nullable|string|max:255',
                'note' => 'nullable|string|max:500',
            ]);

            $activity = Activity::findOrFail($activityId);
            $files = $request->file('images');
            $commonTitle = $request->titolo;
            $commonNote = $request->note;
            
            $successCount = 0;
            $errors = [];
            $uploadedImages = [];

            // Genera il percorso su S3
            $s3Path = $this->getImageS3Path($activity);
            
            foreach ($files as $file) {
                try {
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    
                    // Pulisci il nome e aggiungi timestamp
                    $cleanOriginalName = preg_replace('/[^a-zA-Z0-9-_]/', '_', $originalName);
                    $timestamp = time() . '_' . Str::random(4);
                    $savedName = $cleanOriginalName . '_' . $timestamp . '.' . $extension;
                    
                    // Percorso completo su S3
                    $fullS3Path = $s3Path . '/' . $savedName;
                    
                    // Leggi il contenuto del file
                    $content = file_get_contents($file->getRealPath());
                    
                    // Carica su S3
                    $saved = Storage::disk('s3')->put($fullS3Path, $content);
                    
                    if (!$saved) {
                        throw new \Exception("Errore durante il caricamento su S3: {$originalName}");
                    }
                    
                    $title = $commonTitle ? $commonTitle . ' - ' . $originalName : $originalName;
                    $adminId = Auth::guard('admin')->id();
                    
                    // Salva nel database
                    $image = ActivityImage::create([
                        'activity_id' => $activityId,
                        'path_doc' => 's3://' . $s3Path,
                        'file_name' => $savedName,
                        'titolo' => $title,
                        'note' => $commonNote,
                        'created_by' => $adminId,
                        'updated_by' => $adminId,
                    ]);
                    
                    $uploadedImages[] = $image;
                    $successCount++;
                    
                    Log::info('Immagine attività caricata su S3', [
                        'activity_id' => $activityId,
                        'path' => $fullS3Path,
                        'file' => $savedName
                    ]);
                    
                } catch (\Exception $e) {
                    $errors[] = "Errore per il file '{$originalName}': " . $e->getMessage();
                    Log::error('Errore caricamento immagine attività: ' . $e->getMessage());
                }
            }

            // Ritorna sempre JSON
            return response()->json([
                'success' => $successCount > 0,
                'message' => "{$successCount} immagine/i caricata/e con successo!",
                'errors' => $errors,
                'images' => $uploadedImages
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore di validazione',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Errore generale store immagini: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Errore: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Elimina un'immagine
     */
    public function destroy($activityId, $imageId)
    {
        try {
            $image = ActivityImage::where('activity_id', $activityId)
                ->where('id', $imageId)
                ->firstOrFail();
            
            // Elimina da S3
            if (str_starts_with($image->path_doc, 's3://')) {
                $s3Path = str_replace('s3://', '', $image->path_doc) . '/' . $image->file_name;
                Storage::disk('s3')->delete($s3Path);
            }
            
            $image->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Immagine eliminata con successo!'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Errore eliminazione immagine: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Errore: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Elimina tutte le immagini di un'attività
     */
    public function destroyAll($activityId)
    {
        try {
            $images = ActivityImage::where('activity_id', $activityId)->get();
            
            foreach ($images as $image) {
                if (str_starts_with($image->path_doc, 's3://')) {
                    $s3Path = str_replace('s3://', '', $image->path_doc) . '/' . $image->file_name;
                    Storage::disk('s3')->delete($s3Path);
                }
                $image->delete();
            }
            
            return response()->json([
                'success' => true,
                'message' => count($images) . ' immagini eliminate con successo!'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Errore eliminazione tutte immagini: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Errore: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aggiorna l'ordine delle immagini (drag & drop)
     */
    public function updateOrder(Request $request, $activityId)
    {
        try {
            $request->validate([
                'order' => 'required|array',
                'order.*' => 'integer|exists:activity_images,id'
            ]);

            foreach ($request->order as $index => $imageId) {
                ActivityImage::where('activity_id', $activityId)
                    ->where('id', $imageId)
                    ->update(['order' => $index]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Ordine aggiornato con successo!'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Errore aggiornamento ordine: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Errore: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Genera il percorso su S3 per le immagini dell'attività
     */
    private function getImageS3Path($activity)
    {
        // Ottieni il nome del servizio e del centro di costo per creare una cartella significativa
        $serviceName = $activity->service ? $activity->service->Titolo : 'senza_servizio';
        $costCenterName = $activity->costCenter ? $activity->costCenter->Nome : 'senza_cantiere';
        
        $serviceName = $this->sanitizeFolderName($serviceName);
        $costCenterName = $this->sanitizeFolderName($costCenterName);
        $data = $activity->data_activities ? $activity->data_activities->format('Y-m-d') : 'data_sconosciuta';
        
        return "documents/activities/{$data}_{$serviceName}_{$costCenterName}";
    }

    /**
     * Pulisce una stringa per renderla utilizzabile come nome cartella
     */
    private function sanitizeFolderName($name)
    {
        if (empty($name)) {
            return 'documento';
        }
        
        $name = preg_replace('/[^a-zA-Z0-9À-ÿ\s-]/u', '', $name);
        $name = preg_replace('/[\s-]+/', '_', $name);
        $name = substr($name, 0, 50);
        $name = preg_replace('/_+/', '_', $name);
        $name = trim($name, '_');
        
        return $name ?: 'documento';
    }
}