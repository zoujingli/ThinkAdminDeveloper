<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdminDeveloper
 * +----------------------------------------------------------------------
 * | Copyright (c) 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | Official Website: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | Licensed: https://mit-license.org
 * | Disclaimer: https://thinkadmin.top/disclaimer
 * | Vip Rights: https://thinkadmin.top/vip-introduce
 * +----------------------------------------------------------------------
 * | Gitee Repository: https://gitee.com/zoujingli/ThinkAdmin
 * | Github Repository: https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace think\admin\contract;

/**
 * Queue provider contract bound by runtime plugins.
 */
interface QueueManagerInterface extends QueueRuntimeInterface
{
    public function initialize(string $code = ''): self;

    public function reset(int $wait = 0): self;

    public function registerTask(string $title, string $command, int $later = 0, array $data = [], int $loops = 0, ?int $legacyLoops = null): self;

    public function getCurrentCode(): string;

    public function isInContext(?string $code = null): bool;
}
