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
 *
 * @property int $code_length 数码长度
 * @property int $id
 * @property int $range_after 结束数码
 * @property int $range_number 数码数量
 * @property int $range_start 起始数码
 * @property int $type 物码类型
 * @property string $batch 物码批次号
 * @property string $code_type 数码类型(min小码,mid中码,max大码)
 * @property-read \plugin\wuma\model\PluginWumaCodeRule $main
 * @class PluginWumaCodeRuleRange
 * @package plugin\wuma\model
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