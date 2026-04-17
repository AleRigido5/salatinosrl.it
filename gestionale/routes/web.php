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
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\MezziController;
use App\Http\Controllers\Admin\SettingCategoryController;
use App\Http\Controllers\Admin\ExpirationController;
use App\Http\Controllers\Admin\DocumentController;
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
            
            // Route per ripristinare i filtri dopo la modifica
            Route::post('/restore-filters', [EntityController::class, 'restoreFilters'])->name('restore-filters');
        });

        // =============================================
        // GESTIONE PERSONALE (STAFF)
        // =============================================
        Route::prefix('staff')->name('staff.')->group(function () {
            Route::get('/', [StaffController::class, 'index'])->name('index');
            Route::get('/create', [StaffController::class, 'create'])->name('create');
            Route::post('/', [StaffController::class, 'store'])->name('store');
            Route::get('/{staff}', [StaffController::class, 'show'])->name('show');
            Route::get('/{staff}/edit', [StaffController::class, 'edit'])->name('edit');
            Route::put('/{staff}', [StaffController::class, 'update'])->name('update');
            Route::delete('/{staff}', [StaffController::class, 'destroy'])->name('destroy');
            Route::post('/{staff}/toggle-status', [StaffController::class, 'toggleStatus'])->name('toggle-status');
            
            // Export staff
            Route::get('/export/csv', [StaffController::class, 'export'])->name('export');
            Route::get('/export/pdf', [StaffController::class, 'exportPdf'])->name('export.pdf');
        });

        // =============================================
        // GESTIONE MEZZI (VEHICLES)
        // =============================================
        Route::prefix('vehicles')->name('vehicles.')->group(function () {
            Route::get('/', [MezziController::class, 'index'])->name('index');
            Route::get('/create', [MezziController::class, 'create'])->name('create');
            Route::post('/', [MezziController::class, 'store'])->name('store');
            Route::get('/{vehicle}', [MezziController::class, 'show'])->name('show');
            Route::get('/{vehicle}/edit', [MezziController::class, 'edit'])->name('edit');
            Route::put('/{vehicle}', [MezziController::class, 'update'])->name('update');
            Route::delete('/{vehicle}', [MezziController::class, 'destroy'])->name('destroy');
            Route::post('/{vehicle}/toggle-status', [MezziController::class, 'toggleStatus'])->name('toggle-status');
            
            // Export vehicles
            Route::get('/export/csv', [MezziController::class, 'export'])->name('export');
            Route::get('/export/pdf', [MezziController::class, 'exportPdf'])->name('export.pdf');
        });

        // =============================================
        // GESTIONE SCADENZE (EXPIRATION)
        // =============================================
        Route::prefix('expiration')->name('expiration.')->group(function () {
            Route::get('/', [ExpirationController::class, 'index'])->name('index');
            Route::get('/create', [ExpirationController::class, 'create'])->name('create');
            Route::post('/', [ExpirationController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [ExpirationController::class, 'edit'])->name('edit');
            Route::put('/{id}', [ExpirationController::class, 'update'])->name('update');
            Route::delete('/{id}', [ExpirationController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/restore', [ExpirationController::class, 'restore'])->name('restore');
            Route::post('/{id}/toggle-status', [ExpirationController::class, 'toggleStatus'])->name('toggle-status');
        });

        // =============================================
        // GESTIONE DOCUMENTI
        // =============================================
        Route::prefix('documents')->name('documents.')->group(function () {
            Route::get('{tableRef}/{idRef}', [DocumentController::class, 'index'])->name('index');
            Route::post('{tableRef}/{idRef}', [DocumentController::class, 'store'])->name('store');
            Route::delete('{tableRef}/{idRef}/all', [DocumentController::class, 'destroyAll'])->name('destroy.all');  // <-- AGGIUNGI QUESTA RIGA
            Route::delete('{tableRef}/{idRef}/{documentId}', [DocumentController::class, 'destroy'])->name('destroy');
            Route::get('{tableRef}/{idRef}/{documentId}/download', [DocumentController::class, 'download'])->name('download');
        });

        // =============================================
        // GESTIONE SETTINGS (Impostazioni di Sistema)
        // =============================================

        // Settings Categories Routes - DEVONO ESSERE PRIMA della resource
        Route::prefix('settings/categories')->name('settings.categories.')->group(function () {
            Route::get('/', [SettingCategoryController::class, 'index'])->name('index');
            Route::get('/create', [SettingCategoryController::class, 'create'])->name('create');
            Route::post('/', [SettingCategoryController::class, 'store'])->name('store');
            Route::get('/{slug}', [SettingCategoryController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [SettingCategoryController::class, 'edit'])->name('edit');
            Route::put('/{id}', [SettingCategoryController::class, 'update'])->name('update');
            Route::delete('/{id}', [SettingCategoryController::class, 'destroy'])->name('destroy');
        });

        // Resource settings - DOPO le route delle categorie
        Route::resource('settings', SettingController::class);

        // =============================================
        // GESTIONE SERVIZI
        // =============================================
        Route::prefix('services')->name('services.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\ServiceController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Admin\ServiceController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\ServiceController::class, 'store'])->name('store');
            Route::get('/{service}', [App\Http\Controllers\Admin\ServiceController::class, 'show'])->name('show');
            Route::get('/{service}/edit', [App\Http\Controllers\Admin\ServiceController::class, 'edit'])->name('edit');
            Route::put('/{service}', [App\Http\Controllers\Admin\ServiceController::class, 'update'])->name('update');
            Route::post('/{service}/toggle-status', [App\Http\Controllers\Admin\ServiceController::class, 'toggleStatus'])->name('toggle-status');
        });

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
            
            // API per Personale (Staff)
            Route::get('/search-staff', [StaffController::class, 'search'])->name('search-staff');
            Route::get('/staff/{staff}/contacts', [StaffController::class, 'getContacts'])->name('staff-contacts');
            
            // API per Mezzi (Vehicles)
            Route::get('/search-vehicles', [MezziController::class, 'search'])->name('search-vehicles');
            Route::get('/vehicles/{vehicle}/documents', [MezziController::class, 'getDocuments'])->name('vehicle-documents');
            
            // API per Scadenze (Expiration)
            Route::get('/search-expiration', [ExpirationController::class, 'search'])->name('search-expiration');
            Route::get('/expiration/staff/{staffId}', [ExpirationController::class, 'getByStaff'])->name('expiration.by-staff');
            Route::get('/expiration/vehicle/{vehicleId}', [ExpirationController::class, 'getByVehicle'])->name('expiration.by-vehicle');
        });
    });
});