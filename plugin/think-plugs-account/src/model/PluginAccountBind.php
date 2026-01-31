<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | Payment Plugin for ThinkAdmin
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

namespace plugin\account\model;

use plugin\account\service\Account;
use think\model\relation\HasMany;
use think\model\relation\HasOne;

/**
 * 用户子账号模型.
 *
 * @property int $deleted 删除状态(0未删,1已删)
 * @property int $id
 * @property int $sort 排序权重
 * @property int $status 账号状态
 * @property int $unid 会员编号
 * @property string $appid APPID
 * @property string $create_time 注册时间
 * @property string $extra 扩展数据
 * @property string $headimg 用户头像
 * @property string $nickname 用户昵称
 * @property string $openid OPENID
 * @property string $password 登录密码
 * @property string $phone 绑定手机
 * @property string $type 终端类型
 * @property string $unionid UnionID
 * @property string $update_time 更新时间
 * @property PluginAccountAuth[] $auths
 * @property PluginAccountUser $user
 * @class PluginAccountBind
 */
class PluginAccountBind extends Abs
{
    /**
     * 关联主账号.
     */
    public function user(): HasOne
    {
        return $this->hasOne(PluginAccountUser::class, 'id', 'unid');
    }

    /**
     * 关联授权数据.
     */
    public function auths(): HasMany
    {
        return $this->hasMany(PluginAccountAuth::class, 'usid', 'id');
    }

    /**
     * 增加通道名称显示.
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
