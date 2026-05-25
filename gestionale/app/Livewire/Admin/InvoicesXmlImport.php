<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\InvoiceReceived;
use App\Models\InvoiceRow;
use App\Models\Ownership;
use App\Models\Entity;
use App\Models\CostCenter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class InvoicesXmlImport extends Component
{
    use WithFileUploads;

    // ============================================
    // CONFIGURAZIONE STORAGE - CAMBIA QUI PER PASSARE DA S3 A LOCALE
    // Valori possibili: 's3' o 'local'
    // ============================================
    private const STORAGE_DRIVER = 's3';  // <-- CAMBIA QUI in 'local' per usare storage locale

    // ============================================
    // TRAIT PER PULIZIA UTF-8 (integrato direttamente)
    // ============================================
    
    /**
     * Pulisce una stringa da caratteri UTF-8 malformati
     */
    private function cleanUtf8String($string)
    {
        if (is_null($string) || $string === '') {
            return $string;
        }
        
        // Se non è una stringa, restituisci così com'è
        if (!is_string($string)) {
            return $string;
        }
        
        // Forza la conversione a UTF-8
        if (!mb_check_encoding($string, 'UTF-8')) {
            $string = mb_convert_encoding($string, 'UTF-8', 'auto');
        }
        
        // Rimuovi caratteri di controllo non validi
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $string);
        
        // Decodifica e ricodifica per rimuovere caratteri non validi
        $string = iconv('UTF-8', 'UTF-8//IGNORE', $string);
        
        return $string;
    }
    
    /**
     * Pulisce un array ricorsivamente
     */
    private function cleanArrayUtf8($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_string($value)) {
                    $data[$key] = $this->cleanUtf8String($value);
                } elseif (is_array($value)) {
                    $data[$key] = $this->cleanArrayUtf8($value);
                }
            }
        } elseif (is_string($data)) {
            $data = $this->cleanUtf8String($data);
        }
        
        return $data;
    }

    // ============================================
    // PROPRIETÀ
    // ============================================
    
    public $xml_file;
    public $xml_filename;
    public $xml_content;
    public $file_hash;
    public $payments = [];
    public $vatSummaries = [];
    public $xml_parsed = false;
    public $extracted_attachments = [];
    protected $vatRatesMap = [];
    
    public $id_ownership;
    public $id_entities;
    public $type_invoice = '';
    public $n_invoice = '';
    public $data_invoice = '';
    public $importo_totale = 0;
    public $causale = '';
    public $divisa = 'EUR';
    public $status = 'bozza';
    public $sdi_id = '';
    
    public $committente_denominazione = '';
    public $committente_partita_iva = '';
    public $committente_codice_fiscale = '';
    public $committente_indirizzo = '';
    public $committente_cap = '';
    public $committente_comune = '';
    public $committente_provincia = '';
    public $committente_nazione = '';
    
    public $fornitore_denominazione = '';
    public $fornitore_partita_iva = '';
    public $fornitore_codice_fiscale = '';
    public $fornitore_indirizzo = '';
    public $fornitore_cap = '';
    public $fornitore_comune = '';
    public $fornitore_provincia = '';
    public $fornitore_nazione = '';
    public $fornitore_telefono = '';
    public $fornitore_email = '';
    
    public $supplier_found = false;
    public $supplier_not_found = false;
    public $supplier_display = '';
    public $supplier_created_by_system = false;
    public $ownership_display = '';

    public $rows = [];
    
    public $cost_center_all_search = '';
    public $cost_center_all_results = [];
    public $show_cost_center_all_dropdown = false;
    public $row_cost_center_search = [];
    public $row_cost_center_results = [];
    public $show_row_cost_center_dropdown = [];
    
    public $all_ownerships = [];
    public $all_costCenters = [];

    // Mezzo globale
    public $vehicle_all_search = '';
    public $vehicle_all_results = [];
    public $show_vehicle_all_dropdown = false;

    // Mezzo per riga
    public $row_vehicle_search = [];
    public $row_vehicle_results = [];
    public $show_row_vehicle_dropdown = [];

    public $all_vehicles = [];

    protected $rules = [
        'id_entities' => 'required|exists:entities,id_cliente',
        'id_ownership' => 'nullable|exists:ownership,id_proprieta',
        'type_invoice' => 'required|string|max:10',
        'n_invoice' => 'required|string|max:100',
        'data_invoice' => 'required|date',
        'importo_totale' => 'nullable|numeric|min:0',
        'causale' => 'nullable|string',
        'divisa' => 'required|string|size:3',
        'status' => 'required|string',
        'sdi_id' => 'nullable|string|max:100',
        'rows.*.description' => 'required|string',
        'rows.*.quantity' => 'required|numeric|min:0',
        'rows.*.unit_price' => 'required|numeric|min:0',
        'rows.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
    ];

    // ============================================
    // METODI BASE
    // ============================================
    
    public function mount()
    {
        $this->loadAllData();
    }

    public function loadAllData()
    {
        // Carica i centri di costo
        $this->all_costCenters = CostCenter::where('valid', 1)
            ->select('id', 'Nome as name')
            ->orderBy('Nome')
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'name' => $this->cleanUtf8String($item->name)
                ];
            })
            ->toArray();
        
        // Carica i veicoli
        $this->all_vehicles = DB::table('vehicles')
            ->where('valid', 1)
            ->select('id', 'marca', 'modello', 'targa', 'tipologia')
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'name' => $this->cleanUtf8String(trim($item->marca . ' ' . $item->modello . ' (' . $item->targa . ')')),
                    'details' => $this->cleanUtf8String($item->tipologia . ' - ' . $item->targa)
                ];
            })
            ->toArray();
    }
    

    // ============================================
    // AUTOCOMPLETE CENTRO DI COSTO GLOBALE
    // ============================================
    public function updatedCostCenterAllSearch()
    {
        if (empty($this->cost_center_all_search)) {
            $this->cost_center_all_results = [];
            $this->show_cost_center_all_dropdown = false;
            return;
        }

        $search = strtolower($this->cost_center_all_search);
        $this->cost_center_all_results = array_values(array_slice(
            array_filter($this->all_costCenters, fn($cc) => str_contains(strtolower($cc['name']), $search)),
            0, 10
        ));
        $this->show_cost_center_all_dropdown = !empty($this->cost_center_all_results);
    }

    public function applyCostCenterToAllRows($id)
    {
        $cc = collect($this->all_costCenters)->firstWhere('id', (int)$id);
        
        if ($cc) {
            foreach ($this->rows as $index => $row) {
                $this->rows[$index]['id_cost_center'] = (int)$id;
                $this->rows[$index]['cost_center_name'] = $cc['name'];
                $this->row_cost_center_search[$index] = $cc['name'];
                $this->row_cost_center_results[$index] = [];
                $this->show_row_cost_center_dropdown[$index] = false;
            }
            
            $this->cost_center_all_search = $cc['name'];
            $this->cost_center_all_results = [];
            $this->show_cost_center_all_dropdown = false;
            
            $this->dispatch('alert', type: 'success', message: "Centro di costo '{$cc['name']}' applicato a tutte le " . count($this->rows) . " righe");
        }
    }

    // ============================================
    // AUTOCOMPLETE MEZZO GLOBALE
    // ============================================
    public function updatedVehicleAllSearch()
    {
        if (empty($this->vehicle_all_search)) {
            $this->vehicle_all_results = [];
            $this->show_vehicle_all_dropdown = false;
            return;
        }

        $search = strtolower($this->vehicle_all_search);
        $this->vehicle_all_results = array_values(array_slice(
            array_filter($this->all_vehicles, fn($vehicle) => str_contains(strtolower($vehicle['name']), $search)),
            0, 10
        ));
        $this->show_vehicle_all_dropdown = !empty($this->vehicle_all_results);
    }

    public function applyVehicleToAllRows($id)
    {
        $vehicle = collect($this->all_vehicles)->firstWhere('id', (int)$id);
        
        if ($vehicle) {
            foreach ($this->rows as $index => $row) {
                $this->rows[$index]['id_vehicle'] = (int)$id;
                $this->rows[$index]['vehicle_name'] = $vehicle['name'];
                $this->row_vehicle_search[$index] = $vehicle['name'];
                $this->row_vehicle_results[$index] = [];
                $this->show_row_vehicle_dropdown[$index] = false;
            }
            
            $this->vehicle_all_search = $vehicle['name'];
            $this->vehicle_all_results = [];
            $this->show_vehicle_all_dropdown = false;
            
            $this->dispatch('alert', type: 'success', message: "Mezzo '{$vehicle['name']}' applicato a tutte le " . count($this->rows) . " righe");
        }
    }

    // ============================================
    // AUTOCOMPLETE CENTRO DI COSTO PER RIGA
    // ============================================
    public function updatedRowCostCenterSearch($value, $index)
    {
        if (empty($value)) {
            $this->row_cost_center_results[$index] = [];
            $this->show_row_cost_center_dropdown[$index] = false;
            return;
        }

        $search = strtolower($value);
        $this->row_cost_center_results[$index] = array_values(array_slice(
            array_filter($this->all_costCenters, fn($cc) => str_contains(strtolower($cc['name']), $search)),
            0, 10
        ));
        $this->show_row_cost_center_dropdown[$index] = !empty($this->row_cost_center_results[$index]);
    }

    public function selectCostCenterForRow($id, $index)
    {
        $cc = collect($this->all_costCenters)->firstWhere('id', (int)$id);
        
        if ($cc) {
            $this->rows[$index]['id_cost_center'] = (int)$id;
            $this->rows[$index]['cost_center_name'] = $cc['name'];
            $this->row_cost_center_search[$index] = $cc['name'];
            $this->row_cost_center_results[$index] = [];
            $this->show_row_cost_center_dropdown[$index] = false;
            
            $this->dispatch('alert', type: 'success', message: "Centro di costo '{$cc['name']}' applicato alla riga " . ($index + 1));
        }
    }

    // ============================================
    // AUTOCOMPLETE MEZZO PER RIGA
    // ============================================
    public function updatedRowVehicleSearch($value, $index)
    {
        if (empty($value)) {
            $this->row_vehicle_results[$index] = [];
            $this->show_row_vehicle_dropdown[$index] = false;
            return;
        }

        $search = strtolower($value);
        $this->row_vehicle_results[$index] = array_values(array_slice(
            array_filter($this->all_vehicles, fn($vehicle) => str_contains(strtolower($vehicle['name']), $search)),
            0, 10
        ));
        $this->show_row_vehicle_dropdown[$index] = !empty($this->row_vehicle_results[$index]);
    }

    public function selectVehicleForRow($id, $index)
    {
        $vehicle = collect($this->all_vehicles)->firstWhere('id', (int)$id);
        
        if ($vehicle) {
            $this->rows[$index]['id_vehicle'] = (int)$id;
            $this->rows[$index]['vehicle_name'] = $vehicle['name'];
            $this->row_vehicle_search[$index] = $vehicle['name'];
            $this->row_vehicle_results[$index] = [];
            $this->show_row_vehicle_dropdown[$index] = false;
            
            $this->dispatch('alert', type: 'success', message: "Mezzo '{$vehicle['name']}' applicato alla riga " . ($index + 1));
        }
    }

    public function clearVehicleForRow($index)
    {
        $this->rows[$index]['id_vehicle'] = null;
        $this->rows[$index]['vehicle_name'] = '';
        $this->row_vehicle_search[$index] = '';
    }

    // ============================================
    // METODI PER GLI ALLEGATI - VERSIONE S3 (AMAZON)
    // ============================================
    
    /**
     * Estrae e salva gli allegati su S3
     */
    private function extractAttachmentsToS3($xml)
    {
        Log::info('=== extractAttachmentsToS3 INIZIO ===');
        
        // Verifica classe S3
        $classExists = class_exists('League\Flysystem\AwsS3V3\PortableVisibilityConverter');
        Log::info('Classe PortableVisibilityConverter esiste? ' . ($classExists ? 'SI' : 'NO'));
        
        if (!$classExists) {
            Log::error('Pacchetto AWS S3 non installato, utilizzo storage locale');
            return $this->extractAttachmentsToLocal($xml);
        }
        
        // Verifica che il disco s3 sia configurato
        try {
            $s3disk = Storage::disk('s3');
            Log::info('Disco S3 configurato correttamente');
        } catch (\Exception $e) {
            Log::error('Errore configurazione disco S3: ' . $e->getMessage());
            return $this->extractAttachmentsToLocal($xml);
        }
        
        $savedFiles = [];
        
        $basePrefix = 'invoice-received/';
        $tempFolderName = 'temp_' . $this->file_hash;
        $folderPrefix = $basePrefix . $tempFolderName . '/';
        
        Log::info('Cartella temporanea S3: ' . $folderPrefix);
        
        $allegati = null;
        
        if (isset($xml->FatturaElettronicaBody->Allegati)) {
            $allegati = $xml->FatturaElettronicaBody->Allegati;
            Log::info('Trovato nodo Allegati');
        } elseif (isset($xml->FatturaElettronicaBody->Allegato)) {
            $allegati = $xml->FatturaElettronicaBody;
            Log::info('Trovato nodo Allegato singolo');
        }
        
        if (!$allegati) {
            Log::warning('Nodo Allegati non trovato nell\'XML');
            return $savedFiles;
        }
        
        $allegatoNodes = isset($allegati->Allegato) ? $allegati->Allegato : [$allegati];
        Log::info('Numero allegati trovati: ' . count($allegatoNodes));
        
        foreach ($allegatoNodes as $index => $allegato) {
            try {
                $fileName = $this->cleanUtf8String((string)$allegato->NomeAttachment);
                Log::info('Processo allegato ' . ($index + 1) . ': ' . $fileName);
                
                $base64Content = (string)$allegato->Attachment;
                
                if (empty($base64Content)) {
                    Log::warning('Contenuto Base64 vuoto per allegato', ['fileName' => $fileName]);
                    continue;
                }
                
                Log::info('Lunghezza Base64: ' . strlen($base64Content));
                
                $base64Content = preg_replace('/\s+/', '', $base64Content);
                $decodedContent = base64_decode($base64Content);
                
                if ($decodedContent === false || strlen($decodedContent) === 0) {
                    Log::error('Decodifica Base64 fallita', ['fileName' => $fileName]);
                    continue;
                }
                
                Log::info('Contenuto decodificato, dimensione: ' . strlen($decodedContent) . ' bytes');
                
                $safeFileName = $this->getSafeFileName($fileName, $decodedContent, $index);
                $s3Path = $folderPrefix . $safeFileName;
                
                Log::info('Tentativo salvataggio su S3: ' . $s3Path);
                
                // Prova a salvare su S3
                try {
                    $saved = Storage::disk('s3')->put($s3Path, $decodedContent);
                    
                    if ($saved) {
                        $bucket = config('filesystems.disks.s3.bucket', 'gestionale-152146163010-eu-north-1-an');
                        $region = config('filesystems.disks.s3.region', 'eu-north-1');
                        $publicUrl = "https://{$bucket}.s3.{$region}.amazonaws.com/{$s3Path}";
                        
                        Log::info('✅ Allegato salvato su S3 con successo!', [
                            'path' => $s3Path,
                            'url' => $publicUrl
                        ]);
                        
                        $savedFiles[] = [
                            'original_name' => $fileName,
                            'saved_name' => $safeFileName,
                            's3_path' => $s3Path,
                            'temp_folder' => $tempFolderName,
                            'url' => $publicUrl,
                            'size' => strlen($decodedContent),
                            'storage_driver' => 's3'
                        ];
                    } else {
                        Log::error('❌ Salvataggio su S3 fallito (put returned false)', ['path' => $s3Path]);
                    }
                } catch (\Exception $e) {
                    Log::error('❌ Eccezione durante salvataggio su S3: ' . $e->getMessage());
                    Log::error('Stack trace: ' . $e->getTraceAsString());
                }
                
            } catch (\Exception $e) {
                Log::error('Errore processamento allegato su S3: ' . $e->getMessage());
                Log::error('Stack trace: ' . $e->getTraceAsString());
            }
        }
        
        Log::info('=== extractAttachmentsToS3 FINE, allegati salvati: ' . count($savedFiles) . ' ===');
        
        return $savedFiles;
    }
    
    /**
     * Rinomina la cartella su S3 con il nome del fornitore
     */
    private function renameS3FolderToSupplier($attachments)
    {
        if (empty($attachments) || !isset($attachments[0]['temp_folder'])) {
            return;
        }
        
        $fornitoreSlug = $this->slugify($this->fornitore_denominazione);
        $pivaFornitore = preg_replace('/[^A-Za-z0-9]/', '', $this->fornitore_partita_iva);
        
        if (!empty($pivaFornitore)) {
            $finalFolderName = $fornitoreSlug . '_' . $pivaFornitore;
        } else {
            $finalFolderName = $fornitoreSlug;
        }
        
        $basePrefix = 'invoice-received/';
        $tempFolder = $basePrefix . $attachments[0]['temp_folder'];
        $finalFolder = $basePrefix . $finalFolderName;
        
        $s3 = Storage::disk('s3');
        $files = $s3->files($tempFolder);
        
        $bucket = config('filesystems.disks.s3.bucket', 'gestionale-152146163010-eu-north-1-an');
        $region = config('filesystems.disks.s3.region', 'eu-north-1');
        
        foreach ($files as $file) {
            $fileName = basename($file);
            $newPath = $finalFolder . '/' . $fileName;
            
            $content = $s3->get($file);
            $saved = $s3->put($newPath, $content);
            
            if ($saved) {
                $newUrl = "https://{$bucket}.s3.{$region}.amazonaws.com/{$newPath}";
                
                foreach ($this->extracted_attachments as &$attachment) {
                    if (isset($attachment['s3_path']) && $attachment['s3_path'] === $file) {
                        $attachment['s3_path'] = $newPath;
                        $attachment['url'] = $newUrl;
                        $attachment['final_folder'] = $finalFolderName;
                    }
                }
                
                $s3->delete($file);
            }
        }
        
        if (count($s3->files($tempFolder)) === 0) {
            $s3->deleteDirectory($tempFolder);
        }
    }

    // ============================================
    // METODI PER GLI ALLEGATI - VERSIONE LOCALE
    // ============================================
    
    /**
     * Estrae e salva gli allegati in locale
     */
    private function extractAttachmentsToLocal($xml)
    {
        $savedFiles = [];
        
        $tempFolder = 'allegati_fatture/temp_' . $this->file_hash;
        
        if (!Storage::disk('public')->exists($tempFolder)) {
            Storage::disk('public')->makeDirectory($tempFolder, 0755, true);
        }
        
        $allegati = null;
        
        if (isset($xml->FatturaElettronicaBody->Allegati)) {
            $allegati = $xml->FatturaElettronicaBody->Allegati;
        } elseif (isset($xml->FatturaElettronicaBody->Allegato)) {
            $allegati = $xml->FatturaElettronicaBody;
        }
        
        if (!$allegati) {
            Log::warning('Nodo Allegati non trovato nell\'XML');
            return $savedFiles;
        }
        
        $allegatoNodes = isset($allegati->Allegato) ? $allegati->Allegato : [$allegati];
        
        foreach ($allegatoNodes as $index => $allegato) {
            try {
                $fileName = $this->cleanUtf8String((string)$allegato->NomeAttachment);
                $base64Content = (string)$allegato->Attachment;
                
                if (empty($base64Content)) {
                    Log::warning('Contenuto Base64 vuoto per allegato', ['fileName' => $fileName]);
                    continue;
                }
                
                $base64Content = preg_replace('/\s+/', '', $base64Content);
                $decodedContent = base64_decode($base64Content);
                
                if ($decodedContent === false || strlen($decodedContent) === 0) {
                    Log::error('Decodifica Base64 fallita', ['fileName' => $fileName]);
                    continue;
                }
                
                $safeFileName = $this->getSafeFileName($fileName, $decodedContent, $index);
                $filePath = $tempFolder . '/' . $safeFileName;
                
                $saved = Storage::disk('public')->put($filePath, $decodedContent);
                
                if ($saved) {
                    $publicUrl = Storage::url($filePath);
                    
                    Log::info('Allegato salvato in locale', [
                        'path' => $filePath,
                        'url' => $publicUrl,
                        'size' => strlen($decodedContent)
                    ]);
                    
                    $savedFiles[] = [
                        'original_name' => $fileName,
                        'saved_name' => $safeFileName,
                        'temp_path' => $filePath,
                        'url' => $publicUrl,
                        'size' => strlen($decodedContent),
                        'temp_folder' => $tempFolder,
                        'storage_driver' => 'local'
                    ];
                }
                
            } catch (\Exception $e) {
                Log::error('Errore processamento allegato in locale: ' . $e->getMessage());
            }
        }
        
        return $savedFiles;
    }
    
    /**
     * Rinomina la cartella locale con il nome del fornitore
     */
    private function renameLocalFolderToSupplier($attachments)
    {
        if (empty($attachments)) {
            return;
        }
        
        $fornitoreSlug = $this->slugify($this->fornitore_denominazione);
        $pivaFornitore = preg_replace('/[^A-Za-z0-9]/', '', $this->fornitore_partita_iva);
        
        if (!empty($pivaFornitore)) {
            $finalFolderName = $fornitoreSlug . '_' . $pivaFornitore;
        } else {
            $finalFolderName = $fornitoreSlug;
        }
        
        $finalFolder = 'allegati_fatture/' . $finalFolderName;
        $tempFolder = $attachments[0]['temp_folder'] ?? null;
        
        if (!$tempFolder || !Storage::disk('public')->exists($tempFolder)) {
            Log::warning('Cartella temporanea non trovata', ['folder' => $tempFolder]);
            return;
        }
        
        if (!Storage::disk('public')->exists($finalFolder)) {
            Storage::disk('public')->makeDirectory($finalFolder, 0755, true);
        }
        
        $files = Storage::disk('public')->files($tempFolder);
        
        foreach ($files as $file) {
            $fileName = basename($file);
            $newPath = $finalFolder . '/' . $fileName;
            
            $copied = Storage::disk('public')->copy($file, $newPath);
            
            if ($copied) {
                Log::info('Allegato spostato in locale', [
                    'from' => $file,
                    'to' => $newPath
                ]);
                
                foreach ($this->extracted_attachments as &$attachment) {
                    if (isset($attachment['temp_path']) && $attachment['temp_path'] === $file) {
                        $attachment['path'] = $newPath;
                        $attachment['url'] = Storage::url($newPath);
                    }
                }
            }
        }
        
        Storage::disk('public')->deleteDirectory($tempFolder);
        Log::info('Cartella temporanea locale eliminata', ['folder' => $tempFolder]);
    }

    // ============================================
    // METODO GENERICO PER GENERARE NOME FILE SICURO
    // ============================================
    
    /**
     * Genera un nome file sicuro
     */
    private function getSafeFileName($originalName, $content, $index)
    {
        if (!empty($originalName)) {
            $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
            $extension = pathinfo($name, PATHINFO_EXTENSION);
            
            if (strpos($content, '%PDF') === 0 && strtolower($extension) !== 'pdf') {
                $name = pathinfo($name, PATHINFO_FILENAME) . '.pdf';
            }
            
            return $name;
        }
        
        if (strpos($content, '%PDF') === 0) {
            return 'allegato_' . ($index + 1) . '.pdf';
        }
        
        if (strpos($content, 'PK') === 0) {
            return 'allegato_' . ($index + 1) . '.zip';
        }
        
        return 'allegato_' . ($index + 1) . '.bin';
    }

    /**
     * Converte una stringa in slug
     */
    private function slugify($text)
    {
        $text = $this->cleanUtf8String($text);
        
        $text = preg_replace('~[^\pL\d]+~u', '_', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '_');
        $text = preg_replace('~-+~', '_', $text);
        $text = strtolower($text);
        
        if (empty($text)) {
            $text = 'fornitore';
        }
        
        return $text;
    }

    // ============================================
    // UPLOAD E PARSING XML (SELEZIONA IL DRIVER)
    // ============================================
    
    public function uploadXml()
    {
        $this->validate([
            'xml_file' => 'required|file|max:10240',
        ]);

        try {
            $content = file_get_contents($this->xml_file->getRealPath());
            $content = $this->cleanUtf8String($content);
            
            $xml = simplexml_load_string($content);
            
            if ($xml === false) {
                $this->addError('xml_file', 'File XML non valido');
                return;
            }

            $this->xml_filename = $this->xml_file->getClientOriginalName();
            $this->file_hash = hash('sha256', $content);
            
            // ============================================
            // SELEZIONE DEL DRIVER IN BASE ALLA COSTANTE
            // ============================================
            if (self::STORAGE_DRIVER === 's3') {
                Log::info('Utilizzo storage S3 per gli allegati');
                $attachments = $this->extractAttachmentsToS3($xml);
                $this->extracted_attachments = $attachments;
                
                $cleanXmlContent = $this->removeAttachmentsFromXml($content);
                $cleanXml = simplexml_load_string($cleanXmlContent);
                $this->parseXmlInvoiceRobusto($cleanXml);
                
                if (!empty($attachments) && !empty($this->fornitore_denominazione)) {
                    $this->renameS3FolderToSupplier($attachments);
                }
            } else {
                Log::info('Utilizzo storage locale per gli allegati');
                $attachments = $this->extractAttachmentsToLocal($xml);
                $this->extracted_attachments = $attachments;
                
                $cleanXmlContent = $this->removeAttachmentsFromXml($content);
                $cleanXml = simplexml_load_string($cleanXmlContent);
                $this->parseXmlInvoiceRobusto($cleanXml);
                
                if (!empty($attachments) && !empty($this->fornitore_denominazione)) {
                    $this->renameLocalFolderToSupplier($attachments);
                }
            }
            
            // Verifica duplicati
            if ($this->checkInvoiceExists()) {
                $this->dispatch('alert', [
                    'type' => 'error', 
                    'message' => "❌ FATTURA DUPLICATA! Questa fattura è già stata importata."
                ]);
                return;
            }
            
            $this->xml_parsed = true;
            
            if (!empty($attachments)) {
                $driverName = self::STORAGE_DRIVER === 's3' ? 'S3 (Cloud)' : 'Locale';
                $this->dispatch('alert', [
                    'type' => 'info', 
                    'message' => "📎 Estratti " . count($attachments) . " allegati dalla fattura e salvati su " . $driverName
                ]);
            }
            
            $this->dispatch('alert', ['type' => 'success', 'message' => 'XML analizzato con successo!']);
            
        } catch (\Exception $e) {
            Log::error('Errore upload XML: ' . $e->getMessage());
            $this->addError('xml_file', 'Errore: ' . $e->getMessage());
        }
    }

    private function checkInvoiceExists()
    {
        if (empty($this->fornitore_partita_iva) || empty($this->n_invoice) || empty($this->data_invoice)) {
            return false;
        }
        
        $exists = InvoiceReceived::whereHas('entity', function($query) {
            $query->where('partita_iva', $this->fornitore_partita_iva);
        })->where('n_invoice', $this->n_invoice)
          ->whereDate('data_invoice', $this->data_invoice)
          ->exists();
        
        if ($exists) {
            Log::warning('Tentativo fattura duplicata', [
                'fornitore_piva' => $this->fornitore_partita_iva,
                'n_fattura' => $this->n_invoice,
                'data_fattura' => $this->data_invoice
            ]);
            return true;
        }
        
        if (!empty($this->file_hash)) {
            $hashExists = InvoiceReceived::where('file_hash', $this->file_hash)->exists();
            if ($hashExists) {
                Log::warning('Tentativo file duplicato', ['hash' => $this->file_hash]);
                return true;
            }
        }
        
        return false;
    }

    private function parseXmlInvoiceRobusto($xml)
    {
        // -------------------------------------------------------
        // Strategia: lavoriamo sull'XML grezzo PRIMA che SimpleXML
        // applichi i namespace, così le regex funzionano sempre.
        // -------------------------------------------------------
        $xmlString = $xml->asXML();

        // Pulisci i caratteri UTF-8 malformati
        $xmlString = $this->cleanUtf8String($xmlString);

        // Rimuovi tutti i prefissi di namespace dai tag (es. ns0:Tag -> Tag)
        // e rimuovi le dichiarazioni xmlns dai tag di apertura
        $cleanXml = preg_replace('/(<\/?)[\w]+:/', '$1', $xmlString);
        $cleanXml = preg_replace('/\s+xmlns(?::\w+)?="[^"]*"/', '', $cleanXml);

        // RIMUOVI I MARKER CDATA
        $cleanXml = $this->removeCdataFromXml($cleanXml);

        // Da questo momento lavoriamo su $cleanXml per tutte le regex
        // -------------------------------------------------------

        // DATI COMMITTENTE
        if (preg_match('/<CessionarioCommittente>(.*?)<\/CessionarioCommittente>/is', $cleanXml, $cessionarioMatch)) {
            $cessionarioXml = $cessionarioMatch[1];

            if (preg_match('/<Denominazione>(.*?)<\/Denominazione>/i', $cessionarioXml, $match)) {
                $this->committente_denominazione = $this->cleanUtf8String(trim($match[1]));
            }
            if (empty($this->committente_denominazione)) {
                $nome = $cognome = '';
                if (preg_match('/<Nome>(.*?)<\/Nome>/i', $cessionarioXml, $match)) $nome = $this->cleanUtf8String(trim($match[1]));
                if (preg_match('/<Cognome>(.*?)<\/Cognome>/i', $cessionarioXml, $match)) $cognome = $this->cleanUtf8String(trim($match[1]));
                $this->committente_denominazione = trim("$nome $cognome");
            }

            $this->committente_partita_iva = '';
            if (preg_match('/<IdFiscaleIVA>(.*?)<\/IdFiscaleIVA>/is', $cessionarioXml, $idMatch)) {
                $idXml = $idMatch[1];
                $paese = $codice = '';
                if (preg_match('/<IdPaese>(.*?)<\/IdPaese>/i', $idXml, $m)) $paese = $this->cleanUtf8String(trim($m[1]));
                if (preg_match('/<IdCodice>(.*?)<\/IdCodice>/i', $idXml, $m)) $codice = $this->cleanUtf8String(trim($m[1]));
                $this->committente_partita_iva = $paese . $codice;
            }

            if (preg_match('/<CodiceFiscale>(.*?)<\/CodiceFiscale>/i', $cessionarioXml, $match)) {
                $this->committente_codice_fiscale = $this->cleanUtf8String(trim($match[1]));
            }

            if (preg_match('/<Indirizzo>(.*?)<\/Indirizzo>/i', $cessionarioXml, $match)) $this->committente_indirizzo = $this->cleanUtf8String(trim($match[1]));
            if (preg_match('/<CAP>(.*?)<\/CAP>/i', $cessionarioXml, $match)) $this->committente_cap = $this->cleanUtf8String(trim($match[1]));
            if (preg_match('/<Comune>(.*?)<\/Comune>/i', $cessionarioXml, $match)) $this->committente_comune = $this->cleanUtf8String(trim($match[1]));
            if (preg_match('/<Provincia>(.*?)<\/Provincia>/i', $cessionarioXml, $match)) $this->committente_provincia = $this->cleanUtf8String(trim($match[1]));
            if (preg_match('/<Nazione>(.*?)<\/Nazione>/i', $cessionarioXml, $match)) $this->committente_nazione = $this->cleanUtf8String(trim($match[1]));
        }

        // DATI FORNITORE
        if (preg_match('/<CedentePrestatore>(.*?)<\/CedentePrestatore>/is', $cleanXml, $cedenteMatch)) {
            $cedenteXml = $cedenteMatch[1];

            if (preg_match('/<Denominazione>(.*?)<\/Denominazione>/i', $cedenteXml, $match)) {
                $this->fornitore_denominazione = $this->cleanUtf8String(trim($match[1]));
            }
            if (empty($this->fornitore_denominazione)) {
                $nome = $cognome = '';
                if (preg_match('/<Nome>(.*?)<\/Nome>/i', $cedenteXml, $match)) $nome = $this->cleanUtf8String(trim($match[1]));
                if (preg_match('/<Cognome>(.*?)<\/Cognome>/i', $cedenteXml, $match)) $cognome = $this->cleanUtf8String(trim($match[1]));
                $this->fornitore_denominazione = trim("$nome $cognome");
            }

            $this->fornitore_partita_iva = '';
            if (preg_match('/<IdFiscaleIVA>(.*?)<\/IdFiscaleIVA>/is', $cedenteXml, $idMatch)) {
                $idXml = $idMatch[1];
                $paese = $codice = '';
                if (preg_match('/<IdPaese>(.*?)<\/IdPaese>/i', $idXml, $m)) $paese = $this->cleanUtf8String(trim($m[1]));
                if (preg_match('/<IdCodice>(.*?)<\/IdCodice>/i', $idXml, $m)) $codice = $this->cleanUtf8String(trim($m[1]));
                $this->fornitore_partita_iva = $paese . $codice;
            }

            if (preg_match('/<CodiceFiscale>(.*?)<\/CodiceFiscale>/i', $cedenteXml, $match)) {
                $this->fornitore_codice_fiscale = $this->cleanUtf8String(trim($match[1]));
            }

            if (preg_match('/<Indirizzo>(.*?)<\/Indirizzo>/i', $cedenteXml, $match)) $this->fornitore_indirizzo = $this->cleanUtf8String(trim($match[1]));
            if (preg_match('/<CAP>(.*?)<\/CAP>/i', $cedenteXml, $match)) $this->fornitore_cap = $this->cleanUtf8String(trim($match[1]));
            if (preg_match('/<Comune>(.*?)<\/Comune>/i', $cedenteXml, $match)) $this->fornitore_comune = $this->cleanUtf8String(trim($match[1]));
            if (preg_match('/<Provincia>(.*?)<\/Provincia>/i', $cedenteXml, $match)) $this->fornitore_provincia = $this->cleanUtf8String(trim($match[1]));
            if (preg_match('/<Nazione>(.*?)<\/Nazione>/i', $cedenteXml, $match)) $this->fornitore_nazione = $this->cleanUtf8String(trim($match[1]));

            if (preg_match('/<Contatti>(.*?)<\/Contatti>/is', $cedenteXml, $contattiMatch)) {
                $contattiXml = $contattiMatch[1];
                if (preg_match('/<Telefono>(.*?)<\/Telefono>/i', $contattiXml, $match)) $this->fornitore_telefono = $this->cleanUtf8String(trim($match[1]));
                if (preg_match('/<Email>(.*?)<\/Email>/i', $contattiXml, $match)) $this->fornitore_email = $this->cleanUtf8String(trim($match[1]));
            }
        }

        // DATI FATTURA
        if (preg_match('/<DatiGeneraliDocumento>(.*?)<\/DatiGeneraliDocumento>/is', $cleanXml, $datiGeneraliMatch)) {
            $datiGeneraliXml = $datiGeneraliMatch[1];

            if (preg_match('/<TipoDocumento>(.*?)<\/TipoDocumento>/i', $datiGeneraliXml, $match)) $this->type_invoice = $this->cleanUtf8String(trim($match[1]));
            if (preg_match('/<Divisa>(.*?)<\/Divisa>/i', $datiGeneraliXml, $match)) $this->divisa = $this->cleanUtf8String(trim($match[1]));
            if (preg_match('/<Data>(.*?)<\/Data>/i', $datiGeneraliXml, $match)) $this->data_invoice = $this->cleanUtf8String(trim($match[1]));
            if (preg_match('/<Numero>(.*?)<\/Numero>/i', $datiGeneraliXml, $match)) $this->n_invoice = $this->cleanUtf8String(trim($match[1]));
        }

        // TOTALE: prende ImportoPagamento (somma di tutti i DettaglioPagamento)
        $importoPagamentoTotale = 0;
        if (preg_match_all('/<ImportoPagamento>(.*?)<\/ImportoPagamento>/i', $cleanXml, $pagMatches)) {
            foreach ($pagMatches[1] as $val) {
                $importoPagamentoTotale += floatval(str_replace(',', '.', trim($val)));
            }
        }

        // Se l'importo pagamento è 0, usa ImportoTotaleDocumento
        if ($importoPagamentoTotale > 0) {
            $this->importo_totale = round($importoPagamentoTotale, 2);
        } elseif (preg_match('/<ImportoTotaleDocumento>(.*?)<\/ImportoTotaleDocumento>/i', $cleanXml, $match)) {
            $this->importo_totale = floatval(str_replace(',', '.', trim($match[1])));
        }

        Log::info('Importo totale rilevato', [
            'importo_pagamento' => $importoPagamentoTotale,
            'importo_totale_finale' => $this->importo_totale
        ]);

        // RIGHE FATTURA
        $this->rows = [];
        if (preg_match_all('/<DettaglioLinee>(.*?)<\/DettaglioLinee>/is', $cleanXml, $lineeMatches)) {
            foreach ($lineeMatches[1] as $index => $lineaXml) {
                $row = [
                    'description'         => '',
                    'quantity'            => 1,
                    'unit_price'          => 0,
                    'discount_percentage' => 0,
                    'aliquota_iva'        => 0,
                    'id_cost_center'      => null,
                    'cost_center_name'    => '',
                    'id_vehicle'          => null,   
                    'vehicle_name'        => '', 
                    'codice_articolo'     => [],
                    'unita_misura'        => '',
                    'natura'              => '',
                    'riferimento_amministrativo' => '',
                ];

                if (preg_match('/<Descrizione>(.*?)<\/Descrizione>/i', $lineaXml, $match)) {
                    $row['description'] = $this->cleanUtf8String(trim($match[1]));
                }
                
                if (preg_match('/<Quantita>(.*?)<\/Quantita>/i', $lineaXml, $match)) {
                    $row['quantity'] = floatval(str_replace(',', '.', trim($match[1])));
                }
                
                if (preg_match('/<PrezzoUnitario>(.*?)<\/PrezzoUnitario>/i', $lineaXml, $match)) {
                    $row['unit_price'] = floatval(str_replace(',', '.', trim($match[1])));
                }
                
                if (preg_match('/<UnitaMisura>(.*?)<\/UnitaMisura>/i', $lineaXml, $match)) {
                    $row['unita_misura'] = $this->cleanUtf8String(trim($match[1]));
                }
                
                if (preg_match('/<AliquotaIVA>(.*?)<\/AliquotaIVA>/i', $lineaXml, $match)) {
                    $row['aliquota_iva'] = floatval(str_replace(',', '.', trim($match[1])));
                }

                if (preg_match('/<ScontoMaggiorazione>(.*?)<\/ScontoMaggiorazione>/is', $lineaXml, $scontoMatch)) {
                    if (preg_match('/<Percentuale>(.*?)<\/Percentuale>/i', $scontoMatch[1], $percMatch)) {
                        $row['discount_percentage'] = floatval(str_replace(',', '.', trim($percMatch[1])));
                    }
                }
                
                if (preg_match('/<Natura>(.*?)<\/Natura>/i', $lineaXml, $match)) {
                    $row['natura'] = $this->cleanUtf8String(trim($match[1]));
                }
                
                if (preg_match('/<RiferimentoAmministrativo>(.*?)<\/RiferimentoAmministrativo>/i', $lineaXml, $match)) {
                    $row['riferimento_amministrativo'] = $this->cleanUtf8String(trim($match[1]));
                }
                
                if (preg_match_all('/<CodiceArticolo>(.*?)<\/CodiceArticolo>/is', $lineaXml, $codiceMatches)) {
                    foreach ($codiceMatches[1] as $codiceXml) {
                        $codiceTipo = '';
                        $codiceValore = '';
                        if (preg_match('/<CodiceTipo>(.*?)<\/CodiceTipo>/i', $codiceXml, $tipoMatch)) {
                            $codiceTipo = $this->cleanUtf8String(trim($tipoMatch[1]));
                        }
                        if (preg_match('/<CodiceValore>(.*?)<\/CodiceValore>/i', $codiceXml, $valoreMatch)) {
                            $codiceValore = $this->cleanUtf8String(trim($valoreMatch[1]));
                        }
                        if (!empty($codiceTipo) || !empty($codiceValore)) {
                            $row['codice_articolo'][] = [
                                'tipo' => $codiceTipo,
                                'valore' => $codiceValore
                            ];
                        }
                    }
                }

                $this->rows[] = $row;
            }
        }

        // CERCA FORNITORE NEL DB
        $this->supplier_found = false;
        $this->supplier_not_found = false;

        if (!empty($this->fornitore_partita_iva)) {
            $pulitaPiva = $this->cleanUtf8String($this->fornitore_partita_iva);
            $pivaNoPrefix = preg_replace('/^[A-Z]{2}/i', '', $pulitaPiva);
            
            $entity = Entity::where('partita_iva', $pulitaPiva)
                ->orWhere('partita_iva', $pivaNoPrefix)
                ->first();

            if ($entity) {
                $this->supplier_found = true;
                $this->id_entities = $entity->id_cliente;
                $this->supplier_display = $this->cleanUtf8String($entity->ragione_sociale ?? ($entity->nome . ' ' . $entity->cognome));
                $this->supplier_created_by_system = $entity->created_by_system ?? false;
                if (!empty($entity->partita_iva)) {
                    $this->supplier_display .= ' (P.IVA: ' . $entity->partita_iva . ')';
                }
            }
        }

        if (!$this->supplier_found && !empty($this->fornitore_codice_fiscale)) {
            $entity = Entity::where('codice_fiscale', $this->cleanUtf8String($this->fornitore_codice_fiscale))->first();
            if ($entity) {
                $this->supplier_found = true;
                $this->id_entities = $entity->id_cliente;
                $this->supplier_display = $this->cleanUtf8String($entity->ragione_sociale ?? ($entity->nome . ' ' . $entity->cognome));
                $this->supplier_created_by_system = $entity->created_by_system ?? false;
            }
        }

        if (!$this->supplier_found) {
            $this->supplier_not_found = true;
            $this->supplier_display = $this->fornitore_denominazione;
            if (!empty($this->fornitore_partita_iva)) {
                $this->supplier_display .= ' (P.IVA: ' . $this->fornitore_partita_iva . ')';
            }
        }

        // CERCA PROPRIETÀ
        if (!empty($this->committente_partita_iva)) {
            $pulitaPivaComm = $this->cleanUtf8String($this->committente_partita_iva);
            $ownership = Ownership::where('PivaPr', $pulitaPivaComm)->first();

            if (!$ownership) {
                $pivaNoPrefix = preg_replace('/^[A-Z]{2}/i', '', $pulitaPivaComm);
                $ownership = Ownership::where('PivaPr', $pivaNoPrefix)->first();
            }

            if ($ownership) {
                $this->id_ownership = $ownership->id_proprieta;
                $this->ownership_display = $this->cleanUtf8String($ownership->Rag_Soc_intest ?: $ownership->RagSocialePr);
            }
        }

        // ESTRAZIONE PAGAMENTI
        $this->payments = [];

        if (preg_match_all('/<DettaglioPagamento>(.*?)<\/DettaglioPagamento>/is', $cleanXml, $dettaglioMatches)) {
            foreach ($dettaglioMatches[1] as $dettaglioXml) {
                $payment = [
                    'due_date' => null,
                    'amount' => 0,
                    'payment_method' => null,
                    'iban' => null,
                ];
                
                if (preg_match('/<DataScadenzaPagamento>(.*?)<\/DataScadenzaPagamento>/i', $dettaglioXml, $match)) {
                    $payment['due_date'] = $this->cleanUtf8String(trim($match[1]));
                }
                
                if (preg_match('/<ImportoPagamento>(.*?)<\/ImportoPagamento>/i', $dettaglioXml, $match)) {
                    $payment['amount'] = floatval(str_replace(',', '.', trim($match[1])));
                }
                
                if (preg_match('/<ModalitaPagamento>(.*?)<\/ModalitaPagamento>/i', $dettaglioXml, $match)) {
                    $payment['payment_method'] = $this->cleanUtf8String(trim($match[1]));
                }
                
                if (preg_match('/<IBAN>(.*?)<\/IBAN>/i', $dettaglioXml, $match)) {
                    $payment['iban'] = $this->cleanUtf8String(trim($match[1]));
                }
                
                $this->payments[] = $payment;
            }
        }

        // POST-ELABORAZIONE PAGAMENTI
        if (empty($this->payments)) {
            Log::info('Nessun pagamento trovato nell\'XML, utilizzo data fattura come scadenza');
            
            $this->payments[] = [
                'due_date' => $this->data_invoice,
                'amount' => $this->importo_totale,
                'payment_method' => null,
                'iban' => null,
            ];
        } else {
            foreach ($this->payments as &$payment) {
                if (empty($payment['due_date']) && !empty($this->data_invoice)) {
                    $payment['due_date'] = $this->data_invoice;
                    Log::info('Pagamento senza data scadenza, impostata data fattura: ' . $this->data_invoice);
                }
            }
        }

        Log::info('Pagamenti estratti', ['count' => count($this->payments)]);

        // ESTRAZIONE RIEPILOGO IVA
        $this->vatSummaries = [];
        if (preg_match_all('/<DatiRiepilogo>(.*?)<\/DatiRiepilogo>/is', $cleanXml, $riepilogoMatches)) {
            foreach ($riepilogoMatches[1] as $riepilogoXml) {
                $summary = [
                    'tax_rate' => 0,
                    'sdi_nature' => null,
                    'taxable_amount' => 0,
                    'tax_amount' => 0,
                    'vat_law_reference' => null,
                    'esigibilita_iva' => 'I',
                ];
                
                if (preg_match('/<AliquotaIVA>(.*?)<\/AliquotaIVA>/i', $riepilogoXml, $match)) {
                    $summary['tax_rate'] = floatval(str_replace(',', '.', trim($match[1])));
                }
                if (preg_match('/<Natura>(.*?)<\/Natura>/i', $riepilogoXml, $match)) {
                    $summary['sdi_nature'] = $this->cleanUtf8String(trim($match[1]));
                }
                if (preg_match('/<ImponibileImporto>(.*?)<\/ImponibileImporto>/i', $riepilogoXml, $match)) {
                    $summary['taxable_amount'] = floatval(str_replace(',', '.', trim($match[1])));
                }
                if (preg_match('/<Imposta>(.*?)<\/Imposta>/i', $riepilogoXml, $match)) {
                    $summary['tax_amount'] = floatval(str_replace(',', '.', trim($match[1])));
                }
                if (preg_match('/<RiferimentoNormativo>(.*?)<\/RiferimentoNormativo>/i', $riepilogoXml, $match)) {
                    $summary['vat_law_reference'] = $this->cleanUtf8String(trim($match[1]));
                }
                if (preg_match('/<EsigibilitaIVA>(.*?)<\/EsigibilitaIVA>/i', $riepilogoXml, $match)) {
                    $summary['esigibilita_iva'] = $this->cleanUtf8String(trim($match[1]));
                }
                
                $this->vatSummaries[] = $summary;
            }
        }

        $this->status = 'issued';

        // INIZIALIZZA ARRAY PER AUTOCOMPLETE
        $this->row_cost_center_search = [];
        $this->row_cost_center_results = [];
        $this->show_row_cost_center_dropdown = [];
        $this->row_vehicle_search = [];           
        $this->row_vehicle_results = [];          
        $this->show_row_vehicle_dropdown = [];

        foreach ($this->rows as $index => $row) {
            $this->row_cost_center_search[$index] = $row['cost_center_name'] ?? '';
            $this->row_cost_center_results[$index] = [];
            $this->show_row_cost_center_dropdown[$index] = false;
            $this->row_vehicle_search[$index] = $row['vehicle_name'] ?? '';      
            $this->row_vehicle_results[$index] = [];                              
            $this->show_row_vehicle_dropdown[$index] = false; 
        }

        $this->cost_center_all_search = '';
        $this->cost_center_all_results = [];
        $this->show_cost_center_all_dropdown = false;
        $this->vehicle_all_search = '';        
        $this->vehicle_all_results = [];       
        $this->show_vehicle_all_dropdown = false;
    }       

    public function getNaturaLabel($natura)
    {
        return config('gestionale.natura_operazione.' . $natura, '');
    }

    private function removeAttachmentsFromXml($xmlString)
    {
        $xmlString = preg_replace('/<Allegati>.*?<\/Allegati>/is', '', $xmlString);
        $xmlString = preg_replace('/<Allegato>.*?<\/Allegato>/is', '', $xmlString);
        $xmlString = preg_replace('/<FatturaFirmata>.*?<\/FatturaFirmata>/is', '', $xmlString);
        $xmlString = preg_replace('/\n\s*\n/', "\n", $xmlString);
        return $xmlString;
    }

    private function removeCdataFromXml($xmlString)
    {
        return preg_replace('/<!\[CDATA\[(.*?)\]\]>/s', '$1', $xmlString);
    }

    public function createSupplierAutomatically()
    {
        if (empty($this->fornitore_denominazione)) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Nessun dato fornitore disponibile']);
            return;
        }
        
        try {
            $existingEntity = Entity::where('partita_iva', $this->cleanUtf8String($this->fornitore_partita_iva))->first();
            if ($existingEntity) {
                $this->id_entities = $existingEntity->id_cliente;
                $this->supplier_found = true;
                $this->supplier_not_found = false;
                $this->dispatch('alert', ['type' => 'info', 'message' => 'Fornitore già esistente, riutilizzato']);
                return;
            }
            
            $newEntity = Entity::create([
                'entity_type' => 'fornitore',
                'ragione_sociale' => $this->cleanUtf8String($this->fornitore_denominazione),
                'nome' => $this->cleanUtf8String($this->fornitore_denominazione),
                'indirizzo' => $this->cleanUtf8String($this->fornitore_indirizzo),
                'cap' => $this->cleanUtf8String($this->fornitore_cap),
                'comune' => $this->cleanUtf8String($this->fornitore_comune),
                'provincia' => $this->cleanUtf8String($this->fornitore_provincia),
                'partita_iva' => $this->cleanUtf8String($this->fornitore_partita_iva),
                'codice_fiscale' => $this->cleanUtf8String($this->fornitore_codice_fiscale),
                'telefono' => $this->cleanUtf8String($this->fornitore_telefono),
                'email' => $this->cleanUtf8String($this->fornitore_email),
                'valid' => 1,
                'created_by_system' => 1,
            ]);
            
            $this->id_entities = $newEntity->id_cliente;
            $this->supplier_display = $this->fornitore_denominazione;
            if (!empty($this->fornitore_partita_iva)) {
                $this->supplier_display .= ' (P.IVA: ' . $this->fornitore_partita_iva . ')';
            }
            $this->supplier_found = true;
            $this->supplier_not_found = false;
            $this->supplier_created_by_system = true;
            
            $this->dispatch('alert', ['type' => 'success', 'message' => "✅ Fornitore creato automaticamente!"]);
            
        } catch (\Exception $e) {
            Log::error('Errore creazione fornitore: ' . $e->getMessage());
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Errore: ' . $e->getMessage()]);
        }
    }

    public function calculateTotal()
    {
        $total = 0;
        foreach ($this->rows as $row) {
            $subtotal = ($row['quantity'] ?? 0) * ($row['unit_price'] ?? 0);
            $discount = $subtotal * (($row['discount_percentage'] ?? 0) / 100);
            $total += $subtotal - $discount;
        }
        $this->importo_totale = round($total, 2);
    }

    private function saveXmlFile()
    {
        if (!$this->xml_file) {
            return null;
        }
        
        $content = file_get_contents($this->xml_file->getRealPath());
        $cleanContent = $this->removeAttachmentsFromXml($content);
        $this->xml_content = $this->cleanUtf8String($cleanContent);
        
        $fornitorePiva = preg_replace('/[^A-Za-z0-9]/', '', $this->fornitore_partita_iva);
        if (empty($fornitorePiva)) {
            $fornitorePiva = 'piva_non_trovata_' . time();
        }
        
        return $fornitorePiva . '.xml';
    }

    // ============================================
    // METODI PER LA GESTIONE DELLE ALIQUOTE IVA
    // ============================================
    
    /**
     * Carica la mappa delle aliquote IVA per ricerca rapida
     */
    protected function loadVatRatesMap(): void
    {
        $this->vatRatesMap = [];

        $vatRates = DB::table('vat_rates')
            ->where('is_active', 1)
            ->orderBy('id')          // ordine stabile: prende sempre il primo
            ->get();

        foreach ($vatRates as $vat) {
            $ratePercent = (string) round((float) $vat->rate * 100, 2);
            $sdiNature   = trim((string) ($vat->sdi_nature ?? ''));

            if (!isset($this->vatRatesMap[$ratePercent])) {
                $this->vatRatesMap[$ratePercent] = [];
            }

            if ($ratePercent === '0' && $sdiNature !== '') {
                // Per ogni natura salva solo il PRIMO id trovato (orderBy id garantisce stabilità)
                if (!isset($this->vatRatesMap[$ratePercent][$sdiNature])) {
                    $this->vatRatesMap[$ratePercent][$sdiNature] = (int) $vat->id;
                }
            } else {
                // Per aliquote > 0% salva solo il primo trovato
                if (!isset($this->vatRatesMap[$ratePercent]['default'])) {
                    $this->vatRatesMap[$ratePercent]['default'] = (int) $vat->id;
                }
            }
        }

        Log::info('Mappa aliquote IVA caricata', [
            'count' => count($this->vatRatesMap),
            'keys'  => array_keys($this->vatRatesMap),
            'natura_0' => array_keys($this->vatRatesMap['0'] ?? []),
        ]);
    }

    /**
     * Trova l'ID dell'aliquota IVA in base alla percentuale e alla natura
     */
    protected function findVatRateId(float|int $aliquotaPercentuale, ?string $natura = null): ?int
    {
        $rateKey = (string) round((float) $aliquotaPercentuale, 2);
        $natura  = trim((string) ($natura ?? ''));

        // Caso 1: Aliquota > 0%
        if ((float) $aliquotaPercentuale > 0) {
            if (isset($this->vatRatesMap[$rateKey]['default'])) {
                return $this->vatRatesMap[$rateKey]['default'];
            }

            // Fallback DB diretto
            $vatRate = DB::table('vat_rates')
                ->where('is_active', 1)
                ->whereRaw('ROUND(rate * 100, 2) = ?', [(float) $aliquotaPercentuale])
                ->orderBy('id')
                ->first();

            return $vatRate ? (int) $vatRate->id : null;
        }

        // Caso 2: Aliquota 0% con natura dall'XML
        if ($natura !== '') {
            // Prima cerca in mappa
            if (isset($this->vatRatesMap['0'][$natura])) {
                Log::info("VAT match da mappa: natura={$natura}, id=" . $this->vatRatesMap['0'][$natura]);
                return $this->vatRatesMap['0'][$natura];
            }

            // Fallback DB diretto per natura specifica
            $vatRate = DB::table('vat_rates')
                ->where('is_active', 1)
                ->where('rate', 0)
                ->where('sdi_nature', $natura)
                ->orderBy('id')
                ->first();

            if ($vatRate) {
                Log::info("VAT match da DB: natura={$natura}, id={$vatRate->id}");
                return (int) $vatRate->id;
            }

            Log::warning('Natura non trovata per IVA 0%', ['natura' => $natura]);
        }

        // Caso 3: 0% senza natura — default N2.2
        if (isset($this->vatRatesMap['0']['N2.2'])) {
            return $this->vatRatesMap['0']['N2.2'];
        }

        // Fallback finale: 22%
        if (isset($this->vatRatesMap['22']['default'])) {
            Log::warning('Usata aliquota fallback 22%', [
                'original_rate'   => $aliquotaPercentuale,
                'original_natura' => $natura,
            ]);
            return $this->vatRatesMap['22']['default'];
        }

        return null;
    }

    public function save()
    {
        Log::info('Tentativo salvataggio fattura', [
            'fornitore_partita_iva' => $this->fornitore_partita_iva,
            'n_invoice' => $this->n_invoice,
            'data_invoice' => $this->data_invoice,
            'importo_totale' => $this->importo_totale,
            'rows_count' => count($this->rows),
            'payments_count' => count($this->payments),
            'vatSummaries_count' => count($this->vatSummaries),
            'supplier_found' => $this->supplier_found,
            'id_entities' => $this->id_entities,
        ]);

        $adminId = null;
        if (Auth::guard('admin')->check()) {
            $adminId = Auth::guard('admin')->id();
            Log::info('Admin autenticato ID: ' . $adminId);
        } else {
            Log::warning('Admin NON autenticato!');
        }
        
        if ($this->checkInvoiceExists()) {
            $this->dispatch('alert', ['type' => 'error', 'message' => "❌ FATTURA DUPLICATA! Impossibile importare."]);
            return;
        }
        
        if (!$this->supplier_found && $this->supplier_not_found && !$this->id_entities) {
            $this->createSupplierAutomatically();
        }
        
        try {
            $this->validate([
                'id_entities' => 'required|exists:entities,id_cliente',
                'type_invoice' => 'required|string|max:10',
                'n_invoice' => 'required|string|max:100',
                'data_invoice' => 'required|date',
                'divisa' => 'required|string|size:3',
            ]);
        } catch (\Exception $e) {
            Log::error('Validazione fallita', ['errors' => $e->getMessage()]);
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Dati mancanti: ' . $e->getMessage()]);
            return;
        }

        try {
            DB::beginTransaction();

            // ============================================
            // CARICA LA MAPPA DELLE ALIQUOTE IVA
            // ============================================
            $this->loadVatRatesMap();

            $xmlStoragePath = $this->saveXmlFile();

            // Prepara i dati per la creazione della fattura
            $invoiceData = [
                'id_ownership' => $this->id_ownership,
                'id_entities' => $this->id_entities,
                'type_invoice' => $this->type_invoice ?: 'TD01',
                'n_invoice' => $this->cleanUtf8String($this->n_invoice),
                'data_invoice' => $this->data_invoice,
                'importo_totale' => $this->importo_totale ?: 0,
                'causale' => $this->cleanUtf8String($this->causale),
                'divisa' => $this->cleanUtf8String($this->divisa),
                'status' => $this->status,
                'sdi_id' => $this->cleanUtf8String($this->sdi_id),
                'is_manual' => false, 
                'xml_filename' => $xmlStoragePath,
                'xml_content' => $this->xml_content ?? null,
                'file_hash' => $this->file_hash,
                'imported_at' => now(),
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ];

            // Aggiungi i campi per gli allegati se presenti
            if (!empty($this->extracted_attachments)) {
                $fornitoreSlug = $this->slugify($this->fornitore_denominazione);
                $pivaFornitore = preg_replace('/[^A-Za-z0-9]/', '', $this->fornitore_partita_iva);
                
                if (!empty($pivaFornitore)) {
                    $finalFolderName = $fornitoreSlug . '_' . $pivaFornitore;
                } else {
                    $finalFolderName = $fornitoreSlug;
                }
                
                // Salva gli URL degli allegati come JSON
                $attachmentUrls = array_column($this->extracted_attachments, 'url');
                $attachmentJson = json_encode($attachmentUrls);
                
                $invoiceData['fornitore_slug'] = $finalFolderName;
                $invoiceData['attachments_folder'] = self::STORAGE_DRIVER === 's3' ? 'invoice-received/' . $finalFolderName : 'allegati_fatture/' . $finalFolderName;
                $invoiceData['attachment'] = $attachmentJson;
            }

            $invoice = InvoiceReceived::create($invoiceData);

            Log::info('Fattura creata con ID: ' . $invoice->id);

            // Se ci sono allegati ma non è stato possibile salvare i campi, aggiorna dopo
            if (!empty($this->extracted_attachments) && (empty($invoice->fornitore_slug) || empty($invoice->attachment))) {
                $fornitoreSlug = $this->slugify($this->fornitore_denominazione);
                $pivaFornitore = preg_replace('/[^A-Za-z0-9]/', '', $this->fornitore_partita_iva);
                $finalFolderName = !empty($pivaFornitore) ? $fornitoreSlug . '_' . $pivaFornitore : $fornitoreSlug;
                $attachmentUrls = array_column($this->extracted_attachments, 'url');
                
                $invoice->update([
                    'fornitore_slug' => $finalFolderName,
                    'attachments_folder' => self::STORAGE_DRIVER === 's3' ? 'invoice-received/' . $finalFolderName : 'allegati_fatture/' . $finalFolderName,
                    'attachment' => json_encode($attachmentUrls),
                ]);
            }

            // Salva le righe della fattura
            foreach ($this->rows as $index => $row) {
                Log::info('Salvataggio riga ' . $index, ['description' => $row['description']]);

                $aliquotaIvaPercentuale = $row['aliquota_iva'] ?? 0;
                $natura = $row['natura'] ?? null;
                
                $vatRateId = $this->findVatRateId($aliquotaIvaPercentuale, $natura);
                $vatRateDecimal = $aliquotaIvaPercentuale / 100;
            
                InvoiceRow::create([
                    'document_id'         => $invoice->id,
                    'document_type'       => 'invoice_received',
                    'id_cost_center'      => $row['id_cost_center'] ?? null,
                    'id_vehicle'          => $row['id_vehicle'] ?? null,
                    'description'         => $this->cleanUtf8String($row['description'] ?? ''),
                    'quantity'            => $row['quantity'] ?? 1,
                    'unit_price'          => $row['unit_price'] ?? 0,
                    'discount_percentage' => $row['discount_percentage'] ?? 0,
                    'vat_rate'            => $vatRateDecimal,
                    'vat_rate_id'         => $vatRateId,
                    'total'               => round(
                                                ($row['quantity'] ?? 1) * ($row['unit_price'] ?? 0) *
                                                (1 - ($row['discount_percentage'] ?? 0) / 100),
                                                2
                                            ),
                ]);
            }
            
            $defaultStatus = config('gestionale.invoice_status.issued.code', 'issued');

            // Salva i pagamenti
            foreach ($this->payments as $index => $payment) {
                $dueDate = !empty($payment['due_date']) 
                    ? $payment['due_date'] 
                    : $this->data_invoice;
                
                Log::info('Salvataggio pagamento ' . ($index + 1), [
                    'due_date' => $dueDate,
                    'amount' => $payment['amount'],
                    'payment_method' => $payment['payment_method'] ?? 'Non specificato',
                    'has_iban' => !empty($payment['iban'])
                ]);
                
                $invoice->payments()->create([
                    'due_date' => $dueDate,
                    'amount' => $payment['amount'] ?? $this->importo_totale,
                    'payment_method' => $this->cleanUtf8String($payment['payment_method'] ?? null),
                    'iban' => $this->cleanUtf8String($payment['iban'] ?? null),
                    'status' => $defaultStatus,
                ]);
            }
            
            // Salva i riepiloghi IVA
            foreach ($this->vatSummaries as $summary) {
                $invoice->vatSummaries()->create([
                    'tax_rate' => $summary['tax_rate'] ?? 0,
                    'sdi_nature' => $this->cleanUtf8String($summary['sdi_nature'] ?? null),
                    'taxable_amount' => $summary['taxable_amount'] ?? 0,
                    'tax_amount' => $summary['tax_amount'] ?? 0,
                    'vat_law_reference' => $this->cleanUtf8String($summary['vat_law_reference'] ?? null),
                    'esigibilita_iva' => $this->cleanUtf8String($summary['esigibilita_iva'] ?? 'I'),
                ]);
            }

            DB::commit();

            $attachmentsCount = count($this->extracted_attachments);
            $message = 'Fattura importata con successo!';
            if ($attachmentsCount > 0) {
                $message .= " {$attachmentsCount} allegato/i salvato/i su S3.";
            }
            
            session()->flash('success', $message);
            return redirect()->route('admin.invoices-received.index');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore salvataggio: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Errore: ' . $this->cleanUtf8String($e->getMessage())]);

            return null;
        }
    }

    public function render()
    {
        $tipoDocumento = config('gestionale.tipo_documento', []);
        $currencies = config('gestionale.currencies', []);
        
        return view('livewire.admin.invoices-xml-import', [
            'tipoDocumento' => $tipoDocumento,
            'currencies' => $currencies,
        ]);
    }
}