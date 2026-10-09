<?php

namespace App\Http\Controllers\Health;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealthCheckController extends Controller
{
    /**
     * Run system diagnostic health checks for production monitoring.
     */
    public function check(): JsonResponse
    {
        $startTime = microtime(true);
        $checks = [];
        $isHealthy = true;

        // 1. Database Connection & Latency Check
        try {
            $dbStart = microtime(true);
            DB::connection()->getPdo();
            $dbLatency = round((microtime(true) - $dbStart) * 1000, 2);
            $checks['database'] = [
                'status' => 'UP',
                'latency_ms' => $dbLatency,
                'connection' => config('database.default'),
            ];
        } catch (\Throwable $e) {
            $isHealthy = false;
            $checks['database'] = [
                'status' => 'DOWN',
                'error' => $e->getMessage(),
            ];
            Log::error('Health Check: Database connection failed', ['error' => $e->getMessage()]);
        }

        // 2. Cache Store Check
        try {
            $cacheKey = 'health_check_'.uniqid();
            Cache::put($cacheKey, 'ok', 10);
            $cacheVal = Cache::get($cacheKey);
            Cache::forget($cacheKey);

            if ($cacheVal === 'ok') {
                $checks['cache'] = [
                    'status' => 'UP',
                    'store' => config('cache.default'),
                ];
            } else {
                $isHealthy = false;
                $checks['cache'] = [
                    'status' => 'DEGRADED',
                    'message' => 'Cache write succeeded but read failed',
                ];
            }
        } catch (\Throwable $e) {
            $isHealthy = false;
            $checks['cache'] = [
                'status' => 'DOWN',
                'error' => $e->getMessage(),
            ];
        }

        // 3. Disk Space Storage Check
        try {
            $storagePath = storage_path();
            $freeBytes = @disk_free_space($storagePath);
            $totalBytes = @disk_total_space($storagePath);

            if ($freeBytes !== false && $totalBytes !== false && $totalBytes > 0) {
                $usedPercentage = round((($totalBytes - $freeBytes) / $totalBytes) * 100, 1);
                $freeGb = round($freeBytes / (1024 * 1024 * 1024), 2);
                $totalGb = round($totalBytes / (1024 * 1024 * 1024), 2);

                $checks['storage'] = [
                    'status' => $usedPercentage > 90 ? 'WARNING' : 'UP',
                    'free_gb' => $freeGb,
                    'total_gb' => $totalGb,
                    'used_percent' => $usedPercentage,
                ];

                if ($usedPercentage > 95) {
                    $isHealthy = false;
                }
            } else {
                $checks['storage'] = [
                    'status' => 'UP',
                    'message' => 'Disk metrics not available in current environment',
                ];
            }
        } catch (\Throwable $e) {
            $checks['storage'] = [
                'status' => 'UNKNOWN',
                'error' => $e->getMessage(),
            ];
        }

        // 4. Memory Usage Check
        $memoryBytes = memory_get_usage(true);
        $checks['memory'] = [
            'status' => 'UP',
            'used_mb' => round($memoryBytes / (1024 * 1024), 2),
            'limit' => ini_get('memory_limit'),
        ];

        // 5. Environment & Security Check
        $checks['environment'] = [
            'app_env' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];

        $totalDurationMs = round((microtime(true) - $startTime) * 1000, 2);

        return response()->json([
            'status' => $isHealthy ? 'HEALTHY' : 'UNHEALTHY',
            'timestamp' => now()->toIso8601String(),
            'response_time_ms' => $totalDurationMs,
            'checks' => $checks,
        ], $isHealthy ? 200 : 503);
    }
}
