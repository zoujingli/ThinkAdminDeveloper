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

use think\model\relation\HasOne;

/**
 * 子账号授权模型.
 *
 * @property int $id
 * @property int $time 有效时间
 * @property int $usid 终端账号
 * @property string $create_time 创建时间
 * @property string $token 授权令牌
 * @property string $tokenv 授权验证
 * @property string $type 授权类型
 * @property string $update_time 更新时间
 * @property PluginAccountBind $client
 * @class PluginAccountAuth
 */
class PluginAccountAuth extends Abs
{
    /**
     * 关联子账号.
     */
    public function client(): HasOne
    {
        return $this->hasOne(PluginAccountBind::class, 'id', 'usid')->with(['user']);
    }
}
