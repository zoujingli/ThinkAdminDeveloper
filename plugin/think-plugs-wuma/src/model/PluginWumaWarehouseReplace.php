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

namespace plugin\wuma\model;

/**
 * 标签替换模型.
 *
 * @property int $create_by 上传用户
 * @property string $delete_time 删除时间
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
