<?php

// +----------------------------------------------------------------------
// | Developer Tools for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 Anyon <zoujingli@qq.com>
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-helper
// | github 代码仓库：https://github.com/zoujingli/think-plugs-helper
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\helper;

class Service extends \think\Service
{
    public function boot()
    {
        $this->commands([ModelGen::class]);
    }
}
