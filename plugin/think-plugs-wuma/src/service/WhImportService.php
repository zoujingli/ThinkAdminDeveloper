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

use plugin\wuma\model\PluginWumaWarehouseOrder;
use plugin\wuma\model\PluginWumaWarehouseOrderData;
use plugin\wuma\model\PluginWumaWarehouseOrderDataMins;
use plugin\wuma\model\PluginWumaWarehouseOrderDataNums;
use plugin\wuma\model\PluginWumaWarehouseStock;
use think\admin\Exception;
use think\admin\extend\CodeExtend;
use think\admin\service\AdminService;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 仓库入库服务
 * @class WhImportService
 */
class WhImportService
{
    /**
     * 生成入库单号.
     */
    public static function withCode(string $prefix = 'RK', int $length = 16): string
    {
        do {
            $data = ['code' => CodeExtend::uniqidDate($length, $prefix)];
        } while (PluginWumaWarehouseOrder::mk()->master()->where($data)->findOrEmpty()->isExists());
        return $data['code'];
    }

    /**
     * 按订单入库.
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function order(array $body, array $order)
    {
        // 写入库详情数据
        $count = static::insertData($body, static::checkCodes($body));
        // 检查物码数量是否超出
        if ($order['num_need'] - $order['num_used'] < $count) {
            throw new Exception('入库数量超出！');
        }
        // 更新入库单数据
        $map = ['code' => $body['code']];
        $data = ['num_used' => PluginWumaWarehouseOrderData::mk()->where($map)->sum('number')];
        if ($data['num_used'] >= $order['num_need']) {
            $data['status'] = 2;
        }
        PluginWumaWarehouseOrder::mk()->where($map)->update($data);
        // 刷新仓库统计数据
        PluginWumaWarehouseStock::sync($order['wcode']);
    }

    /**
     * 直接入库.
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function force(array $body)
    {
        $count = static::insertData($body, static::checkCodes($body));
        // 更新入库订单数据
        PluginWumaWarehouseOrder::mk()->save([
            'code' => $body['code'],
            'type' => $body['type'] ?? 2,
            'mode' => $body['mode'],
            'ghash' => $body['ghash'],
            'status' => 2,
            'num_used' => $count,
            'num_need' => $count,
        ]);
        // 刷新仓库统计数据
        PluginWumaWarehouseStock::sync($body['wcode']);
    }

    /**
     * 创建虚拟入库.
     * @param int $count 入库数量
     * @param string $wcode 库存编号
     * @param string $ghash 产品编号
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function virtual(int $count, string $wcode, string $ghash)
    {
        $data = [];
        $data['code'] = CodeExtend::uniqidDate(16, 'RK');
        $data['type'] = 1;
        $data['mode'] = 2;
        $data['status'] = 2;
        $data['ghash'] = $ghash;
        $data['wcode'] = $wcode;
        $data['num_need'] = 0;
        $data['num_used'] = 0;
        $data['vir_need'] = $count;
        $data['vir_used'] = $count;
        PluginWumaWarehouseOrder::mk()->save($data);
        PluginWumaWarehouseOrderData::mk()->save([
            'type' => 1, 'mode' => 2, 'code' => $data['code'], 'number' => $count,
        ]);
        PluginWumaWarehouseStock::sync($wcode);
    }

    /**
     * 检查并处理入库数据.
     * @return array [min=>code#type]
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private static function checkCodes(array $body): array
    {
        $codes = WhCoderService::code2mins($body, true);
        if (empty($codes)) {
            throw new Exception('入库物码不能为空！');
        }
        [$state, , $exists, $items] = WhCoderService::checkImportExist(array_keys($codes), 'min');
        if (!empty($state)) {
            foreach ($exists as $exist) {
                $items[] = strstr($codes[$exist] ?? $exist, '#', true);
            }
            throw new Exception('物码已经入库！', 0, array_unique(array_values($items)));
        }
        return $codes;
    }

    /**
     * 批量写入入库数据.
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private static function insertData(array $body, array $codes): int
    {
        // 订单扩展数据
        $extra = ['type' => $body['type'], 'mode' => $body['mode']];
        // 物码统计处理
        [$count, $maps, $unis] = WhCoderService::code2count($codes);
        // 创建入库订单数据
        ($dataModel = PluginWumaWarehouseOrderData::mk())->save(array_merge([
            'code' => $body['code'], 'number' => $count, 'create_by' => AdminService::getUserId(),
        ], $extra));
        $ddid = $dataModel->getAttr('id');
        // 组装入库真实数据
        [$mins, $itemMins, $itemNums] = [array_keys($codes), [], []];
        // 写入小码数据，并锁定物码数据
        foreach ($mins as $code) {
            $itemMins[] = ['ddid' => $ddid, 'code' => $code] + $extra;
        }
        if (count($itemMins) > 0) {
            RelationService::changeAssignLock($mins, 2);
            foreach (array_chunk($itemMins, 1000) as $items) {
                PluginWumaWarehouseOrderDataMins::mk()->insertAll($items);
            }
        }
        // 写入箱码数据
        foreach ($unis as $code) {
            $attr = explode('#', $maps[$code]);
            $itemNums[] = ['ddid' => $ddid, 'code' => $attr[0], 'type' => $attr[1], 'count' => $attr[2]];
        }
        if (count($itemNums) > 0) {
            PluginWumaWarehouseOrderDataNums::mk()->insertAll($itemNums);
        }
        return $count;
    }
}
