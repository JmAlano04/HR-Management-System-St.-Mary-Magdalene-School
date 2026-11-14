<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SystemUpdatesController extends Controller
{
    public function current(): JsonResponse
    {
        $version = DB::table('system_data_versions')
            ->where('id', 1)
            ->value('version');

        return response()
            ->json(['version' => (int) $version])
            ->header('Cache-Control', 'no-store, private');
    }
}
