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

namespace plugin\wuma\service;

use plugin\wuma\model\PluginWumaCodeRule;
use plugin\wuma\model\PluginWumaCodeRuleRange;
use plugin\wuma\model\PluginWumaWarehouseRelationData;
use think\admin\Exception;
use think\admin\extend\CodeExtend;

/**
 * 标签计算服务层
 * @class CodeService
 * @package plugin\wuma\service
 */
class CodeService
{
    public const TEMPLATE = '{number},{encode},{numurl},{minver}';

    public const FILEDS = [
        'sns'    => '序号',
        'max'    => '大码',
        'mid'    => '中码',
        'min'    => '小码',
        // 'maxver' => '验证码',
        // 'midver' => '验证码',
        'minver' => '验证码',
        'number' => '防窜码',
        'encode' => '防伪码',
        // 'maxurl' => '大码链接',
        // 'midurl' => '中码链接',
        // 'numurl' => '防窜链接',
        // 'encurl' => '防伪链接',
        'numurl' => '防伪链接',
    ];

    /**
     * [基础] 数字码转小码
     * @param string $c
     * @return string
     */
    public static function num2min(string $c): string
    {
        $x = self::_point($c, $s = strlen($c));
        [$c, $p, $b] = [substr($c, 0, $x) . substr($c, $x + 1), base_convert($c[$x], 36, 10), ''];
        for ($i = 0; $i < $s - 1; $i++) ($i + 1) % 4 == 0 && $p > 0 ? $p-- : $b .= $c[$i];
        return self::_de8($p > 0 ? substr($b, 0, strlen($b) - $p) : $b);
    }

    /**
     * [基础] 加密码转小码
     * @param string $c
     * @return string
     */
    public static function enc2min(string $c): string
    {
        $x = self::_point($c, $s = strlen($c));
        [$c, $p, $b] = [substr($c, 0, $x) . substr($c, $x + 1), base_convert($c[$x], 36, 10), ''];
        for ($i = 0; $i < $s - 2; $i++) ($i + 1) % 4 == 0 && $p > 0 ? $p-- : $b .= $c[$i];
        return self::num2min(base_convert($b, 36, 10));
    }

    /**
     * [基础] 验证码计算器
     * @param string $code 基数编码
     * @param integer $full 进制模式
     * @param integer $size 指定长度
     * @return string
     */
    public static function codever(string $code, int $full, int $size = 4): string
    {
        [$a, $b] = [base_convert($code, 10, $full - 1), base_convert(strrev($code), 10, $full - 2)];
        return substr(str_pad(strval(floatval($a) + floatval($b)), $size, '0', STR_PAD_LEFT), 0, $size);
    }

    /**
     * [基础] 链接验证码检查
     * @param string $code
     * @return string
     */
    public static function url2ver(string $code): string
    {
        return static::codever($code, 9);
    }

    /**
     * [基础] 标签验证码检查
     * @param string $code 基础标签码
     * @param integer $size 验证码长度
     * @return string
     */
    public static function min2ver(string $code, int $size = 4): string
    {
        return static::codever($code, 8, $size);
    }

    /**
     * 返回小码序号值
     * @param string $min
     * @param array $batch
     * @return string
     * @throws \think\admin\Exception
     */
    public static function min2seq(string $min, array $batch = []): string
    {
        if (empty($batch)) $batch = self::batch('min', $min);
        if (empty($batch)) return '计算标签序号异常！';
        $seq = intval($batch['sn_start']) + intval($min) - intval($batch['min']['range_start']);
        return str_pad(strval($seq), $batch['sn_length'], '0', STR_PAD_LEFT);
    }

    /**
     * [业务] 解析物码数据为标准规则
     * @param array $codes 解析物码
     * @param string $from 物码类型(max,mid,min,encode,number,numenc,encnum)
     * @return array [min=>code]
     * @throws \think\admin\Exception
     */
    public static function min2min(array $codes, string $from): array
    {
        $mins = [];
        foreach ($codes as $code) {
            [, $min] = static::parseCode($from, $code);
            $mins[$min] = $code;
        }
        return $mins;
    }

    /**
     * [业务] 大码转中码，仅前关联
     * @param mixed $max 大码数据
     * @param array $rule 配置数据
     * @param mixed $where 查件条件
     * @return array
     * @throws \think\admin\Exception
     */
    public static function max2mid(string $max, array &$rule = [], $where = []): array
    {
        if (empty($rule)) $rule = static::batch('max', $max, $where, ['type' => 1]);
        $start = $rule['mid']['range_start'] + $rule['max_mid'] * (intval($max) - $rule['max']['range_start']);
        return range($start, min([$start + $rule['max_mid'] - 1, $rule['mid']['range_after']]));
    }

