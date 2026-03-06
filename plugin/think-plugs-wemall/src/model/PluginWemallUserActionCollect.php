<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
 * +----------------------------------------------------------------------
 * | 鐗堟潈鎵€鏈?2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | 瀹樻柟缃戠珯: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | 寮€婧愬崗璁?( https://mit-license.org )
 * | 鍏嶈矗澹版槑 ( https://thinkadmin.top/disclaimer )
 * | 浼氬憳鐗规潈 ( https://thinkadmin.top/vip-introduce )
 * +----------------------------------------------------------------------
 * | gitee 浠ｇ爜浠撳簱锛歨ttps://gitee.com/zoujingli/ThinkAdmin
 * | github 浠ｇ爜浠撳簱锛歨ttps://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace plugin\wemall\model;

use think\model\relation\HasOne;

/**
 * 鐢ㄦ埛鏀惰棌琛屼负鏁版嵁.
 *
 * @property int $id
 * @property int $sort 鎺掑簭鏉冮噸
 * @property int $times 璁板綍娆℃暟
 * @property int $unid 鐢ㄦ埛缂栧彿
 * @property string $create_time 鍒涘缓鏃堕棿
 * @property string $gcode 鍟嗗搧缂栧彿
 * @property string $update_time 鏇存柊鏃堕棿
 * @property PluginWemallGoods $goods
 * @class PluginWemallUserActionCollect
 */
class PluginWemallUserActionCollect extends AbsUser
{
    protected $deleteTime = false;

    /**
     * 鍏宠仈鍟嗗搧淇℃伅.
     */
    public function goods(): HasOne
    {
        return $this->hasOne(PluginWemallGoods::class, 'code', 'gcode');
    }
}
