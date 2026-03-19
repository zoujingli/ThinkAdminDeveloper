<?php

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

namespace plugin\wemall\controller\user;

use plugin\account\model\PluginAccountUser;
use plugin\wemall\model\PluginWemallUserCoupon;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 用户卡券管理.
 * @class Coupon
 */
class Coupon extends Controller
{
    /**
     * 用户卡券管理.
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        PluginWemallUserCoupon::mQuery()->layTable(function () {
            $this->title = '用户卡券管理';
        }, function (QueryHelper $query) {
            // 数据关联
            $query->with(['coupon', 'bindUser']);
            // 代理条件查询
            $query->like('code')->dateBetween('create_time');
            // 会员条件查询
            $db = PluginAccountUser::mQuery()->like('nickname|phone#user')->db();
            if (!empty($db->getOptions()['where'] ?? [])) {
                $query->whereRaw("order_unid in {$db->field('id')->buildSql()}");
            }
        });
    }
}
