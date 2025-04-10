<?php

// +----------------------------------------------------------------------
// | Account Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
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

use think\model\relation\HasMany;

/**
 * 用户账号模型
 *
 * @property int $deleted 删除状态(0未删,1已删)
 * @property int $id
 * @property int $sort 排序权重
 * @property int $status 用户状态(0拉黑,1正常)
 * @property string $code 用户编号
 * @property string $create_time 注册时间
 * @property string $email 用户邮箱
 * @property string $extra 扩展数据
 * @property string $headimg 用户头像
 * @property string $nickname 用户昵称
 * @property string $password 认证密码
 * @property string $phone 用户手机
 * @property string $region_area 所在区域
 * @property string $region_city 所在城市
 * @property string $region_prov 所在省份
 * @property string $remark 备注(内部使用)
 * @property string $unionid UnionID
 * @property string $update_time 更新时间
 * @property string $username 用户姓名
 * @property-read \plugin\account\model\PluginAccountBind[] $clients
 * @class PluginAccountUser
 * @package plugin\account\model
 */
class PluginAccountUser extends Abs
{
    /**
     * 关联子账号
     * @return \think\model\relation\HasMany
     */
    public function clients(): HasMany
    {
        return $this->hasMany(PluginAccountBind::class, 'unid', 'id');
    }
}