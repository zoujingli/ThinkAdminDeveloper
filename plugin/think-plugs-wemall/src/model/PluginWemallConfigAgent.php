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
use think\db\exception\DbException;

/**
 * 鍟嗗煄浠ｇ悊绛夌骇鏁版嵁.
 *
 * @property int $id
 * @property int $number 绾у埆搴忓彿
 * @property int $status 绛夌骇鐘舵€?1浣跨敤,0绂佺敤)
 * @property int $upgrade_type 鍗囩骇瑙勫垯(0鍗曚釜,1鍚屾椂)
 * @property int $utime 鏇存柊鏃堕棿
 * @property string $cardbg 绛夌骇鍗＄墖
 * @property string $cover 绛夌骇鍥炬爣
 * @property string $create_time 鍒涘缓鏃堕棿
 * @property string $extra 鍗囩骇瑙勫垯
 * @property string $name 绾у埆鍚嶇О
 * @property string $remark 绾у埆鎻忚堪
 * @property string $update_time 鏇存柊鏃堕棿
 * @class PluginWemallConfigAgent
 */
class PluginWemallConfigAgent extends Abs
{
    protected $deleteTime = false;

    /**
     * 鑾峰彇浠ｇ悊绛夌骇.
     * @param ?string $first 澧炲姞棣栭」鍐呭
     * @param string $fields 鎸囧畾鏌ヨ瀛楁
     */
    public static function items(?string $first = null, string $fields = 'name,number as prefix,number'): array
    {
        $items = $first ? [-1 => ['name' => $first, 'prefix' => '-', 'number' => -1]] : [];
        $query = static::mk()->where(['status' => 1])->withoutField('id,utime,status,update_time,create_time');
        return array_merge($items, $query->order('number asc')->column($fields, 'number'));
    }

    /**
     * 鑾峰彇鏈€澶х骇鍒暟.
     * @throws DbException
     */
    public static function maxNumber(): int
    {
        if (static::mk()->count() < 1) {
            return 0;
        }
        return intval(static::mk()->max('number') + 1);
    }
}
