<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Fattura Elettronica {{ $xmlData['documento']['numero'] ?? $invoice->n_invoice }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9px;
            line-height: 1.3;
            color: #333;
            background: #fff;
            padding: 15px;
        }
        
        .invoice-container {
            max-width: 100%;
            margin: 0 auto;
        }
        
        /* Intestazione */
        .header {
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #4CAF50;
        }
        
        .header h1 {
            font-size: 16px;
            color: #333;
            margin: 0;
            font-weight: bold;
        }
        
        .header .subtitle {
            font-size: 8px;
            color: #666;
            margin-top: 3px;
        }
        
        /* Sezioni */
        .section {
            margin-bottom: 12px;
            page-break-inside: avoid;
        }
        
        .section-title {
            font-size: 10px;
            font-weight: bold;
            background-color: #e8f5e9;
            padding: 4px 8px;
            margin-bottom: 8px;
            border-left: 3px solid #4CAF50;
            color: #2e7d32;
        }
        
        /* Due colonne */
        .two-columns {
            display: flex;
            gap: 15px;
            margin-bottom: 12px;
        }
        
        .column {
            flex: 1;
        }
        
        /* Griglia informazioni */
        .info-grid {
            width: 100%;
        }
        
        .info-row {
            margin-bottom: 3px;
            display: flex;
            flex-wrap: wrap;
        }
        
        .info-label {
            width: 85px;
            font-weight: bold;
            color: #555;
            font-size: 8px;
        }
        
        .info-value {
            flex: 1;
            font-size: 8px;
        }
        
        /* Tabella righe */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5px;
        }
        
        .items-table th {
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            padding: 5px 3px;
            text-align: center;
            font-weight: bold;
        }
        
        .items-table td {
            border: 1px solid #ccc;
            padding: 4px 3px;
            vertical-align: top;
        }
        
        .items-table .text-left {
            text-align: left;
        }
        
        .items-table .text-right {
            text-align: right;
        }
        
        .items-table .text-center {
            text-align: center;
        }
        
        /* Riepilogo IVA */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            margin-bottom: 8px;
        }
        
        .summary-table th {
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            padding: 4px;
            text-align: center;
        }
        
        .summary-table td {
            border: 1px solid #ccc;
            padding: 4px;
        }
        
        .summary-table .text-right {
            text-align: right;
        }
        
        .summary-table .text-center {
            text-align: center;
        }
        
        .summary-table .text-left {
            text-align: left;
        }
        
        /* Tabella pagamento */
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        
        .payment-table th {
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            padding: 4px;
            text-align: center;
        }
        
        .payment-table td {
            border: 1px solid #ccc;
            padding: 4px;
        }
        
        .payment-table .text-right {
            text-align: right;
        }
        
        /* Footer */
        .footer {
            margin-top: 15px;
            padding-top: 8px;
            border-top: 1px solid #ddd;
            font-size: 7px;
            color: #999;
            text-align: center;
        }
        
        hr {
            margin: 8px 0;
            border: none;
            border-top: 1px solid #eee;
        }
        
        .totals-row {
            font-weight: bold;
        }
        
        .badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 7px;
        }
        
        .codice-articolo {
            font-size: 7px;
            line-height: 1.2;
        }
        
        .natura-info {
            font-size: 7px;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        
        <!-- HEADER -->
        <div class="header">
            <h1>FATTURA ELETTRONICA</h1>
            <div class="subtitle">
                {{ $xmlData['documento']['tipo_label'] ?? $invoice->type_invoice_label }} n. {{ $xmlData['documento']['numero'] ?? $invoice->n_invoice }} del {{ \Carbon\Carbon::parse($xmlData['documento']['data'] ?? $invoice->data_invoice)->format('d/m/Y') }}
            </div>
        </div>

        <!-- DUE COLONNE: Cedente e Cessionario -->
        <div class="two-columns">
            <!-- Cedente / Prestatore -->
            <div class="column">
                <div class="section-title">CEDENTE / PRESTATORE</div>
                <div class="info-grid">
                    <div class="info-row">
                        <span class="info-label">Denominazione:</span>
                        <span class="info-value">{{ $xmlData['cedente']['denominazione'] ?? $invoice->entity->ragione_sociale ?? '-' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Partita IVA:</span>
                        <span class="info-value">{{ ($xmlData['cedente']['id_paese'] ?? '') . ($xmlData['cedente']['id_codice'] ?? '') ?: ($invoice->entity->partita_iva ?? '-') }}</span>
                    </div>
                    @if(!empty($xmlData['cedente']['codice_fiscale']))
                    <div class="info-row">
                        <span class="info-label">Codice Fiscale:</span>
                        <span class="info-value">{{ $xmlData['cedente']['codice_fiscale'] }}</span>
                    </div>
                    @endif
                    @if(!empty($xmlData['cedente']['indirizzo']))
                    <div class="info-row">
                        <span class="info-label">Sede:</span>
                        <span class="info-value">{{ $xmlData['cedente']['indirizzo'] ?? '' }} - {{ $xmlData['cedente']['cap'] ?? '' }} {{ $xmlData['cedente']['comune'] ?? '' }} ({{ $xmlData['cedente']['provincia'] ?? '' }})</span>
                    </div>
                    @endif
                    @if(!empty($xmlData['cedente']['telefono']) || !empty($xmlData['cedente']['email']))
                    <div class="info-row">
                        <span class="info-label">Contatti:</span>
                        <span class="info-value">
                            @if(!empty($xmlData['cedente']['telefono']))Tel: {{ $xmlData['cedente']['telefono'] }} @endif
                            @if(!empty($xmlData['cedente']['email'])) @if(!empty($xmlData['cedente']['telefono'])) | @endif Email: {{ $xmlData['cedente']['email'] }} @endif
                        </span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Cessionario / Committente -->
            <div class="column">
                <div class="section-title">CESSIONARIO / COMMITTENTE</div>
                <div class="info-grid">
                    <div class="info-row">
                        <span class="info-label">Denominazione:</span>
                        <span class="info-value">{{ $xmlData['cessionario']['denominazione'] ?? $invoice->ownership->Rag_Soc_intest ?? '-' }}</span>
                    </div>
                    @if(!empty($xmlData['cessionario']['id_paese']) || !empty($xmlData['cessionario']['id_codice']))
                    <div class="info-row">
                        <span class="info-label">Partita IVA:</span>
                        <span class="info-value">{{ ($xmlData['cessionario']['id_paese'] ?? '') . ($xmlData['cessionario']['id_codice'] ?? '') }}</span>
                    </div>
                    @endif
                    @if(!empty($xmlData['cessionario']['codice_fiscale']))
                    <div class="info-row">
                        <span class="info-label">Codice Fiscale:</span>
                        <span class="info-value">{{ $xmlData['cessionario']['codice_fiscale'] }}</span>
                    </div>
                    @endif
                    @if(!empty($xmlData['cessionario']['indirizzo']))
                    <div class="info-row">
                        <span class="info-label">Sede:</span>
                        <span class="info-value">{{ $xmlData['cessionario']['indirizzo'] ?? '' }} - {{ $xmlData['cessionario']['cap'] ?? '' }} {{ $xmlData['cessionario']['comune'] ?? '' }} ({{ $xmlData['cessionario']['provincia'] ?? '' }})</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- DATI GENERALI DEL DOCUMENTO -->
        <div class="section">
            <div class="section-title">DATI GENERALI DEL DOCUMENTO</div>
            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">Tipo documento:</span>
                    <span class="info-value">{{ $xmlData['documento']['tipo_label'] ?? $invoice->type_invoice_label }} ({{ $xmlData['documento']['tipo'] ?? $invoice->type_invoice }})</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Numero fattura:</span>
                    <span class="info-value">{{ $xmlData['documento']['numero'] ?? $invoice->n_invoice }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Data emissione:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($xmlData['documento']['data'] ?? $invoice->data_invoice)->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Divisa:</span>
                    <span class="info-value">{{ $xmlData['documento']['divisa'] ?? $invoice->divisa }}</span>
                </div>
                @if(!empty($xmlData['documento']['causale']))
                <div class="info-row">
                    <span class="info-label">Causale:</span>
                    <span class="info-value">{{ $xmlData['documento']['causale'] }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- TABELLA RIGHE FATTURA -->
        <div class="section">
            <div class="section-title">DETTAGLIO ARTICOLI / SERVIZI</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 8%">Codice Art.</th>
                        <th style="width: 32%">Descrizione</th>
                        <th style="width: 8%" class="text-center">Q.ta</th>
                        <th style="width: 5%" class="text-center">U.M.</th>
                        <th style="width: 10%" class="text-right">Prezzo Unit.</th>
                        <th style="width: 6%" class="text-center">Sconto%</th>
                        <th style="width: 10%" class="text-right">Prezzo Tot.</th>
                        <th style="width: 5%" class="text-center">Iva%</th>
                        <th style="width: 16%" class="text-left">Natura</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($xmlData['righe'] as $index => $riga)
                    @php
                        $prezzoTotale = floatval(str_replace(',', '.', $riga['prezzo_totale'] ?? 0));
                        $aliquota = floatval($riga['aliquota_iva'] ?? 0);
                        $prezzoUnitario = floatval(str_replace(',', '.', $riga['prezzo_unitario'] ?? 0));
                        $quantita = floatval(str_replace(',', '.', $riga['quantita'] ?? 0));
                    @endphp
                    <tr>
                        <!-- Codice Articolo -->
                        <td class="align-top">
                            @if(!empty($riga['codice_articolo']))
                                @foreach($riga['codice_articolo'] as $codice)
                                    <div class="codice-articolo">
                                        <strong>{{ $codice['tipo'] }}:</strong> {{ $codice['valore'] }}
                                    </div>
                                @endforeach
                            @else
                                <span class="codice-articolo">-</span>
                            @endif
                        </td>
                        
                        <!-- Descrizione -->
                        <td class="text-left">{{ $riga['descrizione'] ?? '-' }}</td>
                        
                        <!-- Quantità -->
                        <td class="text-center">{{ number_format($quantita, 3, ',', '.') }}</td>
                        
                        <!-- Unità di Misura -->
                        <td class="text-center">{{ $riga['unita_misura'] ?? '-' }}</td>
                        
                        <!-- Prezzo Unitario -->
                        <td class="text-right">{{ number_format($prezzoUnitario, 4, ',', '.') }} €</td>
                        
                        <!-- Sconto -->
                        <td class="text-center">{{ $riga['sconto'] ?? '-' }}</td>
                        
                        <!-- Prezzo Totale -->
                        <td class="text-right">{{ number_format($prezzoTotale, 2, ',', '.') }} €</td>
                        
                        <!-- Iva % -->
                        <td class="text-center">@if($aliquota > 0){{ number_format($aliquota, 2, ',', '.') }}% @else - @endif</td>
                        
                        <!-- Natura -->
                        <td class="text-left">
                            @if(!empty($riga['natura']))
                                <div class="natura-info">
                                    <strong>{{ $riga['natura'] }}</strong>
                                    @if(!empty($riga['natura_label']))
                                        <br><span style="font-size: 6px; color: #666;">{{ $riga['natura_label'] }}</span>
                                    @endif
                                    @if(!empty($riga['riferimento_amministrativo']))
                                        <br><span style="font-size: 6px; color: #999;">Rif. Amm.: {{ $riga['riferimento_amministrativo'] }}</span>
                                    @endif
                                </div>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center">Nessuna riga disponibile</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- RIEPILOGO IVA E TOTALI -->
        <div class="section">
            <div class="section-title">RIEPILOGO IVA</div>
            <table class="summary-table">
                <thead>
                    <tr>
                        <th style="width: 20%">Aliquota IVA</th>
                        <th style="width: 20%">Imponibile (€)</th>
                        <th style="width: 20%">Imposta (€)</th>
                        <th style="width: 20%">Esigibilità</th>
                        <th style="width: 20%">Riferimento Normativo</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $ivaGroup = [];
                        foreach($xmlData['righe'] as $riga) {
                            $aliquota = floatval($riga['aliquota_iva'] ?? 0);
                            if ($aliquota == 0 && !empty($riga['natura'])) {
                                $aliquota = $riga['natura'];
                            }
                            $prezzoTotale = floatval(str_replace(',', '.', $riga['prezzo_totale'] ?? 0));
                            if ($aliquota > 0) {
                                $imponibile = $prezzoTotale / (1 + ($aliquota / 100));
                                $imposta = $prezzoTotale - $imponibile;
                            } else {
                                $imponibile = $prezzoTotale;
                                $imposta = 0;
                            }
                            if (!isset($ivaGroup[$aliquota])) {
                                $ivaGroup[$aliquota] = [
                                    'imponibile' => 0, 
                                    'imposta' => 0,
                                    'natura' => $riga['natura'] ?? null,
                                    'riferimento' => $riga['riferimento_amministrativo'] ?? null
                                ];
                            }
                            $ivaGroup[$aliquota]['imponibile'] += $imponibile;
                            $ivaGroup[$aliquota]['imposta'] += $imposta;
                        }
                        $totaleImponibile = 0;
                        $totaleImposta = 0;
                    @endphp
                    @foreach($ivaGroup as $aliquota => $values)
                    @php
                        $totaleImponibile += $values['imponibile'];
                        $totaleImposta += $values['imposta'];
                    @endphp
                    <tr>
                        <td class="text-center">
                            @if(is_numeric($aliquota) && $aliquota > 0)
                                {{ number_format($aliquota, 2, ',', '.') }}%
                            @else
                                {{ $aliquota }}
                            @endif
                        </td>
                        <td class="text-right">{{ number_format($values['imponibile'], 2, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($values['imposta'], 2, ',', '.') }}</td>
                        <td class="text-center">I</td>
                        <td class="text-left">{{ $values['riferimento'] ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td class="text-right"><strong>TOTALI</strong></td>
                        <td class="text-right"><strong>{{ number_format($totaleImponibile, 2, ',', '.') }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($totaleImposta, 2, ',', '.') }}</strong></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- RIGA TOTALI -->
        <div class="section">
            <table class="summary-table">
                <tr>
                    <td style="width: 20%"><strong>Importo bollo</strong><br>€ {{ number_format($xmlData['bollo'] ?? 0, 2, ',', '.') }}</td>
                    <td style="width: 20%"><strong>Sconto/Maggiorazione</strong><br>€ 0,00</td>
                    <td style="width: 15%"><strong>Arrotondamento</strong><br>€ {{ number_format($xmlData['arrotondamento'] ?? 0, 2, ',', '.') }}</td>
                    <td style="width: 20%"><strong>Valuta</strong><br>EUR</td>
                    <td style="width: 25%"><strong>Totale documento</strong><br><strong style="font-size: 11px; color: #2e7d32;">{{ number_format($invoice->importo_totale, 2, ',', '.') }} €</strong></td>
                </tr>
            </table>
        </div>

        <!-- DATI PAGAMENTO -->
        @if(!empty($xmlData['pagamenti']))
        <div class="section">
            <div class="section-title">DATI PAGAMENTO</div>
            <table class="payment-table">
                <thead>
                    <tr>
                        <th style="width: 25%">Modalità pagamento</th>
                        <th style="width: 30%">IBAN</th>
                        <th style="width: 25%">Istituto</th>
                        <th style="width: 10%" class="text-center">Data scadenza</th>
                        <th style="width: 10%" class="text-right">Importo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-left">{{ $xmlData['pagamenti']['modalita'] ?? 'MP08 Carta di pagamento' }}</td>
                        <td class="text-left">{{ $xmlData['pagamenti']['iban'] ?? '' }}</td>
                        <td class="text-left">{{ $xmlData['pagamenti']['istituto'] ?? '' }}</td>
                        <td class="text-center">{{ !empty($xmlData['pagamenti']['scadenza']) ? \Carbon\Carbon::parse($xmlData['pagamenti']['scadenza'])->format('d/m/Y') : '-' }}</td>
                        <td class="text-right">{{ number_format(floatval(str_replace(',', '.', $xmlData['pagamenti']['importo'] ?? $invoice->importo_totale)), 2, ',', '.') }} €</td>
                    </tr>
                </tbody>
            </table>
        </div>
        @endif

        <!-- FOOTER -->
        <div class="footer">
            <p>Documento generato automaticamente dal Sistema di Fatturazione Elettronica</p>
            <p>ID Fattura: {{ $invoice->id }} | Importata il: {{ $invoice->imported_at ? $invoice->imported_at->format('d/m/Y H:i:s') : '-' }} | Codice SDI: {{ $invoice->sdi_id ?? 'Non presente' }}</p>
        </div>
        
    </div>
</body>
</html>