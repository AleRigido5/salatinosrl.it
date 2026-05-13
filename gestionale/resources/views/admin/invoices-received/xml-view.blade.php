<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fattura {{ $xmlData['documento']['numero'] ?? $invoice->n_invoice }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            background: #fff;
            color: #000;
            padding: 20px 30px;
            display: flex;
            justify-content: center;
        }

        .invoice-container {
            max-width: 1200px;
            width: 100%;
        }

        /* ── BLOCCO CEDENTE / CESSIONARIO ── */
        .parties-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000;
            margin-bottom: 10px;
        }
        .parties-table td {
            width: 50%;
            vertical-align: top;
            padding: 8px 12px;
            border: 1.5px solid #000;
        }
        .party-title {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 6px;
        }
        .party-line {
            margin-bottom: 2px;
            font-size: 11px;
        }

        /* ── BARRA DATI DOCUMENTO ── */
        .doc-info-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 10px;
        }
        .doc-info-table th {
            background: #fff;
            border: 1px solid #000;
            padding: 4px 8px;
            font-weight: bold;
            font-size: 10px;
            text-align: center;
        }
        .doc-info-table td {
            border: 1px solid #000;
            padding: 4px 8px;
            font-size: 11px;
        }

        /* ── TABELLA RIGHE ARTICOLI ── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 10px;
        }
        .items-table th {
            background: #fff;
            border: 1px solid #000;
            padding: 5px 6px;
            font-weight: bold;
            font-size: 10px;
            text-align: center;
        }
        .items-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            font-size: 11px;
            vertical-align: top;
        }
        .num { text-align: right; }
        .ctr { text-align: center; }

        /* ── SEZIONE RIEPILOGHI ── */
        .section-header {
            background: #c8c8c8;
            border: 1px solid #000;
            border-bottom: none;
            padding: 4px 8px;
            font-weight: bold;
            font-size: 10px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .vat-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 0;
        }
        .vat-table th {
            background: #fff;
            border: 1px solid #000;
            padding: 5px 6px;
            font-weight: bold;
            font-size: 10px;
            text-align: center;
        }
        .vat-table td {
            border: 1px solid #000;
            padding: 5px 6px;
            font-size: 11px;
        }

        /* ── RIGA TOTALE DOCUMENTO ── */
        .total-row-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            border-top: none;
            margin-bottom: 10px;
        }
        .total-row-table th {
            background: #fff;
            border: 1px solid #000;
            padding: 4px 6px;
            font-weight: bold;
            font-size: 10px;
            text-align: center;
        }
        .total-row-table td {
            border: 1px solid #000;
            padding: 5px 6px;
            font-size: 11px;
        }

        /* ── TABELLA PAGAMENTO ── */
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 16px;
        }
        .payment-table th {
            background: #fff;
            border: 1px solid #000;
            padding: 5px 8px;
            font-weight: bold;
            font-size: 10px;
            text-align: center;
        }
        .payment-table td {
            border: 1px solid #000;
            padding: 5px 8px;
            font-size: 11px;
        }

        /* ── FOOTER ── */
        .doc-footer {
            font-size: 10px;
            color: #000;
            margin-top: 6px;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="invoice-container">

{{-- ── CEDENTE / CESSIONARIO ── --}}
<table class="parties-table">
    <tr>
        <td>
            <div class="party-title">Cedente/prestatore (fornitore)</div>
            @php
                $cedPiva   = ($xmlData['cedente']['id_paese'] ?? '') . ($xmlData['cedente']['id_codice'] ?? '');
                $cedNome   = $xmlData['cedente']['denominazione'] ?? $invoice->supplier_name ?? '';
                $cedInd    = $xmlData['cedente']['indirizzo'] ?? $invoice->entity->indirizzo ?? '';
                $cedComune = $xmlData['cedente']['comune'] ?? '';
                $cedProv   = $xmlData['cedente']['provincia'] ?? '';
                $cedCap    = $xmlData['cedente']['cap'] ?? '';
            @endphp
            @if($cedPiva)
                <div class="party-line">Identificativo fiscale ai fini IVA: {{ $cedPiva }}</div>
            @endif
            @if($invoice->entity->codice_fiscale ?? null)
                <div class="party-line">Codice fiscale: {{ $invoice->entity->codice_fiscale }}</div>
            @endif
            @if($cedNome)
                <div class="party-line">Denominazione: {{ $cedNome }}</div>
            @endif
            @if($invoice->entity->regime_fiscale ?? null)
                <div class="party-line">Regime fiscale: {{ $invoice->entity->regime_fiscale }}</div>
            @endif
            @if($cedInd)
                <div class="party-line">Indirizzo: {{ $cedInd }}</div>
            @endif
            @if($cedComune)
                <div class="party-line">Comune: {{ $cedComune }}{{ $cedProv ? ' Provincia: ' . $cedProv : '' }}</div>
            @endif
            @if($cedCap)
                <div class="party-line">Cap: {{ $cedCap }} Nazione: {{ $xmlData['cedente']['nazione'] ?? 'IT' }}</div>
            @endif
            @if($invoice->entity->telefono ?? null)
                <div class="party-line">Telefono: {{ $invoice->entity->telefono }}</div>
            @endif
            @if($invoice->entity->email ?? null)
                <div class="party-line">Email: {{ $invoice->entity->email }}</div>
            @endif
        </td>
        <td>
            <div class="party-title">Cessionario/committente (cliente)</div>
            @php
                $cesPiva   = ($xmlData['cessionario']['id_paese'] ?? '') . ($xmlData['cessionario']['id_codice'] ?? '');
                $cesNome   = $xmlData['cessionario']['denominazione'] ?? $invoice->ownership_name ?? '';
                $cesInd    = $xmlData['cessionario']['indirizzo'] ?? $invoice->ownership->Indirizzo ?? '';
                $cesComune = $xmlData['cessionario']['comune'] ?? '';
                $cesProv   = $xmlData['cessionario']['provincia'] ?? '';
                $cesCap    = $xmlData['cessionario']['cap'] ?? '';
            @endphp
            @if($cesPiva)
                <div class="party-line">Identificativo fiscale ai fini IVA: {{ $cesPiva }}</div>
            @endif
            @if($cesNome)
                <div class="party-line">Denominazione: {{ $cesNome }}</div>
            @endif
            @if($cesInd)
                <div class="party-line">Indirizzo: {{ $cesInd }}</div>
            @endif
            @if($cesComune)
                <div class="party-line">Comune: {{ $cesComune }}{{ $cesProv ? ' Provincia: ' . $cesProv : '' }}</div>
            @endif
            @if($cesCap)
                <div class="party-line">Cap: {{ $cesCap }} Nazione: {{ $xmlData['cessionario']['nazione'] ?? 'IT' }}</div>
            @endif
        </td>
    </tr>
</tr>

{{-- ── DATI DOCUMENTO ── --}}
<table class="doc-info-table">
    <thead>
        <tr>
            <th style="width:30%; text-align:left">Tipologia documento</th>
            <th style="width:10%">Art. 73</th>
            <th style="width:22%">Numero documento</th>
            <th style="width:16%">Data documento</th>
            <th style="width:22%">Codice destinatario</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ ($xmlData['documento']['tipo'] ?? 'TD01') }} {{ $xmlData['documento']['tipo_label'] ?? 'fattura' }}</td>
            <td class="ctr"></td>
            <td class="ctr">{{ $xmlData['documento']['numero'] ?? $invoice->n_invoice }}</td>
            <td class="ctr">{{ \Carbon\Carbon::parse($xmlData['documento']['data'] ?? $invoice->data_invoice)->format('d-m-Y') }}</td>
            <td class="ctr">{{ $invoice->codice_destinatario ?? '' }}</td>
        </tr>
    </tbody>
