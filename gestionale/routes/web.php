<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AdministratorController;
use App\Http\Controllers\Admin\TrashController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\EntityController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ContactController;
use Illuminate\Support\Facades\Route;

// Rotte pubbliche
Route::get('/', function () {
    return redirect('/admin/login');
});

// Area Admin
Route::prefix('admin')->name('admin.')->group(function () {
    
    // Autenticazione
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [LoginController::class, 'login']);
    });
    
    // Area protetta
    Route::middleware(['admin.auth'])->group(function () {
        
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

        // =============================================
        // GESTIONE AMMINISTRATORI
        // =============================================
        Route::resource('administrators', AdministratorController::class);
        Route::post('administrators/{administrator}/toggle-status', [AdministratorController::class, 'toggleStatus'])->name('administrators.toggle-status');

        // =============================================
        // GESTIONE CESTINO (Soft Delete)
        // =============================================
        Route::get('/trash/{type}', [TrashController::class, 'index'])->name('trash.index');
        Route::post('/trash/{type}/{id}/restore', [TrashController::class, 'restore'])->name('trash.restore');
        Route::delete('/trash/{type}/{id}/force-delete', [TrashController::class, 'forceDelete'])->name('trash.force-delete');
        Route::post('/trash/{type}/empty', [TrashController::class, 'empty'])->name('trash.empty');

        // Rotte per operazioni bulk
        Route::post('/trash/{type}/bulk-restore', [TrashController::class, 'bulkRestore'])->name('trash.bulk-restore');
        Route::delete('/trash/{type}/bulk-force-delete', [TrashController::class, 'bulkForceDelete'])->name('trash.bulk-force-delete');
        
        // =============================================
        // GESTIONE UTENTI (normali, non admin)
        // =============================================
        Route::resource('users', UserController::class);
        Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        
        // =============================================
        // GESTIONE RUOLI E PERMESSI
        // =============================================
        Route::resource('roles', RoleController::class);

        // =============================================
        // PROFILO E IMPOSTAZIONI
        // =============================================
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

        // =============================================
        // GESTIONE ENTITÀ (Clienti e Fornitori)
        // =============================================
        Route::prefix('entities')->name('entities.')->group(function () {
            Route::get('/', [EntityController::class, 'index'])->name('index');
            Route::get('/create', [EntityController::class, 'create'])->name('create');
            Route::post('/', [EntityController::class, 'store'])->name('store');
            Route::get('/{entity}', [EntityController::class, 'show'])->name('show');
            Route::get('/{entity}/edit', [EntityController::class, 'edit'])->name('edit');
            Route::put('/{entity}', [EntityController::class, 'update'])->name('update');
            Route::delete('/{entity}', [EntityController::class, 'destroy'])->name('destroy');
            Route::post('/{entity}/toggle-status', [EntityController::class, 'toggleStatus'])->name('toggle-status');
            
            // Export entities
            Route::get('/export/csv', [EntityController::class, 'export'])->name('export');
            Route::get('/export/pdf', [EntityController::class, 'exportPdf'])->name('export.pdf');
        });

        // =============================================
        // GESTIONE SETTINGS (Impostazioni di Sistema)
        // =============================================
        Route::resource('settings', SettingController::class);

        // =============================================
        // GESTIONE CONTATTI
        // =============================================
        Route::prefix('contacts')->name('contacts.')->group(function () {
            Route::get('/', [ContactController::class, 'index'])->name('index');
            Route::get('/entity/{entity}', [ContactController::class, 'entityContacts'])->name('entity');
            Route::post('/{contact}/set-principal', [ContactController::class, 'setPrincipal'])->name('set-principal');
            Route::delete('/{contact}', [ContactController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-delete', [ContactController::class, 'bulkDelete'])->name('bulk-delete');
        });

        // =============================================
        // API PER RICERCHE AJAX / Livewire
        // =============================================
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/search-entities', [EntityController::class, 'search'])->name('search-entities');
            Route::get('/entity-contacts/{entity}', [ContactController::class, 'getEntityContacts'])->name('entity-contacts');
            
            // API per indirizzi
            Route::get('/entities/{entityId}/addresses', [EntityController::class, 'getAddresses']);
            Route::post('/entities/{entityId}/addresses', [EntityController::class, 'storeAddress']);
            Route::put('/entities/{entityId}/addresses/{addressId}', [EntityController::class, 'updateAddress']);
            Route::delete('/entities/{entityId}/addresses/{addressId}', [EntityController::class, 'deleteAddress']);
        });
    });
});