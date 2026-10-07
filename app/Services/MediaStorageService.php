<?php

namespace App\Services;

use Cloudinary\Asset\DeliveryType;
use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class MediaStorageService
{
    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct(private ?Cloudinary $client = null) {}

    public function storeScanImage(
        UploadedFile $file,
        int $userId,
        string $scanUuid,
        string $kind
    ): array {
        $folder = match ($kind) {
            'receipt' => 'receipt',
            'composition_label' => 'composition',
            default => 'product',
        };

        return $this->upload(
            $file,
            "textilecycle/scans/{$userId}/{$scanUuid}/{$folder}",
            $kind === 'receipt' ? DeliveryType::AUTHENTICATED : DeliveryType::UPLOAD
        );
    }

    public function storeProductImage(UploadedFile $file): array
    {
        if (config('media.product_storage') === 'public') {
            $mime = $file->getMimeType();
            if (! $file->isValid() || ! in_array($mime, self::ALLOWED_MIME_TYPES, true)) {
                throw new RuntimeException('Le fichier envoyé n’est pas une image JPEG, PNG ou WebP valide.');
            }

            $path = $file->store('products', 'public');
            if (! $path) {
                throw new RuntimeException('La photo n’a pas pu être enregistrée dans le stockage local.');
            }

            return [
                'path' => $path,
                'storage_disk' => 'public',
                'cloudinary_public_id' => null,
                'secure_url' => null,
                'delivery_type' => null,
                'format' => $file->guessExtension(),
                'version' => null,
            ];
        }

        return $this->upload($file, 'textilecycle/products', DeliveryType::UPLOAD);
    }

    public function url(
        ?string $path,
        ?string $disk = null,
        ?string $publicId = null,
        ?string $secureUrl = null,
        ?string $deliveryType = null,
        ?string $format = null,
        int|string|null $version = null
    ): string {
        if ($disk === 'cloudinary' && $publicId) {
            try {
                $asset = $this->cloudinary()->image($publicId)
                    ->deliveryType($deliveryType ?: DeliveryType::UPLOAD)
                    ->signUrl();

                if ($format) {
                    $asset->extension($format);
                }
                if ($version) {
                    $asset->version($version);
                }

                return $asset->toUrl();
            } catch (Throwable $exception) {
                $this->reportStorageError('url', $exception);

                return asset('images/placeholders/media-unavailable.svg');
            }
        }

        $localDisk = $this->localDisk($disk);
        if ($localDisk && $this->isSafeLocalPath($path) && Storage::disk($localDisk)->exists($path)) {
            return Storage::disk($localDisk)->url($path);
        }

        return asset('images/placeholders/media-unavailable.svg');
    }

    public function exists(
        ?string $path,
        ?string $disk = null,
        ?string $publicId = null,
        ?string $deliveryType = null
    ): bool {
        if ($disk === 'cloudinary') {
            if (! $this->isOwnedCloudinaryId($publicId)) {
                return false;
            }

            try {
                $this->cloudinary()->adminApi()->asset($publicId, [
                    'resource_type' => 'image',
                    'type' => $deliveryType ?: DeliveryType::UPLOAD,
                ]);

                return true;
            } catch (Throwable $exception) {
                $this->reportStorageError('exists', $exception);

                return false;
            }
        }

        $localDisk = $this->localDisk($disk);

        return $localDisk && $this->isSafeLocalPath($path) && Storage::disk($localDisk)->exists($path);
    }

    public function delete(
        ?string $path,
        ?string $disk = null,
        ?string $publicId = null,
        ?string $deliveryType = null
    ): bool {
        if ($disk === 'cloudinary') {
            if (! $this->isOwnedCloudinaryId($publicId)) {
                return false;
            }

            try {
                $result = $this->cloudinary()->uploadApi()->destroy($publicId, [
                    'resource_type' => 'image',
                    'type' => $deliveryType ?: DeliveryType::UPLOAD,
                    'invalidate' => true,
                ]);

                return in_array($result['result'] ?? null, ['ok', 'not found'], true);
            } catch (Throwable $exception) {
                $this->reportStorageError('delete', $exception);

                return false;
            }
        }

        $localDisk = $this->localDisk($disk);

        return (bool) ($localDisk && $this->isSafeLocalPath($path) && Storage::disk($localDisk)->delete($path));
    }

    public function downloadToTemporaryFile(
        ?string $path,
        ?string $disk = null,
        ?string $publicId = null,
        ?string $secureUrl = null,
        ?string $deliveryType = null,
        ?string $format = null,
        int|string|null $version = null
    ): ?string {
        $temporaryPath = sys_get_temp_dir().'/textilecycle-media-'.Str::uuid().'.'.($format ?: 'img');

        try {
            if ($disk === 'cloudinary') {
                if (! $this->isOwnedCloudinaryId($publicId)) {
                    return null;
                }

                // The URL is always generated from our stored public ID. An arbitrary remote
                // URL supplied by a user is never accepted here.
                $downloadUrl = $this->url(
                    $path,
                    $disk,
                    $publicId,
                    $secureUrl,
                    $deliveryType,
                    $format,
                    $version
                );
                if (str_contains($downloadUrl, '/images/placeholders/media-unavailable.svg')) {
                    return null;
                }
                $response = Http::timeout(25)->retry(2, 200)->get($downloadUrl);
                if (! $response->successful()) {
                    return null;
                }
                file_put_contents($temporaryPath, $response->body(), LOCK_EX);
            } else {
                $localDisk = $this->localDisk($disk);
                if (! $localDisk || ! $this->isSafeLocalPath($path) || ! Storage::disk($localDisk)->exists($path)) {
                    return null;
                }
                file_put_contents($temporaryPath, Storage::disk($localDisk)->get($path), LOCK_EX);
            }

            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
            if (! in_array($mime, self::ALLOWED_MIME_TYPES, true)) {
                @unlink($temporaryPath);

                return null;
            }

            return $temporaryPath;
        } catch (Throwable $exception) {
            @unlink($temporaryPath);
            $this->reportStorageError('download', $exception);

            return null;
        }
    }

    private function upload(UploadedFile $file, string $folder, string $deliveryType): array
    {
        $mime = $file->getMimeType();
        if (! $file->isValid() || ! in_array($mime, self::ALLOWED_MIME_TYPES, true)) {
            throw new RuntimeException('Le fichier envoyé n’est pas une image JPEG, PNG ou WebP valide.');
        }

        try {
            $result = $this->cloudinary()->uploadApi()->upload($file->getRealPath(), [
                'resource_type' => 'image',
                'folder' => $folder,
                'public_id' => (string) Str::uuid(),
                'unique_filename' => false,
                'overwrite' => false,
                'type' => $deliveryType,
            ]);

            return [
                'path' => $result['public_id'],
                'storage_disk' => 'cloudinary',
                'cloudinary_public_id' => $result['public_id'],
                'secure_url' => $result['secure_url'] ?? null,
                'delivery_type' => $result['type'] ?? $deliveryType,
                'format' => $result['format'] ?? $file->guessExtension(),
                'version' => $result['version'] ?? null,
            ];
        } catch (Throwable $exception) {
            $this->reportStorageError('upload', $exception);
            throw new RuntimeException('Le stockage sécurisé de l’image a échoué. Veuillez réessayer.', 0, $exception);
        }
    }

    public function storeRoadmapProof(UploadedFile $file, int $roadmapId): array
    {
        $name = $file->getClientOriginalName();
        if (config('roadmaps.proof_storage') === 'local') {
            $path = $file->store('roadmap-proofs/'.$roadmapId, 'local');
            if (! $path) {
                throw new RuntimeException('La preuve n’a pas pu être enregistrée.');
            }

            return ['storage' => 'local', 'path' => $path, 'name' => $name];
        }
        $result = $this->cloudinary()->uploadApi()->upload($file->getRealPath(), [
            'resource_type' => 'raw', 'type' => 'authenticated', 'folder' => 'textilecycle/roadmaps/'.$roadmapId,
            'public_id' => (string) Str::uuid().'.'.$file->guessExtension(), 'overwrite' => false,
        ]);

        return ['storage' => 'cloudinary', 'path' => $result['public_id'], 'version' => $result['version'] ?? null, 'name' => $name];
    }

    public function readRoadmapProof(array $file): string
    {
        if ($file['storage'] === 'local') {
            abort_unless(str_starts_with($file['path'], 'roadmap-proofs/') && ! str_contains($file['path'], '..') && Storage::disk('local')->exists($file['path']), 404);

            return Storage::disk('local')->get($file['path']);
        }
        abort_unless(str_starts_with($file['path'], 'textilecycle/roadmaps/') && ! str_contains($file['path'], '..'), 404);
        $asset = $this->cloudinary()->raw($file['path'])->deliveryType('authenticated')->signUrl();
        if (! empty($file['version'])) {
            $asset->version($file['version']);
        }

        return Http::connectTimeout(10)->timeout(30)->get($asset->toUrl())->throw()->body();
    }

    public function deleteRoadmapProof(array $file): void
    {
        if ($file['storage'] === 'local' && str_starts_with($file['path'], 'roadmap-proofs/') && ! str_contains($file['path'], '..')) {
            Storage::disk('local')->delete($file['path']);
        } elseif ($file['storage'] === 'cloudinary' && str_starts_with($file['path'], 'textilecycle/roadmaps/') && ! str_contains($file['path'], '..')) {
            $this->cloudinary()->uploadApi()->destroy($file['path'], ['resource_type' => 'raw', 'type' => 'authenticated']);
        }
    }

    private function cloudinary(): Cloudinary
    {
        if ($this->client) {
            return $this->client;
        }

        $url = $this->normalizedCloudinaryUrl(config('services.cloudinary.url'));
        $credentials = [
            'cloud_name' => trim((string) config('services.cloudinary.cloud_name')),
            'api_key' => trim((string) config('services.cloudinary.api_key')),
            'api_secret' => trim((string) config('services.cloudinary.api_secret')),
        ];
        $hasCompleteCredentials = ! in_array('', $credentials, true);

        if ($url !== null) {
            $configuration = $url;
        } elseif ($hasCompleteCredentials) {
            $configuration = [
                'cloud' => $credentials,
                'url' => ['secure' => (bool) config('services.cloudinary.secure', true)],
            ];
        } else {
            throw new RuntimeException(
                'Cloudinary n’est pas configuré correctement. Définissez CLOUDINARY_URL ou les trois variables CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY et CLOUDINARY_API_SECRET.'
            );
        }

        return $this->client = new Cloudinary($configuration);
    }

    private function normalizedCloudinaryUrl(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $url = trim($value, " \t\n\r\0\x0B\"'");
        $url = preg_replace('/^CLOUDINARY_URL\\s*=\\s*/i', '', $url) ?? $url;
        $parts = parse_url($url);

        if (($parts['scheme'] ?? null) !== 'cloudinary'
            || blank($parts['user'] ?? null)
            || blank($parts['pass'] ?? null)
            || blank($parts['host'] ?? null)
            || str_contains($url, '<')
            || str_contains($url, '>')) {
            return null;
        }

        return $url;
    }

    private function isOwnedCloudinaryId(?string $publicId): bool
    {
        return is_string($publicId)
            && str_starts_with($publicId, 'textilecycle/')
            && ! str_contains($publicId, '..')
            && ! str_contains($publicId, '://');
    }

    private function isSafeLocalPath(?string $path): bool
    {
        return is_string($path)
            && $path !== ''
            && ! str_starts_with($path, '/')
            && ! str_contains($path, '..')
            && ! str_contains($path, '://');
    }

    private function localDisk(?string $disk): ?string
    {
        $disk ??= 'public';

        return in_array($disk, ['public', 'local'], true) ? $disk : null;
    }

    private function reportStorageError(string $operation, Throwable $exception): void
    {
        Log::warning('Échec du stockage média.', [
            'operation' => $operation,
            'exception' => $exception::class,
        ]);
    }
}
