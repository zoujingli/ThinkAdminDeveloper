<?php

declare(strict_types=1);

namespace think\admin\service;

use think\Container;
use think\admin\contract\QueueManagerInterface;

/**
 * Standard queue facade.
 * The concrete runtime implementation is provided by ThinkPlugsWorker.
 */
class QueueService
{
    public const BIND_NAME = 'think.admin.runtime.queue';

    /**
     * Resolve the concrete queue provider.
     */
    protected static function provider(array $vars = [], bool $newInstance = false): QueueManagerInterface
    {
        $container = Container::getInstance();
        if (!$container->bound(static::BIND_NAME)) {
            throw new \RuntimeException('ThinkPlugsWorker is required for queue runtime operations.');
        }

        $provider = $container->make($container->getAlias(static::BIND_NAME), $vars, $newInstance);
        if (!$provider instanceof QueueManagerInterface) {
            throw new \RuntimeException('Queue runtime provider must implement think\\admin\\contract\\QueueManagerInterface.');
        }

        return $provider;
    }

    public static function instance(array $var = [], bool $new = false): QueueManagerInterface
    {
        return static::provider($var, $new);
    }

    public static function register(string $title, string $command, int $later = 0, array $data = [], int $rscript = 0, int $loops = 0): QueueManagerInterface
    {
        return static::provider([], true)->registerTask($title, $command, $later, $data, $rscript, $loops);
    }

    public static function currentCode(): string
    {
        return static::provider()->getCurrentCode();
    }

    public static function inContext(?string $code = null): bool
    {
        return static::provider()->isInContext($code);
    }
}
