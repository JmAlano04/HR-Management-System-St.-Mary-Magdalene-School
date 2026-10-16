<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminDashboardController extends Controller
{
    /** Maximum number of custom banner images. */
    public const MAX_BANNERS = 10;

    /** Dashboard page (called from DashboardController for the Admin role). */
    public function index()
    {
        return view('dashboard.admin', [
            'slides' => $this->slides(),
            'today'  => now()->toDateString(),
        ]);
    }

    // ------------------------------------------------------------------
    // Banner images
    // ------------------------------------------------------------------

    /** Add new images and/or remove existing ones in one request. */
    public function updateBanners(Request $request): JsonResponse
    {
        $request->validate([
            'images'   => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png|max:5120',   // 5MB each
            'remove'   => 'nullable|array',
            'remove.*' => 'integer',
        ]);

        $toRemove = Banner::whereIn('id', $request->input('remove', []))->get();
        $newFiles = $request->file('images', []);

        if (Banner::count() - $toRemove->count() + count($newFiles) > self::MAX_BANNERS) {
            return response()->json([
                'message' => 'You can only have up to ' . self::MAX_BANNERS . ' banner images.',
            ], 422);
        }

        foreach ($toRemove as $banner) {
            Storage::disk('public')->delete($banner->path);
            $banner->delete();
        }

        $order = (int) Banner::max('sort_order');

        foreach ($newFiles as $file) {
            Banner::create([
                'path'        => $file->store('banners', 'public'),
                'alt'         => Str::limit(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 100, ''),
                'sort_order'  => ++$order,
                'uploaded_by' => auth()->id(),
            ]);
        }

        return response()->json(['slides' => $this->slides()]);
    }

    /** "Reset to Defaults": delete every custom banner. */
    public function resetBanners(): JsonResponse
    {
        foreach (Banner::all() as $banner) {
            Storage::disk('public')->delete($banner->path);
            $banner->delete();
        }

        return response()->json(['slides' => $this->slides()]);
    }

    /** Custom banners if any exist, otherwise the 3 default images in public/Image. */
    private function slides(): array
    {
        $custom = Banner::orderBy('sort_order')->orderBy('id')->get();

        if ($custom->isNotEmpty()) {
            return $custom->map(fn (Banner $b) => [
                'id'  => $b->id,
                'url' => asset('storage/' . $b->path),
                'alt' => $b->alt ?: 'Banner',
            ])->values()->all();
        }

        return collect([1, 2, 3])->map(fn ($i) => [
            'id'  => null,
            'url' => asset("Image/promote{$i}.jpg"),
            'alt' => "Promo {$i}",
        ])->all();
    }

    // ------------------------------------------------------------------
    // Calendar events
    // ------------------------------------------------------------------

    /** GET ?month=YYYY-MM  ->  all events in that month. */
    public function events(Request $request): JsonResponse
    {
        $request->validate(['month' => 'required|date_format:Y-m']);

        $start = Carbon::createFromFormat('Y-m-d', $request->month . '-01')->startOfMonth();

        $events = DB::table('calendar_events')->whereBetween('event_date', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()])
            ->orderBy('event_date')
            ->orderBy('id')
            ->get()
            ->map(fn ($e) => $this->eventPayload($e));

        return response()->json($events);
    }

    public function storeEvent(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'       => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'event_date'  => 'required|date',
        ]);

        $now = now();
        $id = DB::table('calendar_events')->insertGetId($data + [
            'created_by' => Auth::id(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $event = DB::table('calendar_events')->where('id', $id)->first();

        return response()->json($this->eventPayload($event), 201);
    }

    public function destroyEvent(int $event): JsonResponse
    {
        DB::table('calendar_events')->where('id', $event)->delete();

        return response()->json(['deleted' => true]);
    }

    private function eventPayload(object $e): array
    {
        return [
            'id'          => $e->id,
            'title'       => $e->title,
            'description' => $e->description,
            'date'        => Carbon::parse($e->event_date)->toDateString(),
        ];
    }
}