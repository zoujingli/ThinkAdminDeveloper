<?php

// +----------------------------------------------------------------------
// | WeMall Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wemall
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wemall
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wemall\model;

use plugin\account\model\Abs;

/**
 * 商城代理等级数据
 *
 * @property int $id
 * @property int $number 级别序号
 * @property int $status 等级状态(1使用,0禁用)
 * @property int $upgrade_type 升级规则(0单个,1同时)
 * @property int $utime 更新时间
 * @property string $cardbg 等级卡片
 * @property string $cover 等级图标
 * @property string $create_time 创建时间
 * @property string $extra 升级规则
 * @property string $name 级别名称
 * @property string $remark 级别描述
 * @property string $update_time 更新时间
 * @class PluginWemallConfigAgent
 * @package plugin\wemall\model
 */
class PluginWemallConfigAgent extends Abs
{
    /**
     * 获取代理等级
     * @param ?string $first 增加首项内容
     * @param string $fields 指定查询字段
     * @return array
     */
    public static function items(string $first = null, string $fields = 'name,number as prefix,number'): array
    {
        $items = $first ? [-1 => ['name' => $first, 'prefix' => '-', 'number' => -1]] : [];
        $query = static::mk()->where(['status' => 1])->withoutField('id,utime,status,update_time,create_time');
        return array_merge($items, $query->order('number asc')->column($fields, 'number'));
    }

    /**
     * 获取最大级别数
     * @return integer
     * @throws \think\db\exception\DbException
     */
    public static function maxNumber(): int
    {
        if (static::mk()->count() < 1) return 0;
        return intval(static::mk()->max('number') + 1);
    }
}