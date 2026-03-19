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
 * Class plugin\wuma\model\PluginWumaSalesOrderDataMins.
 *
 * @property int $auid 代理编号
 * @property int $code 物码数据
 * @property int $ddid 数据编号
 * @property string $delete_time 删除时间
 * @property int $id
 * @property int $mode 操作类型(1扫码,2虚拟)
 * @property int $status 数据状态(0无效,1有效)
 * @property int $stock 库存有效
 * @property string $create_time 创建时间
 * @property string $ghash 商品哈唏
 * @property string $status_time 状态时间
 */
class PluginWumaSalesOrderDataMins extends AbstractPrivate {}
