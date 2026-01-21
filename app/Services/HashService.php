<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

class HashService
{
    /**
     * Replicates the Go GenerateDynamicHash logic
     */
    public static function generate(array $data): string
    {
        $key = env('MAGIC');
        
        // 1. Sort keys alphabetically
        ksort($data);

        $concatenatedData = "";

        // 2. Concatenate values
        foreach ($data as $k => $v) {
            if ($k === 'hash') continue; // Skip if hash is already in array

            // Replicate Go's fmt.Sprintf("%.0f", v) for numbers
            if (is_numeric($v)) {
                $concatenatedData .= number_format((float)$v, 0, '.', '');
            } else {
                $concatenatedData .= (string)$v;
            }
        }

        // 3. SHA-256 (concatenatedData + key)
        return hash('sha256', $concatenatedData . $key);
    }
}