<?php

declare(strict_types=1);

namespace plugin\builder;

use plugin\builder\command\Build;

class Service extends \think\Service
{
    /**
     * 注册打包命令。
     */
    public function boot(): void
    {
        $this->commands([
            Build::class,
        ]);
    }
}
