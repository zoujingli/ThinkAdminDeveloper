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

namespace plugin\wuma\service;

use plugin\wuma\model\PluginWumaCodeRule;
use plugin\wuma\model\PluginWumaCodeRuleRange;
use plugin\wuma\model\PluginWumaSourceAssign;
use plugin\wuma\model\PluginWumaSourceAssignItem;
use plugin\wuma\model\PluginWumaWarehouseOrderDataMins;
use plugin\wuma\model\PluginWumaWarehouseReplace;
use think\admin\Exception;
use think\admin\Service;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\db\Query;

/**
 * 仓库物码数据服务
 * @class WhCoderService
 */
class WhCoderService extends Service
{
    /**
     * 检查物码是否存在.
     * @param array $codes 物码集合
     * @param string $from 来源类型(encode,number,codes,array)
     * @param bool $replace 是否检查替换标签
     * @return array [status, mins, exists, array]
     * @throws Exception
     */
    public static function checkImportExist(array $codes, string $from = 'encode', bool $replace = false): array
    {
        $mins = CodeService::min2min($codes, $from);
        [$exists, $mincodes] = [[], array_keys($mins)];
        // 检查是否已经入库
        $map = [['status', '=', 1], ['deleted', '=', 0], ['code', 'in', $mincodes]];
        if ($items = PluginWumaWarehouseOrderDataMins::mk()->withSearch('inter')->where($map)->column('code')) {
            if ($intersect = array_intersect($items, $mincodes)) {
                foreach ($intersect as $min) {
                    $exists[$min] = $mins[$min];
                }
            }
        }
        // 检查替换码是否存在
        if ($replace && ($reps = static::recheck($mincodes)) && !empty($reps)) {
            foreach ($reps as $min) {
                $exists[$min] = $mins[$min];
            }
        }
        return [count($mins) === count($exists) ? 1 : 0, $mins, $exists, []];
    }

    /**
     * 检查出库记录.
     * @param array $codes 物码集合
     * @param string $from 来源类型(encode,number,codes,array)
     * @param bool $replace 是否检查替换标签
     * @return array [status, mins, exists, array]
     * @throws Exception
     */
    public static function checkExportExist(array $codes, string $from = 'encode', bool $replace = false): array
    {
        $mins = CodeService::min2min($codes, $from);
        [$exists, $mincodes] = [[], array_keys($mins)];
        // 检查小码是否已经出库
        $where = [['status', '=', 1], ['deleted', '=', 0], ['code', 'in', $mincodes]];
        if ($items = PluginWumaWarehouseOrderDataMins::mk()->withSearch('outer')->where($where)->column('code')) {
            if ($intersect = array_intersect($items, $mincodes)) {
                foreach ($intersect as $min) {
                    $exists[$min] = $mins[$min] ?? '-';
                }
                return [1, $mins, $exists, []];
            }
        }
        // 检查替换码是否存在
        if ($replace && ($reps = static::recheck($mincodes)) && !empty($reps)) {
            foreach ($reps as $min) {
                $exists[$min] = $mins[$min];
            }
        }
        return [0, $mins, [], []];
    }

