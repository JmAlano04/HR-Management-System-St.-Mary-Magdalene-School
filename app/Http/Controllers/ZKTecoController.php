<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class ZKTecoController extends Controller
{
    public function sync(): RedirectResponse
    {
        set_time_limit(300);

        $exitCode = Artisan::call('hrms:zkteco-sync');
        $output = trim(Artisan::output());

        if ($exitCode !== 0) {
            return back()->with('error', 'Biometric synchronization failed. ' . $output);
        }

        return back()->with('success', 'Biometric attendance synchronized successfully.');
    }

    public function test()
    {
        return response()->json(
            DB::table('zkteco_attendances')->orderByDesc('id')->limit(5)->get()
        );
    }
}