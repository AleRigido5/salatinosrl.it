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
            font-size: 8pt;
            color: #1c1c1c;
            background: white;
            padding: 16px 22px;
        }

        /* ══ WATERMARK S ══ */
        .watermark {
            position: fixed;
            top: 36%;
            left: 46%;
            transform: translate(-50%, -50%);
            font-size: 1000pt;
            font-weight: 900;
            color: rgba(70, 130, 50, 0.055);
            font-family: 'Poppins', 'DejaVu Sans', sans-serif;
            z-index: 0;
            line-height: 1;
        }

        .page { position: relative; z-index: 1; }

        /* ══ HEADER ══ */
        .header-outer {
            border: 1pt solid #b0b8a8;
            margin-bottom: 0;
            overflow: hidden;
        }

        .header-main {
            width: 100%;
            border-collapse: collapse;
        }

        .header-left {
            padding: 9px 12px 7px 12px;
            width: 65%;
            vertical-align: top;
        }

        .header-right {
            padding: 9px 12px 7px 12px;
            width: 35%;
            border-left: 1pt solid #b0b8a8;
            vertical-align: middle;
            text-align: center;
        }

        .co-name {
            font-size: 20pt;
            font-weight: 700;
            letter-spacing: -0.5px;
            line-height: 1.05;
            color: #111;
            margin-bottom: 1px;
        }

        .co-tagline {
            font-style: italic;
            font-size: 8pt;
            font-weight: 300;
            color: #555;
            margin-bottom: 3px;
        }

        .co-detail {
            font-size: 7.5pt;
            font-weight: 400;
            line-height: 1.6;
            color: #333;
        }

        .co-detail strong { font-weight: 600; }

        .contacts-row {
            width: 100%;
            border-collapse: collapse;
            border-top: 1pt solid #b0b8a8;
        }

        .contacts-row td {
            padding: 3px 12px;
            font-size: 7.8pt;
            font-weight: 400;
            color: #444;
            text-align: center;
        }

        /* ══ FATTURA / CLIENTE ══ */
        .doc-wrap {
            border-left: 1pt solid #b0b8a8;
            border-right: 1pt solid #b0b8a8;
            border-bottom: 1pt solid #b0b8a8;
            margin-bottom: 9px;
            overflow: hidden;
        }

        .doc-table { width: 100%; border-collapse: collapse; }

        .cell-fattura {
            background-color: #e6ece0;
            padding: 5px 10px;
            font-weight: 700;
            font-size: 10pt;
            letter-spacing: 0.3px;
            width: 18%;
            border-right: 1pt solid #b0b8a8;
            border-bottom: 1pt solid #b0b8a8;
        }

        .cell-pag {
            padding: 5px 10px;
            font-size: 8pt;
            font-weight: 400;
            color: #555;
            width: 22%;
            border-right: 1pt solid #b0b8a8;
            border-bottom: 1pt solid #b0b8a8;
            vertical-align: middle;
        }

        .cell-cliente {
            background-color: #e6ece0;
            padding: 5px 10px;
            font-weight: 700;
            font-size: 10pt;
            letter-spacing: 0.3px;
            border-bottom: 1pt solid #b0b8a8;
        }

        .cell-docinfo {
            padding: 10px 10px;
            vertical-align: top;
            width: 40%;
            border-right: 1pt solid #b0b8a8;
        }

        .cell-clientdata { padding: 10px 10px; vertical-align: top; }

        .doc-number { font-size: 9pt; font-weight: 600; margin-bottom: 5px; color: #111; }
        .doc-date   { font-size: 9pt; font-weight: 600; color: #111; }
        .cli-name   { font-size: 10pt; font-weight: 700; line-height: 1.35; margin-bottom: 2px; color: #111; }
        .cli-info   { font-size: 8.5pt; font-weight: 600; line-height: 1.55; color: #222; }

        /* ══ TABELLA PRODOTTI ══ */
        .prod-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            border: 1pt solid #a8b49e;
        }

        .prod-table th {
            background-color: #c8d4b8;
            border: 1pt solid #9aaa8e;
            padding: 5px 5px;
            font-size: 8pt;
            font-weight: 600;
            text-align: center;
            color: #1a1a1a;
        }

        .prod-table td {
            border-right: 0.5pt solid #d0d8c8;
            border-bottom: 0.5pt solid #e8ede4;
            padding: 4px 4px;
            font-size: 8pt;
            font-weight: 400;
            vertical-align: top;
        }

        .td-desc { padding-left: 7px !important; }
        .tr { text-align: right; padding-right: 6px !important; }
        .tc { text-align: center; }

        /* ══ PRIVACY ══ */
        .privacy {
            font-size: 6.2pt;
            font-weight: 400;
            color: #333;
            line-height: 1.4;
            margin-bottom: 9px;
            border: 0.8pt solid #c0c8b8;
            padding: 5px 8px;
            background-color: #fafbf9;
        }

        .privacy-title {
            font-weight: 700;
            font-size: 6.5pt;
            margin-bottom: 2px;
        }

        /* ══ BOTTOM ══ */
        .bottom-outer {
            border: 1pt solid #a8b49e;
            overflow: hidden;
        }

        .bottom-table { width: 100%; border-collapse: collapse; }

        .pay-col {
            width: 37%;
            border-right: 1pt solid #a8b49e;
            vertical-align: top;
        }

        .tot-col { width: 63%; vertical-align: top; }

        .pay-header {
            background-color: #e6ece0;
            font-weight: 700;
            font-size: 8.5pt;
            padding: 5px 9px;
            border-bottom: 1pt solid #b0b8a8;
            letter-spacing: 0.2px;
        }

        .pay-content {
            padding: 6px 9px;
            font-size: 8pt;
            font-weight: 400;
            border-bottom: 1pt solid #b0b8a8;
            min-height: 26px;
        }

        .bank-header {
            background-color: #e6ece0;
            font-weight: 700;
            font-size: 8.5pt;
            padding: 5px 9px;
            border-bottom: 1pt solid #b0b8a8;
            letter-spacing: 0.2px;
        }

        .bank-content {
            padding: 6px 9px;
            font-size: 7.8pt;
            font-weight: 400;
            line-height: 1.6;
        }

        .iban-mono {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 7.5pt;
            font-weight: 700;
        }

        /* Totali 3 colonne */
        .tot-table { width: 100%; border-collapse: collapse; }

        .tot-table tr td {
            padding: 4.5px 8px;
            font-size: 8pt;
            border-bottom: 0.8pt solid #d0d8c8;
        }

        .tot-lbl {
            text-align: right;
            font-weight: 600;
            width: 56%;
            background-color: #f0f4ec;
            border-right: 0.8pt solid #b0b8a8;
        }

        .tot-eur {
            text-align: center;
            width: 9%;
            font-weight: 600;
            background-color: #f0f4ec;
            border-right: 0.8pt solid #b0b8a8;
        }

        .tot-val {
            text-align: right;
            padding-right: 10px;
            width: 35%;
            font-weight: 500;
        }

        .tot-final .tot-lbl {
            background-color: #2d5a1e;
            color: white;
            font-size: 9pt;
            font-weight: 700;
            padding: 7px 8px;
        }

        .tot-final .tot-eur {
            background-color: #2d5a1e;
            color: white;
            font-weight: 700;
            font-size: 9pt;
            padding: 7px 8px;
        }

        .tot-final .tot-val {
            background-color: #2d5a1e;
            color: white;
            font-size: 10pt;
            font-weight: 700;
            padding: 7px 10px;
        }

        /* ══ NOTE FINALI ══ */
        .final-nb {
            font-size: 6.2pt;
            font-weight: 400;
            color: #333;
            margin-top: 9px;
            line-height: 1.42;
        }

        .footer-txt {
            text-align: center;
            font-size: 6pt;
            color: #bbb;
            margin-top: 7px;
            font-weight: 300;
        }
    </style>
</head>
<body>

<div class="watermark">S</div>

@php
    // Recupera i dati dell'ownership associata alla fattura (obbligatoria)
    $ownership = $invoice->ownership;
    
    // Determina i dati dell'azienda ESCLUSIVAMENTE dall'ownership
    $companyData = [
        'name' => $ownership->Rag_Soc_intest,
        'tagline' => $ownership->Rag_Soc_intest ?? '',
        'address' => $ownership->IndirizzoPr && $ownership->LocalitPr 
            ? $ownership->IndirizzoPr . ' - ' . $ownership->LocalitPr . ' (' . ($ownership->ProvinciaPr ?? 'BA') . ')'
            : $ownership->IndirizzoPr,
        'vat' => $ownership->PivaPr,
        'capital' => $ownership->CapitalePr ?? '€ 20.000,00 i.v.',
        'email' => $ownership->EmailPr,
        'website' => $ownership->WebPr,
        'iban' => $ownership->IbanPr,
        'bank' => $ownership->BancaPr,
        'owner_name' => $ownership->NomeRapprPr,
        'registration' => true,
        'province' => $ownership->ProvinciaPr ?? 'Bari',
    ];
    
    // Costruisce la stringa per la registrazione imprese
    $companyData['registration_text'] = 'P.IVA e C.F. iscrizione Registro Imprese di ' . $companyData['province'] . ' <strong>' . $companyData['vat'] . '</strong>';
@endphp

<div class="page">

<!-- INTESTAZIONE -->
<div class="header-outer">
    <table class="header-main">
        <tr>
            <td class="header-left">
                <div class="co-name">{{ $companyData['name'] }}</div>
                @if(!empty($companyData['tagline']))<div class="co-tagline">{{ $companyData['tagline'] }}</div>@endif
                <div class="co-detail">
                    {{ $companyData['address'] }}<br>
                    {!! $companyData['registration_text'] !!}<br>
                    Capitale sociale {{ $companyData['capital'] }}
                </div>
            </td>
            <td class="header-right">
                <div style="font-size:7.8pt; font-weight:400; color:#444; line-height:1.9;">
                    {{ $companyData['email'] }}<br>{{ $companyData['website'] }}
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- FATTURA / CLIENTE -->
<div class="doc-wrap">
    <table class="doc-table">
        <tr>
            <td class="cell-fattura">FATTURA</td>
            <td class="cell-pag">Pag.: 1/1</td>
            <td class="cell-cliente">CLIENTE</td>
        </tr>
        <tr>
            <td class="cell-docinfo" colspan="2">
                <div class="doc-number">Numero: {{ $invoice->n_invoice }}</div>
                <div class="doc-date">Data: {{ $invoice->data_invoice->format('d/m/Y') }}</div>
            </td>
            <td class="cell-clientdata">
                @php $entity = $invoice->entity; @endphp
                @if($entity)
                    <div class="cli-name">{{ $entity->ragione_sociale ?? trim(($entity->nome ?? '') . ' ' . ($entity->cognome ?? '')) }}</div>
                    <div class="cli-info">
                        @if(!empty($entity->indirizzo)){{ $entity->indirizzo }}<br>@endif
                        @if(!empty($entity->cap) && !empty($entity->citta)){{ $entity->cap }} - {{ strtoupper($entity->citta) }}@if(!empty($entity->provincia)) ({{ strtoupper($entity->provincia) }})@endif<br>@endif
                        @if(!empty($entity->partita_iva))P.IVA: {{ $entity->partita_iva }}<br>@endif
                        @if(!empty($entity->codice_fiscale))Cod. Fisc: {{ $entity->codice_fiscale }}@endif
                    </div>
                @else
                    <div class="cli-name">{{ $invoice->customer_name }}</div>
                @endif
            </td>
        </tr>
    </table>
</div>

<!-- RIGHE FATTURA -->
<table class="prod-table">
    <thead>
        <tr>
            <th style="width:46%; text-align:left; padding-left:7px;">Descrizione</th>
            <th style="width:8%;">UM</th>
            <th style="width:10%;">Q.tà</th>
            <th style="width:12%;">Prezzo</th>
            <th style="width:14%;">Importo</th>
            <th style="width:10%;">IVA</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->rows as $row)
        <tr>
            <td class="td-desc">{{ $row->description }}</td>
            <td class="tc">{{ $row->unit_measure ?? 'Pz.' }}</td>
            <td class="tr">{{ number_format($row->quantity, 2, ',', '.') }}</td>
            <td class="tr">{{ number_format($row->unit_price, 3, ',', '.') }}</td>
            <td class="tr">{{ number_format($row->total, 2, ',', '.') }}</td>
            <td class="tc">{{ $row->vat_rate ?? 22 }}%</td>
        </tr>
        @endforeach
        @php $fill = max(0, 14 - count($invoice->rows)); @endphp
        @for($i = 0; $i < $fill; $i++)
        <tr style="height:15px;"><td class="td-desc"></td><td class="tc"></td><td class="tr"></td><td class="tr"></td><td class="tr"></td><td class="tc"></td></tr>
        @endfor
    </tbody>
</table>

<!-- PRIVACY -->
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

<!-- PAGAMENTO + TOTALI -->
@php
    $firstPayment = $invoice->payments->first();
    $vatTotals = [];
    foreach($invoice->rows as $row) {
        $rate = $row->vat_rate ?? 22;
        $vatTotals[$rate] = ($vatTotals[$rate] ?? 0) + $row->total;
    }
    ksort($vatTotals);
@endphp

<div class="bottom-outer">
    <table class="bottom-table">
        <tr>
            <td class="pay-col">
                <div class="pay-header">PAGAMENTO</div>
                <div class="pay-content">{{ $firstPayment->payment_method_label ?? 'Bonifico vista fattura' }}</div>
                <div class="bank-header">NS RIF. BANCARI</div>
                <div class="bank-content">
                    @if($firstPayment && !empty($firstPayment->iban))
                        @if(!empty($firstPayment->bank_name))<strong>{{ $firstPayment->bank_name }}</strong><br>@endif
                        <span class="iban-mono">IBAN: {{ $firstPayment->iban }}</span><br>
                        @if(!empty($firstPayment->bank_account_holder))intestato a {{ $firstPayment->bank_account_holder }}@endif
                    @else
                        @if(!empty($companyData['bank_name']))<strong>{{ $companyData['bank_name'] }}</strong><br>@endif
                        <span class="iban-mono">IBAN: {{ $companyData['iban'] }}</span><br>
                        intestato a {{ $companyData['bank'] }}
                    @endif
                </div>
            </td>
            <td class="tot-col">
                <table class="tot-table">
                    @foreach($vatTotals as $rate => $imponibile)
                    <tr>
                        <td class="tot-lbl">IMPONIBILE AL {{ $rate }}%</td>
                        <td class="tot-eur">€</td>
                        <td class="tot-val">{{ number_format($imponibile, 2, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    @foreach($vatTotals as $rate => $imponibile)
                    <tr>
                        <td class="tot-lbl">IVA AL {{ $rate }}%</td>
                        <td class="tot-eur">€</td>
                        <td class="tot-val">{{ number_format($imponibile * $rate / 100, 2, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr class="tot-final">
                        <td class="tot-lbl">TOTALE A PAGARE s.e. &amp; o.</td>
                        <td class="tot-eur">€</td>
                        <td class="tot-val">{{ number_format($invoice->importo_totale, 2, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>

<!-- NOTE FINALI -->
<div class="final-nb">
    NB. {{ $companyData['name'] }} si riserva la proprietà di tutta la merce fornita e descritta con il presente documento sino al pagamento
    integrale del prezzo e degli interessi eventualmente maturati. Vogliate controllare l'esattezza dei dati anagrafici da Voi forniti e
    riportati al fine di comunicare eventuali errori o integrazioni, la {{ $companyData['name'] }} trascorsi 3gg dalla data fattura riterrà
    gli stessi corrispondenti. Eventuali reclami e/o contestazioni saranno accettati entro 3gg. dal ricevimento della presente
    esclusivamente a mezzo raccomandata a.r. all'indirizzo della {{ $companyData['name'] }}, decorsi tali termini non saranno ritenuti validi.
</div>

<div class="footer-txt">Documento generato elettronicamente · {{ $companyData['name'] }}</div>

</div>
</body>
</html>