    /**
     * [业务] 中码转小码，仅前关联
     * @param mixed $mid 中码数据
     * @param array $rule 配置数据
     * @param mixed $where 查件条件
     * @return array
     * @throws \think\admin\Exception
     */
    public static function mid2min(string $mid, array &$rule = [], $where = []): array
    {
        if (empty($rule)) $rule = static::batch('mid', $mid, $where, ['type' => 1]);
        $start = $rule['min']['range_start'] + $rule['mid_min'] * (intval($mid) - $rule['mid']['range_start']);
        return range($start, min([$start + $rule['mid_min'] - 1, $rule['min']['range_after']]));
    }

    /**
     * [业务] 解析商品码为小码
     * @param string $code 商品码
     * @return string
     */
    public static function code2min(string $code): string
    {
        $method = is_numeric($code) ? 'num2min' : 'enc2min';
        return static::{$method}($code);
    }

    /**
     * [业务] 根据物码查询批次
     * @param string $type 物码类型(max,mid,min,encode,number,numenc,encnum)
     * @param string $code 物码数值
     * @param mixed $map1 查询条件1
     * @param mixed $map2 查询条件2
     * @return mixed
     * @throws \think\admin\Exception
     */
    public static function batch(string $type, string $code, $map1 = [], $map2 = [])
    {
        [$type, $code] = static::parseCode($type, $code);
        // 通过范围规则查询批次号
        $map = ['code_type' => $type, 'status' => 1];
        $query = PluginWumaCodeRuleRange::mk()->where($map)->where($map1)->where($map2);
        $batch = $query->whereRaw("range_start<={$code} and range_after>={$code}")->value('batch');
        if (empty($batch)) throw new Exception('未找到物码规则！');
        // 获取批次号格式化物码规则
        $map = ['batch' => $batch, 'status' => 1, 'deleted' => 0];
        $coder = PluginWumaCodeRule::mk()->with(['rules'])->where($map)->findOrEmpty()->toArray();
        return PluginWumaCodeRule::applyRangeData($coder);
    }

    /**
     * [业务] 通用物码转小码
     * @param string $type 物码类型(max,mid,min,encode,number,numenc,encnum)
     * @param string $code 物码数值
     * @return array [type, code]
     * @throws \think\admin\Exception
     */
    public static function parseCode(string $type, string $code): array
    {
        $ienc = in_array($type, ['numenc', 'encnum', 'encode', 'number']);
        $data = $ienc ? ['min', static::code2min($code)] : [$type, $code];
        if (!in_array($data[0], ['max', 'mid', 'min'])) {
            throw new Exception('物码类型异常！', 0, ['type' => $type, 'code' => $code]);
        } elseif (empty($data[1])) {
            throw new Exception('物码转换失败！', 0, ['type' => $type, 'code' => $code]);
        } else {
            return $data;
        }
    }

    /**
     * 自动识别类型
     * @param string $code
     * @return string max|mid|number|encode|unkown
     */
    public static function auto2type(string $code): string
    {
        if (preg_match('#^\d+$#', $code)) {
            $stype = self::_type($code, ['max', 'mid']);
            return $stype ?: (self::_type(self::num2min($code)) ? 'number' : 'unknow');
        } else {
            return self::_type(self::enc2min($code)) ? 'encode' : 'unknow';
        }
    }

    /**
     * 判断是否为小码
     * @param string $code
     * @param array $types
     * @return ?string
     */
    private static function _type(string $code, array $types = ['min']): ?string
    {
        if (empty($code) || !is_numeric($code)) return null;
        $query = PluginWumaCodeRuleRange::mk()->where(['status' => 1])->whereIn('code_type', $types);
        return $query->whereRaw("range_start<={$code} and range_after>={$code}")->value('code_type');
    }

