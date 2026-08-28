<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        .header { display: table; width: 100%; margin-bottom: 15px; }
        .header-left { display: table-cell; width: 30%; vertical-align: top; }
        .header-right { display: table-cell; width: 70%; text-align: right; vertical-align: top; }
        .title-bar { background: #d9e6f2; padding: 8px 12px; display: table; width: 100%; margin-bottom: 15px; }
        .title-bar-left { display: table-cell; font-size: 14px; font-weight: bold; }
        .title-bar-right { display: table-cell; text-align: right; }
        .box-row { display: table; width: 100%; margin-bottom: 12px; }
        .box { display: table-cell; width: 50%; vertical-align: top; padding-right: 15px; }
        .box-label { font-size: 9px; color: #888; text-transform: uppercase; }
        .box-title { font-weight: bold; margin-bottom: 2px; }
        table.rows { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.rows th { background: #f2f2f2; text-align: left; padding: 5px; font-size: 9px; text-transform: uppercase; border-bottom: 1px solid #ccc; }
        table.rows td { padding: 5px; border-bottom: 1px solid #eee; }
        .footer-info { display: table; width: 100%; margin-top: 30px; }
        .footer-info-row { display: table-row; }
        .footer-info-cell { display: table-cell; width: 50%; padding: 4px 0; border-bottom: 1px solid #eee; }
        .footer-info-label { font-size: 9px; color: #888; text-transform: uppercase; }
        .signatures { display: table; width: 100%; margin-top: 40px; }
        .signature-cell { display: table-cell; width: 33.33%; text-align: center; font-size: 9px; color: #888; border-top: 1px solid #ccc; padding-top: 5px; }
        .page-footer { position: fixed; bottom: -20px; font-size: 8px; color: #888; width: 100%; }
    </style>
</head>
<body>

@php
    $mittente = $ddt->mittente_dati;
    $dest = $ddt->destinatario_dati;
    $luogo = $ddt->luogo_destinazione_dati;
@endphp

<div class="header">
    <div class="header-left"></div>
    <div class="header-right">
        <strong style="font-size: 14px;">{{ $mittente['ragione_sociale'] }}</strong><br>
        {{ $mittente['indirizzo'] }},<br>
        {{ $mittente['citta'] }} {{ $mittente['provincia'] }},<br>
        P.IVA: {{ $mittente['piva'] }} C.F.: {{ $mittente['cf'] }}
    </div>
</div>

<div class="title-bar">
    <div class="title-bar-left">DOCUMENTO DI TRASPORTO</div>
    <div class="title-bar-right">nr. <strong>{{ $ddt->ddt_number }}</strong> del <strong>{{ $ddt->ddt_date->format('d.m.y') }}</strong></div>
</div>

<div class="box-row">
    <div class="box">
        <div class="box-label">Destinatario</div>
        <div class="box-title">{{ $dest['ragione_sociale'] }}</div>
        {{ $dest['indirizzo'] }}<br>
        {{ $dest['cap'] }} {{ $dest['citta'] }}<br>
        {{ $dest['provincia'] }}, Italia<br>
        @if($dest['piva']) P.IVA: {{ $dest['piva'] }} @endif
        @if($dest['cf']) Cod.Fiscale: {{ $dest['cf'] }} @endif
    </div>
    <div class="box">
        <div class="box-label">Luogo di destinazione</div>
        <div class="box-title">{{ $luogo['ragione_sociale'] }}</div>
        {{ $luogo['indirizzo'] }}<br>
        {{ $luogo['cap'] }} {{ $luogo['citta'] }}<br>
        {{ $luogo['provincia'] }}, Italia
    </div>
</div>

<table class="rows">
    <thead>
        <tr>
            <th style="width: 10%;">Codice</th>
            <th>Descrizione</th>
            <th style="width: 12%; text-align: right;">Quantità</th>
            <th style="width: 8%;">UM</th>
        </tr>
    </thead>
    <tbody>
        @foreach($ddt->rows as $row)
        <tr>
            <td>{{ $row->codice ?: ($row->product->sku ?? '-') }}</td>
            <td>{{ $row->description }}</td>
            <td style="text-align: right;">{{ number_format((float) $row->quantity, 2, ',', '.') }}</td>
            <td>{{ $row->unit_of_measure }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer-info">
    <div class="footer-info-row">
        <div class="footer-info-cell">
            <div class="footer-info-label">Causale</div>
            {{ $ddt->causale ?: '-' }}
        </div>
        <div class="footer-info-cell">
            <div class="footer-info-label">Termini di consegna</div>
            {{ $ddt->termini_consegna ?: '-' }}
        </div>
    </div>
    <div class="footer-info-row">
        <div class="footer-info-cell">
            <div class="footer-info-label">Aspetto esteriore beni</div>
            {{ $ddt->aspetto_esteriore_beni ?: '-' }}
        </div>
        <div class="footer-info-cell">
            <div class="footer-info-label">Numero colli</div>
            {{ $ddt->numero_colli ?: '-' }}
        </div>
    </div>
    <div class="footer-info-row">
        <div class="footer-info-cell">
            <div class="footer-info-label">Trasporto a mezzo</div>
            [{{ $ddt->trasporto_a_mezzo === 'mittente' ? 'X' : ' ' }}] Mittente
            [{{ $ddt->trasporto_a_mezzo === 'destinatario' ? 'X' : ' ' }}] Destinatario
            [{{ $ddt->trasporto_a_mezzo === 'vettore' ? 'X' : ' ' }}] Vettore
        </div>
        <div class="footer-info-cell">
            <div class="footer-info-label">Inizio trasporto</div>
            {{ $ddt->inizio_trasporto_at ? $ddt->inizio_trasporto_at->format('d.m.y H:i') : '-' }}
        </div>
    </div>
    @if($ddt->vettore_nome)
    <div class="footer-info-row">
        <div class="footer-info-cell" style="width: 100%;">
            <div class="footer-info-label">Vettore</div>
            {{ $ddt->vettore_nome }}
            @if($ddt->vettore_indirizzo) indirizzo: {{ $ddt->vettore_indirizzo }} @endif
            @if($ddt->vettore_telefono) tel.: {{ $ddt->vettore_telefono }} @endif
            @if($ddt->vettore_email) email: {{ $ddt->vettore_email }} @endif
        </div>
    </div>
    @endif
</div>

<div class="signatures">
    <div class="signature-cell">Data e firma mittente</div>
    <div class="signature-cell">Data e firma destinatario</div>
    <div class="signature-cell">Data e firma corriere</div>
</div>

<div class="page-footer">
    {{ $mittente['ragione_sociale'] }} — P.IVA: {{ $mittente['piva'] }} C.F.: {{ $mittente['cf'] }}
</div>

</body>
</html>