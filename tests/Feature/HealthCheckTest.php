<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_endpoint_returns_ok_status(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'response_time_ms',
                'checks' => [
                    'database' => ['status', 'connection'],
                    'cache' => ['status'],
                    'storage' => ['status'],
                    'memory' => ['status', 'used_mb', 'limit'],
                    'environment' => ['app_env', 'debug_mode', 'php_version', 'laravel_version'],
                ],
            ]);

        $this->assertEquals('HEALTHY', $response->json('status'));
        $this->assertEquals('UP', $response->json('checks.database.status'));
        $this->assertEquals('UP', $response->json('checks.cache.status'));
    }
}
