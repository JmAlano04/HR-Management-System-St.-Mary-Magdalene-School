<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class Banner extends Model
{
    public const PLACEMENTS = ['admin', 'homepage'];

    /** Bundled images that live in /public/image (copy your files there). */
    public const DEFAULTS = [
        'admin' => [
            ['path' => 'image/promote1.jpg', 'alt' => 'promote1'],
            ['path' => 'image/promote2.jpg', 'alt' => 'promote2'],
            ['path' => 'image/promote3.jpg', 'alt' => 'promote3'],
        ],
        'homepage' => [
            ['path' => 'image/homepage1.jpg', 'alt' => 'homepage1'],
            ['path' => 'image/homepage2.png', 'alt' => 'homepage2'],
        ],
    ];

    protected $fillable = ['placement', 'path', 'alt', 'is_default', 'sort_order', 'uploaded_by'];

    protected $casts = ['is_default' => 'boolean'];

    public function getUrlAttribute(): string
    {
        if ($this->is_default) {
            $default = self::DEFAULTS[$this->placement][$this->sort_order - 1] ?? null;

            return asset($default['path'] ?? $this->path);
        }

        return $this->isPublicPath()
            ? asset($this->path)
            : Storage::url($this->path);
    }

    public function deleteImage(): void
    {
        if ($this->is_default) {
            return;
        }

        if ($this->isPublicPath()) {
            File::delete(public_path($this->path));

            return;
        }

        Storage::disk('public')->delete($this->path);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeForPlacement(Builder $query, string $placement): Builder
    {
        return $query->where('placement', $placement)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Slides in the shape the Blade view and JS expect: [{id, url, alt}].
     * If a placement has no rows at all, the defaults are created first.
     */
    public static function slidesFor(string $placement): array
    {
        if (! static::where('placement', $placement)->exists()) {
            static::restoreDefaults($placement);
        }

        return static::forPlacement($placement)->get()
            ->map(fn (self $banner) => [
                'id'  => $banner->id,
                'url' => $banner->url,
                'alt' => $banner->alt ?: 'Banner',
            ])
            ->values()
            ->all();
    }

    /** Alias so both Banner::slides() and Banner::slidesFor() work. */
    public static function slides(string $placement): array
    {
        return static::slidesFor($placement);
    }

    /** Delete uploads (and their files), then re-create the default rows. */
    public static function restoreDefaults(string $placement): void
    {
        static::where('placement', $placement)
            ->where('is_default', false)
            ->get()
            ->each(fn (self $banner) => $banner->deleteImage());

        static::where('placement', $placement)->delete();

        foreach (self::DEFAULTS[$placement] as $i => $default) {
            static::create([
                'placement'  => $placement,
                'path'       => $default['path'],
                'alt'        => $default['alt'],
                'is_default' => true,
                'sort_order' => $i + 1,
            ]);
        }
    }

    /** Uploads saved straight into /public/banner (vs. the "public" storage disk). */
    private function isPublicPath(): bool
    {
        return str_starts_with($this->path, 'banner/');
    }
}