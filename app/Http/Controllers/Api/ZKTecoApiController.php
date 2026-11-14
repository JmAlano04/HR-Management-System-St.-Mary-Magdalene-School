<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ZKTecoApiController extends Controller
{
    /**
     * Receive attendance records from Python middleware.
     */
    public function receiveFromPython(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'records' => ['required', 'array', 'min:1', 'max:500'],
            'records.*.employee_code' => ['required', 'string', 'max:255'],
            'records.*.punch_time' => ['required', 'date'],
            'records.*.punch_type' => ['nullable', 'string', 'max:50'],
            'records.*.device_ip' => ['nullable', 'ip'],
        ]);

        $table = 'zkteco_attendances';

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

        if (!Schema::hasTable($table)) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance table does not exist.',
            ], 500);
        }

        $columns = Schema::getColumnListing($table);

        $missingColumns = array_diff($requiredColumns, $columns);

        if (!empty($missingColumns)) {
            return response()->json([
                'success' => false,
                'message' => 'Missing columns: ' . implode(', ', $missingColumns),
            ], 422);
        }

        $inserted = 0;
        $duplicates = 0;

        DB::transaction(function () use (
            $validated,
            $table,
            &$inserted,
            &$duplicates
        ) {
            foreach ($validated['records'] as $record) {

                $employeeCode = (string) $record['employee_code'];

                $punchTime = Carbon::parse(
                    $record['punch_time'],
                    'Asia/Manila'
                )->format('Y-m-d H:i:s');

                $device = $record['device_ip'] ?? 'main';

                $punchType = strtolower(trim(
                    (string) ($record['punch_type'] ?? '0')
                ));

                // ZKTeco punch status:
                // 0 = Check In
                // 1 = Check Out
                // 2 = Break Out
                // 3 = Break In
                // 4 = OT In
                // 5 = OT Out

                $isCheckout = in_array(
                    $punchType,
                    ['1', '2', '5'],
                    true
                );

                $rawDirection = $isCheckout ? 1 : 0;

                // Generate a consistent unique fingerprint.
                $fingerprint = hash(
                    'sha256',
                    $device . '|' .
                    $employeeCode . '|' .
                    $punchTime . '|' .
                    $punchType
                );

                $data = [
                    'fingerprint' => $fingerprint,
                    'device' => $device,
                    'user_id' => $employeeCode,
                    'uid' => null,
                    'punched_at' => $punchTime,
                    'timezone' => 'Asia/Manila',
                    'direction' => $isCheckout ? 'CheckOut' : 'CheckIn',
                    'verify_method' => 'Fingerprint',
                    'raw_direction' => $rawDirection,
                    'raw_verify' => 1,
                    'work_code' => null,
                ];

                $result = DB::table($table)->insertOrIgnore($data);

                if ($result > 0) {
                    $inserted++;
                } else {
                    $duplicates++;
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Biometric records synchronized successfully.',
            'inserted' => $inserted,
            'duplicates' => $duplicates,
            'total' => count($validated['records']),
        ]);
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
