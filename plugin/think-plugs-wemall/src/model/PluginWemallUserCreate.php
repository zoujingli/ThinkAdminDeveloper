<?php

// +----------------------------------------------------------------------
// | WeMall Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
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
 *
 * @property bool $agent_entry 代理权限
 * @property float $rebate_total 累计返利
 * @property float $rebate_usable 可提返利
 * @property int $deleted 删除状态(0未删,1已删)
 * @property int $id
 * @property int $status 记录状态(0无效,1有效)
 * @property int $unid 关联用户
 * @property string $agent_phone 上级手机
 * @property string $create_time 创建时间
 * @property string $headimg 用户头像
 * @property string $name 用户姓名
 * @property string $password 初始密码
 * @property string $phone 手机号码
 * @property string $rebate_total_code 记录编号
 * @property string $rebate_total_desc 记录描述
 * @property string $rebate_usable_code 记录编号
 * @property string $rebate_usable_desc 记录描述
 * @property string $update_time 更新时间
 * @property-read \plugin\account\model\PluginAccountUser $agent
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