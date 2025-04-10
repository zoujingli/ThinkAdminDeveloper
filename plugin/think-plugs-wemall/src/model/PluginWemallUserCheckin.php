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

/**
 * 用户签到模型
 *
 * @property float $balance 赠送余额
 * @property float $integral 赠送积分
 * @property int $deleted 删除状态(0未删除,1已删除)
 * @property int $id
 * @property int $status 生效状态(0未生效,1已生效)
 * @property int $timed 奖励天数
 * @property int $times 连续天数
 * @property int $unid 用户UNID
 * @property string $create_time 创建时间
 * @property string $date 签到日期
 * @property string $update_time 更新时间
 * @class PluginWemallUserCheckin
 * @package plugin\wemall\model
 */
class PluginWemallUserCheckin extends AbsUser
{
    /**
     * 配置存储名称
     * @var string
     */
    public static $ckcfg = 'plugin.normal.event.checkin';
}