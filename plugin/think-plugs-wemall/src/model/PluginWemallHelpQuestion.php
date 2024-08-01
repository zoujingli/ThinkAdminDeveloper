<?php

// +----------------------------------------------------------------------
// | WeMall Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
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

use think\model\relation\HasMany;

/**
 * 工单数据模型
 * @class PluginWemallHelpQuestion
 * @package plugin\wemall\model
 */
class PluginWemallHelpQuestion extends AbsUser
{

    // 工单状态
    public const tStatus = [
        '已取消', '新工单', '后台回复', '用户回复', '已完结'
    ];

    /**
     * 格式化图片格式
     * @param mixed $value
     * @return array
     */
    public function getImagesAttr($value): array
    {
        return str2arr($value ?? '', '|');
    }

    /**
     * 关联回复记录
     * @return \think\model\relation\HasMany
     */
    public function comments(): HasMany
    {
        return $this->hasMany(PluginWemallHelpQuestionX::class, 'ccid', 'id');
    }
}