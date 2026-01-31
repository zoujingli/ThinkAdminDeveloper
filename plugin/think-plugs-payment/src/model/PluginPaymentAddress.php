<?php

// +----------------------------------------------------------------------
// | Payment Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-account
// | github 代码仓库：https://github.com/zoujingli/think-plugs-account
// +----------------------------------------------------------------------

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

namespace plugin\payment\model;

use plugin\account\model\Abs;

/**
 * 用户地址模型.
 *
 * @property int $deleted 删除状态(1已删,0未删)
 * @property int $id
 * @property int $type 默认状态(0普通,1默认)
 * @property int $unid 主账号ID
 * @property string $create_time 创建时间
 * @property string $idcode 身体证证号
 * @property string $idimg1 身份证正面
 * @property string $idimg2 身份证反面
 * @property string $region_addr 地址-详情
 * @property string $region_area 地址-区域
 * @property string $region_city 地址-城市
 * @property string $region_prov 地址-省份
 * @property string $update_time 更新时间
 * @property string $user_name 收货人姓名
 * @property string $user_phone 收货人手机
 * @class PluginPaymentAddress
 */
class PluginPaymentAddress extends Abs {}
