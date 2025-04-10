<?php

// +----------------------------------------------------------------------
// | Wuma Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
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

namespace plugin\wuma\service;

use plugin\wuma\model\PluginWumaCodeRuleRange;
use plugin\wuma\model\PluginWumaSourceAssign;
use plugin\wuma\model\PluginWumaSourceAssignItem;
use plugin\wuma\model\PluginWumaSourceProduce;
use plugin\wuma\model\PluginWumaWarehouseOrderDataMins;
use plugin\wuma\model\PluginWumaWarehouseRelationData;
use think\admin\Exception;
use think\admin\extend\CodeExtend;
use think\admin\Library;
use think\admin\Service;
use think\db\Query;
use think\Model;

/**
 * 赋码区间计算服务
 * @class RelationService
 * @package plugin\wuma\service
 */
class RelationService extends Service
{

    /**
     * 自动处理赋码锁定
     * @param string $batch
     * @return boolean
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DbException
     * @deprecated 使用 resetAssignLock 处理效果更好
     */
    public static function autoAssignLock(string $batch): bool
    {
        // 读取赋码主记录
        $assign = PluginWumaSourceAssign::mk()->where(['batch' => $batch])->findOrEmpty();
        if ($assign->isEmpty()) throw new Exception("无效的赋码批次号");

        // 检查出入库记录
        $map = [];
        foreach ($assign->range as $range) $map[] = [
            ['code', 'between', [$range['range_start'], $range['range_after']]]
        ];
        $codes = PluginWumaWarehouseOrderDataMins::mk()->whereOr($map)->column('code');
        foreach ($assign->range as $range) {
            $lock = 0;
            foreach ($codes as $code) {
                if ($range['range_start'] <= $code && $code <= $range['range_after']) {
                    $lock = 2;
                    break;
                }
            }
            $range->save(['lock' => $lock]);
        }
        return static::save($batch);
    }

