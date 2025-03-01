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
 * 物码查询验证记录
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
 * @package plugin\wuma\model
 */
class PluginWumaSourceQueryVerify extends AbstractPrivate
{
}