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

namespace plugin\wuma\service;

use plugin\wuma\model\PluginWumaWarehouseOrder;
use plugin\wuma\model\PluginWumaWarehouseOrderData;
use plugin\wuma\model\PluginWumaWarehouseOrderDataMins;
use plugin\wuma\model\PluginWumaWarehouseOrderDataNums;
use plugin\wuma\model\PluginWumaWarehouseStock;
use think\admin\Exception;
use think\admin\extend\CodeExtend;
use think\admin\Library;
use think\admin\service\AdminService;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 仓库出库数据服务
 * @class WhExportService
 */
class WhExportService
{
    /**
     * 生成出库单号.
     */
    public static function withCode(string $prefix = 'CK', int $length = 16): string
    {
        do {
            $data = ['code' => CodeExtend::uniqidDate($length, $prefix)];
        } while (PluginWumaWarehouseOrder::mk()->master()->where($data)->findOrEmpty()->isExists());
        return $data['code'];
    }

    /**
     * 按单出库.
     * @param array $body 输入数据
     * @param array $order 订单数据
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function order(array $body, array $order)
    {
        [$exists, $nones] = static::checkCodes($body);
        // 写入出库详情数据
        $count = static::insertData($body, $exists, $nones);
        // 检查物码数量是否超出
        if ($order['num_need'] - $order['num_used'] < $count) {
            throw new Exception('出库数据超出订单要求！');
        }
        // 更新出库订单状态
        if (array_sum(static::sync($body['code'])) >= $order['num_need']) {
            PluginWumaWarehouseOrder::mk()->where(['code' => $body['code']])->update(['status' => 2]);
        }
        // 刷新仓库统计数据
        PluginWumaWarehouseStock::sync($order['wcode']);
    }

    /**
     * 同步订单统计数据.
     * @return array [扫码,虚拟]
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function sync(string $code): array
    {
        $ddids = PluginWumaWarehouseOrderData::mk()->where($map = ['code' => $code])->column('id');
        $result = PluginWumaWarehouseOrderDataMins::mk()->fieldRaw('mode,count(1) count')->whereIn('ddid', $ddids)->group('mode')->select()->toArray();
        $total = array_column($result, 'count', 'mode');
        PluginWumaWarehouseOrder::mk()->where($map)->update(['num_used' => $total[1] ?? 0, 'vir_used' => $total[2] ?? 0]);
        return [$total[1] ?? 0, $total[2] ?? 0];
    }

    /**
     * 直接出库.
     * @param array $body 输入数据
     * @param bool $verify 是否验证
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function force(array $body, bool $verify = false)
    {
        // 写入出库数据
        [$exists, $nones] = static::checkCodes($body);
        if ($verify && count($exists) > 0) {
            throw new Exception('物码已出库！', 0, $exists);
        }
        [$count, $virtual] = [static::insertData($body, $exists, $nones), count($nones)];
        // 更新出库订单数据
        PluginWumaWarehouseOrder::mk()->save([
            'code' => $body['code'],
            'type' => $body['type'] ?? 5,
            'auid' => $body['auid'],
            'ghash' => $body['ghash'],
            'wcode' => $body['wcode'],
            'status' => 2,
            'num_used' => $count - $virtual,
            'num_need' => $count - $virtual,
            'vir_need' => $virtual,
            'vir_used' => $virtual,
        ]);
        // 刷新仓库统计数据
        PluginWumaWarehouseStock::sync($body['wcode']);
    }

    /**
     * 关联出库处理.
     * @param array $body 输入数据
     * @param bool $virtual 写入虚拟入库
     * @param bool $sample 赋码解锁模式
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function batch(array $body, bool $virtual = true, bool $sample = false)
    {
        // 统一转小码
        $codes = WhCoderService::code2mins($body);
        if (empty($codes)) {
            throw new Exception('出库物码不能为空！');
        }
        Library::$sapp->db->transaction(static function () use ($body, $codes, $virtual, $sample) {
            // 自动分区赋码
            $relation = RelationService::assign(array_keys($codes), $body['batch'], $sample);
            if (empty($body['ghash'])) {
                $body['ghash'] = $relation['ghash'] ?? '';
            }
            // 自动虚拟入库
            $virtual && WhImportService::virtual(count($codes), $body['wcode'], $body['ghash']);
            // 直接扫码出库
            $body['type'] = 7;
            static::force($body, true);
        });
    }

    /**
     * 检查并处理出库物码
     * @param array $body 输入数据
     * @return array [exists, nones]
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private static function checkCodes(array $body): array
    {
        // 统一转换为标准小码;
        $codes = WhCoderService::code2mins($body, true);
        if (empty($codes)) {
            throw new Exception('物码不能为空！');
        }
        // 检查物码是否已经出库
        [$state, , $exists, $items] = WhCoderService::checkExportExist(array_keys($codes), 'min');
        if (!empty($state)) {
            foreach ($exists as $exist) {
                $items[] = strstr($codes[$exist] ?? $exist, '#', true);
            }
            throw new Exception('物码已经出库！', 0, array_unique(array_values($items)));
        }
        // 检查产品库存是否足够
        $map = ['ghash' => $body['ghash'], 'wcode' => $body['wcode']];
        $field = 'sum(stock_total)-sum(sotck_used) stock,sum(vir_total)-sum(vir_used) `virtual`';
        $total = PluginWumaWarehouseStock::mk()->field($field)->where($map)->findOrEmpty();
        if ($total->isEmpty()) {
            throw new Exception('指定产品库存不足！');
        }
        // 检查物码是否已经入库
        [, , $exists] = WhCoderService::checkImportExist(array_keys($codes), 'min');
        foreach ($exists as &$exist) {
            $exist = $codes[$exist];
        }
        if ($total->getAttr('stock') < count($exists)) {
            throw new Exception('扫码库存不足！');
        }
        if ($total->getAttr('virtual') < count($codes) - count($exists)) {
            throw new Exception('虚拟库存不足！');
        }
        // 返回数据结果
        return [$exists, array_diff_key($codes, $exists)];
    }

    /**
     * 批量写入出库数据.
     * @param array $body 输入数据
     * @param array $exists 已入库
     * @param array $nones 未入库
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private static function insertData(array $body, array $exists, array $nones): int
    {
        // 物码统计处理
        $codes = $exists + $nones;
        [$count, $maps, $unis] = WhCoderService::code2count($codes);
        // 写入入库数据
        ($dataModel = PluginWumaWarehouseOrderData::mk())->save([
            'type' => $body['type'],
            'code' => $body['code'],
            'number' => $count,
            'create_by' => AdminService::getUserId(),
        ]);
        $ddid = $dataModel->getAttr('id');
        // 组装入库真实数据
        [$itemMins, $itemNums] = [[], []];
        // 写入小码数据，并锁定物码数据
        foreach ($nones as $min => $code) {
            $itemMins[] = ['ddid' => $ddid, 'code' => $min, 'mode' => 2];
        }
        foreach ($exists as $min => $code) {
            $itemMins[] = ['ddid' => $ddid, 'code' => $min, 'mode' => 1];
        }
        $mins = array_keys($codes);
        RelationService::changeAssignLock($mins, 2);
        foreach (array_chunk($itemMins, 1000) as $items) {
            PluginWumaWarehouseOrderDataMins::mk()->insertAll($items);
        }
        // 写入箱码数据
        foreach ($unis as $code) {
            $attr = explode('#', $maps[$code]);
            $itemNums[] = ['ddid' => $ddid, 'code' => $attr[0], 'type' => $attr[1], 'count' => $attr[2]];
        }
        if (count($itemNums) > 0) {
            PluginWumaWarehouseOrderDataNums::mk()->insertAll($itemNums);
        }
        // 写入代理库存数据
        $body['auid'] = $body['auid'] ?? 0;
        $body['xuid'] = 0;

        $body['_mins'] = $mins;
        $body['_unis'] = $unis;
        $body['_maps'] = $maps;
        $body['_count'] = $count;

        StockService::save($body);
        return $count;
    }
}
