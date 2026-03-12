<?php

declare(strict_types=1);

namespace plugin\worker\support;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use think\App;
use Workerman\Timer;
use Workerman\Worker;

use const DIRECTORY_SEPARATOR;
use const SIGUSR2;

/**
 * Debug file watcher and memory recycler for long-running workers.
 */
class WorkerMonitor
{
    protected array $timers = [];

    protected ?string $fileSignature = null;

    protected bool $reloading = false;

    protected array $monitor = [];

    public function __construct(
        protected App $app,
        protected Worker $worker,
        protected array $config = [],
    ) {
        $this->monitor = (array)($this->config['monitor'] ?? $this->config);
    }

    public function start(): void
    {
        $this->bootFileMonitor();
        $this->bootMemoryMonitor();
    }

    public function stop(): void
    {
        foreach ($this->timers as $timerId) {
            Timer::del($timerId);
        }

        $this->timers = [];
    }

    protected function bootFileMonitor(): void
    {
        $enabled = !array_key_exists('enabled', $this->monitor['files'] ?? []) || !empty($this->monitor['files']['enabled']);
        $time = (int)($this->monitor['files']['interval'] ?? $this->monitor['files']['time'] ?? 0);
        if ($time < 1 || !$this->app->isDebug() || $this->worker->id !== 0) {
            return;
        }
        if (!$enabled) {
            return;
        }

        $this->fileSignature = $this->buildFileSignature();
        if ($this->fileSignature === null) {
            return;
        }

        $this->timers[] = Timer::add($time, function (): void {
            $signature = $this->buildFileSignature();
            if ($signature === null || $signature === $this->fileSignature) {
                return;
            }

            $this->fileSignature = $signature;
            $this->reload('Source files changed, reloading worker processes.');
        });
    }

    protected function bootMemoryMonitor(): void
    {
        $enabled = !array_key_exists('enabled', $this->monitor['memory'] ?? []) || !empty($this->monitor['memory']['enabled']);
        $time = (int)($this->monitor['memory']['interval'] ?? $this->monitor['memory']['time'] ?? 0);
        $limit = $this->parseBytes($this->monitor['memory']['limit'] ?? null);
        if ($time < 1 || $limit < 1) {
            return;
        }
        if (!$enabled) {
            return;
        }

        $this->timers[] = Timer::add($time, function () use ($limit): void {
            $usage = memory_get_usage(true);
            if ($usage < $limit) {
                return;
            }

            $this->reload(sprintf(
                'Worker memory usage %s exceeded limit %s, reloading worker processes.',
                $this->formatBytes($usage),
                $this->formatBytes($limit),
            ));
        });
    }

    protected function reload(string $message): void
    {
        if ($this->reloading) {
            return;
        }

        $this->reloading = true;
        $this->stop();
        Worker::log($message);

        if (DIRECTORY_SEPARATOR === '\\') {
            Worker::log('Automatic reload is unavailable on Windows. Restart `php think xadmin:worker` manually.');
            return;
        }

        if (function_exists('posix_getppid') && function_exists('posix_kill')) {
            @posix_kill(posix_getppid(), SIGUSR2);
            return;
        }

        Worker::log('Unable to signal the Workerman master process for reload.');
    }

    protected function buildFileSignature(): ?string
    {
        clearstatcache();
        $snapshot = [];
        foreach ($this->watchPaths() as $path) {
            if (is_file($path)) {
                if ($this->matchesExtension($path)) {
                    $snapshot[] = $path . ':' . filemtime($path);
                }
                continue;
            }

            if (!is_dir($path)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue;
                }

                $pathname = $file->getPathname();
                if ($this->matchesExtension($pathname)) {
                    $snapshot[] = $pathname . ':' . $file->getMTime();
                }
            }
        }

        if ($snapshot === []) {
            return null;
        }

        sort($snapshot);
        return md5(implode('|', $snapshot));
    }

    protected function watchPaths(): array
    {
        $paths = (array)($this->monitor['files']['paths'] ?? $this->monitor['files']['path'] ?? []);
        if ($paths === []) {
            $paths = ['app', 'config', 'route', 'plugin'];
        }

        $root = rtrim($this->app->getRootPath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $items = [];
        foreach ($paths as $path) {
            if (!is_string($path) || $path === '') {
                continue;
            }

            $items[] = $this->isAbsolutePath($path)
                ? rtrim($path, DIRECTORY_SEPARATOR)
                : rtrim($root . ltrim($path, DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);
        }

        return array_values(array_unique($items));
    }

    protected function matchesExtension(string $path): bool
    {
        $exts = (array)($this->monitor['files']['extensions'] ?? $this->monitor['files']['exts'] ?? ['*']);
        if ($exts === [] || in_array('*', $exts, true)) {
            return true;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($extension, array_map('strtolower', $exts), true);
    }

    protected function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('#^[A-Za-z]:[\\\\/]#', $path) === 1;
    }

    protected function parseBytes(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (!is_string($value) || trim($value) === '') {
            return 0;
        }

        $value = strtoupper(trim($value));
        if (!preg_match('/^(\d+)([KMGTP]?)$/', $value, $matches)) {
            return 0;
        }

        $bytes = (int)$matches[1];
        return match ($matches[2]) {
            'P' => $bytes * 1024 ** 5,
            'T' => $bytes * 1024 ** 4,
            'G' => $bytes * 1024 ** 3,
            'M' => $bytes * 1024 ** 2,
            'K' => $bytes * 1024,
            default => $bytes,
        };
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $power = 0;
        $size = (float)$bytes;
        while ($size >= 1024 && $power < count($units) - 1) {
            $size /= 1024;
            ++$power;
        }

        return sprintf('%.2f%s', $size, $units[$power]);
    }
}
