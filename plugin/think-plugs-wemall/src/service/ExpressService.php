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

namespace plugin\wemall\service;

use plugin\wemall\model\PluginWemallExpressTemplate;
use think\admin\Exception;
use think\admin\service\InterfaceService;

/**
 * 快递查询数据服务
 * @class ExpressService
 */
abstract class ExpressService
{
    /**
     * 模拟计算快递费用.
     * @param array $codes 模板编号
     * @param string $provName 省份名称
     * @param string $cityName 城市名称
     * @param int $deliveryCount 邮费基数
     * @return array [邮费金额, 计费基数, 模板编号, 计费描述]
     * @throws Exception
     */
    public static function amount(array $codes, string $provName, string $cityName, int $deliveryCount = 0): array
    {
        if (in_array('NONE', $codes)) {
            return [0, $deliveryCount, '', '无需运费'];
        }
        if (in_array('FREE', $codes)) {
            return [0, $deliveryCount, '', '免费包邮'];
        }
        if (empty($codes)) {
            throw new Exception('邮费模板为空');
        }
        $where = [['status', '=', 1], ['deleted', '=', 0], ['code', 'in', $codes]];
        $template = PluginWemallExpressTemplate::mk()->where($where)->order('sort desc,id desc')->findOrEmpty();
        if ($template->isEmpty()) {
            throw new Exception('邮费模板无效');
        }
        $config = $template['normal'] ?? [];
        foreach ($template['content'] ?? [] as $item) {
            if (isset($item['city']) && is_array($item['city'])) {
                foreach ($item['city'] as $city) {
                    if ($city['name'] === $provName && in_array($cityName, $city['subs'])) {
                        $config = $item['rule'];
                        break 2;
                    }
                }
            }
        }
        [$firstCount, $firstAmount] = [$config['firstNumber'] ?: 0, $config['firstAmount'] ?: 0];
        [$repeatCount, $repeatAmount] = [$config['repeatNumber'] ?: 0, $config['repeatAmount'] ?: 0];
        if ($deliveryCount <= $firstCount) {
            return [$firstAmount, $deliveryCount, $template['code'], "首件计费，不超过{$firstCount}件"];
        }
        $amount = $repeatCount > 0 ? $repeatAmount * ceil(($deliveryCount - $firstCount) / $repeatCount) : 0;
        $totalAmount = bcadd(strval($firstAmount), strval($amount), 2);
        return [$totalAmount, $deliveryCount, $template['code'], "续件计费，超出{$firstCount}件续件{$amount}元"];
    }

    /**
     * 配送区域树型数据.
     * @param int $level 最大等级
     * @param ?int $status 状态筛选
     * @throws Exception
     */
    public static function region(int $level = 3, ?int $status = null): array
    {
        [$items, $ncodes] = [[], sysdata('plugin.wemall.region.not')];
        foreach (json_decode(file_get_contents(syspath('public/static/plugs/jquery/area/data.json')), true) as $prov) {
            $pstat = intval(!in_array($prov['code'], $ncodes));
            if (is_null($status) || is_numeric($status) && $status === $pstat) {
                $mprov = ['id' => $prov['code'], 'pid' => 0, 'name' => $prov['name'], 'status' => $pstat, 'subs' => []];
                if ($level > 1) {
                    foreach ($prov['list'] as $city) {
                        $cstat = intval(!in_array($city['code'], $ncodes));
                        if (is_null($status) || is_numeric($status) && $status === $cstat) {
                            $mcity = ['id' => $city['code'], 'pid' => $prov['code'], 'name' => $city['name'], 'status' => $cstat, 'subs' => []];
                            if ($level > 2) {
                                foreach ($city['list'] as $area) {
                                    $astat = intval(!in_array($area['code'], $ncodes));
                                    if (is_null($status) || is_numeric($status) && $status === $astat) {
                                        $mcity['subs'][] = ['id' => $area['code'], 'pid' => $city['code'], 'status' => $astat, 'name' => $area['name']];
                                    }
                                }
                            }
                            $mprov['subs'][] = $mcity;
                        }
                    }
                }
                $items[] = $mprov;
            }
        }
        return $items;
    }

    /**
     * 楚才开放平台快递查询.
     * @param string $express 快递公司编号
     * @param string $number 快递配送单号
     * @throws Exception
     */
    public static function query(string $express, string $number): array
    {
        return static::getInterface()->doRequest('api.auth.express/query', [
            'type' => 'free', 'express' => $express, 'number' => $number,
        ]);
    }

    /**
     * 楚才开放平台快递公司.
     * @throws Exception
     */
    public static function company(): array
    {
        return static::getInterface()->doRequest('api.auth.express/getCompany');
    }

    /**
     * 获取楚才开放平台接口实例.
     */
    private static function getInterface(): InterfaceService
    {
        $service = InterfaceService::instance();
        // 测试的账号及密钥随时可能会变更，请联系客服更新
        $service->getway('https://open.cuci.cc/user/');
        $service->setAuth('6998081316132228', '193fc1d9a2aac78475bc8dbeb9a5feb1');
        return $service;
    }
}
