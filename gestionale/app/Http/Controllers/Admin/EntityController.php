<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Entity;
use App\Models\Address;
use App\Models\Permission;
use App\Models\Contact;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EntityController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_entities')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $query = Entity::query();
        
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('ragione_sociale', 'like', "%{$request->search}%")
                ->orWhere('nome', 'like', "%{$request->search}%")
                ->orWhere('cognome', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")
                ->orWhere('partita_iva', 'like', "%{$request->search}%");
            });
        }
        
        if ($request->filled('type')) {
            $query->where('entity_type', $request->type);
        }
        
        if ($request->filled('status')) {
            $query->where('valid', $request->status === 'active');
        }
        
        $entities = $query->latest('data_inserimento')->paginate(15);
        $entityTypes = Entity::getEntityTypes();
        
        // Calcola il numero di elementi nel cestino
        $trashCount = Entity::onlyTrashed()->count();
        
        return view('admin.entities.index', compact('entities', 'entityTypes', 'trashCount'));
    }

    public function create()
    {
        $entityTypes = Entity::getEntityTypes();
        $contactTypes = Setting::where('tabella_riferimento', 'contacts')
            ->where('valid', true)
            ->orderBy('ordinamento')
            ->get();
        
        return view('admin.entities.create', compact('entityTypes', 'contactTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'entity_type' => 'required|in:cliente,fornitore,entrambi',
            'ragione_sociale' => 'nullable|string|max:255',
            'nome' => 'nullable|string|max:255',
            'cognome' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'pec' => 'nullable|email|max:255',
            'partita_iva' => 'nullable|string|max:20',
            'codice_fiscale' => 'nullable|string|max:20',
        ]);

        // Controllo duplicati partita IVA
        if (!empty($request->partita_iva)) {
            $existingEntity = Entity::where('partita_iva', $request->partita_iva)->first();
            if ($existingEntity) {
                return back()->with('error', "Partita IVA {$request->partita_iva} già presente in archivio per: " . $existingEntity->full_name);
            }
        }
        
        // Controllo duplicati codice fiscale
        if (!empty($request->codice_fiscale)) {
            $existingByCF = Entity::where('codice_fiscale', $request->codice_fiscale)->first();
            if ($existingByCF) {
                return back()->with('error', "Codice Fiscale {$request->codice_fiscale} già presente in archivio per: " . $existingByCF->full_name);
            }
        }

        $adminId = Auth::guard('admin')->id();

        $entity = Entity::create([
            'entity_type' => $request->entity_type,
            'ragione_sociale' => $request->ragione_sociale,
            'nome' => $request->nome,
            'cognome' => $request->cognome,
            'persona_riferimento' => $request->persona_riferimento,
            'email' => $request->email,
            'pec' => $request->pec,
            'partita_iva' => $request->partita_iva,
            'codice_fiscale' => $request->codice_fiscale,
            'id_gruppo' => $request->id_gruppo ?? 0,
            'valid' => $request->boolean('valid'),
            'data_inserimento' => now(),
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => $adminId,
            'updated_by' => $adminId
        ]);

        // Salva i contatti
        if ($request->has('contacts')) {
            foreach ($request->contacts as $settingId => $valore) {
                if (!empty($valore)) {
                    Contact::create([
                        'id_entities' => $entity->id_cliente,
                        'id_settings' => $settingId,
                        'valore' => $valore,
                        'principale' => isset($request->principal_contact) && $request->principal_contact == $settingId,
                    ]);
                }
            }
        }

        return redirect()->route('admin.entities.index')->with('success', 'Entità creata con successo!');
    }

    public function show(Entity $entity)
    {
        $entity->load(['contacts.setting', 'createdBy', 'updatedBy']);
        
        $contacts = $entity->contacts()->with('setting')->get();
        $contactsByType = $contacts->groupBy(function($contact) {
            return $contact->setting->valore;
        });
        
        return view('admin.entities.show', compact('entity', 'contactsByType'));
    }

    public function edit(Entity $entity)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_entities')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        // Carica l'entità con i contatti e gli indirizzi - UNA SOLA VOLTA
        $entity->load([
            'contacts' => function($q) {
                $q->with('setting');
            },
            'addresses'
        ]);
        
        $entityTypes = Entity::getEntityTypes();
        $contactTypes = Setting::where('tabella_riferimento', 'contacts')
            ->where('valid', true)
            ->orderBy('ordinamento')
            ->get();
        
        return view('admin.entities.edit', compact('entity', 'entityTypes', 'contactTypes'));
    }

    public function update(Request $request, Entity $entity)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_entities')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $request->validate([
            'entity_type' => 'required|in:cliente,fornitore,entrambi',
            'ragione_sociale' => 'nullable|string|max:255',
            'nome' => 'nullable|string|max:255',
            'cognome' => 'nullable|string|max:255',
            'persona_riferimento' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'pec' => 'nullable|email|max:255',
            'partita_iva' => 'nullable|string|max:20',
            'codice_fiscale' => 'nullable|string|max:20',
            'codice_sdi' => 'nullable|string|max:7',
            'valid' => 'boolean',
        ]);

        // Controllo duplicati partita IVA (escludendo se stesso)
        if (!empty($request->partita_iva) && $request->partita_iva != $entity->partita_iva) {
            $existingEntity = Entity::where('partita_iva', $request->partita_iva)->first();
            if ($existingEntity) {
                return back()->with('error', "Partita IVA {$request->partita_iva} già presente in archivio per: " . $existingEntity->full_name);
            }
        }
        
        // Controllo duplicati codice fiscale (escludendo se stesso)
        if (!empty($request->codice_fiscale) && $request->codice_fiscale != $entity->codice_fiscale) {
            $existingByCF = Entity::where('codice_fiscale', $request->codice_fiscale)->first();
            if ($existingByCF) {
                return back()->with('error', "Codice Fiscale {$request->codice_fiscale} già presente in archivio per: " . $existingByCF->full_name);
            }
        }

        $entity->update([
            'entity_type' => $request->entity_type,
            'ragione_sociale' => $request->ragione_sociale,
            'nome' => $request->nome,
            'cognome' => $request->cognome,
            'persona_riferimento' => $request->persona_riferimento,
            'email' => $request->email,
            'pec' => $request->pec,
            'partita_iva' => $request->partita_iva,
            'codice_fiscale' => $request->codice_fiscale,
            'codice_sdi' => $request->codice_sdi,
            'id_gruppo' => $request->id_gruppo ?? 0,
            'valid' => $request->boolean('valid'),
            'updated_by' => Auth::guard('admin')->id(),
            'updated_at' => now()
        ]);

        return redirect()->route('admin.entities.index')
            ->with('success', 'Entità aggiornata con successo!');
    }

    /**
     * API search for clients only (not suppliers)
     */
    public function searchClients(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }
        
        $results = Entity::where('valid', 1)
            ->where(function($q) use ($query) {
                $q->where('ragione_sociale', 'like', '%' . $query . '%')
                ->orWhere('nome', 'like', '%' . $query . '%')
                ->orWhere('cognome', 'like', '%' . $query . '%')
                ->orWhere('partita_iva', 'like', '%' . $query . '%')
                ->orWhere('codice_fiscale', 'like', '%' . $query . '%');
            })
            ->whereIn('entity_type', ['cliente', 'entrambi'])  // Solo clienti
            ->orderBy('ragione_sociale')
            ->orderBy('nome')
            ->limit(10)
            ->get(['id_cliente', 'ragione_sociale', 'nome', 'cognome', 'partita_iva', 'codice_fiscale', 'entity_type']);
        
        return response()->json($results);
    }

    /**
     * API per la ricerca di entità (clienti/fornitori) via AJAX
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }
        
        $entities = Entity::where(function($q) use ($query) {
                $q->where('ragione_sociale', 'like', '%' . $query . '%')
                  ->orWhere('nome', 'like', '%' . $query . '%')
                  ->orWhere('cognome', 'like', '%' . $query . '%')
                  ->orWhere('partita_iva', 'like', '%' . $query . '%')
                  ->orWhere('codice_fiscale', 'like', '%' . $query . '%');
            })
            ->where('valid', 1)
            ->orderBy('ragione_sociale')
            ->limit(10)
            ->get()
            ->map(function($entity) {
                return [
                    'id_cliente' => $entity->id_cliente,
                    'ragione_sociale' => $entity->ragione_sociale,
                    'nome' => $entity->nome,
                    'cognome' => $entity->cognome,
                    'full_name' => $entity->full_name,
                    'partita_iva' => $entity->partita_iva,
                    'codice_fiscale' => $entity->codice_fiscale,
                    'entity_type' => $entity->entity_type,
                ];
            });
        
        return response()->json($entities);
    }
    
    public function destroy(Entity $entity)
    {
        // Verifica se può essere eliminato
        if (!$entity->canBeDeleted()) {
            return back()->with('error', "Impossibile eliminare '{$entity->full_name}' perché ha delle relazioni attive nel sistema.");
        }
        
        $entity->delete();
        return redirect()->route('admin.entities.index')->with('success', 'Entità eliminata con successo!');
    }

    public function toggleStatus(Entity $entity)
    {
        $entity->update([
            'valid' => !$entity->valid,
            'updated_by' => Auth::guard('admin')->id(),
            'updated_at' => now()
        ]);
        $status = $entity->valid ? 'attivata' : 'disattivata';
        return back()->with('success', "Entità {$status} con successo!");
    }
    
    // API per la gestione degli indirizzi
    public function getAddresses($entityId)
    {
        $addresses = Address::where('clienti_id_cliente', $entityId)->get();
        return response()->json($addresses);
    }

    public function storeAddress(Request $request, $entityId)
    {
        $request->validate([
            'sede' => 'nullable|string|max:50',
            'indirizzo' => 'nullable|string|max:255',
            'citta' => 'nullable|string|max:50',
            'provincia' => 'nullable|string|max:5',
            'nazione' => 'nullable|string|max:50',
            'cap' => 'nullable|string|max:10',
            'telefono' => 'nullable|string|max:255',
            'cellulare' => 'nullable|string|max:255',
            'fax' => 'nullable|string|max:255',
        ]);

        // Fix: Gestione valori null per i campi opzionali
        $address = Address::create([
            'clienti_id_cliente' => $entityId,
            'sede' => $request->sede ?: 'principale',
            'indirizzo' => $request->indirizzo ?? null,
            'citta' => $request->citta ?? null,
            'provincia' => $request->provincia ?? null,
            'nazione' => $request->nazione ?: 'Italia',
            'cap' => $request->cap ?? null,
            'telefono' => $request->telefono ?? null,
            'cellulare' => $request->cellulare ?? null,
            'fax' => $request->fax ?? null,
        ]);

        return response()->json(['success' => true, 'address' => $address]);
    }

    public function updateAddress(Request $request, $entityId, $addressId)
    {
        $request->validate([
            'sede' => 'nullable|string|max:50',
            'indirizzo' => 'nullable|string|max:255',
            'citta' => 'nullable|string|max:50',
            'provincia' => 'nullable|string|max:5',
            'nazione' => 'nullable|string|max:50',
            'cap' => 'nullable|string|max:10',
            'telefono' => 'nullable|string|max:255',
            'cellulare' => 'nullable|string|max:255',
            'fax' => 'nullable|string|max:255',
        ]);

        $address = Address::where('clienti_id_cliente', $entityId)
            ->where('id_indirizzo', $addressId)
            ->firstOrFail();

        $address->update([
            'sede' => $request->sede ?: 'principale',
            'indirizzo' => $request->indirizzo ?? null,
            'citta' => $request->citta ?? null,
            'provincia' => $request->provincia ?? null,
            'nazione' => $request->nazione ?: 'Italia',
            'cap' => $request->cap ?? null,
            'telefono' => $request->telefono ?? null,
            'cellulare' => $request->cellulare ?? null,
            'fax' => $request->fax ?? null,
        ]);

        return response()->json(['success' => true, 'address' => $address]);
    }

    public function deleteAddress($entityId, $addressId)
    {
        $address = Address::where('clienti_id_cliente', $entityId)
            ->where('id_indirizzo', $addressId)
            ->firstOrFail();

        $address->delete();

        return response()->json(['success' => true]);
    }
}