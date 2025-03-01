<?php

// +----------------------------------------------------------------------
// | Wuma Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
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
 * Class plugin\wuma\model\PluginWumaSalesOrderDataMins
 *
 * @property int $auid 代理编号
 * @property int $code 物码数据
 * @property int $ddid 数据编号
 * @property int $deleted 删除状态(0有效,1已删)
 * @property int $id
 * @property int $mode 操作类型(1扫码,2虚拟)
 * @property int $status 数据状态(0无效,1有效)
 * @property int $stock 库存有效
 * @property string $create_time 创建时间
 * @property string $ghash 商品哈唏
 * @property string $status_time 状态时间
 */
class PluginWumaSalesOrderDataMins extends AbstractPrivate
{
}