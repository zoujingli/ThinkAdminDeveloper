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

namespace plugin\wuma\model;

/**
 * 标签替换模型.
 *
 * @property int $create_by 上传用户
 * @property int $deleted 删除状态(0未删,1已删)
 * @property int $id
 * @property int $lock 锁定状态
 * @property int $smin 原值小码
 * @property int $status 记录状态(0无效,1有效)
 * @property int $tmin 目标小码
 * @property string $create_time 创建时间
 * @property string $source 原物码值
 * @property string $target 目标物码
 * @property string $type 物码类型
 * @class PluginWumaWarehouseReplace
 */
class PluginWumaWarehouseReplace extends AbstractPrivate {}
