<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseDdt extends Model
{
    use SoftDeletes;

    protected $table = 'warehouse_ddt';

    const TYPE_PURCHASE = 'acquisto';
    const TYPE_SALE = 'vendita';

    const STATUS_DRAFT = 'bozza';
    const STATUS_ISSUED = 'emesso';

    const TRASPORTO_MITTENTE = 'mittente';
    const TRASPORTO_DESTINATARIO = 'destinatario';
    const TRASPORTO_VETTORE = 'vettore';

    protected $fillable = [
        'type',
        'ddt_number',
        'ddt_date',
        'id_entities',
        'id_ownership',
        'id_cost_centers',
        'causale',
        'riferimento_fattura',
        'status',
        'issued_at',
        'created_by',
        'updated_by',

        // Destinatario (sovrascrivibile rispetto all'anagrafica Entity)
        'dest_ragione_sociale',
        'dest_indirizzo',
        'dest_cap',
        'dest_citta',
        'dest_provincia',
        'dest_piva',
        'dest_cf',

        // Luogo di destinazione (sovrascrivibile, può differire dal destinatario)
        'luogo_ragione_sociale',
        'luogo_indirizzo',
        'luogo_cap',
        'luogo_citta',
        'luogo_provincia',

        // Dati di trasporto
        'termini_consegna',
        'aspetto_esteriore_beni',
        'numero_colli',
        'trasporto_a_mezzo',
        'inizio_trasporto_at',
        'vettore_nome',
        'vettore_indirizzo',
        'vettore_telefono',
        'vettore_email',
    ];

    protected $casts = [
        'ddt_date' => 'date',
        'issued_at' => 'datetime',
        'inizio_trasporto_at' => 'datetime',
        'numero_colli' => 'integer',
    ];

    public function rows(): HasMany
    {
        return $this->hasMany(WarehouseDdtRow::class, 'id_ddt')->orderBy('id');
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'id_entities', 'id_cliente');
    }

    public function ownership(): BelongsTo
    {
        return $this->belongsTo(Ownership::class, 'id_ownership', 'id_proprieta');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'id_cost_centers');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'updated_by');
    }

    public function isIssued(): bool
    {
        return $this->status === self::STATUS_ISSUED;
    }

    public function isPurchase(): bool
    {
        return $this->type === self::TYPE_PURCHASE;
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->isPurchase() ? 'Acquisto' : 'Vendita';
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->isIssued() ? 'Emesso' : 'Bozza';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return $this->isIssued()
            ? 'bg-green-100 text-green-800'
            : 'bg-yellow-100 text-yellow-800';
    }

    /**
     * Tipo di movimento di magazzino che questo DDT genera quando emesso:
     * un DDT di acquisto fa ENTRARE merce, uno di vendita la fa USCIRE.
     */
    public function movementType(): string
    {
        return $this->isPurchase() ? WarehouseMovement::TYPE_IN : WarehouseMovement::TYPE_OUT;
    }

    /**
     * Valore usato in warehouse_movements.reference_type per collegare
     * ogni movimento generato al DDT che l'ha originato.
     */
    public function referenceType(): string
    {
        return 'ddt_' . $this->type;
    }

    /**
     * Etichetta leggibile per "Trasporto a mezzo"
     */
    public function getTrasportoAMezzoLabelAttribute(): string
    {
        return match ($this->trasporto_a_mezzo) {
            self::TRASPORTO_MITTENTE => 'Mittente',
            self::TRASPORTO_DESTINATARIO => 'Destinatario',
            self::TRASPORTO_VETTORE => 'Vettore',
            default => '-',
        };
    }

    /**
     * Dati del mittente (azienda) da mostrare nel PDF: presi dalla
     * Proprietà (Ownership) collegata al DDT — stessa fonte usata dal
     * template delle fatture di vendita (Rag_Soc_intest, IndirizzoPr,
     * LocalitPr, ProvinciaPr, PivaPr).
     */
    public function getMittenteDatiAttribute(): array
    {
        $ownership = $this->ownership;

        return [
            'ragione_sociale' => $ownership->Rag_Soc_intest ?? '-',
            'indirizzo' => $ownership->IndirizzoPr ?? '',
            'citta' => $ownership->LocalitPr ?? '',
            'provincia' => $ownership->ProvinciaPr ?? '',
            'piva' => $ownership->PivaPr ?? '',
            'cf' => $ownership->CFPr ?? $ownership->PivaPr ?? '',
            'email' => $ownership->EmailPr ?? '',
        ];
    }

    /**
     * Nome/indirizzo del destinatario da mostrare nel PDF: usa i campi
     * propri del DDT se compilati (permette override), altrimenti
     * ricade sull'anagrafica Entity + il suo indirizzo principale (address).
     */
    public function getDestinatarioDatiAttribute(): array
    {
        if ($this->dest_ragione_sociale) {
            return [
                'ragione_sociale' => $this->dest_ragione_sociale,
                'indirizzo' => $this->dest_indirizzo,
                'cap' => $this->dest_cap,
                'citta' => $this->dest_citta,
                'provincia' => $this->dest_provincia,
                'piva' => $this->dest_piva,
                'cf' => $this->dest_cf,
            ];
        }

        $entity = $this->entity;
        $address = $entity ? $entity->primary_address : null;

        return [
            'ragione_sociale' => $entity ? ($entity->ragione_sociale ?? trim(($entity->nome ?? '') . ' ' . ($entity->cognome ?? ''))) : '',
            'indirizzo' => $address->indirizzo ?? null,
            'cap' => $address->cap ?? null,
            'citta' => $address->citta ?? null,
            'provincia' => $address->provincia ?? null,
            'piva' => $entity->partita_iva ?? null,
            'cf' => $entity->codice_fiscale ?? null,
        ];
    }

    /**
     * Luogo di destinazione: usa i campi propri se compilati, altrimenti
     * ricade sui dati del destinatario (comportamento visto nell'esempio,
     * dove i due blocchi coincidono di default).
     */
    public function getLuogoDestinazioneDatiAttribute(): array
    {
        if ($this->luogo_ragione_sociale) {
            return [
                'ragione_sociale' => $this->luogo_ragione_sociale,
                'indirizzo' => $this->luogo_indirizzo,
                'cap' => $this->luogo_cap,
                'citta' => $this->luogo_citta,
                'provincia' => $this->luogo_provincia,
            ];
        }

        $dest = $this->destinatario_dati;
        return [
            'ragione_sociale' => $dest['ragione_sociale'],
            'indirizzo' => $dest['indirizzo'],
            'cap' => $dest['cap'],
            'citta' => $dest['citta'],
            'provincia' => $dest['provincia'],
        ];
    }
}