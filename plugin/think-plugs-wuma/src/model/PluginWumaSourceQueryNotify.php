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
 * 窜货查询记录模型.
 *
 * @property int $auid 代理用户
 * @property int $code 小码数码
 * @property int $id
 * @property int $times 查询次数
 * @property string $addr 详细地址
 * @property string $agent_area 代理区域
 * @property string $agent_city 代理城市
 * @property string $agent_prov 代理省份
 * @property string $area 所在区域
 * @property string $city 所在城市
 * @property string $create_time 创建时间
 * @property string $encode 物码编号
 * @property string $geoip 访问IP
 * @property string $gtype 定位类型
 * @property string $latlng 经纬度
 * @property string $pcode 商品编号
 * @property string $prov 所在省份
 * @property string $pspec 商品规格
 * @property string $type 记录类型
 * @property string $update_time 更新时间
 * @class PluginWumaSourceQueryNotify
 */
class PluginWumaSourceQueryNotify extends AbstractPrivate {}
