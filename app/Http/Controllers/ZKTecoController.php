<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ZKTecoController extends Controller
{
    // SCHOOL ATTENDANCE SETTINGS
    private const SHIFT_START = '07:00:00';
    private const GRACE_MINUTES = 0;
    private const REQUIRED_HOURS = 8;
    private const TIMEZONE = 'Asia/Manila';

    /**
     * Authenticate Python middleware.
     */
    private function authenticatePython(Request $request): bool
    {
        $apiKey = $request->header('X-API-KEY');

        $expectedKey = config('services.zkteco.api_key');

        return !empty($expectedKey)
            && !empty($apiKey)
            && hash_equals($expectedKey, $apiKey);
    }

    /**
     * Manual synchronization through Laravel command.
     */
    public function sync(): RedirectResponse
    {
        set_time_limit(300);

        $exitCode = Artisan::call('hrms:zkteco-sync');

        $output = trim(Artisan::output());

        if ($exitCode !== 0) {

            Log::error('ZKTeco synchronization failed', [
                'output' => $output
            ]);

            return back()->with(
                'error',
                'Biometric synchronization failed. ' . $output
            );
        }

        return back()->with(
            'success',
            'Biometric attendance synchronized successfully.'
        );
    }

    /**
     * Receive attendance records from Python middleware.
     */
    public function receiveFromPython(Request $request): JsonResponse
    {
        // --------------------------------------
        // API KEY AUTHENTICATION
        // --------------------------------------

        if (!$this->authenticatePython($request)) {

            return response()->json([
                'success' => false,
                'message' => 'Invalid API key.'
            ], 401);
        }

        // --------------------------------------
        // CHECK TABLE
        // --------------------------------------

        $table = 'zkteco_attendances';

        if (!Schema::hasTable($table)) {

            return response()->json([
                'success' => false,
                'message' => 'ZKTeco attendance table does not exist.'
            ], 500);
        }

        // --------------------------------------
        // VALIDATION
        // --------------------------------------

        $validated = $request->validate([
            'records' => ['required', 'array', 'min:1', 'max:500'],

            'records.*.employee_code' => [
                'required',
                'string',
                'max:255'
            ],

            'records.*.punch_time' => [
                'required',
                'date'
            ],

            'records.*.punch_type' => [
                'nullable',
                'string',
                'max:50'
            ],

            'records.*.device_ip' => [
                'nullable',
                'ip'
            ],
        ]);

        // --------------------------------------
        // REQUIRED DATABASE COLUMNS
        // --------------------------------------

        $requiredColumns = [
            'fingerprint',
            'device',
            'user_id',
            'uid',
            'punched_at',
            'timezone',
            'direction',
            'verify_method',
            'raw_direction',
            'raw_verify',
            'work_code',
        ];

        $columns = Schema::getColumnListing($table);

        $missingColumns = array_diff(
            $requiredColumns,
            $columns
        );

        if (!empty($missingColumns)) {

            return response()->json([
                'success' => false,
                'message' => 'Missing database columns: '
                    . implode(', ', $missingColumns)
            ], 422);
        }

        // --------------------------------------
        // INITIALIZE COUNTERS
        // --------------------------------------

        $inserted = 0;
        $duplicates = 0;

        $touched = [];

        // --------------------------------------
        // DATABASE TRANSACTION
        // --------------------------------------

        try {

            DB::transaction(function () use (
                $validated,
                $table,
                &$inserted,
                &$duplicates,
                &$touched
            ) {

                foreach ($validated['records'] as $record) {

                    $employeeCode = (string) $record['employee_code'];

                    // Always use Philippine time.
                    $punchedAt = Carbon::parse(
                        $record['punch_time'],
                        self::TIMEZONE
                    )->setTimezone(self::TIMEZONE)
                     ->format('Y-m-d H:i:s');

                    $device = $record['device_ip'] ?? 'main';

                    // Keep the original punch type for fingerprint matching.
                    $punchType = (string) (
                        $record['punch_type'] ?? ''
                    );

                    $normalizedPunch = strtolower(trim($punchType));

                    // ZKTeco punch status:
                    // 0 = Check In
                    // 1 = Check Out
                    // 2 = Break Out
                    // 3 = Break In
                    // 4 = OT In
                    // 5 = OT Out

                    $isCheckout = str_contains(
                        $normalizedPunch,
                        'out'
                    ) || in_array(
                        $normalizedPunch,
                        ['1', '2', '5'],
                        true
                    );

                    $rawDirection = $isCheckout ? 1 : 0;

                    // ----------------------------------
                    // MATCH PYTHON FINGERPRINT
                    // ----------------------------------

                    $fingerprint = hash(
                        'sha256',
                        $device . '|' .
                        $employeeCode . '|' .
                        $punchedAt . '|' .
                        $punchType
                    );

                    // ----------------------------------
                    // ATTENDANCE DATA
                    // ----------------------------------

                    $data = [
                        'fingerprint' => $fingerprint,

                        'device' => $device,

                        'user_id' => $employeeCode,

                        'uid' => null,

                        'punched_at' => $punchedAt,

                        'timezone' => self::TIMEZONE,

                        'direction' => $isCheckout
                            ? 'CheckOut'
                            : 'CheckIn',

                        'verify_method' => 'Fingerprint',

                        'raw_direction' => $rawDirection,

                        'raw_verify' => 1,

                        'work_code' => null,
                    ];

                    // ----------------------------------
                    // INSERT OR SKIP DUPLICATE
                    // ----------------------------------

                    $result = DB::table($table)->insertOrIgnore($data);

                    if ($result > 0) {

                        $inserted++;

                        $date = substr($punchedAt, 0, 10);

                        $touched[$employeeCode . '|' . $date] = [
                            $employeeCode,
                            $date
                        ];

                    } else {

                        $duplicates++;
                    }
                }

                // ----------------------------------
                // UPDATE DAILY ATTENDANCE
                // ----------------------------------

                foreach ($touched as [$code, $date]) {

                    $this->syncAttendanceRecord($code, $date);
                }

            }, 3);

        } catch (\Throwable $error) {

            Log::error('ZKTeco Python synchronization failed', [
                'error' => $error->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Attendance synchronization failed.',
            ], 500);
        }

        // --------------------------------------
        // API RESPONSE
        // --------------------------------------

        return response()->json([
            'success' => true,
            'message' => 'Biometric records synchronized successfully.',
            'inserted' => $inserted,
            'duplicates' => $duplicates,
            'total' => count($validated['records']),
        ]);
    }

    /**
     * Update daily attendance_records from ZKTeco punches.
     */
    private function syncAttendanceRecord(
        string $zktecoUserId,
        string $date
    ): void {

        // --------------------------------------
        // FIND EMPLOYEE
        // --------------------------------------

        $employeeId = DB::table('employees')
            ->where('zkteco_user_id', $zktecoUserId)
            ->value('id');

        if (!$employeeId) {

            // Raw biometric record remains saved.
            // Employee mapping can be fixed later.

            Log::warning('Unmapped ZKTeco employee', [
                'zkteco_user_id' => $zktecoUserId
            ]);

            return;
        }

        // --------------------------------------
        // GET FIRST AND LAST PUNCH
        // --------------------------------------

        $punches = DB::table('zkteco_attendances')
            ->where('user_id', $zktecoUserId)
            ->whereDate('punched_at', $date)
            ->selectRaw('
                MIN(punched_at) AS first_punch,
                MAX(punched_at) AS last_punch
            ')
            ->first();

        if (!$punches || !$punches->first_punch) {
            return;
        }

        $first = Carbon::parse(
            $punches->first_punch,
            self::TIMEZONE
        );

        $last = Carbon::parse(
            $punches->last_punch,
            self::TIMEZONE
        );

        $newIn = $first->format('H:i:s');

        $newOut = $last->gt($first)
            ? $last->format('H:i:s')
            : null;

        // --------------------------------------
        // CHECK EXISTING DAILY ATTENDANCE
        // --------------------------------------

        $existing = DB::table('attendance_records')
            ->where('employee_id', $employeeId)
            ->where('date', $date)
            ->first();

        $timeIn = $newIn;
        $timeOut = $newOut;

        // Preserve earlier approved time-in and later time-out.
        if ($existing) {

            if (
                $existing->time_in &&
                $existing->time_in < $timeIn
            ) {
                $timeIn = $existing->time_in;
            }

            if (
                $existing->time_out &&
                (
                    $timeOut === null ||
                    $existing->time_out > $timeOut
                )
            ) {
                $timeOut = $existing->time_out;
            }
        }

        // --------------------------------------
        // COMPUTE WORKING HOURS
        // --------------------------------------

        $hoursWorked = null;

        if ($timeOut !== null) {

            $seconds = Carbon::parse(
                "$date $timeIn",
                self::TIMEZONE
            )->diffInSeconds(
                Carbon::parse(
                    "$date $timeOut",
                    self::TIMEZONE
                )
            );

            $hoursWorked = round($seconds / 3600, 2);
        }

        // --------------------------------------
        // COMPUTE ATTENDANCE STATUS
        // --------------------------------------

        $lateAfter = Carbon::parse(
            "$date " . self::SHIFT_START,
            self::TIMEZONE
        )->addMinutes(self::GRACE_MINUTES);

        $isLate = Carbon::parse(
            "$date $timeIn",
            self::TIMEZONE
        )->gt($lateAfter);

        if (
            $hoursWorked !== null &&
            $hoursWorked < self::REQUIRED_HOURS
        ) {

            $status = 'Undertime';

        } elseif ($isLate) {

            $status = 'Late';

        } else {

            $status = 'Present';
        }

        // --------------------------------------
        // SAVE DAILY ATTENDANCE
        // --------------------------------------

        $now = now();

        if ($existing) {

            DB::table('attendance_records')
                ->where('id', $existing->id)
                ->update([
                    'time_in' => $timeIn,
                    'time_out' => $timeOut,
                    'hours_worked' => $hoursWorked,
                    'status' => $status,
                    'updated_at' => $now,
                ]);

        } else {

            DB::table('attendance_records')->insert([
                'employee_id' => $employeeId,
                'date' => $date,
                'time_in' => $timeIn,
                'time_out' => $timeOut,
                'hours_worked' => $hoursWorked,
                'status' => $status,
                'archived' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Test API and database connection.
     */
    public function test(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'ZKTeco API is working.',
            'records' => DB::table('zkteco_attendances')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
        ]);
    }
}
