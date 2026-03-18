<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
 * +----------------------------------------------------------------------
 * | 版权所有 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | 官方网站: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | 开源协议 ( https://mit-license.org )
 * | 免责声明 ( https://thinkadmin.top/disclaimer )
 * | 会员特权 ( https://thinkadmin.top/vip-introduce )
 * +----------------------------------------------------------------------
 * | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
 * | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace plugin\worker\service;

use think\App;

/**
 * Normalize ThinkPlugsWorker configuration into a standard service shape.
 */
class WorkerConfig
{
    /** @var string[] */
    protected const SUPPORTED_DRIVERS = ['http', 'queue'];

    /** @var null|array<string, array> */
    protected ?array $resolvedServices = null;

    public function __construct(protected App $app) {}

    /**
     * Resolve all configured services.
     *
     * @return array<string, array>
     */
    public function services(bool $enabledOnly = false): array
    {
        $services = $this->resolvedServices ??= $this->resolveServices();
        if (!$enabledOnly) {
            return $services;
        }

        return array_filter($services, static fn (array $service): bool => !empty($service['enabled']));
    }

    /**
     * Resolve a single configured service.
     */
    public function service(string $name): array
    {
        $services = $this->services();
        if (!isset($services[$name])) {
            throw new \InvalidArgumentException("Worker service [{$name}] is not defined.");
        }

        return $services[$name];
    }

    /**
     * Resolve service targets.
     *
     * @return string[]
     */
    public function targets(string $target): array
    {
        if ($target === 'all') {
            return array_keys($this->services(true));
        }

        $this->service($target);
        return [$target];
    }

    /**
     * Build default runtime file path for a service.
     */
    public function runtimeFile(string $name, string $type): string
    {
        $service = $this->service($name);
        if (!empty($service['runtime'][$type])) {
            return (string)$service['runtime'][$type];
        }

        return $this->defaultRuntimeFile($service['slug'], $type);
    }

    /**
     * Resolve and normalize all configured services.
     *
     * @return array<string, array>
     */
    protected function resolveServices(): array
    {
        $services = [];
        $defaults = $this->resolveDefaults();
        foreach ((array)$this->app->config->get('worker.services', []) as $name => $service) {
            if (!is_string($name) || $name === '' || !is_array($service)) {
                continue;
            }

            $resolved = $this->normalizeService($name, $service, $defaults);
            if (!in_array($resolved['driver'], self::SUPPORTED_DRIVERS, true)) {
                continue;
            }

            $services[$name] = $resolved;
        }

        return $services;
    }

    /**
     * Resolve shared defaults from the standard config root.
     */
    protected function resolveDefaults(): array
    {
        $defaults = (array)$this->app->config->get('worker.defaults', []);

        return [
            'runtime' => $this->normalizeRuntime((array)($defaults['runtime'] ?? [])),
            'monitor' => $this->normalizeMonitor((array)($defaults['monitor'] ?? [])),
        ];
    }

    /**
     * Apply defaults to a service definition.
     */
    protected function normalizeService(string $name, array $service, array $defaults): array
    {
        $driver = strtolower((string)($service['driver'] ?? ($name === 'queue' ? 'queue' : 'http')));
        $service = array_replace_recursive($this->defaultService($name, $driver), $service);

        $service['name'] = $name;
        $service['slug'] = $this->slug((string)($service['slug'] ?? $name));
        $service['label'] = trim((string)($service['label'] ?? '')) ?: strtoupper($name);
        $service['enabled'] = !empty($service['enabled']);
        $service['driver'] = $driver;
        $service['classes'] = array_values(array_filter((array)$service['classes'], 'is_string'));
        $service['server'] = $this->normalizeServer((array)$service['server'], $driver);
        $service['process'] = $this->normalizeProcess((array)$service['process'], $driver, $service['slug']);
        $service['queue'] = $this->normalizeQueue((array)$service['queue']);
        $service['monitor'] = $this->normalizeMonitor(array_replace_recursive($defaults['monitor'], (array)$service['monitor']));
        $service['runtime'] = $this->normalizeRuntime(array_replace_recursive($defaults['runtime'], (array)$service['runtime']));

        foreach (['pidFile', 'statusFile', 'logFile'] as $type) {
            if (empty($service['runtime'][$type])) {
                $service['runtime'][$type] = $this->defaultRuntimeFile($service['slug'], $type);
            }
        }
        if (!empty($service['runtime']['stdoutFile'])) {
            $service['runtime']['stdoutFile'] = (string)$service['runtime']['stdoutFile'];
        }

        return $service;
    }

    /**
     * Provide the standard service structure.
     */
    protected function defaultService(string $name, string $driver): array
    {
        return [
            'name' => $name,
            'slug' => $name,
            'label' => strtoupper($name),
            'enabled' => true,
            'driver' => $driver,
            'classes' => [],
            'server' => [
                'scheme' => 'http',
                'listen' => '',
                'host' => '127.0.0.1',
                'port' => $driver === 'http' ? 2346 : 0,
                'context' => [],
                'callable' => null,
            ],
            'process' => [
                'name' => $driver === 'queue' ? 'ThinkAdminQueue' : 'ThinkAdmin' . ucfirst($this->slug($name)),
                'count' => $driver === 'queue' ? 1 : 4,
            ],
            'queue' => [
                'scan_interval' => 1,
                'batch_limit' => 20,
            ],
            'runtime' => [],
            'monitor' => [],
        ];
    }

    /**
     * Normalize server options.
     */
    protected function normalizeServer(array $server, string $driver): array
    {
        $server = array_replace([
            'scheme' => 'http',
            'listen' => '',
            'host' => '127.0.0.1',
            'port' => $driver === 'http' ? 2346 : 0,
            'context' => [],
            'callable' => null,
        ], $server);

        $server['scheme'] = strtolower(trim((string)$server['scheme'])) ?: 'http';
        $server['listen'] = trim((string)$server['listen']);
        $server['host'] = trim((string)$server['host']);
        $server['port'] = max(0, intval($server['port']));
        $server['context'] = is_array($server['context']) ? $server['context'] : [];

        return $server;
    }

    /**
     * Normalize worker process options.
     */
    protected function normalizeProcess(array $process, string $driver, string $slug): array
    {
        $process = array_replace([
            'name' => $driver === 'queue' ? 'ThinkAdminQueue' : 'ThinkAdmin' . ucfirst($slug),
            'count' => $driver === 'queue' ? 1 : 4,
        ], $process);

        $process['name'] = trim((string)$process['name']) ?: ($driver === 'queue' ? 'ThinkAdminQueue' : 'ThinkAdmin' . ucfirst($slug));
        $process['count'] = max(1, intval($process['count']));

        return $process;
    }

    /**
     * Normalize queue dispatcher options.
     */
    protected function normalizeQueue(array $queue): array
    {
        $queue = array_replace([
            'scan_interval' => 1,
            'batch_limit' => 20,
        ], $queue);

        $queue['scan_interval'] = max(1, intval($queue['scan_interval']));
        $queue['batch_limit'] = max(1, intval($queue['batch_limit']));

        return $queue;
    }

    /**
     * Normalize monitor options.
     */
    protected function normalizeMonitor(array $monitor): array
    {
        $monitor = array_replace_recursive(['files' => [], 'memory' => []], $monitor);

        $files = (array)$monitor['files'];
        $files = array_replace([
            'enabled' => true,
            'interval' => 3,
            'paths' => ['app', 'config', 'route', 'plugin'],
            'extensions' => ['php', 'env', 'ini', 'yaml', 'yml'],
        ], $files);

        $memory = (array)$monitor['memory'];
        $memory = array_replace([
            'enabled' => true,
            'interval' => 60,
            'limit' => '1G',
        ], $memory);

        $files['enabled'] = !empty($files['enabled']);
        $files['interval'] = $files['enabled'] ? max(0, intval($files['interval'])) : 0;
        $files['paths'] = array_values(array_filter((array)$files['paths'], 'is_string'));
        $files['extensions'] = array_values(array_filter((array)$files['extensions'], 'is_string'));

        $memory['enabled'] = !empty($memory['enabled']);
        $memory['interval'] = $memory['enabled'] ? max(0, intval($memory['interval'])) : 0;
        if (!is_string($memory['limit']) && !is_int($memory['limit'])) {
            $memory['limit'] = '';
        }

        return ['files' => $files, 'memory' => $memory];
    }

    /**
     * Normalize runtime keys while accepting snake_case aliases.
     */
    protected function normalizeRuntime(array $runtime): array
    {
        return $this->renameKeys($runtime, [
            'pid_file' => 'pidFile',
            'status_file' => 'statusFile',
            'log_file' => 'logFile',
            'stdout_file' => 'stdoutFile',
            'log_max_size' => 'logFileMaxSize',
            'stop_timeout' => 'stopTimeout',
            'event_loop' => 'eventLoopClass',
            'on_master_reload' => 'onMasterReload',
            'on_master_stop' => 'onMasterStop',
            'on_worker_exit' => 'onWorkerExit',
        ]);
    }

    /**
     * Rename aliased keys without overwriting the normalized key.
     */
    protected function renameKeys(array $config, array $aliases): array
    {
        foreach ($aliases as $old => $new) {
            if (array_key_exists($old, $config) && !array_key_exists($new, $config)) {
                $config[$new] = $config[$old];
            }
        }

        return $config;
    }

    /**
     * Sanitize service names for runtime files.
     */
    protected function slug(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/[^a-z0-9_-]+/', '-', $name) ?: 'worker';
        return trim($name, '-');
    }

    /**
     * Build a default runtime file path from a service slug.
     */
    protected function defaultRuntimeFile(string $slug, string $type): string
    {
        return match ($type) {
            // Phar 环境下需要写入外部可写目录
            'pidFile' => runpath("safefile/worker/{$slug}.pid"),
            'statusFile' => runpath("safefile/worker/{$slug}.status"),
            'logFile' => runpath("safefile/worker/{$slug}.log"),
            'stdoutFile' => runpath("safefile/worker/{$slug}.stdout.log"),
            default => throw new \InvalidArgumentException("Unsupported runtime file type [{$type}]."),
        };
    }
}
