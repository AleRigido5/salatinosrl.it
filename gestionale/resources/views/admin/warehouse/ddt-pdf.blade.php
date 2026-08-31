<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9pt;
            color: #333;
            padding: 20px 24px;
        }

        /* ===== HEADER ===== */
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

        /* ===== TITLE BAR ===== */
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

        /* ===== DESTINATARIO / LUOGO ===== */
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

        /* ===== TABELLA RIGHE ===== */
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

        /* ===== BLOCCO DATI TRASPORTO (stile "campo modulo") ===== */
        .footer-info {
            display: table;
            width: 100%;
            margin-top: 10px;
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

        /* ===== FIRME ===== */
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

        /* ===== FOOTER PAGINA ===== */
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
    </style>
</head>
<body>

@php
    $mittente = $ddt->mittente_dati;
    $dest = $ddt->destinatario_dati;
    $luogo = $ddt->luogo_destinazione_dati;
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
        <div class="co-name">{{ $mittente['ragione_sociale'] }}</div>
        <div class="co-detail">
            {{ $mittente['indirizzo'] }},<br>
            {{ $mittente['citta'] }} {{ $mittente['provincia'] }},<br>
            P.IVA: {{ $mittente['piva'] }} C.F.: {{ $mittente['cf'] }}
        </div>
    </div>
</div>

<!-- TITLE BAR -->
<div class="title-bar">
    <div class="title-bar-left">DOCUMENTO DI TRASPORTO</div>
    <div class="title-bar-right">nr. <strong>{{ $ddt->ddt_number }}</strong> del <strong>{{ $ddt->ddt_date->format('d.m.y') }}</strong></div>
</div>

<!-- DESTINATARIO / LUOGO DI DESTINAZIONE -->
<div class="box-row">
    <div class="box">
        <div class="box-label">Destinatario</div>
        <div class="box-title">{{ $dest['ragione_sociale'] }}</div>
        <div class="box-line">
            {{ $dest['indirizzo'] }}<br>
            {{ $dest['cap'] }} {{ $dest['citta'] }}<br>
            {{ $dest['provincia'] }}, Italia<br>
            @if($dest['piva']) P.IVA: {{ $dest['piva'] }} @endif
            @if($dest['cf']) &nbsp;Cod.Fiscale: {{ $dest['cf'] }} @endif
        </div>
    </div>
    <div class="box">
        <div class="box-label">Luogo di destinazione</div>
        <div class="box-title">{{ $luogo['ragione_sociale'] }}</div>
        <div class="box-line">
            {{ $luogo['indirizzo'] }}<br>
            {{ $luogo['cap'] }} {{ $luogo['citta'] }}<br>
            {{ $luogo['provincia'] }}, Italia
        </div>
    </div>
</div>

<!-- TABELLA RIGHE -->
<table class="rows">
    <thead>
        <tr>
            <th style="width: 9%;">Codice</th>
            <th>Descrizione</th>
            <th style="width: 12%;" class="col-qty">Quantità</th>
            <th style="width: 8%;">UM</th>
        </tr>
    </thead>
    <tbody>
        @foreach($ddt->rows as $row)
        <tr>
            <td>{{ $row->codice ?: ($row->product->sku ?? '-') }}</td>
            <td>{{ $row->description }}</td>
            <td class="col-qty">{{ number_format((float) $row->quantity, 2, ',', '.') }}</td>
            <td>{{ $row->unit_of_measure }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<!-- DATI DI TRASPORTO -->
<div class="footer-info">
    <div class="footer-info-row">
        <div class="footer-info-cell">
            <div class="footer-info-label">Causale</div>
            <div class="footer-info-value">{{ $ddt->causale }}</div>
        </div>
        <div class="footer-info-cell">
            <div class="footer-info-label">Termini di consegna</div>
            <div class="footer-info-value">{{ $ddt->termini_consegna }}</div>
        </div>
    </div>
    <div class="footer-info-row">
        <div class="footer-info-cell">
            <div class="footer-info-label">Aspetto esteriore beni</div>
            <div class="footer-info-value">{{ $ddt->aspetto_esteriore_beni }}</div>
        </div>
        <div class="footer-info-cell">
            <div class="footer-info-label">Numero colli</div>
            <div class="footer-info-value">{{ $ddt->numero_colli }}</div>
        </div>
    </div>
    <div class="footer-info-row">
        <div class="footer-info-cell">
            <div class="footer-info-label">Trasporto a mezzo</div>
            <div class="footer-info-value">
                [{{ in_array($ddt->trasporto_a_mezzo, ['mittente', 'destinatario']) ? 'X' : ' ' }}] Destinatario/Mittente
                &nbsp;&nbsp;[{{ $ddt->trasporto_a_mezzo === 'vettore' ? 'X' : ' ' }}] Vettore
            </div>
        </div>
        <div class="footer-info-cell">
            <div class="footer-info-label">Inizio trasporto</div>
            <div class="footer-info-value">{{ $ddt->inizio_trasporto_at ? $ddt->inizio_trasporto_at->format('d.m.y H:i') : '' }}</div>
        </div>
    </div>
    @if($ddt->trasporto_a_mezzo === 'vettore')
    <div class="footer-info-row">
        <div class="footer-info-cell-full">
            <div class="footer-info-label">Vettore</div>
            <div class="footer-info-value">
                {{ $ddt->vettore_nome }}
                &nbsp;&nbsp;<strong>indirizzo:</strong> {{ $ddt->vettore_indirizzo }}
                &nbsp;&nbsp;<strong>tel.:</strong> {{ $ddt->vettore_telefono }}
                &nbsp;&nbsp;<strong>email:</strong> {{ $ddt->vettore_email }}
            </div>
        </div>
    </div>
    @endif
</div>

<!-- FIRME -->
<div class="signatures">
    <div class="signature-cell">Data e firma mittente</div>
    <div class="signature-cell">Data e firma destinatario</div>
    <div class="signature-cell">Data e firma corriere</div>
</div>

<!-- FOOTER PAGINA -->
<div class="page-footer">
    <div class="page-footer-left"><strong>{{ $mittente['ragione_sociale'] }}</strong> &nbsp; P.IVA: {{ $mittente['piva'] }} &nbsp; C.F.: {{ $mittente['cf'] }}</div>
    <div class="page-footer-right">Pagina 1/1</div>
</div>

</body>
</html>