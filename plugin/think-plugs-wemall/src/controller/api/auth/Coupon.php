<?php

// +----------------------------------------------------------------------
// | WeMall Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wemall
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wemall
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wemall\controller\api\auth;

use plugin\wemall\controller\api\Auth;
use plugin\wemall\model\PluginWemallConfigCoupon;
use plugin\wemall\model\PluginWemallUserCoupon;
use plugin\wemall\service\UserCoupon;
use think\admin\helper\QueryHelper;
use think\db\Query;
use think\exception\HttpResponseException;

/**
 * 卡券数据接口
 * @class Coupon
 * @package plugin\wemall\controller\api\auth
 */
class Coupon extends Auth
{

    /**
     * 获取卡券
     * @return void
     */
    public function get()
    {
        PluginWemallConfigCoupon::mQuery(null, function (QueryHelper $query) {
            $query->equal('type,id#coid')->where(['status' => 1, 'deleted' => 0]);
            $this->success('获取卡券', $query->order('sort desc,id desc')->page(intval(input('page')), false, false, 20));
        });
    }

    /**
     * 统计卡券
     * @param array $data
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function _get_page_filter(array &$data)
    {
        $where = ['coid' => array_column($data, 'id'), 'unid' => $this->unid, 'deleted' => 0];
        $query = PluginWemallUserCoupon::mk()->field(['count(1)' => 'c', 'coid' => 'd']);
        $total = $query->where($where)->group('coid')->select()->column('c', 'd');
        foreach ($data as &$vo) $vo['used'] = $total[$vo['id']] ?? 0;
    }

    /**
     * 领取卡券
     * @return void
     */
    public function add()
    {
        try {
            $data = $this->_vali(['coid.require' => '卡券为空！']);
            $coupon = UserCoupon::create($this->relation, intval($data['coid']));
            $this->success('领取成功！', $coupon->toArray());
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 我的卡券
     * @return void
     */
    public function mine()
    {
        PluginWemallUserCoupon::mQuery(null, function (QueryHelper $query) {
            $query->with('coupon')->equal('type,coid,status')->where(['deleted' => 0]);
            $this->success('我的卡券', $query->order('id desc')->page(intval(input('page')), false, false, 20));
        });
    }

    /**
     * 查询卡券
     * @return void
     */
    public function query()
    {
        $data = $this->_vali([
            'level.default'  => '-',
            'usable.default' => 0,
            'amount.default' => 0,
            'gcodes.require' => '商品编号为空！',
        ]);
        if (empty($gcodes = str2arr($data['gcodes']))) $this->error('商品编号为空！');
        PluginWemallConfigCoupon::mQuery(null, function (QueryHelper $query) use ($data, $gcodes) {
            $query->where(['status' => 1, 'deleted' => 0]);
            if (($amount = floatval($data['amount'])) > 0) {
                $query->where('limit_amount', '<=', $amount);
            }
            $query->where(function (Query $query) use ($gcodes) {
                $query->where(['type' => 0])->whereOr(function (Query $query) use ($gcodes) {
                    $likes = [];
                    foreach ($gcodes as $gcode) $likes[] = "%{$gcode}%";
                    $query->where(['type' => 1])->where('extra', 'like', $likes, 'OR');
                });
            });
            if ($data['level'] !== '-') {

            }
            // 检索自己可用卡券
            if (!empty($data['usable'])) {
                $db = PluginWemallUserCoupon::mk()->where(['unid' => $this->unid, 'status' => 1, 'deleted' => 0]);
                $query->whereRaw("id in {$db->field('id')->buildSql()}");
                $query->with('usable')->withCount(['usable' => function (Query $query) {
                    $query->where(['status' => 1, 'deleted' => 0]);
                }]);
            }
            $this->success('查询卡券！', $query->order('amount desc')->page(intval(input('page')), false, false, 30));
        });
    }
}