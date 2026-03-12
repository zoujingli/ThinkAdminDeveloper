<?php

declare(strict_types=1);

namespace think\admin\contract;

/**
 * Queue task execution contract.
 */
interface QueueHandlerInterface
{
    /**
     * Execute the queue task with runtime context.
     */
    public function handle(QueueRuntimeInterface $queue): mixed;
}
