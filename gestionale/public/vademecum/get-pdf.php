<?php
// public/vademecum/get-pdf.php

// Trova tutti i PDF
$files = glob('*.pdf');

if (count($files) > 0) {
    $file = $files[0];
    
    // Headers per il PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $file . '"');
    header('Content-Length: ' . filesize($file));
    
    // Output del file
    readfile($file);
    exit;
}

// Nessun PDF trovato
http_response_code(404);
echo 'Nessun file PDF trovato';
?>