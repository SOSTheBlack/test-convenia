<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke(Request $request): JsonResponse
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
     * @return array<string, string>
     */
    private function checkDatabaseConnection(): array
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'ok'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
