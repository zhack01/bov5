<?php

namespace App\Services\ApiTest;

use Illuminate\Support\Facades\File;

class NewmanRunner
{
    public static function run(string $roundId, array $dbValues): array
    {
        $storagePath = storage_path('app/newman');
        File::ensureDirectoryExists($storagePath);
        File::ensureDirectoryExists(public_path('reports'));

        // 1. Build the Environment Values based on SURGE_STAGING.json
        $envValues = [];

        // Map database columns to the exact keys from your provided JSON
        $mapping = [
            'gamename'          => $dbValues['gamename'] ?? '',
            'gameid'            => $dbValues['gameid'] ?? '',
            'providername'      => $dbValues['providername'] ?? '',
            'providercode'      => $dbValues['providercode'] ?? '',
            'player_username'   => $dbValues['player_username'] ?? '',
            'client_player_id'  => $dbValues['client_player_id'] ?? '',
            'token'             => $dbValues['token'] ?? '',
            'client_id'         => $dbValues['client_id'] ?? '',
            'currencycode'      => $dbValues['currencycode'] ?? '',
            'access_token'      => $dbValues['access_token'] ?? '',
            'api_key'           => $dbValues['api_key'] ?? '',
            'player_details_url'=> $dbValues['player_details_url'] ?? '',
            'fund_transfer_url' => $dbValues['fund_transfer_url'] ?? '',
            'check_transfer_url'=> $dbValues['check_transfer_url'] ?? '',
            'roundId'           => $roundId,
            'amount'            => '5', // Default from your config
            'is_test'           => 'true',
        ];

        foreach ($mapping as $key => $value) {
            $envValues[] = [
                'key' => $key,
                'value' => (string)$value,
                'enabled' => true
            ];
        }

        // 2. Add dynamic calculated variables
        $envValues[] = [
            'key' => 'hashkey', 
            'value' => md5(($dbValues['api_key'] ?? '') . ($dbValues['access_token'] ?? '')), 
            'enabled' => true
        ];
        $envValues[] = [
            'key' => 'datesent', 
            'value' => now()->format('Y-m-d H:i:s'), 
            'enabled' => true
        ];

        $envFileContent = [
            'name' => "Dynamic_Env_$roundId",
            'values' => $envValues,
            '_postman_variable_scope' => 'environment',
        ];

        $envFile = "$storagePath/env_$roundId.json";
        File::put($envFile, json_encode($envFileContent));

        // 3. Run Newman
        $collection = storage_path('app/json_collections/uat_v8.2.json');
        $reportName = "newman_report_{$roundId}.html";
        $reportPath = public_path("reports/$reportName");

        $command = "newman run \"$collection\" -e \"$envFile\" -r cli,htmlextra --reporter-htmlextra-export \"$reportPath\" --reporter-htmlextra-darkTheme 2>&1";

        $output = shell_exec($command);

        return [
            'report_url' => asset("reports/$reportName"),
            'output' => $output
        ];
    }
}