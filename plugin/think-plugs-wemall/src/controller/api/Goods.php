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

namespace plugin\wemall\controller\api;

use plugin\wemall\model\PluginWemallExpressCompany;
use plugin\wemall\model\PluginWemallGoods;
use plugin\wemall\model\PluginWemallGoodsCate;
use plugin\wemall\model\PluginWemallGoodsMark;
use plugin\wemall\model\PluginWemallUserActionComment;
use plugin\wemall\model\PluginWemallUserActionSearch;
use plugin\wemall\model\PluginWemallUserCoupon;
use plugin\wemall\service\ExpressService;
use think\admin\Controller;
use think\admin\Exception;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\db\Query;

/**
 * 获取商品数据接口.
 * @class Goods
 */
class Goods extends Controller
{
    /**
     * 获取商品列表或详情.
     */
    public function get()
    {
        $this->coupon = null;
        $this->cnames = null;
        PluginWemallGoods::mQuery(null, function (QueryHelper $query) {
            // 根据优惠券展示商品
            if ($couponCode = input('coupon')) {
                $where = ['code' => $couponCode, 'deleted' => 0];
                $userCoupon = PluginWemallUserCoupon::mk()->where($where)->findOrEmpty();
                if ($userCoupon->isEmpty()) {
                    $this->error('无效优惠券！');
                }
                // 追加卡券信息到商品信息
                $map = ['status' => 1, 'deleted' => 0];
                $this->coupon = $userCoupon->coupon()->where($map)->field('type,name,extra,amount,limit_amount,limit_times')->findOrEmpty()->toArray();
                if (empty($this->coupon)) {
                    $this->error('优惠券已停用！');
                }
                if ($this->coupon['type'] == 1) {
                    $gcodes = array_column($this->coupon['extra'], 'code');
                    count($gcodes) > 0 ? $query->whereIn('code', $gcodes) : $query->whereRaw('1<>0');
                }
                unset($this->coupon['extra']);
            }
            // 根据多标签内容过滤
            if (!empty($vMarks = input('vmarks'))) {
                $query->where('marks', 'like', array_map(function ($mark) {
                    return "%,{$mark},%";
                }, str2arr($vMarks)), 'OR');
            }
            // 显示分类显示
            if (!empty($vCates = input('cates'))) {
                $cates = array_filter(PluginWemallGoodsCate::items(), function ($v) use ($vCates) {
                    return $v['id'] == $vCates;
                });
                $this->cnames = null;
                if (count($cates) > 0) {
                    $cate = array_pop($cates);
                    $this->cnames = array_combine($cate['ids'], $cate['names']);
                }
            }
            $query->equal('code')->like('name#keys')->like('marks,cates', ',');
            if (!empty($code = input('code'))) {
                // 查询单个商品详情
                $query->with(['discount', 'items', 'comments' => function (Query $query) {
                    $query->limit(2)->where(['status' => 1, 'deleted' => 0]);
                }])->withCount(['comments' => function (Query $query) {
                    $query->where(['status' => 1, 'deleted' => 0]);
                }]);
                PluginWemallGoods::mk()->where(['code' => $code])->inc('num_read')->update([]);
            } else {
                $query->with('discount')->withoutField('content');
            }
            // 数据排序处理
            $sort = intval(input('sort', 0));
            $type = intval(input('order', 0)) ? 'asc' : 'desc';
            if ($sort === 1) {
                $query->order("num_read {$type},sort {$type},id {$type}");
            } elseif ($sort === 2) {
                $query->order("price_selling {$type},sort {$type},id {$type}");
            } else {
                $query->order("sort {$type},id {$type}");
            }
            $query->where(['status' => 1, 'deleted' => 0]);
            // 查询数据分页
            $page = intval(input('page', 1));
            $limit = max(min(intval(input('limit', 20)), 60), 1);
            $this->success('获取商品数据', $query->page($page, false, false, $limit));
        });
    }

    /**
     * 获取商品分类及标签.
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function cate()
    {
        $this->success('获取分类成功', [
            'mark' => PluginWemallGoodsMark::items(),
            'cate' => PluginWemallGoodsCate::dtree(),
        ]);
    }

    /**
     * 获取商品评论.
     */
    public function comments()
    {
        PluginWemallUserActionComment::mQuery(null, function (QueryHelper $query) {
            $query->with(['bindUser'])->equal('gcode')->order('id desc');
            $this->success('获取评论成功！', $query->page(intval(input('page', 1)), false, false, 30));
        });
    }

    /**
     * 获取物流配送区域
     * @throws Exception
     */
    public function region()
    {
        $this->success('获取配送区域', ExpressService::region(3, 1));
    }

    /**
     * 获取快递公司数据.
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function express()
    {
        $query = PluginWemallExpressCompany::mk()->where(['status' => 1, 'deleted' => 0]);
        $query->field(['name' => 'text', 'code' => 'value'])->order('sort desc,id desc');
        $this->success('获取快递公司', $query->select()->toArray());
    }

    /**
     * 获取搜索热词.
     */
    public function hotkeys()
    {
        PluginWemallUserActionSearch::mQuery(null, function (QueryHelper $query) {
            $query->whereTime('sort', '-30 days')->like('keys');
            $query->field('keys')->group('keys')->cache(true, 60)->order('sort desc');
            $this->success('获取搜索热词！', ['keys' => $query->limit(0, 15)->column('keys')]);
        });
    }

    /**
     * 数据结果处理.
     */
    protected function _get_page_filter(array &$data, array &$result)
    {
        $result['cnames'] = $this->cnames ?? null;
        $result['coupon'] = $this->coupon ?? null;
    }

    /**
     * 商品评论处理.
     */
    protected function _comments_page_filter(array &$data)
    {
        foreach ($data as &$item) {
            $item['user_phone'] = preg_replace('/(^\d{3})\d+(\d{3}$)/', '$1***$2', $item['user_phone']);
        }
    }
}
