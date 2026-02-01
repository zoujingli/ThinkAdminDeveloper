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

namespace plugin\wuma\controller\scaner;

use plugin\wemall\model\PluginWemallGoods;
use plugin\wuma\model\PluginWumaCodeRule;
use plugin\wuma\model\PluginWumaSourceQuery;
use plugin\wuma\model\PluginWumaSourceQueryNotify;
use plugin\wuma\model\PluginWumaWarehouseOrderDataMins;
use think\admin\Controller;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 大数据实时监测.
 * @class Protal
 */
class Protal extends Controller
{
    /**
     * 大数据实时监测.
     * @menu true
     * @auth true
     */
    public function index()
    {
        $this->title = '大数据实时监测';
        $this->fetch();
    }

    /**
     * 加载数据.
     * @auth true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function load()
    {
        $map = ['status' => 1, 'deleted' => 0];
        $model = PluginWumaSourceQuery::mk()->with(['bindAgent', 'bindProduct'])->order('id desc');
        $this->success('获取数据成功！', [
            '已出库' => PluginWumaWarehouseOrderDataMins::mk()->cache(true, 10)->where('type', [3, 4])->where($map)->count(),
            '已入库' => PluginWumaWarehouseOrderDataMins::mk()->cache(true, 10)->where('type', [1, 2])->where($map)->count(),
            '物码总数' => PluginWumaCodeRule::mk()->cache(true, 10)->sum('number'),
            '商品种类' => PluginWemallGoods::mk()->cache(true, 10)->count(),
            '扫码总量' => PluginWumaSourceQuery::mk()->cache(true, 10)->group('code')->count(),
            '窜货总量' => PluginWumaSourceQueryNotify::mk()->cache(true, 10)->group('code')->count(),
            '实时溯源' => $model->limit(0, 50)->cache(true, 10)->select()->toArray(),
            '地图数据' => $this->getRegion(),
            '查询统计' => $this->getTotal(),
        ]);
    }

    /**
     * 获取查询统计数据.
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function getTotal(): array
    {
        $ckey = 'region_total';
        // 读取地图的缓存数据
        $cache = $this->app->cache->get($ckey, []);
        if (!empty($cache) && count($cache) > 0) {
            return $cache;
        }

        // 刷新地图的缓存数据
        $model = PluginWumaSourceQuery::mk()->where(['notify' => 0])->group('prov');
        $items1 = $model->fieldRaw('prov name,count(1) count')->orderRaw('count desc')->select()->toArray();

        $model = PluginWumaSourceQuery::mk()->where(['notify' => 1])->group('prov');
        $items2 = $model->fieldRaw('prov name,count(1) count')->orderRaw('count desc')->select()->toArray();

        $items = [];
        foreach ($this->applyRegion($items1) as $item) {
            $items[$item['name']]['name'] = $item['name'];
            $items[$item['name']]['value1'] = $item['count'];
        }

        foreach ($this->applyRegion($items2) as $item) {
            $items[$item['name']]['name'] = $item['name'];
            $items[$item['name']]['value2'] = $item['count'];
        }
        $this->app->cache->set($ckey, $items = array_values($items), 10);
        return $items;
    }

    /**
     * 获取查询区域
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function getRegion(): array
    {
        // 读取地图的缓存数据
        $ckey = 'region_total2';
        $cache = $this->app->cache->get($ckey, []);
        if (!empty($cache) && count($cache) > 0) {
            return $cache;
        }

        // 刷新地图的缓存数据
        $model = PluginWumaSourceQuery::mk()->group('prov');
        $items = $model->fieldRaw('prov name,count(1) count')->orderRaw('count desc')->select()->toArray();
        $this->app->cache->set($ckey, $this->applyRegion($items), 10);
        return $items;
    }

    /**
     * 应用地图省份名称.
     */
    private function applyRegion(array &$items): array
    {
        $mapping = [
            '北京市' => '北京',
            '天津市' => '天津',
            '河北省' => '河北',
            '山西省' => '山西',
            '内蒙古自治区' => '内蒙古',
            '辽宁省' => '辽宁',
            '吉林省' => '吉林',
            '黑龙江省' => '黑龙江',
            '上海市' => '上海',
            '江苏省' => '江苏',
            '浙江省' => '浙江',
            '安徽省' => '安徽',
            '福建省' => '福建',
            '江西省' => '江西',
            '山东省' => '山东',
            '河南省' => '河南',
            '湖北省' => '湖北',
            '湖南省' => '湖南',
            '广东省' => '广东',
            '广西壮族自治区' => '广西',
            '海南省' => '海南',
            '重庆市' => '重庆',
            '四川省' => '四川',
            '贵州省' => '贵州',
            '云南省' => '云南',
            '西藏自治区' => '西藏',
            '陕西省' => '陕西',
            '甘肃省' => '甘肃',
            '青海省' => '青海',
            '宁夏回族自治区' => '宁夏',
            '新疆维吾尔自治区' => '新疆',
            '台湾省' => '台湾',
            '香港特别行政区' => '香港',
            '澳门特别行政区' => '澳门',
        ];
        foreach ($items as &$item) {
            if (isset($mapping[$item['name']])) {
                $item['name'] = $mapping[$item['name']];
            }
            $item['count'] = intval($item['count']);
        }
        // 填充没有数据的区域
        $exists = array_column($items, 'name');
        foreach ($mapping as $v) {
            if (!in_array($v, $exists)) {
                $items[] = ['name' => $v, 'count' => 0];
            }
        }
        return $items;
    }
}
