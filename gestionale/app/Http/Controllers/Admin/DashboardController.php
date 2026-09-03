<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Administrator;
use App\Models\Role;
use App\Models\Entity;
use App\Models\AccountingEntry;
use App\Models\Communication;
use App\Models\InvoiceSent;
use App\Models\InvoiceReceived;
use App\Models\Ownership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    // Porzioni di nome usate per identificare le due Proprietà nei grafici.
    // Se l'anagrafica cambia ragione sociale, aggiorna solo qui.
    private const OWNERSHIP_NAMES = [
        'agricola' => 'Agricola Salatino',
        'vitivinicola' => 'Vitivinicola Salatino',
    ];

    public function index()
    {
        $admin = Auth::guard('admin')->user();

        $stats = [
            'total_admins' => Administrator::count(),
            'active_admins' => Administrator::where('is_active', true)->count(),
            'total_entities' => Entity::count(),
            'total_roles' => Role::count(),
        ];

        $recentAdmins = Administrator::with('role')->latest()->take(5)->get();
        $recentEntities = Entity::latest('created_at')->take(5)->get();
        $entityTypes = Entity::getEntityTypes();

        // ==================== ULTIMI PAGAMENTI (INCASSI) ====================
        $recentPayments = AccountingEntry::where('type', 'entrata')
            ->with(['bankAccount', 'paymentMethod'])
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        // ==================== ULTIME COMUNICAZIONI ====================
        $recentCommunications = Communication::with('entity')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        // ==================== PROPRIETÀ (per i grafici) ====================
        $agricolaOwnership = $this->findOwnership(self::OWNERSHIP_NAMES['agricola']);
        $vitivinicolaOwnership = $this->findOwnership(self::OWNERSHIP_NAMES['vitivinicola']);

        $year = now()->year;

        $agricolaSalesMonthly = $agricolaOwnership
            ? $this->monthlySalesTotals($agricolaOwnership->id_proprieta, $year)
            : array_fill(0, 12, 0);

        $agricolaPurchasesMonthly = $agricolaOwnership
            ? $this->monthlyPurchaseTotals($agricolaOwnership->id_proprieta, $year)
            : array_fill(0, 12, 0);

        $vitivinicolaSalesMonthly = $vitivinicolaOwnership
            ? $this->monthlySalesTotals($vitivinicolaOwnership->id_proprieta, $year)
            : array_fill(0, 12, 0);

        $vitivinicolaPurchasesMonthly = $vitivinicolaOwnership
            ? $this->monthlyPurchaseTotals($vitivinicolaOwnership->id_proprieta, $year)
            : array_fill(0, 12, 0);

        return view('admin.dashboard.index', compact(
            'admin', 'stats', 'recentAdmins', 'recentEntities', 'entityTypes',
            'recentPayments', 'recentCommunications',
            'agricolaOwnership', 'vitivinicolaOwnership',
            'agricolaSalesMonthly', 'agricolaPurchasesMonthly',
            'vitivinicolaSalesMonthly', 'vitivinicolaPurchasesMonthly',
            'year'
        ));
    }

    /**
     * Cerca una Proprietà per nome parziale (RagAbbrev o Rag_Soc_intest).
     * Ritorna null se non trovata — i grafici corrispondenti mostreranno
     * tutti zeri invece di generare un errore.
     */
    private function findOwnership(string $namePart): ?Ownership
    {
        return Ownership::where('RagAbbrev', 'like', '%' . $namePart . '%')
            ->orWhere('Rag_Soc_intest', 'like', '%' . $namePart . '%')
            ->first();
    }

    /**
     * Totali mensili (12 valori, indice 0 = Gennaio) del fatturato di
     * vendita per una Proprietà nell'anno indicato. Le note di credito
     * (TD04/TD08, o importo negativo) vengono sottratte, non sommate —
     * stessa logica già usata altrove nel gestionale (es. estratto conto)
     * per non gonfiare il fatturato mostrato.
     */
    private function monthlySalesTotals(int $ownershipId, int $year): array
    {
        $invoices = InvoiceSent::where('id_ownership', $ownershipId)
            ->whereYear('data_invoice', $year)
            ->get(['data_invoice', 'importo_totale', 'type_invoice']);

        $totals = array_fill(1, 12, 0.0);

        foreach ($invoices as $invoice) {
            $month = (int) $invoice->data_invoice->format('n');
            $isCreditNote = in_array($invoice->type_invoice, ['TD04', 'TD08']) || $invoice->importo_totale < 0;
            $amount = abs((float) $invoice->importo_totale);
            $totals[$month] += $isCreditNote ? -$amount : $amount;
        }

        return array_values($totals);
    }

    /**
     * Totali mensili (12 valori) delle fatture di acquisto per una
     * Proprietà nell'anno indicato.
     */
    private function monthlyPurchaseTotals(int $ownershipId, int $year): array
    {
        $invoices = InvoiceReceived::where('id_ownership', $ownershipId)
            ->whereYear('data_invoice', $year)
            ->get(['data_invoice', 'importo_totale', 'type_invoice']);

        $totals = array_fill(1, 12, 0.0);

        foreach ($invoices as $invoice) {
            $month = (int) $invoice->data_invoice->format('n');
            $isCreditNote = in_array($invoice->type_invoice, ['TD04', 'TD08']) || $invoice->importo_totale < 0;
            $amount = abs((float) $invoice->importo_totale);
            $totals[$month] += $isCreditNote ? -$amount : $amount;
        }

        return array_values($totals);
    }
}