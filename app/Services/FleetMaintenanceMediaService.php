<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\VehicleMaintenanceLog;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

class FleetMaintenanceMediaService
{
    public const IMAGE_MAX_MB_SETTING = 'fleet_maintenance_image_max_mb';

    public const DEFAULT_IMAGE_MAX_MB = 5;

    public const MAX_PHOTO_COUNT = 10;

    public function imageMaxMb(): int
    {
        return max(1, min(50, (int) AppSetting::value(self::IMAGE_MAX_MB_SETTING, (string) self::DEFAULT_IMAGE_MAX_MB)));
    }

    public function imageRules(): array
    {
        return [
            'photos' => ['nullable', 'array', 'max:'.self::MAX_PHOTO_COUNT],
            'photos.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.($this->imageMaxMb() * 1024)],
        ];
    }

    public function youtubeUrlRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || trim($value) === '') {
                return;
            }

            $url = parse_url(trim($value));
            $host = strtolower($url['host'] ?? '');
            $scheme = strtolower($url['scheme'] ?? '');
            $allowedHosts = ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'www.youtu.be', 'youtube-nocookie.com', 'www.youtube-nocookie.com'];

            if (! in_array($scheme, ['http', 'https'], true) || ! in_array($host, $allowedHosts, true) || empty($url['path'])) {
                $fail('The YouTube link must be a valid YouTube video URL.');
            }
        };
    }

    /**
     * @param  array<int, UploadedFile>  $photos
     * @return array<int, string>
     */
    public function attachPhotos(VehicleMaintenanceLog $log, array $photos): array
    {
        $storedPaths = [];

        try {
            foreach ($photos as $photo) {
                $path = $photo->store('fleet/maintenance/'.now()->format('Y/m'), 'local');
                $storedPaths[] = $path;
                $log->photos()->create([
                    'path' => $path,
                    'original_name' => $photo->getClientOriginalName(),
                    'mime_type' => $photo->getMimeType() ?: 'application/octet-stream',
                    'size' => $photo->getSize(),
                ]);
            }
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }

        return $storedPaths;
    }
}
