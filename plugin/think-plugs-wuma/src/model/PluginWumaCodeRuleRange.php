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
