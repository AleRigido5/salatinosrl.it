<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Fattura {{ $invoice->n_invoice }}</title>
    <style>
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 300;
            src: url("{{ public_path('fonts/poppins/Poppins-Light.ttf') }}") format('truetype');
        }
        @font-face {
            font-family: 'Poppins';
            font-style: italic;
            font-weight: 300;
            src: url("{{ public_path('fonts/poppins/Poppins-LightItalic.ttf') }}") format('truetype');
        }
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 400;
            src: url("{{ public_path('fonts/poppins/Poppins-Regular.ttf') }}") format('truetype');
        }
        @font-face {
            font-family: 'Poppins';
            font-style: italic;
            font-weight: 400;
            src: url("{{ public_path('fonts/poppins/Poppins-Italic.ttf') }}") format('truetype');
        }
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 500;
            src: url("{{ public_path('fonts/poppins/Poppins-Medium.ttf') }}") format('truetype');
        }
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 600;
            src: url("{{ public_path('fonts/poppins/Poppins-SemiBold.ttf') }}") format('truetype');
        }
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 700;
            src: url("{{ public_path('fonts/poppins/Poppins-Bold.ttf') }}") format('truetype');
        }
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 900;
            src: url("{{ public_path('fonts/poppins/Poppins-Black.ttf') }}") format('truetype');
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            color: #1c1c1c;
            background: white;
            padding: 20px 24px;
        }

        /* ===== HEADER (stile DDT) ===== */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 14px;
        }
        .header-left {
            display: table-cell;
            width: 35%;
            vertical-align: top;
        }
        .header-left img {
            max-height: 85px;
        }
        .header-right {
            display: table-cell;
            width: 65%;
            text-align: right;
            vertical-align: top;
        }
        .co-name {
            font-size: 15pt;
            font-weight: bold;
            color: #1a1a1a;
        }
        .co-detail {
            font-size: 8.5pt;
            color: #444;
            line-height: 1.5;
            margin-top: 2px;
        }

        /* ===== TITLE BAR (stile DDT) ===== */
        .title-bar {
            display: table;
            width: 100%;
            background-color: #dbe6f0;
            padding: 9px 14px;
            margin-bottom: 14px;
        }
        .title-bar-left {
            display: table-cell;
            font-size: 12pt;
            font-weight: bold;
            color: #1a1a1a;
            vertical-align: middle;
        }
        .title-bar-right {
            display: table-cell;
            text-align: right;
            font-size: 9pt;
            color: #333;
            vertical-align: middle;
        }
        .title-bar-right strong { font-weight: bold; }

        /* ===== DESTINATARIO / LUOGO (stile DDT) ===== */
        .box-row {
            display: table;
            width: 100%;
            margin-bottom: 12px;
            border-bottom: 0.75pt solid #ddd;
            padding-bottom: 10px;
        }
        .box {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 20px;
        }
        .box-label {
            font-size: 7.5pt;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 2px;
        }
        .box-title {
            font-weight: bold;
            font-size: 9.5pt;
            color: #1a1a1a;
            margin-bottom: 2px;
        }
        .box-line {
            font-size: 8.5pt;
            color: #333;
            line-height: 1.55;
        }

        /* ===== TABELLA RIGHE (stile DDT) ===== */
        table.rows {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.rows thead th {
            background-color: #f2f2f2;
            text-align: left;
            padding: 6px 8px;
            font-size: 7.5pt;
            font-weight: bold;
            color: #555;
            text-transform: uppercase;
            border-bottom: 0.75pt solid #ddd;
        }
        table.rows tbody td {
            padding: 7px 8px;
            font-size: 8.5pt;
            color: #333;
            border-bottom: 0.5pt solid #f0f0f0;
        }
        .col-qty { text-align: right; }
        .col-price { text-align: right; }
        .col-total { text-align: right; font-weight: 500; }

        /* ===== TOTALE (stile DDT ma più evidenziato) ===== */
        .total-box {
            margin-top: 15px;
            margin-bottom: 20px;
        }
        .total-table {
            width: 100%;
            border-collapse: collapse;
        }
        .total-table td {
            padding: 5px 10px;
            font-size: 8.5pt;
            border-bottom: 0.5pt solid #eee;
        }
        .total-label {
            text-align: right;
            font-weight: 600;
            width: 70%;
            background-color: #f9fafb;
            padding-right: 20px;
        }
        .total-value {
            text-align: right;
            font-weight: 600;
            width: 30%;
        }
        .grand-total td {
            font-size: 10pt;
            font-weight: 700;
            border-top: 1.5pt solid #2d5a1e;
            border-bottom: 1.5pt solid #2d5a1e;
            background-color: #f0f7ea;
            padding: 8px 10px;
        }
        .grand-total .total-label {
            color: #1a4a0e;
            font-size: 10pt;
        }
        .grand-total .total-value {
            color: #1a4a0e;
            font-size: 11pt;
        }

        /* ===== PAGAMENTO / BANCA (stile DDT) ===== */
        .footer-info {
            display: table;
            width: 100%;
            margin-top: 10px;
            border-top: 1pt solid #ddd;
            padding-top: 10px;
        }
        .footer-info-row {
            display: table-row;
        }
        .footer-info-cell {
            display: table-cell;
            width: 50%;
            padding: 5px 10px 5px 0;
            border-bottom: 0.5pt solid #eee;
            vertical-align: top;
        }
        .footer-info-label {
            font-size: 7.5pt;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 2px;
        }
        .footer-info-value {
            font-size: 8.5pt;
            color: #222;
        }
        .footer-info-cell-full {
            display: table-cell;
            width: 100%;
            padding: 5px 0;
            border-bottom: 0.5pt solid #eee;
        }

        /* IBAN monospace */
        .iban-mono {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 7.5pt;
            font-weight: 700;
        }

        /* ===== PRIVACY / NOTE LEGALI (stile DDT compatto) ===== */
        .privacy {
            font-size: 6.2pt;
            font-weight: 400;
            color: #555;
            line-height: 1.4;
            margin-top: 12px;
            border: 0.8pt solid #e0e4e0;
            padding: 5px 8px;
            background-color: #fafbf9;
        }
        .privacy-title {
            font-weight: 700;
            font-size: 6.5pt;
            margin-bottom: 2px;
            color: #333;
        }

        /* ===== FIRME (stile DDT) ===== */
        .signatures {
            display: table;
            width: 100%;
            margin-top: 55px;
            border-top: 0.75pt solid #ccc;
            padding-top: 5px;
        }
        .signature-cell {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            font-size: 7.5pt;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* ===== FOOTER PAGINA (stile DDT) ===== */
        .page-footer {
            display: table;
            width: 100%;
            margin-top: 14px;
            padding-top: 6px;
            border-top: 0.5pt solid #eee;
        }
        .page-footer-left {
            display: table-cell;
            font-size: 7.5pt;
            color: #999;
        }
        .page-footer-right {
            display: table-cell;
            text-align: right;
            font-size: 7.5pt;
            color: #999;
        }
        .page-footer-left strong { color: #666; }

        /* ===== UTILITY ===== */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: 'DejaVu Sans Mono', monospace; }
    </style>
</head>
<body>

@php
    $ownership = $invoice->ownership;

    $companyData = [
        'name' => $ownership->Rag_Soc_intest ?? 'Azienda',
        'address' => $ownership->IndirizzoPr && $ownership->LocalitPr 
            ? $ownership->IndirizzoPr . ' - ' . $ownership->LocalitPr . ' (' . ($ownership->ProvinciaPr ?? 'BA') . ')'
            : $ownership->IndirizzoPr,
        'vat' => $ownership->PivaPr,
        'email' => $ownership->EmailPr,
        'website' => $ownership->WebPr,
        'iban' => $ownership->IbanPr,
        'bank' => $ownership->BancaPr,
        'owner_name' => $ownership->NomeRapprPr,
        'province' => $ownership->ProvinciaPr ?? 'Bari',
    ];

    $companyData['registration_text'] = 'P.IVA: ' . '<strong>' . $companyData['vat'] . '</strong>';

    // ============================================================
    // FIX: risoluzione affidabile dell'aliquota IVA per riga.
    // ============================================================
    $vatRatesById = $vatRates ?? collect();

    $resolveVatRatePercent = function ($row) use ($vatRatesById) {
        if (!empty($row->vat_rate_id) && $vatRatesById->has($row->vat_rate_id)) {
            return (float) $vatRatesById[$row->vat_rate_id]->rate * 100;
        }

        $raw = (float) ($row->vat_rate ?? 0);

        if ($raw > 0 && $raw <= 1) {
            return $raw * 100;
        }

        if ($raw > 1) {
            return $raw;
        }

        return 0.0;
    };

    $formatVatPercent = function ($percent) {
        return rtrim(rtrim(number_format($percent, 2, ',', '.'), '0'), ',');
    };

    // ============================================================
    // Calcolo totali IVA
    // ============================================================
    $vatTotals = [];
    foreach($invoice->rows as $row) {
        $rate = $resolveVatRatePercent($row);
        $vatTotals[$rate] = ($vatTotals[$rate] ?? 0) + $row->total;
    }
    ksort($vatTotals);

    $imponibileTotale = array_sum($vatTotals);
    $ivaTotale = 0;
    foreach($vatTotals as $rate => $imponibile) {
        $ivaTotale += $imponibile * $rate / 100;
    }

    // ============================================================
    // Risoluzione IBAN con supporto al campo emittente
    // ============================================================
    $firstPayment = $invoice->payments->first();
    $resolvedBankAccount = $bankAccount ?? null;

    if ($firstPayment && !empty($firstPayment->iban)) {
        $bankIban = $firstPayment->iban;
        $bankName = $firstPayment->bank_name ?? null;
        $bankHolder = $firstPayment->bank_account_holder ?? null;
    } elseif (!empty($companyData['iban'])) {
        $bankIban = $companyData['iban'];
        $bankName = $companyData['bank'] ?? null;
        $bankHolder = $companyData['name'] ?? null;
    } elseif (!empty($resolvedBankAccount->iban ?? null)) {
        $bankIban = $resolvedBankAccount->iban;
        // PRIORITÀ AL CAMPO EMITTENTE, POI NAME COME FALLBACK
        $bankName = $resolvedBankAccount->emittente ?? $resolvedBankAccount->name ?? null;
        $bankHolder = $companyData['name'] ?? null;
    } else {
        $bankIban = null;
        $bankName = null;
        $bankHolder = null;
    }

    // Determina il tipo di documento
    $docTypeLabel = $invoice->type_invoice_label ?? $invoice->type_invoice ?? 'FATTURA';
    $isCreditNote = in_array($invoice->type_invoice, ['TD04', 'TD08']);

    // Logo path
    $logoPath = public_path('images/logo-pdf-ddt.png');
@endphp

<!-- HEADER -->
<div class="header">
    <div class="header-left">
        @if(file_exists($logoPath))
            <img src="{{ $logoPath }}" alt="Logo">
        @endif
    </div>
    <div class="header-right">
        <div class="co-name">{{ $companyData['name'] }}</div>
        <div class="co-detail">
            {{ $companyData['address'] }}<br>
            {!! $companyData['registration_text'] !!}
        </div>
    </div>
</div>

<!-- TITLE BAR -->
<div class="title-bar">
    <div class="title-bar-left">{{ strtoupper($docTypeLabel) }}</div>
    <div class="title-bar-right">
        nr. <strong>{{ $invoice->n_invoice }}</strong> 
        del <strong>{{ $invoice->data_invoice->format('d.m.y') }}</strong>
        @if($isCreditNote)
            <span style="color: #c0392b; margin-left: 10px; font-weight: bold;">(NOTA DI CREDITO)</span>
        @endif
    </div>
</div>

<!-- DESTINATARIO -->
<div class="box-row">
    <div class="box">
        <div class="box-label">Cliente / Destinatario</div>
        @php 
            $entity = $invoice->entity;
            $addr = $entity ? $entity->primary_address : null;
        @endphp
        @if($entity)
            <div class="box-title">{{ $entity->full_name }}</div>
            <div class="box-line">
                @if($addr)
                    @if(!empty($addr->indirizzo)){{ $addr->indirizzo }}<br>@endif
                    @if(!empty($addr->cap) || !empty($addr->citta))
                        {{ $addr->cap ?? '' }}{{ !empty($addr->cap) && !empty($addr->citta) ? ' ' : '' }}{{ !empty($addr->citta) ? strtoupper($addr->citta) : '' }}@if(!empty($addr->provincia)) ({{ strtoupper($addr->provincia) }})@endif<br>
                    @endif
                @endif
                @if(!empty($entity->partita_iva))P.IVA: {{ $entity->partita_iva }}<br>@endif
                @if(!empty($entity->codice_fiscale))C.F.: {{ $entity->codice_fiscale }}@endif
            </div>
        @else
            <div class="box-title">{{ $invoice->customer_name }}</div>
        @endif
    </div>
    <div class="box">
        <div class="box-label">Dati Fattura</div>
        <div class="box-line">
            <strong>Numero:</strong> {{ $invoice->n_invoice }}<br>
            <strong>Data:</strong> {{ $invoice->data_invoice->format('d/m/Y') }}<br>
            @if($invoice->causale)
                <strong>Causale:</strong> {{ $invoice->causale }}<br>
            @endif
            @if($invoice->sdi_id)
                <strong>SDI ID:</strong> <span class="font-mono" style="font-size:7.5pt;">{{ $invoice->sdi_id }}</span>
            @endif
        </div>
    </div>
</div>

<!-- TABELLA RIGHE -->
<table class="rows">
    <thead>
        <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 45%;">Descrizione</th>
            <th style="width: 8%;" class="col-qty">Q.tà</th>
            <th style="width: 10%;" class="col-price">Prezzo</th>
            <th style="width: 12%;" class="col-price">Sconto</th>
            <th style="width: 10%;" class="col-price">IVA</th>
            <th style="width: 10%;" class="col-total">Totale</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->rows as $index => $row)
        <tr>
            <td class="text-center">{{ $index + 1 }}</td>
            <td>{{ $row->description }}</td>
            <td class="col-qty">{{ number_format($row->quantity, 2, ',', '.') }}</td>
            <td class="col-price">{{ number_format($row->unit_price, 3, ',', '.') }}</td>
            <td class="col-price">{{ $row->discount_percentage > 0 ? number_format($row->discount_percentage, 2, ',', '.') . '%' : '-' }}</td>
            <td class="text-center">{{ $formatVatPercent($resolveVatRatePercent($row)) }}%</td>
            <td class="col-total">{{ number_format($row->total, 2, ',', '.') }}</td>
        </tr>
        @endforeach
        
        <!-- Righe vuote per riempimento (minimo 12 righe) -->
        @php $fill = max(0, 12 - count($invoice->rows)); @endphp
        @for($i = 0; $i < $fill; $i++)
        <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
        @endfor
    </tbody>
</table>

<!-- TOTALI -->
<div class="total-box">
    <table class="total-table">
        @foreach($vatTotals as $rate => $imponibile)
        <tr>
            <td class="total-label">IMPONIBILE AL {{ $formatVatPercent($rate) }}%</td>
            <td class="total-value">€ {{ number_format($imponibile, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="total-label">IVA AL {{ $formatVatPercent($rate) }}%</td>
            <td class="total-value">€ {{ number_format($imponibile * $rate / 100, 2, ',', '.') }}</td>
        </tr>
        @endforeach
        <tr class="grand-total">
            <td class="total-label">TOTALE A PAGARE s.e. &amp; o.</td>
            <td class="total-value">€ {{ number_format($invoice->importo_totale, 2, ',', '.') }}</td>
        </tr>
        @if($isCreditNote)
        <tr>
            <td colspan="2" class="text-center" style="color: #c0392b; font-weight: 600; padding-top: 8px; border: none; background: #fff5f5;">
                <i class="fas fa-info-circle" style="margin-right: 5px;"></i>
                DOCUMENTO DI CREDITO - Da detrarre dal totale fatture
            </td>
        </tr>
        @endif
    </table>
</div>

<!-- DATI DI PAGAMENTO -->
<div class="footer-info">
    <div class="footer-info-row">
        <div class="footer-info-cell">
            <div class="footer-info-label">Condizioni di pagamento</div>
            <div class="footer-info-value">{{ $firstPayment->payment_method_label ?? 'Bonifico bancario' }}</div>
        </div>
        <div class="footer-info-cell">
            <div class="footer-info-label">Scadenza</div>
            <div class="footer-info-value">{{ $firstPayment->due_date ? $firstPayment->due_date->format('d/m/Y') : 'Vedi scadenze' }}</div>
        </div>
    </div>
    <div class="footer-info-row">
        <div class="footer-info-cell">
            <div class="footer-info-label">Riferimenti bancari</div>
            <div class="footer-info-value">
                @if($bankIban)
                    @if($bankName)
                        <strong>{{ $bankName }}</strong><br>
                    @endif
                    <span class="iban-mono">IBAN: {{ $bankIban }}</span>
                    @if($bankHolder)
                        <br>Intestato a: {{ $bankHolder }}
                    @endif
                @else
                    <span style="color:#999;">Riferimenti bancari non disponibili</span>
                @endif
            </div>
        </div>
        <div class="footer-info-cell">
            <div class="footer-info-label">Stato fattura</div>
            <div class="footer-info-value">
                @php
                    $statusMap = ['draft' => 'Bozza', 'issued' => 'Emessa', 'approved' => 'Approvata', 'paid' => 'Pagata'];
                @endphp
                {{ $statusMap[$invoice->status] ?? $invoice->status }}
            </div>
        </div>
    </div>
</div>

<!-- PRIVACY / NOTE LEGALI -->
<div class="privacy">
    <div class="privacy-title">INFORMATIVA AI SENSI DELL'ART. 13 DLGS 196/2003</div>
    I dati personali sono raccolti direttamente presso gli interessati e sono trattati nell'ambito della normale attività amministrativa dell'azienda
    per ottemperare a tutti gli obblighi fiscali, amministrativi e previdenziali, previsti dalla legge. Il trattamento dei dati personali avviene
    mediante strumenti manuali, informatici e telematici in modo da garantire la sicurezza e la riservatezza dei medesimi. I dati personali non
    vengono diffusi in alcun modo o comunicati a terzi, ad eccezione dei dati necessari alle normali operazioni finanziarie di incasso e/o pagamento
    e comunque nel rispetto delle finalità di cui sopra. E' riconosciuto all'interessato il diritto di conoscere, cancellare, rettificare, aggiornare,
    integrare, opporsi al trattamento dei dati personali, nonché agli altri diritti riconosciuti dall'art. 7 della stessa norma.
    @if(!empty($companyData['owner_name']))
        Il titolare del trattamento è il Sig. {{ $companyData['owner_name'] }}.
    @endif
</div>

<div style="font-size: 6.5pt; color: #666; margin-top: 8px; line-height: 1.4;">
    <strong>NB.</strong> {{ $companyData['name'] }} si riserva la proprietà di tutta la merce fornita e descritta con il presente documento sino al pagamento
    integrale del prezzo e degli interessi eventualmente maturati. Vogliate controllare l'esattezza dei dati anagrafici da Voi forniti e
    riportati al fine di comunicare eventuali errori o integrazioni, la {{ $companyData['name'] }} trascorsi 3gg dalla data fattura riterrà
    gli stessi corrispondenti. Eventuali reclami e/o contestazioni saranno accettati entro 3gg. dal ricevimento della presente
    esclusivamente a mezzo raccomandata a.r. all'indirizzo della {{ $companyData['name'] }}, decorsi tali termini non saranno ritenuti validi.
</div>

<!-- FIRME (stile DDT) -->
<div class="signatures">
    <div class="signature-cell">Data e firma mittente</div>
    <div class="signature-cell">Data e firma cliente</div>
    <div class="signature-cell">Data e firma per quietanza</div>
</div>

<!-- FOOTER PAGINA (stile DDT) -->
<div class="page-footer">
    <div class="page-footer-left">
        <strong>{{ $companyData['name'] }}</strong> 
        &nbsp; P.IVA: {{ $companyData['vat'] }}
        @if($companyData['email'])&nbsp; Email: {{ $companyData['email'] }}@endif
    </div>
</div>

</body>
</html>