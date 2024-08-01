<?php

// +----------------------------------------------------------------------
// | Payment Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-account
// | github 代码仓库：https://github.com/zoujingli/think-plugs-account
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\payment\controller;

use plugin\account\model\PluginAccountUser;
use plugin\payment\model\PluginPaymentRefund;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

/**
 * 支付退款管理
 * @class Refund
 * @package plugin\payment\controller
 */
class Refund extends Controller
{
    /**
     * 支付退款管理
     * @auth true
     * @menu true
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->mode = $this->get['open_type'] ?? 'index';
        PluginPaymentRefund::mQuery()->layTable(function () {
            if ($this->mode === 'index') $this->title = '支付行为管理';
        }, static function (QueryHelper $query) {
            $query->with(['user', 'record'])->like('order_no|order_name#orderinfo')->dateBetween('create_time');
            $db = PluginAccountUser::mQuery()->like('email|nickname|username|phone#userinfo')->db();
            if ($db->getOptions('where')) $query->whereRaw("unid in {$db->field('id')->buildSql()}");
        });
    }
}
