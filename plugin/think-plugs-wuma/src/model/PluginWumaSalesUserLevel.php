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
 * @property int $number 代理级别序号
 * @property int $status 代理等级状态(1使用,0禁用)
 * @property int $utime 等级更新时间
 * @property string $create_time 等级创建时间
 * @property string $name 代理级别名称
 * @property string $remark 代理级别描述
 */
class PluginWumaSalesUserLevel extends AbstractPrivate
{
    /**
     * 获取所有等级数据.
     * @param mixed $map
     */
    public static function lists($map = []): array
    {
        $one = static::mk()->order('number asc,utime asc');
        return $one->where($map)->column('name,status,number', 'number');
    }

    /**
     * 获取最大级别数.
     * @throws DbException
     */
    public static function stepMax(): int
    {
        return intval(static::mk()->count() < 1 ? 0 : static::mk()->max('number') + 1);
    }

    /**
     * 读取模型数据.
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
