<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>XML Fattura {{ $invoice->n_invoice }}</title>
    <style>
        body {
            font-family: monospace;
            font-size: 12px;
            margin: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        pre {
            background: #f8f8f8;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .header {
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>XML Originale Fattura {{ $invoice->n_invoice }}</h2>
            <p>Data: {{ $invoice->data_invoice->format('d/m/Y') }} | Fornitore: {{ $invoice->supplier_name }}</p>
        </div>
        <pre>{{ $xmlContent }}</pre>
    </div>
</body>
</html>