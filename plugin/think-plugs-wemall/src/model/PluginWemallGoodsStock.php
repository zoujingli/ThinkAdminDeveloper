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
 * 商城商品库存数据
 *
 * @property int $deleted 删除状态(0未删,1已删)
 * @property int $gstock 入库数量
 * @property int $id
 * @property int $status 数据状态(1使用,0禁用)
 * @property string $batch_no 操作批量
 * @property string $create_time 创建时间
 * @property string $gcode 商品编号
 * @property string $ghash 商品哈希
 * @property string $gspec 商品规格
 * @property string $update_time 更新时间
 * @class PluginWemallGoodsStock
 * @package plugin\wemall\model
 */
class PluginWemallGoodsStock extends Abs
{
}