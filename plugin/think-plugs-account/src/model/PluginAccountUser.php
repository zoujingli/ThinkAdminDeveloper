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

namespace plugin\account\model;

use think\model\relation\HasMany;

/**
 * 用户账号模型.
 *
 * @property string $delete_time 删除时间
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
 * @property PluginAccountBind[] $clients
 * @class PluginAccountUser
 */
class PluginAccountUser extends Abs
{
    /**
     * 关联子账号.
     */
    public function clients(): HasMany
    {
        return $this->hasMany(PluginAccountBind::class, 'unid', 'id');
    }
}
