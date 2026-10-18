<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    /**
     * Attendance Dashboard — Current Month / Archive (folders by date) + filters.
     */
    public function index(Request $request): View
    {
        [$view, $folder] = $this->resolveView($request);
        $filters = $request->only(['q', 'date', 'department', 'status']);

        $showFolders = $view === 'archive' && ! $folder;

        // Archive: folders grouped by date
        $folders = $showFolders
            ? AttendanceRecord::archived()
                ->select('date', DB::raw('COUNT(*) as total'))
                ->groupBy('date')
                ->orderByDesc('date')
                ->get()
            : collect();

        $records = $showFolders
            ? null
            : $this->query($view, $folder, $filters)
                ->paginate(15)
                ->withQueryString();

        // Department dropdown — kinukuha sa employees table
        $departments = Employee::query()
            ->whereNotNull('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department')
            ->mapWithKeys(fn ($d) => [$d => AttendanceRecord::departmentLabel($d)]);

        return view('attendance', [
            'view'        => $view,
            'folder'      => $folder,
            'showFolders' => $showFolders,
            'folders'     => $folders,
            'records'     => $records,
            'departments' => $departments,
            'filters'     => $filters,
            'statuses'    => AttendanceRecord::STATUSES,
        ]);
    }

    /**
     * "Select to Archive" — move the selected current records to the Archive.
     */
    public function archive(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:attendance_records,id'],
        ], ['ids.required' => 'Select at least one record to archive.']);

        $count = AttendanceRecord::current()->whereIn('id', $data['ids'])->update(['archived' => true]);

        return back()->with('success', $count . ' record(s) archived.');
    }

    /**
     * Inside an archive folder — permanently delete the selected archived records.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ], ['ids.required' => 'Select at least one record to delete.']);

        $count = AttendanceRecord::archived()->whereIn('id', $data['ids'])->delete();

        return back()->with('success', $count . ' archived record(s) deleted.');
    }

    /**
     * Export the filtered records (all pages) — CSV, opens directly in Excel.
     */
    public function export(Request $request): StreamedResponse
    {
        [$view, $folder] = $this->resolveView($request);
        $filters = $request->only(['q', 'date', 'department', 'status']);

        $query = $this->query($view, $folder, $filters);

        $fileName = 'attendance-' . ($folder ?: now()->format('Y-m-d')) . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM para tama ang pagbukas sa Excel

            fputcsv($out, ['Employee ID', 'Name', 'Department', 'Date', 'Time In', 'Time Out', 'Hours Worked', 'Status']);

            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r->employee?->employee_id,
                        $r->employee?->name,
                        AttendanceRecord::departmentLabel($r->employee?->department),
                        $r->date->format('Y-m-d'),
                        $r->time_in_label,
                        $r->time_out_label,
                        $r->hours_label,
                        $r->status,
                    ]);
                }
            });

            fclose($out);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /* ---------- Helpers ---------- */

    // [view, folder date | null]
    private function resolveView(Request $request): array
    {
        $view = $request->query('view') === 'archive' ? 'archive' : 'current';

        $folder = null;
        if ($view === 'archive' && $request->filled('folder')) {
            try {
                $folder = Carbon::createFromFormat('Y-m-d', $request->query('folder'))->format('Y-m-d');
            } catch (\Throwable) {
                $folder = null;
            }
        }

        return [$view, $folder];
    }

    private function query(string $view, ?string $folder, array $filters)
    {
        return AttendanceRecord::query()
            ->with('employee')
            ->where('archived', $view === 'archive')
            ->when($folder, fn ($q) => $q->whereDate('date', $folder))
            ->filter($filters, ignoreDate: (bool) $folder)
            ->orderByDesc('date')
            ->orderBy('id');
    }
}