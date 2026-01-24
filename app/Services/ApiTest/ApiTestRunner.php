<?php

namespace App\Services\ApiTest;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response; // <--- MUST HAVE THIS
use Illuminate\Support\Str;

class ApiTestRunner
{
    public static function run(array $context, array $scenarios): array
    {
        set_time_limit(0);
        
        $results = [];

        foreach ($scenarios as $scenario) {
            $vars = array_merge($context, [
                'hashkey' => md5(($context['api_key'] ?? '') . ($context['access_token'] ?? '')),
                'datesent' => now()->format('Y-m-d H:i:s'),
                'transactionId' => 'TR-' . Str::upper(Str::random(10)) . '-ID',
            ]);

            $url = self::parse($scenario['url'], $vars);
            $body = self::parse($scenario['body_template'], $vars);

            $payload = json_decode($body, true) ?: [];

            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                            ->timeout(10)
                            ->post($url, $payload);

            if (!($response instanceof Response)) {
                continue;
            }
               
            $results[] = [
                'name' => $scenario['name'],
                'passed' => $response->successful(),
                'status' => $response->status(),
                'sent_body' => $payload, 
                'assertions' => [
                    'HTTP 200' => $response->status() === 200,
                    'Valid JSON' => $response->json() !== null,
                ],
                'response' => $response->json() ?? ['raw_error' => $response->body()], 
            ];
        }

        return $results;
    }

    private static function parse(string $text, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $text = str_replace('{{' . $key . '}}', (string)$value, $text);
        }
        return $text;
    }
}