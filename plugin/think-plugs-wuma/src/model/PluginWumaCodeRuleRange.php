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

use think\admin\Model;
use think\model\relation\HasOne;

/**
 * 物码范围模型
 * @class PluginWumaCodeRuleRange
 * @package  plugin\wuma\model
 */
class PluginWumaCodeRuleRange extends Model
{
    /**
     * 关联物码主记录
     * @return \think\model\relation\HasOne
     */
    public function main(): HasOne
    {
        return $this->hasOne(PluginWumaCodeRule::class, 'batch', 'batch');
    }
}