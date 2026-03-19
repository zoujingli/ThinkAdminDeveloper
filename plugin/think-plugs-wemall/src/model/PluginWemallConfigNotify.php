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

use plugin\account\model\Abs;

/**
 * 系统通知内容数据.
 *
 * @property string $delete_time 删除时间
 * @property int $id
 * @property int $num_read 阅读次数
 * @property int $sort 排序权重
 * @property int $status 激活状态(0无效,1有效)
 * @property int $tips TIPS显示
 * @property string $code 通知编号
 * @property string $content 通知内容
 * @property string $cover 通知图片
 * @property string $create_time 创建时间
 * @property string $levels 用户等级
 * @property string $name 通知标题
 * @property string $remark 通知描述
 * @property string $update_time 更新时间
 * @class PluginWemallConfigNotify
 */
class PluginWemallConfigNotify extends Abs {}
