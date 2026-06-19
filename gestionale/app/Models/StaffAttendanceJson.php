<?php
// app/Models/StaffAttendanceJson.php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class StaffAttendanceJson
{
    protected static string $basePath = 'attendance_data';

    public static function getForMonth(int $staffId, int $year, int $month): ?array
    {
        $filename = self::getFilename($staffId, $year, $month);
        return self::readFile($filename);
    }

    public static function getStaff(int $staffId, int $year, int $month): ?array
    {
        $filename = self::getFilename($staffId, $year, $month);
        return self::readFile($filename);
    }

    public static function save(array $data): bool
    {
        try {
            $staffId = $data['dipendente_id'];
            $month = $data['mese'];
            [$year, $monthNum] = explode('-', $month);

            $dateSet = [];
            $giorniEffettivi = 0;
            $giorniMessi = 0;
            
            foreach ($data['presenze'] as $presenza) {
                $date = $presenza['data'];
                if (!isset($dateSet[$date])) {
                    $dateSet[$date] = true;
                    // Se non ha causale, conta come giorno effettivo
                    if (empty($presenza['causale'])) {
                        $giorniEffettivi++;
                        // Se ha is_present true, conta come giorno messo
                        if ($presenza['is_present'] ?? false) {
                            $giorniMessi++;
                        }
                    }
                }
            }
            
            $data['n_giornate_effettive'] = (string) $giorniEffettivi;
            $data['n_giornate_messe'] = (string) $giorniMessi;

            usort($data['presenze'], function($a, $b) {
                return strcmp($a['data'], $b['data']);
            });

            $filename = self::getFilename((int)$staffId, (int)$year, (int)$monthNum);
            return self::writeFile($filename, $data);

        } catch (\Exception $e) {
            Log::error('Errore nel salvataggio JSON delle presenze', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            return false;
        }
    }

    public static function saveMany(array $changes): array
    {
        $results = [];
        $grouped = [];

        foreach ($changes as $change) {
            $staffId = $change['staff_id'];
            $date = $change['date'];
            $dt = Carbon::parse($date);
            $key = $staffId . '_' . $dt->year . '_' . $dt->month;
            
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'staff_id' => $staffId,
                    'year' => $dt->year,
                    'month' => $dt->month,
                    'changes' => [],
                ];
            }
            $grouped[$key]['changes'][] = $change;
        }

        foreach ($grouped as $group) {
            $staffId = $group['staff_id'];
            $year = $group['year'];
            $month = $group['month'];
            
            $existingData = self::getStaff($staffId, $year, $month);
            
            if ($existingData) {
                $data = $existingData;
            } else {
                $staff = \App\Models\Staff::find($staffId);
                $data = [
                    'dipendente_id' => (string) $staffId,
                    'nome' => $staff ? $staff->CognomePers . ' ' . $staff->NomePers : 'Dipendente ' . $staffId,
                    'mese' => $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT),
                    'n_giornate_effettive' => '0',
                    'n_giornate_messe' => '0',
                    'presenze' => [],
                ];
            }

            $presenzeIndex = [];
            foreach ($data['presenze'] as $index => $presenza) {
                $key = $presenza['data'];
                if (isset($presenza['id_ownership'])) {
                    $key .= '_' . $presenza['id_ownership'];
                }
                $presenzeIndex[$key] = $index;
            }

            foreach ($group['changes'] as $change) {
                $date = $change['date'];
                $ownershipId = $change['ownership_id'] ?? null;
                $isPresent = (bool) $change['checked'];

                $key = $date;
                if ($ownershipId) {
                    $key .= '_' . $ownershipId;
                }

                if (isset($presenzeIndex[$key])) {
                    $index = $presenzeIndex[$key];
                    $data['presenze'][$index]['is_present'] = $isPresent;
                } else {
                    $data['presenze'][] = [
                        'data' => $date,
                        'id_ownership' => $ownershipId,
                        'is_present' => $isPresent,
                    ];
                }

                $results[] = [
                    'date' => $date,
                    'ownership' => $ownershipId,
                    'is_present' => $isPresent,
                    'status' => 'saved',
                ];
            }

            // Ricalcola i totali
            $dateSet = [];
            $giorniEffettivi = 0;
            $giorniMessi = 0;
            
            foreach ($data['presenze'] as $presenza) {
                $date = $presenza['data'];
                if (!isset($dateSet[$date])) {
                    $dateSet[$date] = true;
                    if (empty($presenza['causale'])) {
                        $giorniEffettivi++;
                        if ($presenza['is_present'] ?? false) {
                            $giorniMessi++;
                        }
                    }
                }
            }
            
            $data['n_giornate_effettive'] = (string) $giorniEffettivi;
            $data['n_giornate_messe'] = (string) $giorniMessi;

            usort($data['presenze'], function($a, $b) {
                return strcmp($a['data'], $b['data']);
            });

            self::save($data);
        }

        return $results;
    }

    protected static function getFilename(int $staffId, int $year, int $month): string
    {
        return sprintf('staff_%d_%04d_%02d.json', $staffId, $year, $month);
    }

    protected static function readFile(string $filename): ?array
    {
        try {
            $path = self::$basePath . '/' . $filename;
            if (!Storage::disk('local')->exists($path)) {
                return null;
            }
            $content = Storage::disk('local')->get($path);
            return json_decode($content, true);
        } catch (\Exception $e) {
            Log::error('Errore nella lettura del file JSON', [
                'file' => $filename,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    protected static function writeFile(string $filename, array $data): bool
    {
        try {
            $path = self::$basePath . '/' . $filename;
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            if (!Storage::disk('local')->exists(self::$basePath)) {
                Storage::disk('local')->makeDirectory(self::$basePath);
            }

            Storage::disk('local')->put($path, $json);
            return true;
        } catch (\Exception $e) {
            Log::error('Errore nella scrittura del file JSON', [
                'file' => $filename,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public static function listAll(): array
    {
        try {
            $files = Storage::disk('local')->files(self::$basePath);
            $result = [];

            foreach ($files as $file) {
                $filename = basename($file);
                if (preg_match('/staff_(\d+)_(\d{4})_(\d{2})\.json/', $filename, $matches)) {
                    $data = self::readFile($filename);
                    $result[] = [
                        'filename' => $filename,
                        'staff_id' => (int) $matches[1],
                        'year' => (int) $matches[2],
                        'month' => (int) $matches[3],
                        'nome' => $data['nome'] ?? 'Dipendente ' . $matches[1],
                        'n_giornate_effettive' => $data['n_giornate_effettive'] ?? '0',
                        'n_giornate_messe' => $data['n_giornate_messe'] ?? '0',
                        'size' => Storage::disk('local')->size($file),
                        'modified_at' => Storage::disk('local')->lastModified($file),
                    ];
                }
            }

            usort($result, function ($a, $b) {
                return $b['modified_at'] - $a['modified_at'];
            });

            return $result;
        } catch (\Exception $e) {
            Log::error('Errore nel recupero dei file JSON', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    public static function deleteFile(int $staffId, int $year, int $month): bool
    {
        $filename = self::getFilename($staffId, $year, $month);
        $path = self::$basePath . '/' . $filename;
        
        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->delete($path);
        }
        
        return true;
    }
}