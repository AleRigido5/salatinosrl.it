<?php
// app/Console/Commands/MigrateAttendanceJsonToDb.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StaffAttendance;
use App\Models\Staff;
use App\Models\Ownership;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class MigrateAttendanceJsonToDb extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:attendance-json-to-db
                            {--dry-run : Esegui solo la simulazione senza salvare}
                            {--staff-id= : Migra solo per un ID staff specifico}
                            {--year= : Migra solo per un anno specifico}
                            {--month= : Migra solo per un mese specifico (1-12)}
                            {--force : Forza la migrazione anche se esistono già dati}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migra i file JSON delle presenze dalla cartella attendance_data al database';

    /**
     * Path della cartella contenente i file JSON
     */
    protected $jsonPath = 'attendance_data';

    /**
     * Statistiche di migrazione
     */
    protected $stats = [
        'files_found' => 0,
        'files_processed' => 0,
        'files_skipped' => 0,
        'records_inserted' => 0,
        'records_updated' => 0,
        'records_skipped' => 0,
        'errors' => 0,
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Avvio migrazione presenze da JSON a Database');
        $this->line('');

        // Verifica che la tabella esista
        if (!Schema::hasTable('staff_attendances')) {
            $this->error('❌ La tabella staff_attendances non esiste!');
            $this->info('Esegui prima: php artisan migrate');
            return 1;
        }

        // Opzioni
        $dryRun = $this->option('dry-run');
        $staffId = $this->option('staff-id');
        $year = $this->option('year');
        $month = $this->option('month');
        $force = $this->option('force');

        if ($dryRun) {
            $this->warn('⚠️  MODALITÀ DRY-RUN: Nessun dato verrà salvato');
            $this->line('');
        }

        // Recupera i file JSON
        $files = $this->getJsonFiles($staffId, $year, $month);

        if (empty($files)) {
            $this->error('❌ Nessun file JSON trovato in ' . $this->jsonPath);
            return 1;
        }

        $this->stats['files_found'] = count($files);
        $this->info("📁 Trovati " . count($files) . " file JSON");

        // Mostra la lista dei file trovati
        if ($this->getOutput()->isVerbose()) {
            $this->line('');
            $this->info('📋 Lista file:');
            foreach ($files as $file) {
                $this->line("   - " . $file['filename'] . " (Staff ID: " . $file['staff_id'] . ")");
            }
            $this->line('');
        }

        // Conferma per dry-run
        if (!$dryRun && !$force) {
            $totalRecords = $this->countTotalRecords($files);
            $this->warn("⚠️  Verranno migrati circa {$totalRecords} record");
            
            if (!$this->confirm('Procedere con la migrazione?')) {
                $this->info('Migrazione annullata.');
                return 0;
            }
        }

        $this->line('');
        $this->info('📊 Elaborazione in corso...');
        $this->line('');

        // Progress bar
        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        // Processa ogni file
        foreach ($files as $fileInfo) {
            $this->processFile($fileInfo, $dryRun);
            $bar->advance();
        }

        $bar->finish();
        $this->line('');
        $this->line('');

        // Mostra statistiche
        $this->showStats();

        return 0;
    }

    /**
     * Recupera la lista dei file JSON da processare
     */
    protected function getJsonFiles($staffId = null, $year = null, $month = null)
    {
        $files = [];

        // Verifica che la cartella esista
        if (!Storage::disk('local')->exists($this->jsonPath)) {
            $this->warn("⚠️  Cartella {$this->jsonPath} non trovata");
            return $files;
        }

        // Recupera tutti i file nella cartella
        $allFiles = Storage::disk('local')->files($this->jsonPath);

        foreach ($allFiles as $file) {
            $filename = basename($file);

            // Filtra solo file JSON con formato staff_{id}_{year}_{month}.json
            if (!preg_match('/staff_(\d+)_(\d{4})_(\d{2})\.json/', $filename, $matches)) {
                continue;
            }

            $fileStaffId = (int) $matches[1];
            $fileYear = (int) $matches[2];
            $fileMonth = (int) $matches[3];

            // Applica filtri
            if ($staffId && $fileStaffId != $staffId) continue;
            if ($year && $fileYear != $year) continue;
            if ($month && $fileMonth != $month) continue;

            $files[] = [
                'path' => $file,
                'filename' => $filename,
                'staff_id' => $fileStaffId,
                'year' => $fileYear,
                'month' => $fileMonth,
                'full_path' => Storage::disk('local')->path($file),
            ];
        }

        // Ordina per staff_id, year, month
        usort($files, function($a, $b) {
            if ($a['staff_id'] != $b['staff_id']) return $a['staff_id'] - $b['staff_id'];
            if ($a['year'] != $b['year']) return $a['year'] - $b['year'];
            return $a['month'] - $b['month'];
        });

        return $files;
    }

    /**
     * Conta il numero totale di record da migrare
     */
    protected function countTotalRecords($files)
    {
        $total = 0;
        foreach ($files as $file) {
            $data = $this->readJsonFile($file['path']);
            if ($data && isset($data['presenze'])) {
                $total += count($data['presenze']);
            }
        }
        return $total;
    }

    /**
     * Processa un singolo file JSON
     */
    protected function processFile($fileInfo, $dryRun)
    {
        $filePath = $fileInfo['path'];
        $staffId = $fileInfo['staff_id'];
        $year = $fileInfo['year'];
        $month = $fileInfo['month'];

        // Verifica che lo staff esista
        $staff = Staff::find($staffId);
        if (!$staff) {
            $this->warn("⚠️  Staff ID {$staffId} non trovato, salto file: " . $fileInfo['filename']);
            $this->stats['files_skipped']++;
            return;
        }

        // Leggi il file JSON
        $data = $this->readJsonFile($filePath);
        if (!$data || !isset($data['presenze'])) {
            $this->warn("⚠️  File non valido: " . $fileInfo['filename']);
            $this->stats['files_skipped']++;
            return;
        }

        // Verifica se esistono già dati nel database per questo staff/mese
        if (!$dryRun) {
            $existing = StaffAttendance::where('id_staff', $staffId)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->count();

            if ($existing > 0) {
                if (!$this->option('force')) {
                    $this->warn("⚠️  Esistono già {$existing} record per staff {$staffId} - {$year}-{$month}, salto");
                    $this->stats['files_skipped']++;
                    return;
                } else {
                    // Force mode: elimina i dati esistenti
                    StaffAttendance::where('id_staff', $staffId)
                        ->whereYear('date', $year)
                        ->whereMonth('date', $month)
                        ->delete();
                    $this->stats['records_updated'] += $existing;
                    $this->info("   🔄 Rimossi {$existing} record esistenti per staff {$staffId}");
                }
            }
        }

        // Processa le presenze
        $records = [];
        $presenze = $data['presenze'];
        $totalPresenze = count($presenze);
        $validRecords = 0;

        foreach ($presenze as $presenza) {
            if (!isset($presenza['data']) || !isset($presenza['is_present'])) {
                continue;
            }

            $date = $presenza['data'];
            $isPresent = (bool) $presenza['is_present'];
            $ownershipId = isset($presenza['id_ownership']) ? $presenza['id_ownership'] : null;

            // Verifica che la data sia valida
            try {
                $carbonDate = Carbon::parse($date);
            } catch (\Exception $e) {
                $this->warn("⚠️  Data non valida: {$date}, salto record");
                $this->stats['records_skipped']++;
                continue;
            }

            // Verifica che l'ownership esista (se specificata)
            if ($ownershipId) {
                $ownership = Ownership::find($ownershipId);
                if (!$ownership) {
                    $this->warn("⚠️  Ownership ID {$ownershipId} non trovato, salto record per {$date}");
                    $this->stats['records_skipped']++;
                    continue;
                }
            }

            $records[] = [
                'id_staff' => $staffId,
                'id_ownership' => $ownershipId,
                'date' => $date,
                'is_present' => $isPresent,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $validRecords++;
        }

        if (empty($records)) {
            $this->warn("⚠️  Nessun record valido nel file: " . $fileInfo['filename']);
            $this->stats['files_skipped']++;
            return;
        }

        // Salva nel database
        if (!$dryRun) {
            try {
                $inserted = 0;
                foreach ($records as $record) {
                    // Usa updateOrCreate per evitare duplicati
                    StaffAttendance::updateOrCreate(
                        [
                            'id_staff' => $record['id_staff'],
                            'id_ownership' => $record['id_ownership'],
                            'date' => $record['date'],
                        ],
                        [
                            'is_present' => $record['is_present'],
                            'updated_at' => now(),
                        ]
                    );
                    $inserted++;
                }

                $this->stats['records_inserted'] += $inserted;
                $this->stats['files_processed']++;

                if ($this->getOutput()->isVerbose()) {
                    $this->line("   ✅ " . $fileInfo['filename'] . " → {$inserted} record migrati su {$totalPresenze}");
                }
            } catch (\Exception $e) {
                $this->error("❌ Errore nel salvare il file: " . $fileInfo['filename']);
                $this->error("   " . $e->getMessage());
                $this->stats['errors']++;
            }
        } else {
            // Dry-run: solo conteggio
            $this->stats['records_inserted'] += $validRecords;
            $this->stats['files_processed']++;
            
            if ($this->getOutput()->isVerbose()) {
                $this->line("   📋 " . $fileInfo['filename'] . " → {$validRecords} record da migrare");
            }
        }
    }

    /**
     * Legge un file JSON e restituisce i dati
     */
    protected function readJsonFile($filePath)
    {
        try {
            if (!Storage::disk('local')->exists($filePath)) {
                return null;
            }

            $content = Storage::disk('local')->get($filePath);
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->warn("⚠️  Errore JSON in " . basename($filePath) . ": " . json_last_error_msg());
                return null;
            }

            return $data;
        } catch (\Exception $e) {
            $this->error("❌ Errore nella lettura del file: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Mostra le statistiche finali
     */
    protected function showStats()
    {
        $this->line('');
        $this->info('📊 STATISTICHE MIGRAZIONE');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line("  📁 File trovati:            " . $this->stats['files_found']);
        $this->line("  ✅ File processati:         " . $this->stats['files_processed']);
        $this->line("  ⏭️  File saltati:            " . $this->stats['files_skipped']);
        $this->line("  📝 Record inseriti:         " . $this->stats['records_inserted']);
        $this->line("  🔄 Record aggiornati:       " . $this->stats['records_updated']);
        $this->line("  ⏭️  Record saltati:          " . $this->stats['records_skipped']);
        $this->line("  ❌ Errori:                  " . $this->stats['errors']);
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        if ($this->option('dry-run')) {
            $this->warn('⚠️  DRY-RUN: Nessun dato è stato effettivamente salvato');
            $this->info('Rimuovi l\'opzione --dry-run per eseguire la migrazione effettiva');
        } else {
            if ($this->stats['errors'] > 0) {
                $this->warn('⚠️  Ci sono stati errori durante la migrazione. Verifica i log.');
                $this->info('Puoi controllare i dettagli con: tail -f storage/logs/laravel.log');
            } else {
                $this->info('✅ Migrazione completata con successo!');
                $this->line('');
                $this->info('💡 Puoi verificare i dati con:');
                $this->line('   php artisan tinker');
                $this->line('   >>> \\App\\Models\\StaffAttendance::count();');
            }
        }
    }
}