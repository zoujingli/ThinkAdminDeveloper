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
 * Class plugin\wuma\model\PluginWumaSalesOrderData
 *
 * @property int $auid 经销商编号
 * @property int $id
 * @property int $mode 操作方式(1扫码,2虚拟)
 * @property int $number 物码总数
 * @property int $status 记录状态(0无效,1有效)
 * @property int $xuid 来源经销商
 * @property string $code 操作单号
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 */
class PluginWumaSalesOrderData extends AbstractPrivate
{
}