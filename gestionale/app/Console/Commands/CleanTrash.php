<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Administrator;
use App\Models\User;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CleanTrash extends Command
{
    protected $signature = 'trash:clean';
    protected $description = 'Elimina permanentemente gli elementi nel cestino da più di 30 giorni';

    public function handle()
    {
        $date = Carbon::now()->subDays(30);
        
        // Elimina amministratori
        $adminsCount = Administrator::onlyTrashed()
            ->where('deleted_at', '<=', $date)
            ->forceDelete();
        
        // Elimina utenti
        $usersCount = User::onlyTrashed()
            ->where('deleted_at', '<=', $date)
            ->forceDelete();
        
        // Elimina ruoli
        $rolesCount = Role::onlyTrashed()
            ->where('deleted_at', '<=', $date)
            ->forceDelete();
        
        $this->info("Pulizia completata: {$adminsCount} amministratori, {$usersCount} utenti, {$rolesCount} ruoli eliminati permanentemente.");
        
        // Log dell'operazione
        Log::info("Pulizia automatica cestino eseguita", [
            'administrators' => $adminsCount,
            'users' => $usersCount,
            'roles' => $rolesCount,
            'date' => now()
        ]);
    }
}