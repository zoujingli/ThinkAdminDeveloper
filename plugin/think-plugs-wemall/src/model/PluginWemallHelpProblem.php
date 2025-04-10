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
 * 常见问题数据模型
 *
 * @property int $deleted 删除状态(0未删,1已删)
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
 * @package plugin\wemall\model
 */
class PluginWemallHelpProblem extends AbsUser
{
}