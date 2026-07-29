<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Vehicles;
use App\Models\Ownership;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VehiclesTable extends Component
{
    use WithPagination;

    public $search = '';
    public $tipoFilter = '';
    public $statoFilter = '';
    public $ownershipFilter = '';
    public $polizzaSearch = '';
    public $perPage = 100;
    public $sortField = 'id';
    public $sortDirection = 'asc';

    // Modal visualizzazione
    public $showViewModal = false;
    public $viewingVehicle = null;

    // Modal creazione
    public $showCreateModal = false;
    public $createTarga = '';
    public $createMarca = '';
    public $createModello = '';
    public $createTipologia = '';
    public $createImmatricolazione = '';
    public $createValid = 1;
    public $createIdOwnership = '';
    public $createNote = '';

    // Modal modifica
    public $showEditModal = false;
    public $editId = null;
    public $editTarga = '';
    public $editMarca = '';
    public $editModello = '';
    public $editTipologia = '';
    public $editImmatricolazione = '';
    public $editValid = 1;
    public $editIdOwnership = '';
    public $editNote = '';

    // Modal eliminazione
    public $showDeleteModal = false;
    public $deleteId = null;
    public $deleteName = '';

    protected $paginationTheme = 'tailwind';
    protected $queryString = [
        'search', 'tipoFilter', 'statoFilter', 'ownershipFilter',
        'polizzaSearch', 'perPage', 'sortField', 'sortDirection'
    ];

    protected $listeners = [
        'filters-reset' => 'resetFilters'
    ];

    // ==================== TIPI E STATI ====================

    public function getTipiListProperty()
    {
        $tipi = Vehicles::select('tipologia')
            ->whereNotNull('tipologia')
            ->distinct()
            ->pluck('tipologia', 'tipologia')
            ->toArray();

        if (empty($tipi)) {
            return [
                'Autocarro' => 'Autocarro',
                'Autovettura' => 'Autovettura',
                'Trattore Stradale' => 'Trattore Stradale',
                'Trattrice Agricola' => 'Trattrice Agricola',
                'Macchina Agricola' => 'Macchina Agricola',
                'Rimorchio' => 'Rimorchio',
                'Attrezzature' => 'Attrezzature',
                'Vendemmiatrice' => 'Vendemmiatrice',
                'Escavatore' => 'Escavatore',
                'Furgone' => 'Furgone',
                'motociclo' => 'motociclo'
            ];
        }

        return $tipi;
    }

    public function getProprietaListProperty()
    {
        $ownerships = Ownership::all();
        $list = [];

        foreach ($ownerships as $ownership) {
            if (!empty($ownership->RagAbbrev)) {
                $nome = $ownership->RagAbbrev;
            }
            elseif (!empty($ownership->Rag_Soc_intest)) {
                $nome = $ownership->Rag_Soc_intest;
            }
            elseif (!empty($ownership->RagSocialePr)) {
                $nome = $ownership->RagSocialePr;
            }
            else {
                $nome = 'Proprietà ' . $ownership->id_proprieta;
            }

            $list[$ownership->id_proprieta] = $nome;
        }

        return $list;
    }

    public function getStatiListProperty()
    {
        return [
            '1' => 'Attivi',
            '0' => 'Disattivi'
        ];
    }

    public function getPerPageOptionsProperty()
    {
        return [
            '100' => '100',
            '200' => '200',
            'all' => 'Tutto',
        ];
    }

    // ==================== QUERY CONDIVISA (tabella + export) ====================

    protected function buildFilteredQuery()
    {
        $query = Vehicles::with('ownership', 'expirations');

        if ($this->search) {
            $query->where(function($q) {
                $q->where('targa', 'like', '%'.$this->search.'%')
                  ->orWhere('marca', 'like', '%'.$this->search.'%')
                  ->orWhere('modello', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->tipoFilter) {
            $query->where('tipologia', $this->tipoFilter);
        }

        if ($this->statoFilter !== '') {
            $query->where('valid', $this->statoFilter);
        }

        if ($this->ownershipFilter) {
            $query->where('id_ownership', $this->ownershipFilter);
        }

        if ($this->polizzaSearch) {
            $query->whereHas('expirations', function($q) {
                $q->where('codice', 'like', '%'.$this->polizzaSearch.'%');
            });
        }

        switch ($this->sortField) {
            case 'id':
                $query->orderBy('id', $this->sortDirection);
                break;
            case 'id_ownership':
                $query->orderBy('id_ownership', $this->sortDirection);
                break;
            case 'tipologia':
                $query->orderBy('tipologia', $this->sortDirection);
                break;
            case 'marca':
                $query->orderBy('marca', $this->sortDirection)
                      ->orderBy('modello', $this->sortDirection);
                break;
            case 'targa':
                $query->orderBy('targa', $this->sortDirection);
                break;
            case 'immatricolazione':
                $query->orderBy('immatricolazione', $this->sortDirection);
                break;
            case 'valid':
                $query->orderBy('valid', $this->sortDirection);
                break;
            default:
                $query->orderBy('id', 'asc');
                break;
        }

        return $query;
    }

    public function getVehiclesProperty()
    {
        $query = $this->buildFilteredQuery();

        if ($this->perPage === 'all') {
            $total = (clone $query)->count();
            return $query->paginate($total > 0 ? $total : 1);
        }

        return $query->paginate((int) $this->perPage);
    }

    // ==================== SORTING ====================

    public function sortBy($field)
    {
        $validSortFields = ['id', 'id_ownership', 'tipologia', 'marca', 'targa', 'immatricolazione', 'valid'];

        if (!in_array($field, $validSortFields)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    // ==================== FILTRI ====================

    public function resetFilters()
    {
        $this->search = '';
        $this->tipoFilter = '';
        $this->statoFilter = '';
        $this->ownershipFilter = '';
        $this->polizzaSearch = '';
        $this->sortField = 'id';
        $this->sortDirection = 'asc';
        $this->resetPage();
        $this->dispatch('filters-reset');
        $this->dispatch('table-refreshed');
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingTipoFilter() { $this->resetPage(); }
    public function updatingStatoFilter() { $this->resetPage(); }
    public function updatingOwnershipFilter() { $this->resetPage(); }
    public function updatingPolizzaSearch() { $this->resetPage(); }
    public function updatedPerPage() { $this->resetPage(); }

    // ==================== VISUALIZZAZIONE ====================

    public function viewVehicle($id)
    {
        try {
            $vehicle = Vehicles::with(['createdBy', 'updatedBy', 'ownership'])->find($id);

            if (!$vehicle) {
                $this->dispatch('showError', message: 'Mezzo non trovato');
                return;
            }

            $this->viewingVehicle = $vehicle;
            $this->showViewModal = true;

        } catch (\Exception $e) {
            Log::error('VehiclesTable: viewVehicle errore', ['error' => $e->getMessage()]);
            $this->dispatch('showError', message: 'Errore nel caricamento: ' . $e->getMessage());
        }
    }

    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewingVehicle = null;
    }

    // ==================== CREAZIONE ====================

    public function openCreateModal()
    {
        $this->resetCreateForm();
        $this->showCreateModal = true;
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        $this->resetCreateForm();
    }

    public function resetCreateForm()
    {
        $this->createTarga = '';
        $this->createMarca = '';
        $this->createModello = '';
        $this->createTipologia = '';
        $this->createImmatricolazione = '';
        $this->createValid = 1;
        $this->createIdOwnership = '';
        $this->createNote = '';
    }

    public function saveVehicle()
    {
        $this->validate([
            'createTarga' => 'required|string|max:20|unique:vehicles,targa',
            'createMarca' => 'nullable|string|max:255',
            'createModello' => 'nullable|string|max:255',
            'createTipologia' => 'required|string|max:50',
            'createImmatricolazione' => 'nullable|date',
            'createIdOwnership' => 'required|exists:ownership,id_proprieta',
        ]);

        try {
            $adminId = Auth::guard('admin')->id();

            $vehicle = Vehicles::create([
                'targa' => strtoupper($this->createTarga),
                'marca' => $this->createMarca,
                'modello' => $this->createModello,
                'tipologia' => $this->createTipologia,
                'immatricolazione' => $this->createImmatricolazione ?: null,
                'valid' => $this->createValid,
                'id_ownership' => $this->createIdOwnership,
                'note' => $this->createNote,
                'created_by' => $adminId,
                'updated_by' => $adminId
            ]);

            $this->closeCreateModal();
            $this->dispatch('showSuccess', message: "Mezzo '{$vehicle->targa}' creato con successo!");
            $this->resetPage();
            $this->dispatch('table-refreshed');

        } catch (\Exception $e) {
            Log::error('VehiclesTable: saveVehicle errore', ['error' => $e->getMessage()]);
            $this->dispatch('showError', message: 'Errore durante il salvataggio: ' . $e->getMessage());
        }
    }

    // ==================== MODIFICA ====================

    public function openEditModal($id)
    {
        try {
            $vehicle = Vehicles::with('ownership')->find($id);

            if (!$vehicle) {
                $this->dispatch('showError', message: 'Mezzo non trovato');
                return;
            }

            $this->editId = $vehicle->id;
            $this->editTarga = $vehicle->targa;
            $this->editMarca = $vehicle->marca;
            $this->editModello = $vehicle->modello;
            $this->editTipologia = $vehicle->tipologia;
            $this->editImmatricolazione = $vehicle->immatricolazione ? date('Y-m-d', strtotime($vehicle->immatricolazione)) : '';
            $this->editValid = $vehicle->valid;
            $this->editIdOwnership = $vehicle->id_ownership;
            $this->editNote = $vehicle->note;

            $this->showEditModal = true;

        } catch (\Exception $e) {
            Log::error('VehiclesTable: openEditModal errore', ['error' => $e->getMessage()]);
            $this->dispatch('showError', message: 'Errore nel caricamento: ' . $e->getMessage());
        }
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->resetEditForm();
    }

    public function resetEditForm()
    {
        $this->editId = null;
        $this->editTarga = '';
        $this->editMarca = '';
        $this->editModello = '';
        $this->editTipologia = '';
        $this->editImmatricolazione = '';
        $this->editValid = 1;
        $this->editIdOwnership = '';
        $this->editNote = '';
    }

    public function updateVehicle()
    {
        try {
            $validator = validator([
                'editTarga' => $this->editTarga,
                'editMarca' => $this->editMarca,
                'editModello' => $this->editModello,
                'editTipologia' => $this->editTipologia,
                'editImmatricolazione' => $this->editImmatricolazione,
                'editIdOwnership' => $this->editIdOwnership,
            ], [
                'editTarga' => 'required|string|max:20|unique:vehicles,targa,' . $this->editId,
                'editMarca' => 'nullable|string|max:255',
                'editModello' => 'nullable|string|max:255',
                'editTipologia' => 'required|string|max:50',
                'editImmatricolazione' => 'nullable|date',
                'editIdOwnership' => 'required|exists:ownership,id_proprieta',
            ]);

            if ($validator->fails()) {
                $this->dispatch('showError', message: 'Errore di validazione: ' . json_encode($validator->errors()->toArray()));
                return;
            }

        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore di validazione: ' . $e->getMessage());
            return;
        }

        try {
            $vehicle = Vehicles::find($this->editId);

            if (!$vehicle) {
                $this->dispatch('showError', message: 'Mezzo non trovato');
                return;
            }

            $vehicle->update([
                'targa' => strtoupper($this->editTarga),
                'marca' => $this->editMarca,
                'modello' => $this->editModello,
                'tipologia' => $this->editTipologia,
                'immatricolazione' => $this->editImmatricolazione ?: null,
                'valid' => $this->editValid,
                'id_ownership' => $this->editIdOwnership,
                'note' => $this->editNote,
                'updated_by' => Auth::guard('admin')->id(),
                'updated_at' => now()
            ]);

            $updatedVehicle = Vehicles::find($this->editId);

            $this->closeEditModal();
            $this->dispatch('showSuccess', message: "Mezzo '{$updatedVehicle->targa}' aggiornato con successo!");
            $this->resetPage();
            $this->dispatch('table-refreshed');

        } catch (\Exception $e) {
            Log::error('VehiclesTable: updateVehicle errore', ['error' => $e->getMessage()]);
            $this->dispatch('showError', message: 'Errore durante l\'aggiornamento: ' . $e->getMessage());
        }
    }

    // ==================== ELIMINAZIONE ====================

    public function confirmDelete($id)
    {
        $vehicle = Vehicles::find($id);

        if (!$vehicle) {
            $this->dispatch('showError', message: 'Mezzo non trovato');
            return;
        }

        $this->deleteId = $vehicle->id;
        $this->deleteName = $vehicle->full_name ?? $vehicle->targa;
        $this->showDeleteModal = true;
    }

    public function deleteVehicle()
    {
        try {
            $vehicle = Vehicles::find($this->deleteId);

            if (!$vehicle) {
                $this->dispatch('showError', message: 'Mezzo non trovato');
                return;
            }

            $vehicleName = $vehicle->full_name ?? $vehicle->targa;
            $vehicle->delete();

            $this->dispatch('showSuccess', message: "Mezzo '{$vehicleName}' eliminato con successo!");

            $this->showDeleteModal = false;
            $this->deleteId = null;
            $this->deleteName = '';

            $this->resetPage();
            $this->dispatch('table-refreshed');

        } catch (\Exception $e) {
            Log::error('VehiclesTable: deleteVehicle errore', ['error' => $e->getMessage()]);
            $this->dispatch('showError', message: 'Errore durante l\'eliminazione: ' . $e->getMessage());
            $this->showDeleteModal = false;
        }
    }

    public function cancelDelete()
    {
        $this->showDeleteModal = false;
        $this->deleteId = null;
        $this->deleteName = '';
    }

    // ==================== CAMBIO STATO ====================

    public function toggleStatus($id)
    {
        try {
            $vehicle = Vehicles::find($id);

            if (!$vehicle) {
                $this->dispatch('showError', message: 'Mezzo non trovato');
                return;
            }

            $newStatus = $vehicle->valid == 1 ? 0 : 1;
            $vehicle->update([
                'valid' => $newStatus,
                'updated_by' => Auth::guard('admin')->id(),
                'updated_at' => now()
            ]);

            $statusText = $newStatus == 1 ? 'attivato' : 'disattivato';

            $this->dispatch('showSuccess', message: "Mezzo '{$vehicle->targa}' {$statusText} con successo!");
            $this->resetPage();
            $this->dispatch('table-refreshed');

        } catch (\Exception $e) {
            Log::error('VehiclesTable: toggleStatus errore', ['error' => $e->getMessage()]);
            $this->dispatch('showError', message: 'Errore durante il cambio di stato: ' . $e->getMessage());
        }
    }

    // ==================== SCADENZE ====================

    public function goToExpiration($vehicleId)
    {
        return redirect()->route('admin.expiration-vehicle.index', ['vehicleId' => $vehicleId]);
    }

    // ==================== EXPORT (senza classi/viste esterne) ====================

    public function exportExcel()
    {
        try {
            $vehicles = $this->buildFilteredQuery()->get();
            $filename = 'mezzi_' . now()->format('Y-m-d_His') . '.csv';

            return response()->streamDownload(function () use ($vehicles) {
                // BOM per far riconoscere correttamente gli accenti a Excel
                echo "\xEF\xBB\xBF";

                $output = fopen('php://output', 'w');

                fputcsv($output, ['ID', 'Proprietà', 'Tipo', 'Marca', 'Modello', 'Targa', 'Immatricolazione', 'Stato'], ';');

                foreach ($vehicles as $vehicle) {
                    fputcsv($output, [
                        $vehicle->id,
                        $vehicle->proprieta_nome,
                        $vehicle->tipologia,
                        $vehicle->marca,
                        $vehicle->modello,
                        $vehicle->targa,
                        $vehicle->immatricolazione ? $vehicle->immatricolazione->format('d/m/Y') : '-',
                        $vehicle->valid ? 'Attivo' : 'Disattivo',
                    ], ';');
                }

                fclose($output);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);

        } catch (\Exception $e) {
            Log::error('VehiclesTable: exportExcel errore', ['error' => $e->getMessage()]);
            $this->dispatch('showError', message: 'Errore durante l\'esportazione: ' . $e->getMessage());
        }
    }

    public function exportPdf()
    {
        try {
            $vehicles = $this->buildFilteredQuery()->get();

            // HTML generato direttamente qui, senza vista Blade esterna
            $rows = '';
            foreach ($vehicles as $vehicle) {
                $immatricolazione = $vehicle->immatricolazione ? $vehicle->immatricolazione->format('d/m/Y') : '-';
                $statoLabel = $vehicle->valid ? 'Attivo' : 'Disattivo';
                $statoClass = $vehicle->valid ? 'color:#15803d;font-weight:bold;' : 'color:#b91c1c;';

                $rows .= '<tr>'
                    . '<td>' . e($vehicle->id) . '</td>'
                    . '<td>' . e($vehicle->proprieta_nome) . '</td>'
                    . '<td>' . e($vehicle->tipologia) . '</td>'
                    . '<td>' . e($vehicle->marca ?: '-') . '</td>'
                    . '<td>' . e($vehicle->modello ?: '-') . '</td>'
                    . '<td>' . e($vehicle->targa ?: '-') . '</td>'
                    . '<td>' . e($immatricolazione) . '</td>'
                    . '<td style="' . $statoClass . '">' . e($statoLabel) . '</td>'
                    . '</tr>';
            }

            if ($vehicles->isEmpty()) {
                $rows = '<tr><td colspan="8" style="text-align:center;">Nessun mezzo trovato</td></tr>';
            }

            $generatedAt = now()->format('d/m/Y H:i');
            $total = $vehicles->count();

            $html = <<<HTML
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="utf-8">
                    <style>
                        body { font-family: sans-serif; font-size: 11px; color: #333; }
                        h2 { margin-bottom: 4px; }
                        .subtitle { color: #666; font-size: 10px; margin-bottom: 16px; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #ccc; padding: 5px 7px; text-align: left; }
                        th { background: #f3f4f6; font-weight: bold; }
                        tr:nth-child(even) { background: #fafafa; }
                    </style>
                </head>
                <body>
                    <h2>Elenco Mezzi</h2>
                    <div class="subtitle">Generato il {$generatedAt} — Totale: {$total} mezzi</div>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Proprietà</th>
                                <th>Tipo</th>
                                <th>Marca</th>
                                <th>Modello</th>
                                <th>Targa</th>
                                <th>Immatricolazione</th>
                                <th>Stato</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$rows}
                        </tbody>
                    </table>
                </body>
                </html>
                HTML;

            $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');
            $filename = 'mezzi_' . now()->format('Y-m-d_His') . '.pdf';

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $filename);

        } catch (\Exception $e) {
            Log::error('VehiclesTable: exportPdf errore', ['error' => $e->getMessage()]);
            $this->dispatch('showError', message: 'Errore durante l\'esportazione PDF: ' . $e->getMessage());
        }
    }

    // ==================== RENDER ====================

    public function render()
    {
        return view('livewire.admin.vehicles.vehicles-table', [
            'vehicles' => $this->vehicles,
            'tipiList' => $this->tipiList,
            'statiList' => $this->statiList,
            'proprietaList' => $this->proprietaList,
            'perPageOptions' => $this->perPageOptions,
        ]);
    }
}