<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminDashboardController extends Controller
{
    /** Maximum number of custom banner images. */
    public const MAX_BANNERS = 10;

    /** Dashboard page (called from DashboardController for the Admin role). */
    public function index()
    {

        return view('dashboard', [
            'adminBannerSlides' => Banner::slidesFor('admin'),
            'homepageBannerSlides' => Banner::slidesFor('homepage'),
            'today' => now()->toDateString(),
        ]);
    }

    // ------------------------------------------------------------------
    // Banner images
    // ------------------------------------------------------------------

    public function currentDashboardBanners(): JsonResponse
    {
        return response()->json([
            'slides' => Banner::slidesFor('admin'),
        ])->header('Cache-Control', 'no-store, private');
    }

    /** Add new images and/or remove existing ones in one request. */
    public function updateBanners(Request $request, string $placement): JsonResponse
    {
        abort_unless(in_array($placement, Banner::PLACEMENTS, true), 404);

        $request->validate([
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png|max:5120',   // 5MB each
            'remove' => 'nullable|array',
            'remove.*' => [
                'integer',
                Rule::exists('banners', 'id')->where('placement', $placement),
            ],
        ]);

        $toRemove = Banner::query()
            ->where('placement', $placement)
            ->whereIn('id', $request->input('remove', []))
            ->get();
        $newFiles = $request->file('images', []);

        $bannerQuery = Banner::query()->where('placement', $placement);

        if ((clone $bannerQuery)->count() - $toRemove->count() + count($newFiles) > self::MAX_BANNERS) {
            return response()->json([
                'message' => 'You can only have up to '.self::MAX_BANNERS.' banner images.',
            ], 422);
        }

        foreach ($toRemove as $banner) {
            $banner->deleteImage();
            $banner->delete();
        }

        $order = (int) (clone $bannerQuery)->max('sort_order');

        foreach ($newFiles as $file) {
            $filename = Str::uuid().'.'.$file->guessExtension();
            File::ensureDirectoryExists(public_path('banner'));
            $file->move(public_path('banner'), $filename);

            Banner::create([
                'placement' => $placement,
                'path' => 'banner/'.$filename,
                'alt' => Str::limit(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 100, ''),
                'sort_order' => ++$order,
                'uploaded_by' => Auth::id(),
            ]);
        }

        return response()->json(['slides' => Banner::slidesFor($placement)]);
    }

    /** "Reset to Defaults": delete every custom banner. */
    public function resetBanners(string $placement): JsonResponse
    {
        abort_unless(in_array($placement, Banner::PLACEMENTS, true), 404);

        foreach (Banner::where('placement', $placement)->get() as $banner) {
            $banner->deleteImage();
            $banner->delete();
        }

        return response()->json(['slides' => Banner::slidesFor($placement)]);
    }

    // ------------------------------------------------------------------
    // Calendar events
    // ------------------------------------------------------------------

    /** GET ?month=YYYY-MM  ->  all events in that month. */
    public function events(Request $request): JsonResponse
    {
        $request->validate(['month' => 'required|date_format:Y-m']);

        $start = Carbon::createFromFormat('Y-m-d', $request->month.'-01')->startOfMonth();

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
            'title' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'event_date' => 'required|date',
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
            'id' => $e->id,
            'title' => $e->title,
            'description' => $e->description,
            'date' => Carbon::parse($e->event_date)->toDateString(),
        ];
    }
}
