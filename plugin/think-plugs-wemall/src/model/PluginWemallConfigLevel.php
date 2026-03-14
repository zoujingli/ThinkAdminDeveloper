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

namespace plugin\wemall\model;

use plugin\account\model\Abs;
use think\db\exception\DbException;

/**
 * 鍟嗗煄浼氬憳绛夌骇鏁版嵁.
 *
 * @property int $id
 * @property int $number 绾у埆搴忓彿
 * @property int $status 绛夌骇鐘舵€?1浣跨敤,0绂佺敤)
 * @property int $upgrade_team 鍥㈤槦浜烘暟缁熻(0涓嶈,1绱)
 * @property int $upgrade_type 鍗囩骇瑙勫垯(0鍗曚釜,1鍚屾椂)
 * @property int $utime 鏇存柊鏃堕棿
 * @property string $cardbg 绛夌骇鍗＄墖
 * @property string $cover 绛夌骇鍥炬爣
 * @property string $create_time 鍒涘缓鏃堕棿
 * @property string $extra 閰嶇疆瑙勫垯
 * @property string $name 绾у埆鍚嶇О
 * @property string $remark 鐢ㄦ埛绾у埆鎻忚堪
 * @property string $update_time 鏇存柊鏃堕棿
 * @class PluginWemallConfigLevel
 */
class PluginWemallConfigLevel extends Abs
{
    protected $deleteTime = false;

    /**
     * 鑾峰彇浼氬憳绛夌骇.
     * @param ?string $first 澧炲姞棣栭」鍐呭
     * @param string $field 鎸囧畾鏌ヨ瀛楁
     */
    public static function items(?string $first = null, string $field = 'name,number as prefix,number,upgrade_team,extra'): array
    {
        try {
            $query = static::mk()->withoutField('id,utime,status,update_time,create_time');
            $items = $query->field($field)->where(['status' => 1])->order('number asc')->select()->toArray();
            if ($first) {
                array_unshift($items, ['name' => $first, 'prefix' => '-', 'number' => -1, 'upgrade_team' => 0, 'extra' => []]);
            }
            return $items;
        } catch (\Exception $exception) {
            trace_file($exception);
            return [];
        }
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
