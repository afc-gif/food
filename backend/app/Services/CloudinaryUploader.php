<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CloudinaryUploader
{
    public function upload(UploadedFile $file, ?string $folder = null): string
    {
        [$cloudName, $apiKey, $apiSecret] = $this->credentials();
        $targetFolder = trim((string) ($folder ?: config('services.cloudinary.folder', 'afc')), '/ ');

        if (! $cloudName || ! $apiKey || ! $apiSecret) {
            throw new RuntimeException('Cloudinary credentials are not configured.');
        }

        $timestamp = time();
        $params = [
            'folder' => $targetFolder,
            'timestamp' => $timestamp,
        ];

        $response = Http::timeout(30)->attach(
            'file',
            file_get_contents($file->getRealPath()),
            $file->getClientOriginalName()
        )->post("https://api.cloudinary.com/v1_1/{$cloudName}/image/upload", [
            'api_key' => $apiKey,
            'folder' => $targetFolder,
            'timestamp' => $timestamp,
            'signature' => $this->signature($params, $apiSecret),
        ]);

        if (! $response->successful()) {
            throw new RuntimeException($response->json('error.message') ?: 'Cloudinary upload failed.');
        }

        return $response->json('secure_url');
    }

    private function credentials(): array
    {
        $cloudName = trim((string) config('services.cloudinary.cloud_name'));
        $apiKey = trim((string) config('services.cloudinary.api_key'));
        $apiSecret = trim((string) config('services.cloudinary.api_secret'));

        if ((! $cloudName || ! $apiKey || ! $apiSecret) && config('services.cloudinary.url')) {
            $url = parse_url((string) config('services.cloudinary.url'));
            $cloudName = $cloudName ?: trim($url['host'] ?? '');
            $apiKey = $apiKey ?: trim($url['user'] ?? '');
            $apiSecret = $apiSecret ?: trim($url['pass'] ?? '');
        }

        return [$cloudName, $apiKey, $apiSecret];
    }

    private function signature(array $params, string $apiSecret): string
    {
        ksort($params);

        $payload = collect($params)
            ->map(fn ($value, $key) => "{$key}={$value}")
            ->implode('&');

        return sha1($payload . $apiSecret);
    }
}
