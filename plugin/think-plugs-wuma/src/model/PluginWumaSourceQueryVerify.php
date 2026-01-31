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

namespace plugin\wuma\model;

/**
 * 物码查询验证记录.
 *
 * @property int $auid 代理用户
 * @property int $code 小码数码
 * @property int $id
 * @property int $times 查询次数
 * @property string $create_time 创建时间
 * @property string $encode 物码编号
 * @property string $ghash 商品编号
 * @property string $update_time 更新时间
 * @class PluginWumaSourceQueryVerify
 */
class PluginWumaSourceQueryVerify extends AbstractPrivate {}
