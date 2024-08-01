<?php

// +----------------------------------------------------------------------
// | Wuma Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 收费插件 ( https://thinkadmin.top/fee-introduce.html )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wuma
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wuma
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wuma\model;

use think\model\relation\HasMany;
use think\model\relation\HasOne;

class PluginWumaSalesUser extends AbstractPrivate
{

    /**
     * 查询指定规则的数据列表
     * @param mixed $map
     * @param string $fields
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function lists($map = [], string $fields = '*'): array
    {
        $query = static::mq()->field($fields)->where($map);
        return $query->order('id desc')->select()->toArray();
    }

    /**
     * 关联上级代理
     * @return \think\model\relation\HasOne
     */
    public function supAgent(): HasOne
    {
        return $this->hasOne(self::class, 'id', 'auid');
    }

    /**
     * 获取下级代理
     * @return \think\model\relation\HasMany
     */
    public function subAgent(): HasMany
    {
        return $this->hasMany(self::class, 'auid', 'id');
    }

    /**
     * 关联等级数据
     * @return HasOne
     */
    public function levelinfo(): HasOne
    {
        return $this->hasOne(PluginWumaSalesUserLevel::class, 'number', 'level');
    }
}