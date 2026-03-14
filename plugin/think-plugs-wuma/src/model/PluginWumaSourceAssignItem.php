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

use think\model\relation\HasOne;

/**
 * 璧嬬爜鎵规鏁版嵁妯″瀷.
 *
 * @property int $id
 * @property int $lock 鏄惁宸查攣瀹? * @property int $range_after 缁撴潫鐗╃爜鍖洪棿
 * @property int $range_start 寮€濮嬬墿鐮佸尯闂? * @property int $real 鏄惁鐪熼攣瀹? * @property string $batch 璧嬬爜鎵规鍙? * @property string $cbatch 鐗╃爜鎵规鍙? * @property string $create_time 鍒涘缓鏃堕棿
 * @property string $pbatch 鐢熶骇鎵规鍙? * @property string $update_time 鏇存柊鏃堕棿
 * @property PluginWumaSourceAssign $assign
 * @property PluginWumaSourceProduce $bind_produce
 * @property PluginWumaSourceProduce $produce
 * @class PluginWumaSourceBatchAssignItem
 */
class PluginWumaSourceAssignItem extends AbstractPrivate
{
    protected $deleteTime = false;

    /**
     * 鍏宠仈鐢熶骇妯℃澘鏁版嵁.
     */
    public function produce(): HasOne
    {
        $one = $this->hasOne(PluginWumaSourceProduce::class, 'batch', 'pbatch');
        return $one->with(['bindGoods', 'bindTemplate']);
    }

    /**
     * 鍏宠仈鐢熶骇鎵规鏁版嵁.
     */
    public function bindProduce(): HasOne
    {
        return $this->produce()->bind([
            'tcode' => 'tcode',
            'tname' => 'tname',
            'ghash' => 'ghash',
            'gcode' => 'gcode',
            'gname' => 'gname',
            'gspec' => 'gspec',
            'gunit' => 'gunit',
            'gcover' => 'gcover',
        ]);
    }

    /**
     * 鍏宠仈鐢熶骇妯℃澘鏁版嵁.
     */
    public function assign(): HasOne
    {
        return $this->hasOne(PluginWumaSourceAssign::class, 'batch', 'batch');
    }
}
