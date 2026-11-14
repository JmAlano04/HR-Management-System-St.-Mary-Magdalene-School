<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class TrackSystemDataUpdates
{
    public function handle(Request $request, Closure $next): Response
    {
        $version = DB::table('system_data_versions')
            ->where('id', 1)
            ->value('version');

        if ($version === null) {
            throw new RuntimeException('The system data update version has not been initialized.');
        }

        View::share('systemDataVersion', (int) $version);

        $response = $next($request);

        if (
            in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            && $response->getStatusCode() >= 200
            && $response->getStatusCode() < 400
        ) {
            $updated = DB::table('system_data_versions')
                ->where('id', 1)
                ->increment('version', 1, ['updated_at' => now()]);

            if ($updated !== 1) {
                throw new RuntimeException('The system data update version could not be advanced.');
            }
        }

        return $response;
    }
}
