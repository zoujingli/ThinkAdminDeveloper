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
 * 会员充值数据.
 *
 * @property float $amount 操作金额
 * @property int $create_by 系统用户
 * @property int $deleted_by 系统用户
 * @property int $id
 * @property int $unid 账号编号
 * @property string $code 操作编号
 * @property string $create_time 创建时间
 * @property string $delete_time 删除时间
 * @property string $name 操作名称
 * @property string $remark 操作备注
 * @property string $update_time 更新时间
 * @class PluginWemallUserRecharge
 */
class PluginWemallUserRecharge extends AbsUser {}
