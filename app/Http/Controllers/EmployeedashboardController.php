<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\DashboardTask;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeDashboardController extends Controller
{
    /** Dashboard page (called from DashboardController for the Employee role). */
    public function index()
    {
        return view('dashboard', [
            'slides'    => $this->slides(),
            'today'     => now()->toDateString(),
            'birthdays' => $this->upcomingBirthdays(),
        ]);
    }

    // ------------------------------------------------------------------
    // Personal tasks (each employee only ever sees and changes their own)
    // ------------------------------------------------------------------

    /** GET ?month=YYYY-MM -> my tasks in that month. */
    public function tasks(Request $request): JsonResponse
    {
        $request->validate(['month' => 'required|date_format:Y-m']);

        $start = Carbon::createFromFormat('!Y-m', $request->month);

        $tasks = DashboardTask::where('user_id', Auth::id())
            ->whereBetween('task_date', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()])
            ->orderBy('task_date')
            ->orderBy('id')
            ->get()
            ->map->toCard();

        return response()->json($tasks);
    }

    public function storeTask(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'     => ['required', 'string', 'max:255'],
            'task_date' => ['required', 'date'],
        ]);

        $task = DashboardTask::create([
            'user_id'   => Auth::id(),
            'task_date' => $data['task_date'],
            'title'     => trim($data['title']),
        ]);

        return response()->json($task->toCard(), 201);
    }

    /** Used for both "Remove" and ticking the checkbox (completed). */
    public function destroyTask(DashboardTask $task): JsonResponse
    {
        abort_unless($task->user_id === Auth::id(), 403);

        $task->delete();

        return response()->json(['deleted' => true]);
    }

    // ------------------------------------------------------------------
    // Banner (read-only here; Admin edits it on the Admin dashboard)
    // ------------------------------------------------------------------

    /** Custom banners if the Admin uploaded any, otherwise the 3 default images in public/Image. */
    private function slides(): array
    {
        $custom = Banner::orderBy('sort_order')->orderBy('id')->get();

        if ($custom->isNotEmpty()) {
            return $custom->map(fn (Banner $b) => [
                'url' => asset('storage/' . $b->path),
                'alt' => $b->alt ?: 'Banner',
            ])->values()->all();
        }

        return collect([1, 2, 3])->map(fn ($i) => [
            'url' => asset("Image/promote{$i}.jpg"),
            'alt' => "Promo {$i}",
        ])->all();
    }

    // ------------------------------------------------------------------
    // Employee birthdays (this year, from today onwards, soonest first)
    // ------------------------------------------------------------------

    private function upcomingBirthdays(): array
    {
        $today = now()->startOfDay();

        return Employee::query()
            ->get(['name', 'position', 'birthday'])
            ->map(function (Employee $e) use ($today) {
                // Feb 29 in a non-leap year falls back to Feb 28
                $month = $today->copy()->setDate($today->year, $e->birthday->month, 1);
                $date  = $month->copy()->day(min($e->birthday->day, $month->daysInMonth));

                return ['employee' => $e, 'date' => $date];
            })
            ->filter(fn ($b) => $b['date']->gte($today))
            ->sortBy(fn ($b) => $b['date']->timestamp)
            ->values()
            ->map(fn ($b) => [
                'name'       => $b['employee']->name,
                'role'       => $b['employee']->position,
                'initials'   => $b['employee']->initials,
                'iso'        => $b['date']->toDateString(),
                'date_label' => $b['date']->format('M j'),
            ])
            ->all();
    }
}