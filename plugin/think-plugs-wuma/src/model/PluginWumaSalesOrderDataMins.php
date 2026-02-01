<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
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

namespace plugin\wuma\model;

/**
 * Class plugin\wuma\model\PluginWumaSalesOrderDataMins.
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
class PluginWumaSalesOrderDataMins extends AbstractPrivate {}
