<?php

namespace App\Http\Controllers;

use App\Models\TimeCorrection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** HR / Admin side: list requests and approve or reject them. */
class TimeCorrectionReviewController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeReviewer($request);

        $requests = TimeCorrection::with('employee')
            ->when($request->status, fn ($q, $s) => $q->where(fn ($w) => $w->where('hr_status', $s)->orWhere('admin_status', $s)))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('time-corrections.review', compact('requests'));
    }

    public function update(Request $request, TimeCorrection $timeCorrection, string $reviewer): RedirectResponse
    {
        $this->authorizeReviewer($request, $reviewer);

        $validated = $request->validate([
            'status' => ['required', 'in:Approved,Rejected'],
        ]);

        $timeCorrection->update([
            "{$reviewer}_status"      => $validated['status'],
            "{$reviewer}_reviewed_at" => now(),
            "{$reviewer}_reviewed_by" => $request->user()->id,
        ]);

        return back()->with('success', 'Request marked as ' . $validated['status'] . '.');
    }

    /**
     * Adjust to your own role system (Spatie, Gate, middleware, etc.).
     * Assumes users.role is 'hr' or 'admin'.
     */
    private function authorizeReviewer(Request $request, ?string $reviewer = null): void
    {
        $role = $request->user()->role ?? null;

        abort_unless(in_array($role, ['hr', 'admin'], true), 403);

        if ($reviewer !== null) {
            abort_unless($role === $reviewer, 403, 'You can only review as your own role.');
        }
    }
}