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
 * 商城会员等级数据.
 *
 * @property int $id
 * @property int $number 级别序号
 * @property int $status 等级状态(1使用,0禁用)
 * @property int $upgrade_team 团队人数统计(0不计,1累计)
 * @property int $upgrade_type 升级规则(0单个,1同时)
 * @property int $utime 更新时间
 * @property string $cardbg 等级卡片
 * @property string $cover 等级图标
 * @property string $create_time 创建时间
 * @property string $extra 配置规则
 * @property string $name 级别名称
 * @property string $remark 用户级别描述
 * @property string $update_time 更新时间
 * @class PluginWemallConfigLevel
 */
class PluginWemallConfigLevel extends Abs
{
    /**
     * 获取会员等级.
     * @param ?string $first 增加首项内容
     * @param string $field 指定查询字段
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
     * 获取最大级别数.
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