    /**
     * 强行重置分区锁定
     * @param string $batch 物码批次
     * @param boolean $relation 是否关联模式
     * @return boolean
     * @throws \think\admin\Exception
     */
    public static function resetAssignLock(string $batch, bool $relation = false): bool
    {
        // 读取赋码主记录
        $assign = PluginWumaSourceAssign::mk()->where(['batch' => $batch])->findOrEmpty();
        if ($assign->isEmpty()) throw new Exception('无标签赋码！');

        // 读取标签范围及已使用标签
        $map = ['batch' => $assign->getAttr('cbatch'), 'code_type' => 'min'];
        $range = PluginWumaCodeRuleRange::mk()->where($map)->column('range_start,range_after');
        if (empty($range)) throw new Exception('物码规则异常！');
        $codes = PluginWumaWarehouseOrderDataMins::mk()->whereBetween('code', array_values($range[0]))->column('code');

        // 标签待处理数据(优化 lock 等级高的区间)
        [$items, $batchs] = [[], []];
        foreach (PluginWumaSourceAssignItem::mk()->where(['batch' => $batch])->order('lock desc')->field('range_start,range_after,pbatch')->cursor() as $range) {
            foreach ($codes as $idx => $code) if ($range['range_start'] <= $code && $code <= $range['range_after']) {
                unset($codes[$idx]);
                $batchs[$range['pbatch']][] = $code;
            }
        }
        // 清理未使用的区间记录
        if (empty($batchs)) {
            $assign->save(['type' => $relation ? 1 : 0]);
            PluginWumaSourceAssignItem::mk()->where(['batch' => $batch])->delete();
            return false;
        }
        try {
            if ($relation) { /* 关联赋码 */
                foreach ($batchs as $pbatch => $codes) foreach ($codes as $code) $items[] = [
                    'real'        => '',
                    'lock'        => '',
                    'batch'       => $batch,
                    'pbatch'      => $pbatch,
                    'cbatch'      => $assign->getAttr('cbatch'),
                    'range_start' => $code,
                    'range_after' => $code,
                    'create_time' => date('Y-m-d H:i:s'),
                    'update_time' => date('Y-m-d H:i:s'),
                ];
                Library::$sapp->db->transaction(static function () use ($assign, $items) {
                    $assign->save(['type' => 1]);
                    PluginWumaSourceAssignItem::mk()->where(['batch' => $assign->getAttr('batch')])->delete();
                    PluginWumaSourceAssignItem::mk()->insertAll($items);
                });
            } else { /* 区间赋码 */
                Library::$sapp->db->transaction(static function () use ($assign, $batchs) {
                    $assign->save(['type' => 0]);
                    PluginWumaSourceAssignItem::mk()->where(['batch' => $assign->getAttr('batch')])->delete();
                    foreach ($batchs as $pbatch => $codes) static::assign($codes, $pbatch);
                });
            }
            return true;
        } catch (\Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * 锁定赋码批次分区
     * @param array $mins 小码，格式如：[min1,min2,min2]
     * @param ?integer $lock
     * @return array|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function changeAssignLock(array $mins, ?int $lock = null): array
    {
        [$map, $data] = [[], []];
        foreach ($mins as $min) {
            $map[] = [['range_start', '<=', $min], ['range_after', '>=', $min]];
        }
        // 仅更新锁定数据状态
        if (is_numeric($lock)) {
            static::changeRelationLock($mins, $lock);
            if (($record = PluginWumaSourceAssignItem::mk()->whereOr($map)->findOrEmpty())->isExists()) {
                $record->save(['lock' => $lock]) && static::save($record['batch']);
            }
            return [];
        }
        // 查询出关联的赋码数据
        $model = PluginWumaSourceAssignItem::mk()->with([
            'produce' => static function (Query $query) {
                $query->with('bindGoods')->field('batch,gcode,gspec');
            },
        ]);
        $model->field('id,lock,0 code,batch,coder_batch,range_start,range_after,production_batch');
        /** @var PluginWumaSourceAssignItem $item */
        foreach ($model->whereOr($map)->cursor() as $item) foreach ($mins as $k => $min) {
            if ($item->getAttr('range_start') <= $min && $min <= $item->getAttr('range_after')) {
                $data[$k] = array_merge($item->toArray(), ['code' => $min]);
            }
        }
        return $data;
    }

    /**
     * 锁定后关联标签码
     * @param array $mins
     * @param integer $lock
     */
    public static function changeRelationLock(array $mins, int $lock = 1)
    {
        PluginWumaWarehouseRelationData::mk()->whereIn('min', $mins)->update(['lock' => $lock]);
    }

    /**
     * 同步计算赋码规则
     * @param string $batch 批次编号
     * @param array $ranges 赋码区间
     * @return boolean
     * @throws \think\db\exception\DbException
     */
    public static function save(string $batch, array $ranges = []): bool
    {
        if (empty($ranges)) return true;
        $query = PluginWumaSourceAssign::mk()->where(['batch' => $batch])->findOrEmpty();
        if (is_array($ranges) && count($ranges) > 0) {
            $query->range()->delete();
            $query->range()->insertAll($ranges);
        }
        return true;
    }

    /**
     * 自动设置赋码数据
     * @param array $codes 小码二维数组
     * @param string $pbatch 生产批次号
     * @param boolean $sample 解锁模式
     * @return array PluginWumaSourceProduce
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function assign(array $codes, string $pbatch, bool $sample = false): array
    {
        if (empty($codes)) throw new Exception('无效的标签数据！');
        $produce = PluginWumaSourceProduce::mk()->where(['batch' => $pbatch, 'deleted' => 0])->findOrEmpty();
        if ($produce->isEmpty()) throw new Exception('无效的生产批次数据！');

        // 读取批次数据
        [$coder, $ranges] = [CodeService::batch('min', $codes[0]), []];
        $assign = PluginWumaSourceAssign::mk()->where(['cbatch' => $coder['batch']])->findOrEmpty();

        // 分区合并计算
        if ($assign->range->isEmpty()) {
            $ranges = [[$coder['min']['range_start'], $coder['min']['range_after'], 0, $pbatch]];
        } else foreach ($assign['range'] as $v) {
            if ($sample && $v['lock'] == 1) $v['lock'] = 2;
            $ranges[] = [$v['range_start'], $v['range_after'], $v['lock'], $v['pbatch']];
        }
        [$ranges, $exists] = static::withMergeInput($ranges, $codes, $pbatch);
        if (!empty($exists)) throw new Exception('分区已锁且生产批次不匹配！', 0, $exists);
        // 设置模型数据
        if (empty($assign['batch'])) {
            $assign['batch'] = CodeExtend::uniqidDate(16, 'F');
            $assign['cbatch'] = $coder['batch'];
        }
        // 合并范围数据
        foreach ($ranges as &$v) $v = [
            'lock'        => $v[2] > 0 ? 2 : 0,
            'batch'       => $assign['batch'],
            'cbatch'      => $coder['batch'],
            'pbatch'      => $v[3],
            'range_start' => $v[0],
            'range_after' => $v[1],
        ];
        // 更新写入赋码规则
        static::save($assign['batch'], $ranges);
        return $produce->toArray();
    }

    /**
     * 合并相似分区数据
     * @param array $list 范围数据
     * @param boolean $lock 区分状态
     * @return array
     */
    public static function withMergeRange(array &$list, bool $lock = false): array
    {
        foreach ($list as $key => &$item) {
            if (isset($prev) && $prev['pbatch'] . ($lock ? $prev['lock'] : '') === $item['pbatch'] . ($lock ? $item['lock'] : '')) {
                $prev['range_after'] = $item['range_after'];
                unset($list[$key]);
            } else {
                $prev = &$item;
            }
        }
        return $list = array_values($list);
    }

    /**
     * 分区计算及批次合并
     * @param array $ranges 原始分区
     * @param array $inputs 输入数值
     * @param string $pbatch 生产批次
     * @return array
     */
    private static function withMergeInput(array $ranges, array $inputs, string $pbatch): array
    {
        [$exists, $items, $chars] = [[], [], $inputs, sort($inputs)];
        foreach ($ranges as $range) {
            [$next, $temps] = [0, []];
            while (count($inputs) > 0) {
                if ($range[0] > $inputs[0] || $inputs[0] > $range[1]) break;
                $numb = array_shift($inputs);
                if ($range[2] > 1 && $range[3] !== $pbatch) {
                    $exists[] = $numb;
                } elseif (!empty($temps) && (empty($next) || $next === $numb)) {
                    $temps[count($temps) - 1][] = $numb;
                } else {
                    $temps[] = [$numb];
                }
                $next = $numb + 1;
            }
            // 无需处理
            if (empty($temps)) {
                $items[] = $range;
            } else {
                // 前置分区
                $start = $range[0];
                $after = min($temps[0] ?? [$range[0]]) - 1;
                if ($after >= $start) {
                    $items[] = [$start, $after, $range[2], $range[3]];
                    $start = $after + 1;
                }
                // 中间分区
                foreach ($temps as $temp) {
                    $after = min($temp) - 1;
                    if ($start <= $after) {
                        $items[] = [$start, $after, $range[2], $range[3]];
                        $start = $after + 1;
                    }
                    $after = max($temp);
                    $lock = in_array($start, $chars) ? 2 : 0;
                    $items[] = [$start, $after, $lock, $pbatch];
                    $start = $after + 1;
                }
                // 后置分区
                if ($start <= $range[1]) {
                    $items[] = [$start, $range[1], $range[2], $range[3]];
                }
            }
        }
        // 合并相似状态
        foreach ($items as $key => &$item) {
            if (isset($prev) && $prev[2] === $item[2] && $prev[3] === $item[3]) {
                $prev[1] = $item[1];
                unset($items[$key]);
            } else {
                $prev = &$item;
            }
        }
        return [$items, $exists];
    }
}