</table>

{{-- ── RIGHE FATTURA ── --}}
<table class="items-table">
    <thead>
        <tr>
            <th style="width:42px">Cod.</th>
            <th style="text-align:center">Descrizione</th>
            <th style="width:72px">Quantità</th>
            <th style="width:80px">Prezzo</th>
            <th style="width:40px">UM</th>
            <th style="width:40px">Sc.</th>
            <th style="width:52px">%IVA</th>
            <th style="width:90px">Prezzo totale</th>
        </tr>
    </thead>
    <tbody>
        @forelse($xmlData['righe'] as $index => $riga)
        <tr>
            <td class="ctr">{{ $index + 1 }}</td>
            <td>{{ $riga['descrizione'] ?? '' }}</td>
            <td class="num">
                @php $qty = floatval(str_replace(',', '.', $riga['quantita'] ?? 0)); @endphp
                @if($qty != 0) {{ number_format($qty, 2, ',', '.') }} @endif
            </td>
            <td class="num">
                @php $pu = floatval(str_replace(',', '.', $riga['prezzo_unitario'] ?? 0)); @endphp
                @if($pu != 0) {{ number_format($pu, 5, ',', '.') }} @endif
            </td>
            <td class="ctr">{{ $riga['unita_misura'] ?? '' }}</td>
            <td class="ctr">{{ $riga['sconto'] ?? '' }}</td>
            <td class="num">
                @if(isset($riga['aliquota_iva']))
                    {{ number_format(floatval(str_replace(',', '.', $riga['aliquota_iva'])), 2, ',', '.') }}
                @endif
            </td>
            <td class="num">
                @php $pt = floatval(str_replace(',', '.', $riga['prezzo_totale'] ?? 0)); @endphp
                @if($pt != 0) {{ number_format($pt, 5, ',', '.') }} @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center; padding:10px; color:#666;">Nessuna riga disponibile</td></tr>
        @endforelse
    </tbody>