    /**
     * [业务] 任意码转小码
     * @param string $type 物码类型(max,mid,min,encode,number,numenc,encnum)
     * @param string $code 物码数值
     * @return array [batch, codes]
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function tomins(string $type, string $code): array
    {
        $codes = [];
        $batch = static::batch($type, $code);
        if ($batch['type'] === 1) {
            if ($type === 'max') {
                foreach (static::max2mid($code, $batch) as $mid) {
                    $codes[$code][$mid] = static::mid2min($mid, $batch);
                }
            } elseif ($type === 'mid') {
                $codes['_'][$code] = static::mid2min($code, $batch);
            } elseif ($type === 'min') {
                $codes['_']['_'][] = $code;
            } elseif (in_array($type, ['encode', 'number'])) {
                $codes['_']['_'][] = static::code2min($code);
            }
        } else {
            if (in_array($type, ['encode', 'number'])) {
                $map = ['min' => static::code2min($code)];
            } else {
                $map = [$type => $code];
            }
            $items = PluginWumaWarehouseRelationData::mk()->where($map)->select()->toArray();
            if (empty($items)) throw new Exception("物码未进行关联", 0, ['type' => $type, 'code' => $code]);
            foreach ($items as $item) $codes[$item['max']][$item['mid']][] = $item['min'];
        }
        return [$batch, $codes];
    }

    /**
     * [业务] 检查物码是否符合规则
     * @param array $where 查询条件
     * @param array $codes 物码集合
     * @param string $from 来源类型(max,mid,min,encode,number,numenc,encnum)
     * @param string $type 检查类型(max,mid,min)
     * @return array [status, codes, array]
     * @throws \think\admin\Exception
     */
    public static function checkValid(array $where, array $codes, string $from, string $type): array
    {
        static $ranges = [];
        if (empty($ranges[$type])) {
            $model = PluginWumaCodeRuleRange::mk()->where(['code_type' => $type]);
            $ranges[$type] = $model->where($where)->column('type,range_start,range_after', 'batch');
        }
        $exists = [];
        foreach (static::min2min($codes, $from) as $min => $code) {
            if (!in_array($code, $exists)) foreach ($ranges[$type] as $range) {
                if ($range['range_start'] <= $min && $min <= $range['range_after']) {
                    $exists[] = $code;
                    continue 2;
                }
            }
        }
        if (count($exists) === count($codes)) {
            return [1, $exists, []];
        } else {
            return [0, array_intersect($exists, $codes), []];
        }
    }

    /**
     * [业务] 查找物码查检规则
     * @param string $code 标签内容
     * @param string $type 标签类型(max|mid|min|encode|number)
     * @return array
     * @throws \think\admin\Exception
     */
    public static function find(string $code, string $type = 'min'): array
    {
        if ($type === 'number') [$code, $type] = [self::num2min($code), 'min'];
        if ($type === 'encode') [$code, $type] = [self::enc2min($code), 'min'];
        $map = [['code_type', '=', $type], ['range_start', '<=', $code], ['range_after', '>=', $code]];
        $range = PluginWumaCodeRuleRange::mk()->with('main')->where($map)->findOrEmpty()->toArray();
        if (empty($range)) throw new Exception('物码查询失败，区间不存在！');
        if (empty($range['main'])) throw new Exception('物码查询失败，规则不存在！');
        return [
            'type'    => $type,
            'code'    => $code,
            'batch'   => $range['batch'],
            'remark'  => $range['main']['remark'],
            'status'  => $range['main']['status'],
            'deleted' => $range['main']['deleted'],
        ];
    }

    /**
     * 创建物码规则
     * @param array $rule 物码规则
     * @return array [code, info, data]
     */
    public static function add(array $rule = []): array
    {
        // 查询物码起始位置
        $batch = CodeExtend::uniqidDate(16, 'B');
        $snsAfter = PluginWumaCodeRule::mk()->max('sns_after') + 1;
        // 转换物码所需规则
        $data = [
            'type'       => $rule['type'],
            'batch'      => $batch,
            'max_mid'    => $rule['max_mid'] ?? 0,
            'mid_min'    => $rule['mid_min'] ?? 0,
            'sns_start'  => $snsAfter,
            'sns_after'  => $snsAfter + $rule['number'],
            'sns_length' => $rule['sns_length'] ?? 0,
            'max_length' => $rule['max_length'] ?? 0,
            'mid_length' => $rule['mid_length'] ?? 0,
            'min_length' => $rule['min_length'] ?? 0,
            'hex_length' => $rule['hex_length'] ?? 0,
            'ver_length' => $rule['ver_length'] ?? 0,
            'mid_number' => $rule['mid_number'] ?? 0,
            'max_number' => $rule['max_number'] ?? 0,
            'template'   => $rule['template'] ?? CodeService::TEMPLATE,
            'number'     => $rule['number'] ?? 0,
            'remark'     => $rule['remark'] ?? '',
        ];
        // 物码数据入库
        if (PluginWumaCodeRule::mk()->save($data) !== false)
            return static::create($batch);
        else {
            return ['code' => 0, 'info' => '创建物码规则失败！', 'data' => $batch];
        }
    }

