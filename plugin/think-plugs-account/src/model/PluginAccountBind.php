<?php

// +----------------------------------------------------------------------
// | Account Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-account
// | github 代码仓库：https://github.com/zoujingli/think-plugs-account
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\account\model;

use plugin\account\service\Account;
use think\model\relation\HasMany;
use think\model\relation\HasOne;

/**
 * 用户子账号模型
 * @class PluginAccountBind
 * @package plugin\account\model
 */
class PluginAccountBind extends Abs
{
    /**
     * 关联主账号
     * @return \think\model\relation\HasOne
     */
    public function user(): HasOne
    {
        return $this->hasOne(PluginAccountUser::class, 'id', 'unid');
    }

    /**
     * 关联授权数据
     * @return \think\model\relation\HasMany
     */
    public function auths(): HasMany
    {
        return $this->hasMany(PluginAccountAuth::class, 'usid', 'id');
    }

    /**
     * 增加通道名称显示
     * @return array
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        if (isset($data['type'])) {
            $data['type_name'] = Account::get($data['type'])['name'] ?? $data['type'];
        }
        return $data;
    }
}