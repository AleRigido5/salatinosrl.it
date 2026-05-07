<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fatture di Acquisto</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 1px solid #ccc; padding-bottom: 10px; }
        .header h1 { font-size: 18px; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
        .footer { position: fixed; bottom: 20px; text-align: center; font-size: 8px; color: #666; width: 100%; }
    </style>
</head>
<body>
    <div class="header">
        <h1>ELENCO FATTURE DI ACQUISTO</h1>
        <p>Data estrazione: {{ date('d/m/Y H:i:s') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>N. Fattura</th>
                <th>Data</th>
                <th>Fornitore</th>
                <th class="text-right">Totale</th>
                <th>Stato</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoices as $invoice)
            <tr>
                <td>{{ $invoice->n_invoice }}</td>
                <td>{{ $invoice->data_invoice->format('d/m/Y') }}</td>
                <td>{{ $invoice->supplier_name }}</td>
                <td class="text-right">{{ number_format($invoice->importo_totale, 2, ',', '.') }} €</td>
                <td>{{ $invoice->status_label }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #f5f5f5;">
                <td colspan="3" class="text-right"><strong>TOTALE GENERALE</strong></td>
                <td class="text-right"><strong>{{ number_format($invoices->sum('importo_totale'), 2, ',', '.') }} €</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>Documento generato automaticamente</p>
    </div>
</body>
</html>