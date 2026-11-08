<?php

use App\Http\Controllers\BannerImageController;
use App\Models\Banner;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

it('serves uploaded banner images with their image content type', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('banners/homepage.jpg', 'image-data');

    $banner = new Banner(['path' => 'banners/homepage.jpg']);
    $response = app(BannerImageController::class)->show($banner);

    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    expect($response->headers->get('Content-Type'))->toBe('image/jpeg')
        ->and($content)->toBe('image-data');
});

it('uses the public banner directory URL for directly uploaded images', function (): void {
    $banner = new Banner(['path' => 'banner/homepage.jpg']);

    expect($banner->url)->toBe(asset('banner/homepage.jpg'));
});

it('uses existing bundled files for the default homepage banners', function (): void {
    foreach (Banner::DEFAULTS['homepage'] as $default) {
        expect(file_exists(public_path($default['path'])))->toBeTrue();
    }

    $legacySecondDefault = new Banner([
        'placement' => 'homepage',
        'path' => 'Image/homepage2.jpg',
        'is_default' => true,
        'sort_order' => 2,
    ]);

    expect($legacySecondDefault->url)->toBe(asset('image/homepage2.png'));
});
