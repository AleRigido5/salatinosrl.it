<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Staff;
use App\Models\Activity;
use App\Models\Expiration;
use App\Models\Vehicles;
use App\Models\AdminTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
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
        $name = substr($name, 0, 80);
        $name = preg_replace('/_+/', '_', $name);
        $name = trim($name, '_');
        
        return $name ?: 'documento';
    }

    /**
     * Genera il percorso su S3 per i documenti
     * Il path su S3 inizierà con 's3://' per essere riconosciuto
     */
    private function getDocumentS3Path($tableRef, $idRef)
    {
        switch ($tableRef) {
            case 'expiration-staff':
                $expiration = Expiration::find($idRef);
                if ($expiration && $expiration->id_references) {
                    $staff = Staff::find($expiration->id_references);
                    if ($staff) {
                        $staffName = $this->sanitizeFolderName($staff->full_name);
                        $titolo = $this->sanitizeFolderName($expiration->titolo);
                        return "s3://documents/expiration-staff/{$staffName}_{$titolo}";
                    }
                }
                return "s3://documents/expiration-staff/{$idRef}";
                
            case 'expiration-vehicles':
                $expiration = Expiration::find($idRef);
                if ($expiration) {
                    $vehicle = $expiration->vehicles()->first();
                    if ($vehicle) {
                        $vehicleName = '';
                        if ($vehicle->marca) {
                            $vehicleName .= $this->sanitizeFolderName($vehicle->marca);
                        }
                        if ($vehicle->modello) {
                            $vehicleName .= '_' . $this->sanitizeFolderName($vehicle->modello);
                        }
                        if ($vehicle->targa) {
                            $vehicleName .= '_' . $this->sanitizeFolderName($vehicle->targa);
                        }
                        if (empty($vehicleName)) {
                            $vehicleName = 'mezzo_' . $vehicle->id;
                        }
                        $titolo = $this->sanitizeFolderName($expiration->titolo);
                        return "s3://documents/expiration-vehicles/{$vehicleName}_{$titolo}";
                    }
                }
                return "s3://documents/expiration-vehicles/{$idRef}";
                
            case 'staff':
                $staff = Staff::find($idRef);
                if ($staff) {
                    $staffName = $this->sanitizeFolderName($staff->full_name);
                    return "s3://documents/staff/{$staffName}";
                }
                return "s3://documents/staff/{$idRef}";
                
            case 'vehicles':
                $vehicle = Vehicles::find($idRef);
                if ($vehicle) {
                    $vehicleName = '';
                    if ($vehicle->marca) {
                        $vehicleName .= $this->sanitizeFolderName($vehicle->marca);
                    }
                    if ($vehicle->modello) {
                        $vehicleName .= '_' . $this->sanitizeFolderName($vehicle->modello);
                    }
                    if ($vehicle->targa) {
                        $vehicleName .= '_' . $this->sanitizeFolderName($vehicle->targa);
                    }
                    if (empty($vehicleName)) {
                        $vehicleName = 'mezzo_' . $vehicle->id;
                    }
                    return "s3://documents/vehicles/{$vehicleName}";
                }
                return "s3://documents/vehicles/{$idRef}";

            // 🆕 TASK AMMINISTRATIVI (sezione "In Evidenza")
            case 'admin_tasks':
                $task = AdminTask::find($idRef);
                if ($task) {
                    $titolo = $this->sanitizeFolderName($task->title);
                    return "s3://documents/admin_tasks/{$idRef}_{$titolo}";
                }
                return "s3://documents/admin_tasks/{$idRef}";
                
            default:
                return "s3://documents/{$idRef}";
        }
    }

    /**
     * Mostra i documenti per un riferimento specifico
     */
    public function index($tableRef, $idRef, Request $request)
    {
        $documents = Document::where('table_ref', $tableRef)
            ->where('id_ref', $idRef)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->get();
        
        $title = '';
        $backUrl = '';
        $staffId = $request->get('staff_id');
        $vehicleId = $request->get('vehicle_id');
        
        if ($tableRef === 'staff') {
            $staff = Staff::find($idRef);
            $title = $staff ? $staff->full_name : 'Documenti Personale';
            $backUrl = route('admin.staff.show', $idRef);
        } 
        elseif ($tableRef === 'expiration-staff') {
            $expiration = Expiration::find($idRef);
            if ($expiration && $expiration->id_references) {
                $staff = Staff::find($expiration->id_references);
                $title = $staff ? $staff->full_name : 'Documenti Scadenza Personale';
            } else {
                $title = 'Documenti Scadenza Personale';
            }
            $backUrl = route('admin.expiration-staff.index', ['staffId' => $staffId]);
        }
        elseif ($tableRef === 'expiration-vehicles') {
            $expiration = Expiration::find($idRef);
            $title = 'Documenti Scadenza Mezzo';
            
            if ($vehicleId) {
                $vehicle = Vehicles::find($vehicleId);
                if ($vehicle) {
                    $title = ($vehicle->full_name ?? $vehicle->targa) . ' - Documenti Scadenza';
                }
                $backUrl = route('admin.expiration-vehicle.index', ['vehicleId' => $vehicleId]);
            } else {
                if ($expiration && $expiration->vehicles()->count() > 0) {
                    $firstVehicle = $expiration->vehicles->first();
                    if ($firstVehicle) {
                        $title = ($firstVehicle->full_name ?? $firstVehicle->targa) . ' - Documenti Scadenza';
                        $backUrl = route('admin.expiration-vehicle.index', ['vehicleId' => $firstVehicle->id]);
                    } else {
                        $backUrl = route('admin.expiration-vehicle.index');
                    }
                } else {
                    $backUrl = route('admin.expiration-vehicle.index');
                }
            }
        }
        elseif ($tableRef === 'vehicles') {
            $vehicle = Vehicles::find($idRef);
            $title = $vehicle ? ($vehicle->full_name ?? $vehicle->targa) : 'Documenti Mezzo';
            $backUrl = route('admin.vehicles.show', $idRef);
        }
        // 🆕 TASK AMMINISTRATIVI (sezione "In Evidenza")
        elseif ($tableRef === 'admin_tasks') {
            $task = AdminTask::find($idRef);
            $title = $task ? $task->title . ' - Allegati' : 'Documenti Task';
            $backUrl = route('admin.admin-tasks.index');
        }
        else {
            $backUrl = route('admin.expiration-staff.index');
        }
        
        return view('admin.documents.index', compact(
            'documents', 
            'tableRef', 
            'idRef', 
            'title', 
            'backUrl', 
            'staffId', 
            'vehicleId'
        ));
    }
    
    /**
     * Salva i documenti su Amazon S3
     */
    public function store(Request $request, $tableRef, $idRef)
    {
        $request->validate([
            'document_files' => 'required|array',
            'document_files.*' => 'file|max:5120|mimes:pdf,jpeg,jpg,png',
            'titolo' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:500',
        ]);
        
        $files = $request->file('document_files');
        $commonTitle = $request->titolo;
        $commonNote = $request->note;
        $staffId = $request->staff_id;
        $vehicleId = $request->vehicle_id;
        
        $successCount = 0;
        $errors = [];
        
        $s3PathWithPrefix = $this->getDocumentS3Path($tableRef, $idRef);
        $s3Path = str_replace('s3://', '', $s3PathWithPrefix);
        
        foreach ($files as $file) {
            try {
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                
                $cleanOriginalName = preg_replace('/[^a-zA-Z0-9-_]/', '_', $originalName);
                $timestamp = time() . '_' . Str::random(4);
                $savedName = $cleanOriginalName . '_' . $timestamp . '.' . $extension;
                
                $fullS3Path = $s3Path . '/' . $savedName;
                
                $content = file_get_contents($file->getRealPath());
                
                $saved = Storage::disk('s3')->put($fullS3Path, $content);
                
                if (!$saved) {
                    throw new \Exception("Errore durante il caricamento su S3: {$originalName}");
                }
                
                $title = $commonTitle ? $commonTitle . ' - ' . $originalName : $originalName;
                
                Document::create([
                    'titolo' => $title,
                    'note' => $commonNote,
                    'path_doc' => $s3PathWithPrefix,
                    'file_name' => $savedName,
                    'table_ref' => $tableRef,
                    'id_ref' => $idRef,
                ]);
                
                $successCount++;
                
                Log::info('Documento caricato su S3', [
                    'path' => $fullS3Path,
                    'file' => $savedName,
                    'table_ref' => $tableRef,
                    'id_ref' => $idRef
                ]);
                
            } catch (\Exception $e) {
                $errors[] = "Errore per il file '{$originalName}': " . $e->getMessage();
                Log::error('Errore caricamento documento su S3: ' . $e->getMessage());
            }
        }
        
        $redirectUrl = $this->buildRedirectUrl($tableRef, $idRef, $staffId, $vehicleId);
        
        $message = "{$successCount} documento/i caricato/i con successo su Amazon S3!";
        
        if (!empty($errors)) {
            return redirect($redirectUrl)->with('errors', $errors)->with('warning', $message);
        }
        
        return redirect($redirectUrl)->with('success', $message);
    }
    
    /**
     * Elimina un documento da Amazon S3
     */
    public function destroy($tableRef, $idRef, $documentId, Request $request)
    {
        try {
            $document = Document::where('table_ref', $tableRef)
                ->where('id_ref', $idRef)
                ->where('id', $documentId)
                ->firstOrFail();
            
            if (str_starts_with($document->path_doc, 's3://')) {
                $s3Path = str_replace('s3://', '', $document->path_doc) . '/' . $document->file_name;
                $deleted = Storage::disk('s3')->delete($s3Path);
                
                if (!$deleted) {
                    Log::warning('File non trovato su S3 durante eliminazione', ['path' => $s3Path]);
                }
            } else {
                $filePath = public_path($document->path_doc . '/' . $document->file_name);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            $document->forceDelete();
            
            $redirectUrl = $this->buildRedirectUrl($tableRef, $idRef, $request->staff_id, $request->vehicle_id);
            return redirect($redirectUrl)->with('success', 'Documento eliminato con successo da S3!');
            
        } catch (\Exception $e) {
            Log::error('Errore durante eliminazione documento: ' . $e->getMessage());
            $redirectUrl = $this->buildRedirectUrl($tableRef, $idRef, $request->staff_id, $request->vehicle_id);
            return redirect($redirectUrl)->with('error', 'Errore durante l\'eliminazione: ' . $e->getMessage());
        }
    }
    
    /**
     * Scarica un documento da Amazon S3
     */
    public function download($tableRef, $idRef, $documentId, Request $request)
    {
        try {
            $document = Document::where('table_ref', $tableRef)
                ->where('id_ref', $idRef)
                ->where('id', $documentId)
                ->firstOrFail();
            
            if (str_starts_with($document->path_doc, 's3://')) {
                $s3Path = str_replace('s3://', '', $document->path_doc) . '/' . $document->file_name;
                $content = Storage::disk('s3')->get($s3Path);
                
                if (!$content) {
                    throw new \Exception('File non trovato su S3');
                }
                
                $originalName = $document->file_name;
                $parts = explode('_', $document->file_name);
                if (count($parts) >= 3) {
                    array_pop($parts);
                    array_pop($parts);
                    $originalName = implode('_', $parts) . '.' . $document->extension;
                }
                
                return response($content)
                    ->header('Content-Type', 'application/octet-stream')
                    ->header('Content-Disposition', 'attachment; filename="' . $originalName . '"');
            } else {
                $filePath = public_path($document->path_doc . '/' . $document->file_name);
                
                if (!file_exists($filePath)) {
                    throw new \Exception('File non trovato');
                }
                
                $originalName = $document->file_name;
                $parts = explode('_', $document->file_name);
                if (count($parts) >= 3) {
                    array_pop($parts);
                    array_pop($parts);
                    $originalName = implode('_', $parts) . '.' . $document->extension;
                }
                
                return response()->download($filePath, $originalName);
            }
            
        } catch (\Exception $e) {
            Log::error('Errore durante download documento: ' . $e->getMessage());
            $redirectUrl = $this->buildRedirectUrl($tableRef, $idRef, $request->staff_id, $request->vehicle_id);
            return redirect($redirectUrl)->with('error', 'Errore durante il download: ' . $e->getMessage());
        }
    }
    
    /**
     * Elimina tutti i documenti di un riferimento da Amazon S3
     */
    public function destroyAll($tableRef, $idRef, Request $request)
    {
        try {
            if (empty($tableRef) || empty($idRef)) {
                throw new \Exception('Parametri non validi');
            }
            
            $documents = Document::where('table_ref', $tableRef)
                ->where('id_ref', $idRef)
                ->get();
            
            if ($documents->isEmpty()) {
                $redirectUrl = $this->buildRedirectUrl($tableRef, $idRef, $request->staff_id, $request->vehicle_id);
                return redirect($redirectUrl)->with('warning', 'Nessun documento da eliminare.');
            }
            
            $count = 0;
            foreach ($documents as $document) {
                if (str_starts_with($document->path_doc, 's3://')) {
                    $s3Path = str_replace('s3://', '', $document->path_doc) . '/' . $document->file_name;
                    Storage::disk('s3')->delete($s3Path);
                } else {
                    $filePath = public_path($document->path_doc . '/' . $document->file_name);
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                $document->forceDelete();
                $count++;
            }
            
            $redirectUrl = $this->buildRedirectUrl($tableRef, $idRef, $request->staff_id, $request->vehicle_id);
            return redirect($redirectUrl)->with('success', "{$count} documento/i eliminato/i con successo da S3!");
            
        } catch (\Exception $e) {
            Log::error('Errore destroyAll: ' . $e->getMessage());
            $redirectUrl = $this->buildRedirectUrl($tableRef, $idRef, $request->staff_id, $request->vehicle_id);
            return redirect($redirectUrl)->with('error', 'Errore durante l\'eliminazione: ' . $e->getMessage());
        }
    }
    
    /**
     * Costruisce l'URL di redirect mantenendo i parametri
     */
    private function buildRedirectUrl($tableRef, $idRef, $staffId = null, $vehicleId = null)
    {
        if ($tableRef === 'expiration-staff') {
            $url = route('admin.documents.index', [$tableRef, $idRef]);
            if ($staffId) {
                $url .= '?staff_id=' . $staffId;
            }
        } elseif ($tableRef === 'expiration-vehicles') {
            $url = route('admin.documents.index', [$tableRef, $idRef]);
            if ($vehicleId) {
                $url .= '?vehicle_id=' . $vehicleId;
            }
        } else {
            $url = route('admin.documents.index', [$tableRef, $idRef]);
        }
        
        return $url;
    }
}