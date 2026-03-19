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

use think\Model;

/**
 * Queue runtime state contract.
 */
interface QueueRuntimeInterface
{
    public function getCode(): string;

    public function getTitle(): string;

    public function getData(): array;

    public function getRecord(): Model;

    public function progress(?int $status = null, ?string $message = null, ?string $progress = null, int $backline = 0): array;

    public function message(int $total, int $count, string $message = '', int $backline = 0): void;

    public function success(string $message): void;

    public function error(string $message): void;
}
