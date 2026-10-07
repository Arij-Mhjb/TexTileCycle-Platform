<?php

namespace Tests\Feature;

use App\Models\Scan;
use App\Models\ScanAsset;
use App\Models\User;
use App\Services\MediaStorageService;
use App\Services\MultimodalScanService;
use Cloudinary\Cloudinary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class CloudinarySmartScanTest extends TestCase
{
    use RefreshDatabase;

    public function test_consumer_creates_scan_with_three_durable_images(): void
    {
        $user = User::factory()->create(['role' => 'consumer']);
        $media = Mockery::mock(MediaStorageService::class);
        $sequence = 0;
        $media->shouldReceive('storeScanImage')->times(3)->andReturnUsing(
            function ($file, $userId, $scanUuid, $kind) use (&$sequence) {
                $sequence++;

                return $this->cloudinaryReference("textilecycle/scans/{$userId}/{$scanUuid}/{$kind}/image-{$sequence}", $kind);
            }
        );
        $this->app->instance(MediaStorageService::class, $media);

        $analysis = Mockery::mock(MultimodalScanService::class);
        $analysis->shouldReceive('analyse')->once()->andReturn(['confidence' => 0]);
        $this->app->instance(MultimodalScanService::class, $analysis);

        $response = $this->actingAs($user)->post(route('scans.store'), [
            'source_type' => 'multimodal',
            'privacy_consent' => '1',
            'receipt_image' => UploadedFile::fake()->image('ticket.jpg'),
            'label_image' => UploadedFile::fake()->image('composition.png'),
            'product_image' => UploadedFile::fake()->image('produit.webp'),
        ]);

        $scan = Scan::with('assets')->firstOrFail();
        $response->assertRedirect(route('scans.show', $scan));
        $this->assertCount(3, $scan->assets);
        $this->assertSame(['cloudinary'], $scan->assets->pluck('storage_disk')->unique()->values()->all());
        $this->assertTrue($scan->assets->every(fn ($asset) => str_starts_with($asset->cloudinary_public_id, 'textilecycle/scans/')));
        $this->assertSame('authenticated', $scan->assets->firstWhere('type', 'receipt')->delivery_type);
    }

    public function test_service_generates_secure_cloudinary_url_without_exposing_secret(): void
    {
        $service = new MediaStorageService(new Cloudinary('cloudinary://api-key:super-secret@demo'));
        $url = $service->url(
            'textilecycle/scans/1/test/receipt/id',
            'cloudinary',
            'textilecycle/scans/1/test/receipt/id',
            null,
            'authenticated',
            'jpg',
            123
        );

        $this->assertStringStartsWith('https://', $url);
        $this->assertStringContainsString('/image/authenticated/', $url);
        $this->assertStringNotContainsString('super-secret', $url);
        $this->assertStringNotContainsString('api-key', $url);
    }

    public function test_cloudinary_image_is_downloaded_to_a_verified_temporary_file(): void
    {
        $service = new MediaStorageService(new Cloudinary('cloudinary://key:secret@demo'));
        Http::fake(['res.cloudinary.com/*' => Http::response($this->jpegBytes(), 200, ['Content-Type' => 'image/jpeg'])]);

        $path = $service->downloadToTemporaryFile(
            'textilecycle/scans/1/test/product/id',
            'cloudinary',
            'textilecycle/scans/1/test/product/id',
            null,
            'upload',
            'jpg',
            1
        );

        $this->assertNotNull($path);
        $this->assertFileExists($path);
        $this->assertSame('image/jpeg', (new \finfo(FILEINFO_MIME_TYPE))->file($path));
        @unlink($path);
    }

    public function test_ocr_always_removes_downloaded_temporary_file(): void
    {
        $user = User::factory()->create(['role' => 'consumer']);
        $scan = Scan::create(['user_id' => $user->id, 'source_type' => 'multimodal', 'status' => 'analysing']);
        $asset = $scan->assets()->create([
            'type' => 'product_front',
            ...$this->cloudinaryReference('textilecycle/scans/1/test/product/id', 'product_front'),
        ]);
        $temporaryPath = tempnam(sys_get_temp_dir(), 'textilecycle-test-').'.jpg';
        file_put_contents($temporaryPath, $this->jpegBytes());

        $media = Mockery::mock(MediaStorageService::class);
        $media->shouldReceive('downloadToTemporaryFile')->once()->with(
            $asset->path,
            $asset->storage_disk,
            $asset->cloudinary_public_id,
            $asset->secure_url,
            $asset->delivery_type,
            $asset->format,
            $asset->version,
        )->andReturn($temporaryPath);

        (new MultimodalScanService($media))->analyse($scan);
        $this->assertFileDoesNotExist($temporaryPath);
    }

    public function test_cloudinary_scan_can_be_reanalysed(): void
    {
        $user = User::factory()->create(['role' => 'consumer']);
        $scan = Scan::create(['user_id' => $user->id, 'source_type' => 'multimodal', 'status' => 'needs_confirmation']);
        $scan->assets()->create(['type' => 'product_front', ...$this->cloudinaryReference('textilecycle/scans/1/test/product/id', 'product_front')]);

        $media = Mockery::mock(MediaStorageService::class);
        $media->shouldReceive('exists')->once()->andReturnTrue();
        $this->app->instance(MediaStorageService::class, $media);
        $analysis = Mockery::mock(MultimodalScanService::class);
        $analysis->shouldReceive('analyse')->once()->andReturn(['confidence' => 10]);
        $this->app->instance(MultimodalScanService::class, $analysis);

        $this->actingAs($user)->post(route('scans.reanalyse', $scan))->assertRedirect(route('scans.show', $scan));
    }

    public function test_legacy_local_image_remains_compatible_when_present(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('scans/legacy.jpg', $this->jpegBytes());
        $asset = new ScanAsset(['path' => 'scans/legacy.jpg']);

        $this->assertTrue($asset->is_available);
        $this->assertStringContainsString('/storage/scans/legacy.jpg', $asset->url);
    }

    public function test_missing_legacy_image_uses_placeholder_and_disables_reanalysis(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'consumer']);
        $scan = Scan::create(['user_id' => $user->id, 'source_type' => 'multimodal', 'status' => 'needs_confirmation']);
        $asset = $scan->assets()->create(['type' => 'receipt', 'path' => 'scans/lost.jpg']);

        $this->assertFalse($asset->is_available);
        $this->assertStringContainsString('media-unavailable.svg', $asset->url);
        $this->actingAs($user)->get(route('scans.show', $scan))
            ->assertOk()
            ->assertSee('Image originale indisponible après l’ancien déploiement')
            ->assertSee('Aucune image source disponible', false);
    }

    public function test_deleting_scan_deletes_all_cloudinary_resources(): void
    {
        $user = User::factory()->create(['role' => 'consumer']);
        $scan = Scan::create(['user_id' => $user->id, 'source_type' => 'multimodal', 'status' => 'completed']);
        $scan->assets()->create(['type' => 'receipt', ...$this->cloudinaryReference('textilecycle/scans/1/test/receipt/id', 'receipt')]);
        $scan->assets()->create(['type' => 'product_front', ...$this->cloudinaryReference('textilecycle/scans/1/test/product/id', 'product_front')]);

        $media = Mockery::mock(MediaStorageService::class);
        $media->shouldReceive('delete')->twice()->andReturnTrue();
        $this->app->instance(MediaStorageService::class, $media);

        $this->actingAs($user)->delete(route('scans.destroy', $scan))->assertRedirect(route('scans.index'));
        $this->assertDatabaseMissing('scans', ['id' => $scan->id]);
    }

    public function test_reanalysis_is_safe_when_no_old_file_survives(): void
    {
        $user = User::factory()->create(['role' => 'consumer']);
        $scan = Scan::create(['user_id' => $user->id, 'source_type' => 'multimodal', 'status' => 'needs_confirmation']);
        $scan->assets()->create(['type' => 'receipt', 'path' => 'scans/lost.jpg']);

        $media = Mockery::mock(MediaStorageService::class);
        $media->shouldReceive('exists')->once()->andReturnFalse();
        $this->app->instance(MediaStorageService::class, $media);
        $analysis = Mockery::mock(MultimodalScanService::class);
        $analysis->shouldNotReceive('analyse');
        $this->app->instance(MultimodalScanService::class, $analysis);

        $this->actingAs($user)->post(route('scans.reanalyse', $scan))
            ->assertRedirect()
            ->assertSessionHasErrors('images');
    }

    public function test_repository_files_do_not_contain_cloudinary_or_github_secrets(): void
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
            base_path(),
            \FilesystemIterator::SKIP_DOTS
        ));
        foreach ($files as $file) {
            $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
            if (preg_match('#^(vendor|node_modules|storage|public/build|\.git)(/|\\\\)#', $relative)
                || ! in_array($file->getExtension(), ['php', 'json', 'xml', 'md', 'js', 'css', 'sh', 'example', 'yml', 'yaml'], true)) {
                continue;
            }
            $contents = file_get_contents($file->getPathname());
            $this->assertDoesNotMatchRegularExpression('/ghp_[A-Za-z0-9]{20,}/', $contents, $relative);
            $this->assertDoesNotMatchRegularExpression('/cloudinary:\/\/[0-9]{10,}:[A-Za-z0-9_-]{20,}@/', $contents, $relative);
            $this->assertDoesNotMatchRegularExpression('/CLOUDINARY_API_SECRET=[A-Za-z0-9_-]{20,}/', $contents, $relative);
        }
        $this->assertStringContainsString('CLOUDINARY_API_SECRET=', file_get_contents(base_path('.env.example')));
    }

    private function cloudinaryReference(string $publicId, string $kind): array
    {
        return [
            'path' => $publicId,
            'storage_disk' => 'cloudinary',
            'cloudinary_public_id' => $publicId,
            'secure_url' => "https://res.cloudinary.com/demo/image/upload/{$publicId}.jpg",
            'delivery_type' => $kind === 'receipt' ? 'authenticated' : 'upload',
            'format' => 'jpg',
            'version' => 1,
        ];
    }

    private function jpegBytes(): string
    {
        return base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAEf/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABBQJ//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPwF//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPwF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQAGPwJ//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPyF//9oADAMBAAIAAwAAABD/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAEDAQE/EF//xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAECAQE/EF//xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAE/EF//2Q==');
    }
}
