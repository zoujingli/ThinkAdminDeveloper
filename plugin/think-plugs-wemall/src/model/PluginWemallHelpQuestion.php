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

use think\model\relation\HasMany;

/**
 * 工单数据模型.
 *
 * @property array $images 工单图片
 * @property int $deleted 删除状态(0未删,1已删)
 * @property int $id
 * @property int $sort 排序权重
 * @property int $status 工单状态(0取消,1新工单,2后台回复,3前台回复,4已完结)
 * @property int $unid 提问用户
 * @property string $content 工单描述
 * @property string $create_time 创建时间
 * @property string $name 工单标题
 * @property string $order_no 关联订单
 * @property string $phone 联系电话
 * @property string $update_time 更新时间
 * @property PluginWemallHelpQuestionX[] $comments
 * @class PluginWemallHelpQuestion
 */
class PluginWemallHelpQuestion extends AbsUser
{
    // 工单状态
    public const tStatus = [
        '已取消', '新工单', '后台回复', '用户回复', '已完结',
    ];

    /**
     * 格式化图片格式.
     * @param mixed $value
     */
    public function getImagesAttr($value): array
    {
        return str2arr($value ?? '', '|');
    }

    /**
     * 关联回复记录.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(PluginWemallHelpQuestionX::class, 'ccid', 'id');
    }
}
