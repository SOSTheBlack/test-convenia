<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $dbStatus = $this->checkDatabaseConnection();

        $status = [
            'status' => $dbStatus['status'] === 'ok' ? 'ok' : 'error',
            'timestamp' => date('Y-m-d H:i:s'),
            'checks' => [
                'database' => $dbStatus,
            ],
        ];

        return response()->json($status, $status['status'] === 'ok' ? 202 : 500);
    }

    /**
     * Check database connection.
     *
     * @return array
     */
    private function checkDatabaseConnection(): array
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'ok'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
