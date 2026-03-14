<?php

declare(strict_types=1);

namespace think\admin\contract;

/**
 * Queue provider contract bound by runtime plugins.
 */
interface QueueManagerInterface extends QueueRuntimeInterface
{
    public function initialize(string $code = ''): self;

    public function reset(int $wait = 0): self;

    public function registerTask(string $title, string $command, int $later = 0, array $data = [], int $rscript = 0, int $loops = 0): self;

    public function getCurrentCode(): string;

    public function isInContext(?string $code = null): bool;
}
