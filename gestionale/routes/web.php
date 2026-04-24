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
use App\Http\Controllers\Admin\VehiclesController;
use App\Http\Controllers\Admin\SettingCategoryController;
use App\Http\Controllers\Admin\ExpirationStaffController;
use App\Http\Controllers\Admin\ExpirationVehicleController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\VademecumController;
use App\Http\Controllers\Admin\CostCenterController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\ExpirationAllController;
use App\Models\Ownership;
use App\Models\Entity;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

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
        // GESTIONE ATTIVITÀ (ACTIVITIES)
        // =============================================
        Route::prefix('activities')->name('activities.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\ActivityController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Admin\ActivityController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\ActivityController::class, 'store'])->name('store');
            Route::get('/{activity}', [App\Http\Controllers\Admin\ActivityController::class, 'show'])->name('show');
            Route::get('/{activity}/edit', [App\Http\Controllers\Admin\ActivityController::class, 'edit'])->name('edit');
            Route::put('/{activity}', [App\Http\Controllers\Admin\ActivityController::class, 'update'])->name('update');
            Route::delete('/{activity}', [App\Http\Controllers\Admin\ActivityController::class, 'destroy'])->name('destroy');
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
            Route::get('/', [VehiclesController::class, 'index'])->name('index');
            Route::get('/create', [VehiclesController::class, 'create'])->name('create');
            Route::post('/', [VehiclesController::class, 'store'])->name('store');
            Route::get('/{vehicle}', [VehiclesController::class, 'show'])->name('show');
            Route::get('/{vehicle}/edit', [VehiclesController::class, 'edit'])->name('edit');
            Route::put('/{vehicle}', [VehiclesController::class, 'update'])->name('update');
            Route::delete('/{vehicle}', [VehiclesController::class, 'destroy'])->name('destroy');
            Route::post('/{vehicle}/toggle-status', [VehiclesController::class, 'toggleStatus'])->name('toggle-status');
            
            // Export vehicles
            Route::get('/export/csv', [VehiclesController::class, 'export'])->name('export');
            Route::get('/export/pdf', [VehiclesController::class, 'exportPdf'])->name('export.pdf');
        });

        // =============================================
        // GESTIONE CENTRI DI COSTO
        // =============================================
        Route::prefix('cost-centers')->name('cost_centers.')->group(function () {
            Route::get('/', [CostCenterController::class, 'index'])->name('index');
            Route::get('/create', [CostCenterController::class, 'create'])->name('create');
            Route::post('/', [CostCenterController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [CostCenterController::class, 'edit'])->name('edit');
            Route::put('/{id}', [CostCenterController::class, 'update'])->name('update');
            Route::delete('/{id}', [CostCenterController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/toggle-status', [CostCenterController::class, 'toggleStatus'])->name('toggle-status');
        });

        // API per centri di costo
        Route::get('/api/references/{type}', function ($type) {
            if ($type === 'ownership') {
                $items = Ownership::where('valid', 1)->get();
                return response()->json($items->map(function($item) {
                    return [
                        'id' => $item->id_proprieta,
                        'name' => $item->RagAbbrev ?? $item->Rag_Soc_intest ?? 'Proprietà ' . $item->id_proprieta
                    ];
                }));
            } elseif ($type === 'entities') {
                $items = Entity::where('valid', 1)->get();
                return response()->json($items->map(function($item) {
                    return [
                        'id' => $item->id_cliente,
                        'name' => $item->ragione_sociale ?? ($item->nome . ' ' . $item->cognome)
                    ];
                }));
            }
            return response()->json([]);
        })->name('api.references');

        // =============================================
        // GESTIONE SCADENZE (EXPIRATION)
        // =============================================
        // Scadenze Staff
        Route::prefix('expiration-staff')->name('expiration-staff.')->middleware(['auth:admin'])->group(function () {
            Route::get('/', [ExpirationStaffController::class, 'index'])->name('index');
            Route::get('/create', [ExpirationStaffController::class, 'create'])->name('create');
            Route::post('/', [ExpirationStaffController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [ExpirationStaffController::class, 'edit'])->name('edit');
            Route::put('/{id}', [ExpirationStaffController::class, 'update'])->name('update');
            Route::delete('/{id}', [ExpirationStaffController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/restore', [ExpirationStaffController::class, 'restore'])->name('restore');
            Route::post('/{id}/toggle-status', [ExpirationStaffController::class, 'toggleStatus'])->name('toggle-status');
        });

        // Scadenze Veicoli
        Route::prefix('expiration-vehicle')->name('expiration-vehicle.')->middleware(['auth:admin'])->group(function () {
            Route::get('/', [ExpirationVehicleController::class, 'index'])->name('index');
            Route::get('/create', [ExpirationVehicleController::class, 'create'])->name('create');
            Route::post('/', [ExpirationVehicleController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [ExpirationVehicleController::class, 'edit'])->name('edit');
            Route::put('/{id}', [ExpirationVehicleController::class, 'update'])->name('update');
            Route::delete('/{id}', [ExpirationVehicleController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/restore', [ExpirationVehicleController::class, 'restore'])->name('restore');
            Route::post('/{id}/toggle-status', [ExpirationVehicleController::class, 'toggleStatus'])->name('toggle-status');
        });

        // Scadenze Generiche (tutte)
        Route::prefix('expiration')->name('expiration-all.')->middleware(['auth:admin'])->group(function () {
            Route::get('/', [ExpirationAllController::class, 'index'])->name('index');
        });

        // =============================================
        // GESTIONE DOCUMENTI
        // =============================================
        // Documenti - Rotta generica per tutti i tipi
        Route::prefix('documents/{tableRef}/{idRef}')->name('documents.')->group(function () {
            Route::get('/', [DocumentController::class, 'index'])->name('index');
            Route::post('/', [DocumentController::class, 'store'])->name('store');
            Route::delete('/{documentId}', [DocumentController::class, 'destroy'])->name('destroy');
            Route::delete('/all/delete', [DocumentController::class, 'destroyAll'])->name('destroyAll');
            Route::get('/{documentId}/download', [DocumentController::class, 'download'])->name('download');
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
            Route::get('/', [ServiceController::class, 'index'])->name('index');
            Route::get('/create', [ServiceController::class, 'create'])->name('create');
            Route::post('/', [ServiceController::class, 'store'])->name('store');
            Route::get('/{service}', [ServiceController::class, 'show'])->name('show');
            Route::get('/{service}/edit', [ServiceController::class, 'edit'])->name('edit');
            Route::put('/{service}', [ServiceController::class, 'update'])->name('update');
            Route::post('/{service}/toggle-status', [ServiceController::class, 'toggleStatus'])->name('toggle-status');
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
        // GESTIONE VADEMECUM
        // =============================================
        // Vista index per la gestione del VADEMECUM
        Route::get('/vademecum', function () {
            $vademecumPath = public_path('vademecum');
            $pdfFiles = File::glob($vademecumPath . '/*.pdf');
            $currentPdf = null;
            
            if (!empty($pdfFiles)) {
                $pdfFile = $pdfFiles[0];
                $currentPdf = [
                    'filename' => basename($pdfFile),
                    'size' => File::size($pdfFile),
                    'last_modified' => File::lastModified($pdfFile)
                ];
            }
            
            return view('admin.vademecum.index', compact('currentPdf'));
        })->name('vademecum.index');
        
        // Route per il PDF (accessibile a tutti gli admin autenticati)
        Route::get('/vademecum/pdf', [VademecumController::class, 'getPdf'])->name('vademecum.pdf');
        Route::get('/vademecum/info', [VademecumController::class, 'info'])->name('vademecum.info');
        Route::post('/vademecum/upload', [VademecumController::class, 'upload'])->name('vademecum.upload');

        // =============================================
        // API PER RICERCHE AJAX / Livewire
        // =============================================
        Route::prefix('api')->name('api.')->group(function () {
            // API per Entità
            Route::get('/search-entities', [EntityController::class, 'search'])->name('search-entities');
            Route::get('/search-clients', [EntityController::class, 'searchClients'])->name('search-clients');
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
            Route::get('/search-vehicles', [VehiclesController::class, 'search'])->name('search-vehicles');
            Route::get('/vehicles/{vehicle}', [VehiclesController::class, 'getVehicle'])->name('vehicles.get');
            Route::get('/vehicles/{vehicle}/documents', [VehiclesController::class, 'getDocuments'])->name('vehicle-documents');

            // ===== API PER ACTIVITIES =====
            // API per Centri di Costo
            Route::get('/search-cost-centers', [CostCenterController::class, 'search'])->name('search-cost-centers');
            
            // API per recuperare il cliente associato a un centro di costo
            Route::get('/cost-center-client', [CostCenterController::class, 'getClientByCostCenter'])->name('cost-center-client');
            
            // API per Servizi
            Route::get('/search-services', [ServiceController::class, 'search'])->name('search-services');
        });
    });
});