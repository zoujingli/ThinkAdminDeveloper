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

use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * Class plugin\wuma\model\PluginWumaSalesUserLevel.
 *
 * @property int $id
 * @property int $number 浠ｇ悊绾у埆搴忓彿
 * @property int $status 浠ｇ悊绛夌骇鐘舵€?1浣跨敤,0绂佺敤)
 * @property int $utime 绛夌骇鏇存柊鏃堕棿
 * @property string $create_time 绛夌骇鍒涘缓鏃堕棿
 * @property string $name 浠ｇ悊绾у埆鍚嶇О
 * @property string $remark 浠ｇ悊绾у埆鎻忚堪
 */
class PluginWumaSalesUserLevel extends AbstractPrivate
{
    protected $deleteTime = false;

    /**
     * 鑾峰彇鎵€鏈夌瓑绾ф暟鎹?
     * @param mixed $map
     */
    public static function lists($map = []): array
    {
        $one = static::mk()->order('number asc,utime asc');
        return $one->where($map)->column('name,status,number', 'number');
    }

    /**
     * 鑾峰彇鏈€澶х骇鍒暟.
     * @throws DbException
     */
    public static function stepMax(): int
    {
        return intval(static::mk()->count() < 1 ? 0 : static::mk()->max('number') + 1);
    }

    /**
     * 璇诲彇妯″瀷鏁版嵁.
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function stepSync()
    {
        $isasc = input('old_number', 0) <= input('number', 0);
        $order = $isasc ? 'number asc,utime asc' : 'number asc,utime desc';
        foreach (static::mk()->order($order)->select() as $number => $item) {
            $item->save(['number' => $number]);
        }
    }
}
