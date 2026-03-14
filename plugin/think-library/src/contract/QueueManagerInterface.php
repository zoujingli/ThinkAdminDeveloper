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