    /**
     * 检查物码处理.
     * @param array $body 源数据 ['nums','ecns','mins','mids','maxs','ghash']
     * @param bool $assign 是否检查赋码产品
     * @param bool $relation 是否检查物码关联
     * @param bool $replace 是否检查替换标签
     * @return array [min=>code#type]
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function code2mins(array &$body, bool $assign = false, bool $relation = true, bool $replace = true): array
    {
        [$oks, $ers, $codes] = [[], [], []];
        if (!empty($body['nums']) && count($items = static::replace(array_unique(str2arr($body['nums'])))) > 0) {
            [$state, $nums] = CodeService::checkValid([], $items, 'number', 'min');
            if (empty($state)) {
                throw new Exception('物码范围无效！', 0, array_values(array_diff($items, $nums)));
            }
            if ($relation) {
                foreach ($nums as $num) {
                    if (isset($codes[$min = CodeService::num2min($num)])) {
                        throw new Exception('存在包含关系，', 0, [$num, strstr($codes[$min], '#', true)]);
                    }
                }
            }
            $codes[$min] = "{$num}#NUM";
        }
        if (!empty($body['encs']) && count($items = static::replace(array_unique(str2arr($body['encs'])))) > 0) {
            [$state, $encs] = CodeService::checkValid([], $items, 'encode', 'min');
            if (empty($state)) {
                throw new Exception('物码范围无效！', 0, array_values(array_diff($items, $encs)));
            }
            if ($relation) {
                foreach ($encs as $enc) {
                    if (isset($codes[$min = CodeService::enc2min($enc)])) {
                        throw new Exception('存在包含关系！', 0, [$enc, strstr($codes[$min], '#', true)]);
                    }
                }
            }
            $codes[$min] = "{$enc}#ENC";
        }
        if (!empty($body['mins']) && count($items = array_unique(str2arr($body['mins']))) > 0) {
            [$state, $mins] = CodeService::checkValid([], $items, 'min', 'min');
            if (empty($state)) {
                throw new Exception('小码范围无效！', 0, array_values(array_diff($items, $mins)));
            }
            if ($relation) {
                foreach ($mins as $min) {
                    $codes[$min] = "{$min}#MIN";
                }
            }
        }
        if (!empty($body['mids']) && count($items = array_unique(str2arr($body['mids']))) > 0) {
            [$state, $mids] = CodeService::checkValid([], $items, 'mid', 'mid');
            if (empty($state)) {
                throw new Exception('中码范围无效！', 0, array_values(array_diff($items, $mids)));
            }
            if ($relation) {
                foreach ($mids as $mid) {
                    foreach (CodeService::tomins('mid', $mid)[1] as $tmids) {
                        foreach ($tmids as $tmins) {
                            foreach ($tmins as $min) {
                                if (isset($codes[$min])) {
                                    throw new Exception('存在包含关系！', 0, [$mid, strstr($codes[$min], '#', true)]);
                                }
                                $codes[$min] = "{$mid}#MID";
                            }
                        }
                    }
                }
            }
        }
        if (!empty($body['maxs']) && count($items = array_unique(str2arr($body['maxs']))) > 0) {
            [$state, $maxs] = CodeService::checkValid([], $items, 'max', 'max');
            if (empty($state)) {
                throw new Exception('大码范围无效', 0, array_values(array_diff($items, $maxs)));
            }
            if ($relation) {
                foreach ($maxs as $max) {
                    foreach (CodeService::tomins('max', $max)[1] as $tmids) {
                        foreach ($tmids as $tmins) {
                            foreach ($tmins as $min) {
                                if (isset($codes[$min])) {
                                    throw new Exception('存在包含关系！', 0, [$max, strstr($codes[$min], '#', true)]);
                                }
                                $codes[$min] = "{$max}#MAX";
                            }
                        }
                    }
                }
            }
        }
        // 检查是否存在替换码
        if ($replace) {
            if (($reps = static::recheck(array_keys($codes))) && !empty($reps)) {
                [$v, $t] = explode('#', $codes[$reps[0]]);
                throw new Exception('存在替换标签', 0, [$v, $t]);
            }
        }
        // 赋码产品关联检查
        if ($assign) {
            foreach (RelationService::changeAssignLock(array_keys($codes)) as $assign) {
                // 首次未传商品信息时自动补充
                if (empty($body['ghash'])) {
                    $body['ghash'] = $assign['produce']['ghash'];
                }
                if ($assign['produce']['ghash'] !== $body['ghash']) {
                    $ers[$assign['code']] = strstr($codes[$assign['code']], '#', true);
                } else {
                    $oks[$assign['code']] = strstr($codes[$assign['code']], '#', true);
                }
            }
            foreach ($codes as $min => $code) {
                if (!isset($oks[$min]) && !isset($ers[$min])) {
                    $ers[$min] = strstr($code, '#', true);
                }
            }
            if (count($ers) > 0) {
                throw new Exception('与赋码产品不一致！', 0, array_unique(array_values($ers)));
            }
        }
        return $codes;
    }

    /**
     * 替换为原始数据.
     * @param array $codes 待替换的标签
     */
    public static function replace(array $codes): array
    {
        $cols = PluginWumaWarehouseReplace::mk()->whereIn('target', $codes)->column('source', 'target');
        foreach ($codes as &$code) {
            if (isset($cols[$code])) {
                $code = $cols[$code];
            }
        }
        return $codes;
    }

    /**
     * 检查替换标签.
     * @param array $codes 待检查的小码
     */
    public static function recheck(array $codes): array
    {
        $tmins = PluginWumaWarehouseReplace::mk()->whereIn('tmin', $codes)->column('tmin');
        if (!empty($tmins) && $intersect = array_intersect($tmins, $codes)) {
            return $intersect;
        }
        return [];
    }

    /**
     * 物码规则统计数量.
     * @param array $codes 物码规则 [min=>code#type]
     * @return array [count, maps, unis, array]
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function code2count(array $codes): array
    {
        // 定义变量名称
        [$maps, $strs, $count] = [[], join(',', $codes), 0];
        // 读取所有物码规则
        $unis = array_unique(array_values($codes));
        $rules = PluginWumaCodeRule::fullRules();
        foreach ($unis as $code) {
            $attr = explode('#', $code);
            if (in_array($attr[1], ['MIN', 'NUM', 'ENC'])) {
                [$count++, $maps[$code] = $code . '#1'];
            } elseif (in_array($attr[1], ['MAX', 'MID'])) {
                foreach ($rules['nums'] as $range => $num) {
                    [$start, $after] = explode('-', $range);
                    if (bccomp(strval($start), strval($attr[0]), 2) <= 0 && bccomp(strval($attr[0]), strval($after), 2) <= 0) {
                        $number = $num['mode'] === 1 ? $num['tomins'] : substr_count($strs, $code);
                        $maps[$code] = $code . '#' . $number;
                        $count += $number;
                        break;
                    }
                }
            }
        }
        return [$count, $maps, $unis, []];
    }

    /**
     * 商品码查询产品
     * @param int|string $code
     * @return array|string
     */
    public static function goods($code)
    {
        $map = [['range_start', '<=', $code], ['range_after', '>=', $code]];
        $coder = PluginWumaCodeRuleRange::mk()->where($map)->findOrEmpty()->toArray();
        if (empty($coder)) {
            return '物码规则异常';
        }

        // 赋码批次关联
        $item = PluginWumaSourceAssignItem::mk()->with(['produce'])->where(static function (Query $query) use ($coder) {
            $assign = PluginWumaSourceAssign::mk()->where(['status' => 1, 'deleted' => 0]);
            $query->where(['cbatch' => $coder['batch']])->whereRaw("batch in {$assign->field('batch')->buildSql()}");
        })->findOrEmpty()->toArray();

        // 检查赋码数据
        if (empty($item['produce']['ghash'])) {
            return '未关联商品数据';
        }

        // 出货代理数据读取
        $map = ['code' => $code, 'status' => 1, 'deleted' => 0];
        $auid = AgentStockOrderDataMins::mk()->where($map)->order('id desc')->value('auid', 0);
        return [$item['produce']['gcode'], $item['produce']['gspec'], $auid];
    }
}
