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

use plugin\account\model\Abs;

/**
 * 商城商品标签数据
 *
 * @property int $id
 * @property int $sort 排序权重
 * @property int $status 标签状态(1使用,0禁用)
 * @property string $create_time 创建时间
 * @property string $name 标签名称
 * @property string $remark 标签描述
 * @property string $update_time 更新时间
 * @class PluginWemallGoodsMark
 * @package plugin\wemall\model
 */
class PluginWemallGoodsMark extends Abs
{
    /**
     * 获取所有标签
     * @return array
     */
    public static function items(): array
    {
        return static::mk()->where(['status' => 1])->order('sort desc,id desc')->column('name');
    }
}