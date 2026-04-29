<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Staff;
use App\Models\Expiration;
use App\Models\Vehicles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
     * Genera il percorso della cartella per i documenti
     */
    private function getDocumentFolderPath($tableRef, $idRef)
    {
        switch ($tableRef) {
            case 'expiration-staff':
                $expiration = Expiration::find($idRef);
                if ($expiration && $expiration->id_references) {
                    $staff = Staff::find($expiration->id_references);
                    if ($staff) {
                        $staffName = $this->sanitizeFolderName($staff->full_name);
                        $titolo = $this->sanitizeFolderName($expiration->titolo);
                        return "uploads/documents/expiration-staff/{$staffName}_{$titolo}";
                    }
                }
                return "uploads/documents/expiration-staff/{$idRef}";
                
            case 'expiration-vehicles':
                $expiration = Expiration::find($idRef);
                if ($expiration && $expiration->id_references) {
                    $vehicle = Vehicles::find($expiration->id_references);
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
                        return "uploads/documents/expiration-vehicles/{$vehicleName}_{$titolo}";
                    }
                }
                return "uploads/documents/expiration-vehicles/{$idRef}";
                
            case 'staff':
                $staff = Staff::find($idRef);
                $staffName = $staff ? $this->sanitizeFolderName($staff->full_name) : $idRef;
                return "uploads/documents/staff/{$staffName}";
                
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
                    return "uploads/documents/vehicles/{$vehicleName}";
                }
                return "uploads/documents/vehicles/{$idRef}";
                
            default:
                return "uploads/documents/{$idRef}";
        }
    }

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
        else {
            $backUrl = route('admin.expiration-staff.index');
        }
        
        return view('admin.documents.index', compact('documents', 'tableRef', 'idRef', 'title', 'backUrl', 'staffId', 'vehicleId'));
    }
    
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
        
        // Genera il percorso della cartella
        $folderPath = $this->getDocumentFolderPath($tableRef, $idRef);
        $fullPath = public_path($folderPath);
        
        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
        
        foreach ($files as $file) {
            try {
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                
                $cleanOriginalName = preg_replace('/[^a-zA-Z0-9-_]/', '_', $originalName);
                $timestamp = time() . '_' . Str::random(4);
                $savedName = $cleanOriginalName . '_' . $timestamp . '.' . $extension;
                
                $file->move($fullPath, $savedName);
                
                $title = $commonTitle ? $commonTitle . ' - ' . $originalName : $originalName;
                
                Document::create([
                    'titolo' => $title,
                    'note' => $commonNote,
                    'path_doc' => $folderPath,
                    'file_name' => $savedName,
                    'table_ref' => $tableRef,
                    'id_ref' => $idRef,
                    'drive_file_id' => null
                ]);
                
                $successCount++;
                
            } catch (\Exception $e) {
                $errors[] = "Errore per il file '{$originalName}': " . $e->getMessage();
            }
        }
        
        // Costruisci URL di redirect
        $redirectUrl = $this->buildRedirectUrl($tableRef, $idRef, $staffId, $vehicleId);
        
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
            
            $filePath = public_path($document->path_doc . '/' . $document->file_name);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            $document->forceDelete();
            
            $redirectUrl = $this->buildRedirectUrl($tableRef, $idRef, $request->staff_id, $request->vehicle_id);
            return redirect($redirectUrl)->with('success', 'Documento eliminato con successo!');
            
        } catch (\Exception $e) {
            $redirectUrl = $this->buildRedirectUrl($tableRef, $idRef, $request->staff_id, $request->vehicle_id);
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
        
        $redirectUrl = $this->buildRedirectUrl($tableRef, $idRef, $request->staff_id, $request->vehicle_id);
        return redirect($redirectUrl)->with('error', 'File non trovato');
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
            
            $redirectUrl = $this->buildRedirectUrl($tableRef, $idRef, $request->staff_id, $request->vehicle_id);
            
            if ($count > 0) {
                return redirect($redirectUrl)->with('success', "{$count} documento/i eliminato/i con successo!");
            } else {
                return redirect($redirectUrl)->with('warning', 'Nessun documento da eliminare.');
            }
            
        } catch (\Exception $e) {
            $redirectUrl = $this->buildRedirectUrl($tableRef, $idRef, $request->staff_id, $request->vehicle_id);
            return redirect($redirectUrl)->with('error', 'Errore durante l\'eliminazione: ' . $e->getMessage());
        }
    }
    
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