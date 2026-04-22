<?php
// public/vademecum/index.php

// Trova tutti i file PDF nella cartella corrente
$pdfFiles = glob('*.pdf');

if (!empty($pdfFiles)) {
    // Prende il primo PDF trovato (qualunque sia il nome)
    $pdfFile = $pdfFiles[0];
    
    // Imposta gli header per visualizzare il PDF nel browser
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $pdfFile . '"');
    header('Content-Length: ' . filesize($pdfFile));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // Legge e restituisce il file
    readfile($pdfFile);
    exit;
}

// Se non trova nessun PDF
http_response_code(404);
echo "Nessun file PDF trovato nella cartella vademecum";
?>