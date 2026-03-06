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

use plugin\account\model\Abs;

/**
 * 鍟嗗煄鍟嗗搧鏍囩鏁版嵁.
 *
 * @property int $id
 * @property int $sort 鎺掑簭鏉冮噸
 * @property int $status 鏍囩鐘舵€?1浣跨敤,0绂佺敤)
 * @property string $create_time 鍒涘缓鏃堕棿
 * @property string $name 鏍囩鍚嶇О
 * @property string $remark 鏍囩鎻忚堪
 * @property string $update_time 鏇存柊鏃堕棿
 * @class PluginWemallGoodsMark
 */
class PluginWemallGoodsMark extends Abs
{
    protected $deleteTime = false;

    /**
     * 鑾峰彇鎵€鏈夋爣绛?
     */
    public static function items(): array
    {
        return static::mk()->where(['status' => 1])->order('sort desc,id desc')->column('name');
    }
}