</table>

{{-- ── RIEPILOGHI IVA E TOTALI ── --}}
@php $summaries = $xmlData['vat_summaries_list'] ?? $invoice->vatSummaries ?? collect(); @endphp

<div class="section-header">Riepiloghi IVA e Totali</div>
<table class="vat-table">
    <thead>
        <tr>
            <th style="width:35%; text-align:left; padding-left:6px">esigibilità iva / riferimenti normativi</th>
            <th style="width:8%">%IVA</th>
            <th style="width:12%">Spese accessorie</th>
            <th style="width:8%">Arr.</th>
            <th style="width:18%">Totale imponibile</th>
            <th style="width:19%">Totale imposta</th>
        </tr>
    </thead>
    <tbody>
        @forelse($summaries as $summary)
        <tr>
            <td>
                {{ $summary->esigibilita_label ?? $summary->esigibilita_iva ?? '' }}
                @if($summary->sdi_nature ?? null)
                    <br><small>{{ $summary->nature_label ?? $summary->sdi_nature }}</small>
                @endif
                @if($summary->vat_law_reference ?? null)
                    <br><small>{{ $summary->vat_law_reference }}</small>
                @endif
            </td>
            <td class="num">{{ number_format($summary->tax_rate, 2, ',', '.') }}</td>
            <td></td>
            <td></td>
            <td class="num">{{ number_format($summary->taxable_amount, 2, ',', '.') }}</td>
            <td class="num">{{ number_format($summary->tax_amount, 2, ',', '.') }}</td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center; padding:8px; color:#666;">Nessun riepilogo IVA</td></tr>
        @endforelse
    </tbody>
</table>

{{-- Riga importo bollo / sconto / valuta / totale documento --}}
<table class="total-row-table">
    <thead>
        <tr>
            <th style="width:20%">Importo bollo</th>
            <th style="width:28%">Sconto/Maggiorazione</th>
            <th style="width:10%">Arr.</th>
            <th style="width:10%">Valuta</th>
            <th style="width:32%">Totale documento</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td class="ctr">{{ $xmlData['documento']['divisa'] ?? 'EUR' }}</td>
            <td class="num" style="font-weight:bold">{{ number_format($invoice->importo_totale, 2, ',', '.') }}</td>
        </tr>
    </tbody>
</table>

{{-- ── DATI PAGAMENTO ── --}}
@php $payments = $invoice->payments ?? collect(); @endphp
<table class="payment-table">
    <thead>
        <tr>
            <th style="width:24%">Modalità pagamento</th>
            <th style="width:30%">IBAN</th>
            <th style="width:14%">Istituto</th>
            <th style="width:16%">Data scadenza</th>
            <th style="width:16%">Importo</th>
        </tr>
    </thead>
    <tbody>
        @forelse($payments as $payment)
        <tr>
            <td>{{ $payment->payment_method ?? '' }} {{ $payment->payment_method_label ?? '' }}</td>
            <td>{{ $payment->iban ?? '' }}</td>
            <td>{{ $payment->istituto ?? '' }}</td>
            <td class="ctr">{{ $payment->due_date ? $payment->due_date->format('d-m-Y') : '' }}</td>
            <td class="num">{{ number_format($payment->amount, 2, ',', '.') }}</td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center; padding:8px; color:#666;">Nessun dato di pagamento</td></tr>
        @endforelse
    </tbody>
</table>

{{-- ── FOOTER ── --}}
<div class="doc-footer">
    Trasmittente: {{ $invoice->trasmittente ?? $invoice->entity->partita_iva ?? '' }} /
    Progressivo: {{ $invoice->progressivo_invio ?? $invoice->id }} /
    Formato Trasmissione: {{ $invoice->formato_trasmissione ?? 'FPR12' }} /
</div>

</div>

</body>
</html>