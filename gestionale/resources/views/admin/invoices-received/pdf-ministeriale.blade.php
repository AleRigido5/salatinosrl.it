<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Fattura Elettronica {{ $numeroDocumento ?? $invoice->n_invoice }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; padding: 15px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        .header h1 { font-size: 18px; color: #333; }
        .section { margin-bottom: 15px; }
        .section-title { font-size: 11px; font-weight: bold; background: #e8f5e9; padding: 5px 10px; border-left: 3px solid #4CAF50; margin-bottom: 10px; }
        .two-columns { display: flex; gap: 20px; margin-bottom: 15px; }
        .column { flex: 1; }
        .info-row { margin-bottom: 4px; display: flex; }
        .info-label { width: 120px; font-weight: bold; }
        .info-value { flex: 1; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background: #f0f0f0; text-align: center; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 8px; text-align: center; }
        .total { font-weight: bold; font-size: 12px; margin-top: 15px; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>FATTURA ELETTRONICA</h1>
        <p>Documento emesso ai sensi del D.Lgs. 127/2015</p>
        <p><strong>Fattura n. {{ $numeroDocumento ?? $invoice->n_invoice }} del {{ \Carbon\Carbon::parse($dataDocumento ?? $invoice->data_invoice)->format('d/m/Y') }}</strong></p>
    </div>

    <!-- DUE COLONNE -->
    <div class="two-columns">
        <!-- Cedente / Prestatore -->
        <div class="column">
            <div class="section-title">CEDENTE / PRESTATORE</div>
            <div class="info-row">
                <span class="info-label">Denominazione:</span>
                <span class="info-value">{{ $cedenteDenominazione ?? $invoice->supplier_name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Partita IVA:</span>
                <span class="info-value">IT{{ $cedentePiva ?? ($invoice->entity->partita_iva ?? '') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Sede:</span>
                <span class="info-value">
                    {{ $cedenteIndirizzo ?? $invoice->entity->indirizzo ?? '' }} 
                    {{ $cedenteCap ?? $invoice->entity->cap ?? '' }} 
                    {{ $cedenteComune ?? $invoice->entity->comune ?? '' }} 
                    ({{ $cedenteProvincia ?? $invoice->entity->provincia ?? '' }})
                </span>
            </div>
        </div>

        <!-- Cessionario / Committente -->
        <div class="column">
            <div class="section-title">CESSIONARIO / COMMITTENTE</div>
            <div class="info-row">
                <span class="info-label">Denominazione:</span>
                <span class="info-value">{{ $cessionarioDenominazione ?? $invoice->ownership_name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Sede:</span>
                <span class="info-value">
                    {{ $cessionarioIndirizzo ?? $invoice->ownership->Indirizzo ?? '' }} 
                    {{ $cessionarioCap ?? $invoice->ownership->CAP ?? '' }} 
                    {{ $cessionarioComune ?? $invoice->ownership->Comune ?? '' }} 
                    ({{ $cessionarioProvincia ?? $invoice->ownership->Provincia ?? '' }})
                </span>
            </div>
        </div>
    </div>

    <!-- DATI GENERALI DOCUMENTO -->
    <div class="section">
        <div class="section-title">DATI GENERALI DOCUMENTO</div>
        <div class="info-row">
            <span class="info-label">Tipo documento:</span>
            <span class="info-value">{{ $tipoDocumento ?? $invoice->type_invoice }} - {{ config('gestionale.tipo_documento.' . ($tipoDocumento ?? $invoice->type_invoice), '') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Divisa:</span>
            <span class="info-value">EUR</span>
        </div>
        <div class="info-row">
            <span class="info-label">Importo totale:</span>
            <span class="info-value">{{ number_format($importoTotale ?? $invoice->importo_totale, 2, ',', '.') }} €</span>
        </div>
    </div>

    <!-- RIGHE FATTURA -->
    <div class="section">
        <div class="section-title">DETTAGLIO ARTICOLI / SERVIZI</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Descrizione</th>
                    <th class="text-center">Quantità</th>
                    <th class="text-right">Prezzo Unit.</th>
                    <th class="text-center">%IVA</th>
                    <th class="text-right">Totale</th>
                </tr>
            </thead>
            <tbody>
                @php $righeList = $righe ?? $invoice->rows; @endphp
                @forelse($righeList as $index => $riga)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-left">
                        @if(is_object($riga) && method_exists($riga, 'getDescrizione'))
                            {{ $riga->getDescrizione() }}
                        @elseif(is_object($riga) && isset($riga->descrizione))
                            {{ $riga->descrizione }}
                        @elseif(is_array($riga) && isset($riga['descrizione']))
                            {{ $riga['descrizione'] }}
                        @else
                            {{ $riga->description ?? '-' }}
                        @endif
                    </td>
                    <td class="text-center">
                        @php
                            $qty = 0;
                            if(is_object($riga) && method_exists($riga, 'getQuantita')) $qty = $riga->getQuantita();
                            elseif(is_object($riga) && isset($riga->quantita)) $qty = $riga->quantita;
                            elseif(is_array($riga) && isset($riga['quantita'])) $qty = $riga['quantita'];
                            else $qty = $riga->quantity ?? 0;
                        @endphp
                        {{ number_format($qty, 2, ',', '.') }}
                    </td>
                    <td class="text-right">
                        @php
                            $price = 0;
                            if(is_object($riga) && method_exists($riga, 'getPrezzoUnitario')) $price = $riga->getPrezzoUnitario();
                            elseif(is_object($riga) && isset($riga->prezzo_unitario)) $price = $riga->prezzo_unitario;
                            elseif(is_array($riga) && isset($riga['prezzo_unitario'])) $price = $riga['prezzo_unitario'];
                            else $price = $riga->unit_price ?? 0;
                        @endphp
                        {{ number_format($price, 4, ',', '.') }} €
                    </td>
                    <td class="text-center">
                        @php
                            $iva = 0;
                            if(is_object($riga) && method_exists($riga, 'getAliquotaIVA')) $iva = $riga->getAliquotaIVA();
                            elseif(is_object($riga) && isset($riga->aliquota_iva)) $iva = $riga->aliquota_iva;
                            elseif(is_array($riga) && isset($riga['aliquota_iva'])) $iva = $riga['aliquota_iva'];
                            else $iva = 22;
                        @endphp
                        {{ $iva }}%
                    </td>
                    <td class="text-right">
                        @php
                            $totale = 0;
                            if(is_object($riga) && method_exists($riga, 'getPrezzoTotale')) $totale = $riga->getPrezzoTotale();
                            elseif(is_object($riga) && isset($riga->prezzo_totale)) $totale = $riga->prezzo_totale;
                            elseif(is_array($riga) && isset($riga['prezzo_totale'])) $totale = $riga['prezzo_totale'];
                            else $totale = ($riga->quantity ?? 0) * ($riga->unit_price ?? 0);
                        @endphp
                        {{ number_format($totale, 2, ',', '.') }} €
                    </td>
                </tr>
                @empty
                    <tr><td colspan="6" class="text-center">Nessuna riga disponibile</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-right"><strong>TOTALE DOCUMENTO</strong></td>
                    <td class="text-right"><strong>{{ number_format($importoTotale ?? $invoice->importo_totale, 2, ',', '.') }} €</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- RIEPILOGO IVA -->
    @if(($vatSummaries ?? $invoice->vatSummaries)->count() > 0)
    <div class="section">
        <div class="section-title">RIEPILOGO IVA</div>
        <table>
            <thead>
                <tr>
                    <th>Aliquota IVA</th>
                    <th class="text-right">Imponibile (€)</th>
                    <th class="text-right">Imposta (€)</th>
                    <th>Esigibilità</th>
                </tr>
            </thead>
            <tbody>
                @foreach(($vatSummaries ?? $invoice->vatSummaries) as $summary)
                <tr>
                    <td class="text-center">{{ number_format($summary->tax_rate, 2, ',', '.') }}%</td>
                    <td class="text-right">{{ number_format($summary->taxable_amount, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($summary->tax_amount, 2, ',', '.') }}</td>
                    <td class="text-center">{{ $summary->esigibilita_iva }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- DATI PAGAMENTO -->
    @if(($payments ?? $invoice->payments)->count() > 0)
    <div class="section">
        <div class="section-title">DATI PAGAMENTO</div>
        <table>
            <thead>
                <tr>
                    <th>Modalità</th>
                    <th>IBAN</th>
                    <th class="text-center">Scadenza</th>
                    <th class="text-right">Importo</th>
                </tr>
            </thead>
            <tbody>
                @foreach(($payments ?? $invoice->payments) as $payment)
                <tr>
                    <td class="text-left">{{ $payment->payment_method_label ?? $payment->payment_method ?? '-' }}</td>
                    <td class="text-left">{{ $payment->iban ?? '-' }}</td>
                    <td class="text-center">{{ $payment->due_date ? $payment->due_date->format('d/m/Y') : '-' }}</td>
                    <td class="text-right">{{ number_format($payment->amount, 2, ',', '.') }} €</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- FOOTER -->
    <div class="footer">
        <p>Documento generato automaticamente | ID Fattura: {{ $invoice->id }} | Importata il: {{ $invoice->imported_at?->format('d/m/Y H:i:s') ?? '-' }}</p>
        <p>Codice SDI: {{ $invoice->sdi_id ?? 'Non presente' }}</p>
    </div>
</body>
</html>