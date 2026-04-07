<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Entity;
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
        
        return view('admin.entities.index', compact('entities', 'entityTypes'));
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
        ]);

        // Salva i contatti
        if ($request->has('contacts')) {
            foreach ($request->contacts as $settingId => $valore) {
                if (!empty($valore)) {
                    Contact::create([
                        'id_entities' => $entity->id,
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
        $contacts = $entity->contacts()->with('setting')->get();
        $contactsByType = $contacts->groupBy(function($contact) {
            return $contact->setting->valore;
        });
        
        return view('admin.entities.show', compact('entity', 'contactsByType'));
    }

    public function edit(Entity $entity)
    {
        $entityTypes = Entity::getEntityTypes();
        $contactTypes = Setting::where('tabella_riferimento', 'contacts')
            ->where('valid', true)
            ->orderBy('ordinamento')
            ->get();
        
        $existingContacts = $entity->contacts()
            ->with('setting')
            ->get()
            ->keyBy('id_settings');
        
        return view('admin.entities.edit', compact('entity', 'entityTypes', 'contactTypes', 'existingContacts'));
    }

    public function update(Request $request, Entity $entity)
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
            'id_gruppo' => $request->id_gruppo ?? 0,
            'valid' => $request->boolean('valid'),
        ]);

        // Aggiorna i contatti
        if ($request->has('contacts')) {
            foreach ($request->contacts as $settingId => $valore) {
                if (!empty($valore)) {
                    Contact::updateOrCreate(
                        [
                            'id_entities' => $entity->id,
                            'id_settings' => $settingId,
                        ],
                        [
                            'valore' => $valore,
                            'principale' => isset($request->principal_contact) && $request->principal_contact == $settingId,
                        ]
                    );
                } else {
                    // Elimina contatti vuoti
                    Contact::where('id_entities', $entity->id)
                        ->where('id_settings', $settingId)
                        ->delete();
                }
            }
        }

        return redirect()->route('admin.entities.index')->with('success', 'Entità aggiornata con successo!');
    }

    public function destroy(Entity $entity)
    {
        $entity->delete();
        return redirect()->route('admin.entities.index')->with('success', 'Entità eliminata con successo!');
    }

    public function toggleStatus(Entity $entity)
    {
        $entity->update(['valid' => !$entity->valid]);
        $status = $entity->valid ? 'attivata' : 'disattivata';
        return back()->with('success', "Entità {$status} con successo!");
    }
}