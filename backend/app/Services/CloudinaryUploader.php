<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CloudinaryUploader
{
    public function upload(UploadedFile $file, ?string $folder = null): string
    {
        $cloudName = config('services.cloudinary.cloud_name');
        $apiKey = config('services.cloudinary.api_key');
        $apiSecret = config('services.cloudinary.api_secret');
        $targetFolder = $folder ?: config('services.cloudinary.folder', 'afc');

        if (! $cloudName || ! $apiKey || ! $apiSecret) {
            throw new RuntimeException('Cloudinary credentials are not configured.');
        }

        $timestamp = time();
        $params = [
            'folder' => $targetFolder,
            'timestamp' => $timestamp,
        ];

        $response = Http::attach(
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

    private function signature(array $params, string $apiSecret): string
    {
        ksort($params);

        $payload = collect($params)
            ->map(fn ($value, $key) => "{$key}={$value}")
            ->implode('&');

        return sha1($payload . $apiSecret);
    }
}
