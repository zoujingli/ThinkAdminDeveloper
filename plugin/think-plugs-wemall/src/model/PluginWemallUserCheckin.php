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

namespace plugin\wemall\model;

/**
 * 用户签到数据.
 *
 * @property float $balance 赠送余额
 * @property float $integral 赠送积分
 * @property string $delete_time 删除时间
 * @property int $id
 * @property int $status 生效状态(0未生效,1已生效)
 * @property int $timed 奖励天数
 * @property int $times 连续天数
 * @property int $unid 用户UNID
 * @property string $create_time 创建时间
 * @property string $date 签到日期
 * @property string $update_time 更新时间
 * @class PluginWemallUserCheckin
 */
class PluginWemallUserCheckin extends AbsUser
{
    /**
     * 配置存储名称.
     * @var string
     */
    public static $ckcfg = 'plugin.normal.event.checkin';
}
