<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Planning\NetworkRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Confirma que a API alcança o PostgreSQL com PostGIS e que a rede está carregada.
 */
class HealthController extends Controller
{
    public function __invoke(NetworkRepository $networks): JsonResponse
    {
        try {
            $postgis = DB::scalar('SELECT postgis_lib_version()');

            return response()->json([
                'status' => 'ok',
                'database' => [
                    'connected' => true,
                    'server' => DB::scalar('SHOW server_version'),
                    'postgis' => $postgis,
                ],
                'network' => [
                    'version' => $networks->currentVersion(),
                    'stops' => DB::table('stops')->count(),
                    'lines' => DB::table('lines')->count(),
                    'connections' => DB::table('connections')->count(),
                    'integrations' => DB::table('integrations')->count(),
                ],
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status' => 'error',
                'database' => ['connected' => false],
            ], 503);
        }
    }
}
