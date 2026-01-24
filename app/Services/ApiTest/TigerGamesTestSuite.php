<?php

namespace App\Services\ApiTest;

use Illuminate\Support\Facades\File;

class TigerGamesTestSuite
{
    public static function requests(): array
    {
        // Use storage_path to get the absolute path on your Windows machine
        $fullPath = storage_path('app/json_collections/uat_v8.2.json');

        if (!File::exists($fullPath)) {
            throw new \Exception("File not found! System looked at: " . $fullPath);
        }

        $json = File::get($fullPath);
        $collection = json_decode($json, true);
        
        $scenarios = [];

        if (!isset($collection['item'])) {
             throw new \Exception("Invalid JSON structure: 'item' key missing.");
        }

        foreach ($collection['item'] as $folder) {
            if (isset($folder['item'])) {
                foreach ($folder['item'] as $request) {
                    $scenarios[] = [
                        'name' => $request['name'],
                        'method' => $request['request']['method'],
                        'url' => $request['request']['url']['raw'] ?? '',
                        'body_template' => $request['request']['body']['raw'] ?? '',
                    ];
                }
            }
        }

        return $scenarios;
    }
}