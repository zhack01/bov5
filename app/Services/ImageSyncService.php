<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Client\Response; // Add this import

class ImageSyncService
{
    public function pullToAssetServer($filename, $uniGameCode, $partner): bool
    {
        $env = app()->environment('production') ? 'prod' : 'stage';
        $path = "/images/games/casino/{$env}/{$partner}/";
        $wgetUrl = config('app.url') . "/storage/uploads/" . $filename;

        /** @var Response $response */ // This docblock also helps Intelephense
        $response = Http::withHeaders([
            'hashen' => 'lm3VDFvlx8eoNK71OneLifroUmxiFux0U5Za86iAj2o=',
        ])->post(env('IMAGE_PULL_URL') . '/api/imagepull', [
            'wget'     => $wgetUrl,
            'endpoint' => $path,
            'name'     => $filename,
        ]);

        if ($response->successful() && $response->json('status') == 200) {
            Storage::disk('public')->delete('uploads/' . $filename);
            return true;
        }

        return false;
    }
}