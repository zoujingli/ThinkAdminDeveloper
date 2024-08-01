<?php

// +----------------------------------------------------------------------
// | WeMall Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wemall
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wemall
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wemall\model;

use plugin\account\model\PluginAccountUser;
use think\model\relation\HasOne;

/**
 * 手动创建会员用户模型
 * @class PluginWemallUserCreate
 * @package plugin\wemall\model
 */
class PluginWemallUserCreate extends AbsUser
{
    /**
     * 关联代理用户
     * @return \think\model\relation\HasOne
     */
    public function agent(): HasOne
    {
        return $this->hasOne(PluginAccountUser::class, 'phone', 'phone');
    }
}