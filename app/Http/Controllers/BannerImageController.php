<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Support\Facades\Storage;

class BannerImageController extends Controller
{
    public function show(Banner $banner)
    {
        abort_if(blank($banner->path) || ! Storage::disk('public')->exists($banner->path), 404);

        return response()->file(Storage::disk('public')->path($banner->path), [
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}