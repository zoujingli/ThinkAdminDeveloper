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

use think\admin\Model;
use think\model\relation\HasOne;

/**
 * 物码范围模型.
 *
 * @property int $code_length 数码长度
 * @property int $id
 * @property int $range_after 结束数码
 * @property int $range_number 数码数量
 * @property int $range_start 起始数码
 * @property int $type 物码类型
 * @property string $batch 物码批次号
 * @property string $code_type 数码类型(min小码,mid中码,max大码)
 * @property PluginWumaCodeRule $main
 * @class PluginWumaCodeRuleRange
 */
class PluginWumaCodeRuleRange extends Model
{
    /**
     * 关联物码主记录.
     */
    public function main(): HasOne
    {
        return $this->hasOne(PluginWumaCodeRule::class, 'batch', 'batch');
    }
}
