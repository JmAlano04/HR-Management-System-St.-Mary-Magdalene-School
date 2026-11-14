<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BannerController extends Controller
{
    /** Public: streams the stored image (no storage:link needed). */
    public function image(Banner $banner)
    {
        $disk = Storage::disk('public');

        abort_unless($banner->path && $disk->exists($banner->path), 404);

        $contentType = match (strtolower(pathinfo($banner->path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'        => 'image/png',
            'webp'       => 'image/webp',
            default      => 'application/octet-stream',
        };

        return response()->stream(function () use ($disk, $banner) {
            $stream = $disk->readStream($banner->path);

            if (is_resource($stream)) {
                fpassthru($stream);
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /** Admin: list banners + upload form. */
    public function index()
    {
        return view('admin.banners', [
            'banners'    => Banner::orderBy('placement')->orderBy('sort_order')->orderBy('id')->get(),
            'placements' => Banner::PLACEMENTS,
        ]);
    }

    /** Admin: upload. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'placement'  => ['required', Rule::in(Banner::PLACEMENTS)],
            'image'      => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], // 5 MB
            'alt'        => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $file = $request->file('image');
        $filename = Str::uuid().'.'.$file->guessExtension();
        File::ensureDirectoryExists(public_path('banner'));
        $file->move(public_path('banner'), $filename);

        Banner::create([
            'placement'   => $data['placement'],
            'path'        => 'banner/'.$filename,
            'alt'         => $data['alt'] ?? null,
            'sort_order'  => $data['sort_order'] ?? 0,
            'uploaded_by' => Auth::id(),
        ]);

        return back()->with('status', 'Banner uploaded.');
    }

    /** Admin: delete record and file. */
    public function destroy(Banner $banner)
    {
        $banner->deleteImage();
        $banner->delete();

        return back()->with('status', 'Banner deleted.');
    }
}