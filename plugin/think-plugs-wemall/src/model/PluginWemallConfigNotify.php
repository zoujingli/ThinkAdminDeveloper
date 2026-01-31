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

namespace plugin\wemall\model;

use plugin\account\model\Abs;

/**
 * 系统通知内容数据.
 *
 * @property int $deleted 删除状态(1已删,0未删)
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
