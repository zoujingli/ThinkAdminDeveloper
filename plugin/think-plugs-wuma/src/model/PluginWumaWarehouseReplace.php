<?php

// +----------------------------------------------------------------------
// | Wuma Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 收费插件 ( https://thinkadmin.top/fee-introduce.html )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wuma
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wuma
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wuma\model;

/**
 * 标签替换模型
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
 * @package plugin\wuma\model
 */
class PluginWumaWarehouseReplace extends AbstractPrivate
{
}