    /**
     * 创建物码规则
     * @param string $batch 物码批次号
     * @return array
     */
    public static function create(string $batch): array
    {
        try {
            if (PluginWumaCodeRuleRange::mk()->where(['batch' => $batch])->count() > 0) {
                return ['code' => 0, 'info' => '该物码已经创建了，无需要再创建！', 'data' => $batch];
            }
            if (!($rule = PluginWumaCodeRule::mk()->where(['batch' => $batch])->find())) {
                return ['code' => 0, 'info' => '物码规则不存在，请刷新页面重试！', 'data' => $batch];
            }
            $default = ['type' => $rule['type'], 'batch' => $rule['batch']];
            // 大码处理
            if (!empty($rule['max_length'])) {
                $maxRangeStart = static::_boxStart($rule['max_length']);
                if ($rule['type'] === 1) {
                    $maxRangeNumber = ceil($rule['number'] / ($rule['mid_min'] * $rule['max_mid']));
                } else {
                    $maxRangeNumber = $rule['max_number'];
                }
                $maxRangeAfter = bcsub(bcadd($maxRangeStart, strval($maxRangeNumber)), '1');
                if ($maxRangeNumber > 0) PluginWumaCodeRuleRange::mk()->insert(array_merge($default, [
                    'code_type'    => 'max',
                    'code_length'  => $rule['max_length'],
                    'range_start'  => $maxRangeStart,
                    'range_after'  => $maxRangeAfter,
                    'range_number' => $maxRangeNumber,
                ]));
            }
            // 中码处理
            if (!empty($rule['mid_length'])) {
                $midRangeStart = static::_boxStart($rule['mid_length']);
                if ($rule['type'] === 1) {
                    $midRangeNumber = ceil($rule['number'] / $rule['mid_min']);
                } else {
                    $midRangeNumber = strval($rule['mid_number']);
                }
                $midRangeAfter = bcsub(bcadd($midRangeStart, strval($midRangeNumber)), '1');
                if ($midRangeNumber > 0) PluginWumaCodeRuleRange::mk()->insert(array_merge($default, [
                    'code_type'    => 'mid',
                    'code_length'  => $rule['mid_length'],
                    'range_start'  => $midRangeStart,
                    'range_after'  => $midRangeAfter,
                    'range_number' => $midRangeNumber,
                ]));
            }
            // 小码处理
            if (!empty($rule['min_length']) && $rule['number'] > 0) {
                $minRangeStart = static::_minStart();
                $minRangeAfter = bcsub(bcadd($minRangeStart, strval($rule['number'])), '1');
                PluginWumaCodeRuleRange::mk()->insert(array_merge($default, [
                    'code_type'    => 'min',
                    'code_length'  => strlen("{$minRangeStart}"),
                    'range_start'  => $minRangeStart,
                    'range_after'  => $minRangeAfter,
                    'range_number' => $rule['number'],
                ]));
            }
            return ['code' => 1, 'info' => '物码数码生成成功！', 'data' => $batch];
        } catch (\Exception $exception) {
            trace_file($exception);
            return ['code' => 0, 'info' => "物码数码生成失败，{$exception->getMessage()}", 'data' => $batch];
        }
    }

    /**
     * 生成物码文件位置
     * @param string $batch 批次号
     * @return string
     */
    public static function withFile(string $batch): string
    {
        return syspath("safefile/code/{$batch}.zip");
    }

    /**
     * 获取小码起始值
     * @return string
     */
    private static function _minStart(): string
    {
        $map = ['code_type' => 'min'];
        $min = PluginWumaCodeRuleRange::mk()->where($map)->order('id desc')->value('range_after');
        return empty($min) ? bcpow('10', '6') : bcadd(strval($min), '1');
    }

    /**
     * 获取箱码起始值
     * @param integer $length
     * @return string
     */
    private static function _boxStart(int $length): string
    {
        $map = [['code_length', '=', $length], ['code_type', 'in', ['max', 'mid']]];
        $max = PluginWumaCodeRuleRange::mk()->where($map)->order('id desc')->value('range_after');
        return empty($max) ? bcpow('10', strval($length - 1)) : bcadd(strval($max), '1');
    }

    /**
     * 去除干扰字符
     * @param string $code
     * @return string
     */
    private static function _de8(string $code): string
    {
        $__ = str_split(base_convert($code, 10, 8), 4);
        foreach ($__ as &$_) isset($_[3]) && ($_ = substr($_, 0, 3));
        return base_convert(join('', $__), 8, 10);
    }

    /**
     * 计算补位数位置
     * @param string $code
     * @param integer $size
     * @return integer
     */
    private static function _point(string $code, int $size): int
    {
        return (intval(substr($code, -1)) ?: 5) % ($size - 2) ?: 1;
    }
}