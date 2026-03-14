<?php

declare(strict_types=1);

namespace plugin\worker\tests;

use PHPUnit\Framework\TestCase;
use plugin\worker\service\WorkerConfig;
use think\App;
use think\admin\service\RuntimeService;

/**
 * @internal
 * @coversNothing
 */
class WorkerConfigTest extends TestCase
{
    public function testStandardConfigIsNormalizedWithoutLegacyAliases(): void
    {
        $config = new WorkerConfig($this->newApp([
            'defaults' => [
                'runtime' => ['stdout_file' => '/tmp/worker.shared.stdout.log'],
                'monitor' => ['files' => ['interval' => 9]],
            ],
            'services' => [
                'http' => [
                    'enabled' => true,
                    'label' => 'Smoke HTTP',
                    'driver' => 'http',
                    'server' => ['host' => '127.0.0.1', 'port' => 2360],
                    'process' => ['name' => 'SmokeHttp', 'count' => 3],
                ],
                'queue' => [
                    'enabled' => false,
                    'driver' => 'queue',
                ],
            ],
        ]));

        $http = $config->service('http');

        $this->assertSame('http', $http['driver']);
        $this->assertSame('127.0.0.1', $http['server']['host']);
        $this->assertSame(2360, $http['server']['port']);
        $this->assertSame('SmokeHttp', $http['process']['name']);
        $this->assertSame(3, $http['process']['count']);
        $this->assertSame('/tmp/worker.shared.stdout.log', $http['runtime']['stdoutFile']);
        $this->assertSame(9, $http['monitor']['files']['interval']);
        $this->assertSame(['http'], $config->targets('all'));
        $this->assertArrayNotHasKey('host', $http);
        $this->assertArrayNotHasKey('worker', $http);
        $this->assertArrayNotHasKey('dispatch', $http);
    }

    public function testLegacyServiceKeysAreIgnoredByStandardConfig(): void
    {
        $config = new WorkerConfig($this->newApp([
            'services' => [
                'http' => [
                    'enabled' => true,
                    'host' => '10.0.0.8',
                    'port' => 9527,
                    'callback' => 'legacy',
                    'worker' => ['count' => 9],
                    'dispatch' => ['interval' => 9, 'limit' => 99],
                ],
            ],
        ]));

        $http = $config->service('http');

        $this->assertSame('127.0.0.1', $http['server']['host']);
        $this->assertSame(2346, $http['server']['port']);
        $this->assertNull($http['server']['callable']);
        $this->assertSame(4, $http['process']['count']);
        $this->assertSame(1, $http['queue']['scan_interval']);
        $this->assertSame(20, $http['queue']['batch_limit']);
    }

    private function newApp(array $worker): App
    {
        $app = new App(WORKER_TEST_PROJECT_ROOT);
        RuntimeService::init($app);
        $app->config->set($worker, 'worker');
        return $app;
    }
}
