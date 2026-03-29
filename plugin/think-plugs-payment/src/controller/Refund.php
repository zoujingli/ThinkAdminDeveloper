<?php

// +----------------------------------------------------------------------
// | Payment Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员特权 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-account
// | github 代码仓库：https://github.com/zoujingli/think-plugs-account
// +----------------------------------------------------------------------

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdminDeveloper
 * +----------------------------------------------------------------------
 * | Copyright (c) 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | Official Website: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | Licensed: https://mit-license.org
 * | Disclaimer: https://thinkadmin.top/disclaimer
 * | Vip Rights: https://thinkadmin.top/vip-introduce
 * +----------------------------------------------------------------------
 * | Gitee Repository: https://gitee.com/zoujingli/ThinkAdmin
 * | Github Repository: https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace plugin\payment\controller;

use plugin\account\model\PluginAccountUser;
use plugin\payment\model\PluginPaymentRefund;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 支付退款管理.
 * @class Refund
 */
class Refund extends Controller
{
    /**
     * 支付退款管理.
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $this->mode = $this->get['open_type'] ?? 'index';
        PluginPaymentRefund::mQuery()->layTable(function () {
            if ($this->mode === 'index') {
                $this->title = lang('支付退款管理');
            }
        }, static function (QueryHelper $query) {
            $query->with(['user', 'record']);
            $query->like('order_no|order_name#orderinfo')->dateBetween('create_time');
            $userQuery = PluginAccountUser::mQuery();
            $userQuery->like('email|nickname|username|phone#userinfo');
            $db = $userQuery->db();
            if (!empty($db->getOptions()['where'] ?? [])) {
                $query->whereRaw("unid in {$db->field('id')->buildSql()}");
            }
        });
    }
}
