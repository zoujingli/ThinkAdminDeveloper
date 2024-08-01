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

use plugin\wuma\service\CodeService;
use think\model\relation\HasMany;

/**
 * 物码规则文件
 * @class PluginWumaCodeRule
 * @package  plugin\wuma\model
 */
class PluginWumaCodeRule extends AbstractPrivate
{
    /**
     * 读取物码规则
     * @return HasMany
     */
    public function rules(): HasMany
    {
        return $this->hasMany(PluginWumaCodeRuleRange::class, 'batch', 'batch');
    }

    /**
     * 查询指定规则的数据列表
     * @param mixed $map
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function lists($map = []): array
    {
        $list = static::mk()->withoutField('deleted')->with(['rules' => function ($query) {
            $query->field('type,batch,code_type,code_length,range_number,range_start,range_after');
        }])->where($map)->order('id desc')->select()->toArray();
        if (count($list) > 0) foreach ($list as &$vo) static::applyRangeData($vo);
        return array_combine(array_column($list, 'batch'), array_values($list));
    }


    /**
     * 获取所有规则列表
     * @param mixed $where 筛选条件
     * @return array[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function fullRules($where = []): array
    {
        $model = static::mk()->with(['rules' => function ($query) {
            $query->field('type,batch,code_type,range_number,range_start,range_after');
        }]);
        $model->where(['status' => 1, 'deleted' => 0])->where($where);
        $model->field('type,batch,real_max_mid max_mid,real_mid_min mid_min,number');
        return static::applyRangeRule($model->order('id desc')->select()->toArray());
    }

    /**
     * 范围数据处理
     * @param mixed $item
     * @return mixed
     */
    public static function applyRangeData(&$item)
    {
        $item['exists'] = file_exists(CodeService::withFile($item['batch']));
        [$item['max'], $item['mid'], $item['min'], $item['hex']] = [[], [], [], []];
        foreach ($item['rules'] as $rule) $item[$rule['code_type']] = $rule;
        return $item;
    }

    /**
     * 范围规则处理
     * @param array $items
     * @return array[]
     */
    public static function applyRangeRule(array $items): array
    {
        [$nums, $mins] = [[], []];
        foreach ($items as $item) foreach ($item['rules'] as $rule) {
            if ($rule['code_type'] == 'max') {
                $nums["{$rule['range_start']}-{$rule['range_after']}"] = [
                    'mode'   => $rule['type'],
                    'type'   => $rule['code_type'],
                    'number' => $rule['range_number'],
                    'tomins' => $item['max_mid'] * $item['mid_min'],
                ];
            } elseif ($rule['code_type'] == 'mid') {
                $nums["{$rule['range_start']}-{$rule['range_after']}"] = [
                    'mode'   => $rule['type'],
                    'type'   => $rule['code_type'],
                    'number' => $rule['range_number'],
                    'tomins' => $item['mid_min'],
                ];
            } elseif ($rule['code_type'] == 'min') {
                $mins["{$rule['range_start']}-{$rule['range_after']}"] = [
                    'mode'   => $rule['type'],
                    'type'   => $rule['code_type'],
                    'number' => $rule['range_number'],
                    'tomins' => 1,
                ];
            }
        }
        return ['nums' => $nums, 'mins' => $mins];
    }
}