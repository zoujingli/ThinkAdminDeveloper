<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | Payment Plugin for ThinkAdmin
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

use plugin\wuma\service\CodeService;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\model\relation\HasMany;

/**
 * 物码规则文件.
 *
 * @property int $deleted 删除状态(0未删,1已删)
 * @property int $hex_length 加密长度
 * @property int $id
 * @property int $max_length 大码长度
 * @property int $max_mid 大码与中码比值
 * @property int $max_number 大码数量
 * @property int $mid_length 中码长度
 * @property int $mid_min 中码与小码比值
 * @property int $mid_number 中码数量
 * @property int $min_length 小码长度
 * @property int $number 物码总数
 * @property int $sns_after 序号结束值
 * @property int $sns_length 序号长度
 * @property int $sns_start 序号起始值
 * @property int $status 记录状态(0无效,1有效)
 * @property int $type 批次类型(1前关联，2后关联)
 * @property int $ver_length 验证长度
 * @property string $batch 批次编号
 * @property string $create_time 创建时间
 * @property string $remark 物码描述
 * @property string $template 导出模板
 * @property string $update_time 更新时间
 * @property PluginWumaCodeRuleRange[] $rules
 * @class PluginWumaCodeRule
 */
class PluginWumaCodeRule extends AbstractPrivate
{
    /**
     * 读取物码规则.
     */
    public function rules(): HasMany
    {
        return $this->hasMany(PluginWumaCodeRuleRange::class, 'batch', 'batch');
    }

    /**
     * 查询指定规则的数据列表.
     * @param mixed $map
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function lists($map = []): array
    {
        $list = static::mk()->withoutField('deleted')->with(['rules' => function ($query) {
            $query->field('type,batch,code_type,code_length,range_number,range_start,range_after');
        }])->where($map)->order('id desc')->select()->toArray();
        if (count($list) > 0) {
            foreach ($list as &$vo) {
                static::applyRangeData($vo);
            }
        }
        return array_combine(array_column($list, 'batch'), array_values($list));
    }

    /**
     * 获取所有规则列表.
     * @param mixed $where 筛选条件
     * @return array[]
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
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
     * 范围数据处理.
     * @param mixed $item
     * @return mixed
     */
    public static function applyRangeData(&$item)
    {
        $item['exists'] = file_exists(CodeService::withFile($item['batch']));
        [$item['max'], $item['mid'], $item['min'], $item['hex']] = [[], [], [], []];
        foreach ($item['rules'] as $rule) {
            $item[$rule['code_type']] = $rule;
        }
        return $item;
    }

    /**
     * 范围规则处理.
     * @return array[]
     */
    public static function applyRangeRule(array $items): array
    {
        [$nums, $mins] = [[], []];
        foreach ($items as $item) {
            foreach ($item['rules'] as $rule) {
                if ($rule['code_type'] == 'max') {
                    $nums["{$rule['range_start']}-{$rule['range_after']}"] = [
                        'mode' => $rule['type'],
                        'type' => $rule['code_type'],
                        'number' => $rule['range_number'],
                        'tomins' => $item['max_mid'] * $item['mid_min'],
                    ];
                } elseif ($rule['code_type'] == 'mid') {
                    $nums["{$rule['range_start']}-{$rule['range_after']}"] = [
                        'mode' => $rule['type'],
                        'type' => $rule['code_type'],
                        'number' => $rule['range_number'],
                        'tomins' => $item['mid_min'],
                    ];
                } elseif ($rule['code_type'] == 'min') {
                    $mins["{$rule['range_start']}-{$rule['range_after']}"] = [
                        'mode' => $rule['type'],
                        'type' => $rule['code_type'],
                        'number' => $rule['range_number'],
                        'tomins' => 1,
                    ];
                }
            }
        }
        return ['nums' => $nums, 'mins' => $mins];
    }
}
