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
 * 常见问题数据模型.
 *
 * @property string $delete_time 删除时间
 * @property int $fid 来自反馈
 * @property int $id
 * @property int $num_er 未解决数
 * @property int $num_ok 已解决数
 * @property int $num_read 阅读次数
 * @property int $sort 排序权重
 * @property int $status 展示状态(1使用,0禁用)
 * @property string $content 问题内容
 * @property string $create_time 创建时间
 * @property string $name 问题标题
 * @property string $update_time 更新时间
 * @class PluginWemallHelpProblem
 */
class PluginWemallHelpProblem extends AbsUser {}
