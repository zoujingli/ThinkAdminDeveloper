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

use plugin\account\model\Abs;
use plugin\account\model\PluginAccountUser;
use think\model\relation\HasOne;

/**
 * 用户基础模型
 * @class AbsUser
 * @package plugin\wemall\model
 */
abstract class AbsUser extends Abs
{
    /**
     * 关联当前用户
     * @return \think\model\relation\HasOne
     */
    public function user(): HasOne
    {
        return $this->hasOne(PluginAccountUser::class, 'id', 'unid');
    }

    /**
     * 绑定用户数据
     * @return HasOne
     */
    public function bindUser(): HasOne
    {
        return $this->user()->bind([
            'user_phone'       => 'phone',
            'user_headimg'     => 'headimg',
            'user_username'    => 'username',
            'user_nickname'    => 'nickname',
            'user_create_time' => 'create_time',
        ]);
    }
}