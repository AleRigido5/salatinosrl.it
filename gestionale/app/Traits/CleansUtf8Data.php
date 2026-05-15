<?php

namespace App\Traits;

trait CleansUtf8Data
{
    /**
     * Pulisce una stringa da caratteri UTF-8 malformati
     */
    protected function cleanUtf8String($string)
    {
        if (is_null($string) || $string === '') {
            return $string;
        }
        
        // Se non è una stringa, restituisci così com'è
        if (!is_string($string)) {
            return $string;
        }
        
        // Forza la conversione a UTF-8
        if (!mb_check_encoding($string, 'UTF-8')) {
            $string = mb_convert_encoding($string, 'UTF-8', 'auto');
        }
        
        // Rimuovi caratteri di controllo non validi
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $string);
        
        // Decodifica e ricodifica per rimuovere caratteri non validi
        $string = iconv('UTF-8', 'UTF-8//IGNORE', $string);
        
        return $string;
    }
    
    /**
     * Pulisce un array ricorsivamente
     */
    protected function cleanArrayUtf8($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_string($value)) {
                    $data[$key] = $this->cleanUtf8String($value);
                } elseif (is_array($value)) {
                    $data[$key] = $this->cleanArrayUtf8($value);
                } elseif (is_object($value)) {
                    // Gestisci oggetti (es. SimpleXMLElement)
                    if (method_exists($value, '__toString')) {
                        $data[$key] = $this->cleanUtf8String((string)$value);
                    }
                }
            }
        } elseif (is_string($data)) {
            $data = $this->cleanUtf8String($data);
        }
        
        return $data;
    }
    
    /**
     * Pulisce un oggetto SimpleXMLElement
     */
    protected function cleanXmlValue($value)
    {
        if ($value instanceof \SimpleXMLElement) {
            $value = (string)$value;
        }
        return $this->cleanUtf8String($value);
    }
    
    /**
     * Pulisce un'intera fattura prima del salvataggio
     */
    protected function cleanInvoiceData($data)
    {
        return $this->cleanArrayUtf8($data);
    }
}