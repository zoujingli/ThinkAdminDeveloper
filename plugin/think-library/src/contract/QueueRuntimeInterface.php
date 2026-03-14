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
