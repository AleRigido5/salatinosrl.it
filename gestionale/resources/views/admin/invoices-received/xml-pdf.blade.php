<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Fattura Elettronica {{ $xmlData['documento']['numero'] ?? $invoice->n_invoice }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 9px; padding: 15px; }
        .invoice-container { max-width: 100%; margin: 0 auto; }
        .header { margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid #4CAF50; text-align: center; }
        .header h1 { font-size: 16px; color: #333; }
        .section { margin-bottom: 12px; }
        .section-title { font-size: 10px; font-weight: bold; background-color: #e8f5e9; padding: 4px 8px; margin-bottom: 8px; border-left: 3px solid #4CAF50; }
        .two-columns { display: flex; gap: 15px; margin-bottom: 12px; }
        .column { flex: 1; }
        .info-row { margin-bottom: 3px; display: flex; }
        .info-label { width: 100px; font-weight: bold; }
        .info-value { flex: 1; }
        .items-table { width: 100%; border-collapse: collapse; font-size: 8px; }
        .items-table th, .items-table td { border: 1px solid #ccc; padding: 5px 3px; }
        .items-table th { background-color: #f0f0f0; text-align: center; }
        .items-table .text-right { text-align: right; }
        .items-table .text-center { text-align: center; }
        .payment-table { width: 100%; border-collapse: collapse; font-size: 8px; margin-top: 10px; }
        .payment-table th, .payment-table td { border: 1px solid #ccc; padding: 5px 3px; }
        .payment-table th { background-color: #f0f0f0; text-align: center; }
        .summary-table { width: 100%; border-collapse: collapse; font-size: 8px; margin-top: 10px; }
        .summary-table th, .summary-table td { border: 1px solid #ccc; padding: 5px 3px; }
        .summary-table th { background-color: #f0f0f0; text-align: center; }
        .footer { margin-top: 15px; padding-top: 8px; border-top: 1px solid #ddd; font-size: 7px; text-align: center; }
        .totals { font-weight: bold; font-size: 10px; margin-top: 10px; text-align: right; }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- HEADER -->
        <div class="header">
            <h1>FATTURA ELETTRONICA</h1>
            <div>{{ $xmlData['documento']['tipo_label'] ?? 'Fattura' }} n. {{ $xmlData['documento']['numero'] ?? $invoice->n_invoice }} del {{ \Carbon\Carbon::parse($xmlData['documento']['data'] ?? $invoice->data_invoice)->format('d/m/Y') }}</div>
        </div>

        <!-- DUE COLONNE -->
        <div class="two-columns">
            <div class="column">
                <div class="section-title">CEDENTE / PRESTATORE</div>
                <div class="info-row"><span class="info-label">Denominazione:</span><span class="info-value">{{ $xmlData['cedente']['denominazione'] ?? $invoice->supplier_name }}</span></div>
                <div class="info-row"><span class="info-label">Partita IVA:</span><span class="info-value">{{ ($xmlData['cedente']['id_paese'] ?? '') . ($xmlData['cedente']['id_codice'] ?? '') ?: ($invoice->entity->partita_iva ?? '-') }}</span></div>
                <div class="info-row"><span class="info-label">Sede:</span><span class="info-value">{{ $xmlData['cedente']['indirizzo'] ?? $invoice->entity->indirizzo ?? '' }}</span></div>
            </div>
            <div class="column">
                <div class="section-title">CESSIONARIO / COMMITTENTE</div>
                <div class="info-row"><span class="info-label">Denominazione:</span><span class="info-value">{{ $xmlData['cessionario']['denominazione'] ?? $invoice->ownership_name }}</span></div>
                <div class="info-row"><span class="info-label">Sede:</span><span class="info-value">{{ $xmlData['cessionario']['indirizzo'] ?? $invoice->ownership->Indirizzo ?? '' }}</span></div>
            </div>
        </div>

        <!-- RIGHE FATTURA -->
        <div class="section">
            <div class="section-title">DETTAGLIO ARTICOLI / SERVIZI</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Cod.</th>
                        <th>Descrizione</th>
                        <th class="text-center">Qtà</th>
                        <th class="text-right">Prezzo Unit.</th>
                        <th class="text-center">Iva%</th>
                        <th class="text-right">Totale</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($xmlData['righe'] as $index => $riga)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $riga['descrizione'] ?? '-' }}</td>
                        <td class="text-center">{{ number_format(floatval(str_replace(',', '.', $riga['quantita'] ?? 0)), 2, ',', '.') }}</td>
                        <td class="text-right">{{ number_format(floatval(str_replace(',', '.', $riga['prezzo_unitario'] ?? 0)), 4, ',', '.') }} €</td>
                        <td class="text-center">{{ $riga['aliquota_iva'] ?? '-' }}%</td>
                        <td class="text-right">{{ number_format(floatval(str_replace(',', '.', $riga['prezzo_totale'] ?? 0)), 2, ',', '.') }} €</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center">Nessuna riga disponibile</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr><td colspan="5" class="text-right"><strong>TOTALE DOCUMENTO</strong></td><td class="text-right"><strong>{{ number_format($invoice->importo_totale, 2, ',', '.') }} €</strong></td></tr>
                </tfoot>
            </table>
        </div>

        <!-- RIEPILOGO IVA -->
        @if($invoice->vatSummaries->count() > 0 || !empty($xmlData['vat_summaries_list']))
        <div class="section">
            <div class="section-title">RIEPILOGO IVA</div>
            <table class="summary-table">
                <thead>
                    <tr>
                        <th>Aliquota IVA</th>
                        <th>Natura</th>
                        <th class="text-right">Imponibile (€)</th>
                        <th class="text-right">Imposta (€)</th>
                        <th>Riferimento Normativo</th>
                        <th>Esigibilità</th>
                    </tr>
                </thead>
                <tbody>
                    @php $summaries = $xmlData['vat_summaries_list'] ?? $invoice->vatSummaries; @endphp
                    @forelse($summaries as $summary)
                    <tr>
                        <td class="text-center">{{ number_format($summary->tax_rate, 2, ',', '.') }}%</td>
                        <td class="text-center">{{ $summary->sdi_nature ?? '-' }}</td>
                        <td class="text-right">{{ number_format($summary->taxable_amount, 2, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($summary->tax_amount, 2, ',', '.') }}</td>
                        <td>{{ $summary->vat_law_reference ?? '-' }}</td>
                        <td class="text-center">{{ $summary->esigibilita_iva }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center">Nessun riepilogo IVA disponibile</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif

        <!-- DATI PAGAMENTO -->
        @if($invoice->payments->count() > 0 || !empty($xmlData['payments_list']))
        <div class="section">
            <div class="section-title">DATI PAGAMENTO</div>
            <table class="payment-table">
                <thead>
                    <tr>
                        <th>Modalità</th>
                        <th>IBAN</th>
                        <th class="text-center">Data Scadenza</th>
                        <th class="text-right">Importo (€)</th>
                        <th>Stato</th>
                    </tr>
                </thead>
                <tbody>
                    @php $payments = $xmlData['payments_list'] ?? $invoice->payments; @endphp
                    @forelse($payments as $payment)
                    <tr>
                        <td>{{ $payment->payment_method_label }}</td>
                        <td>{{ $payment->iban ?? '-' }}</td>
                        <td class="text-center">{{ $payment->due_date ? $payment->due_date->format('d/m/Y') : '-' }}</td>
                        <td class="text-right">{{ number_format($payment->amount, 2, ',', '.') }}</td>
                        <td class="text-center"><span style="background: #f0f0f0; padding: 2px 5px; border-radius: 3px;">{{ $payment->status_label }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center">Nessun pagamento disponibile</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif

        <!-- FOOTER -->
        <div class="footer">
            <p>Documento generato automaticamente | ID Fattura: {{ $invoice->id }} | Importata il: {{ $invoice->imported_at?->format('d/m/Y H:i:s') ?? '-' }}</p>
        </div>
    </div>
</body>
</html>