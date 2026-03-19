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
use plugin\wemall\model\PluginWemallUserRebate;
use plugin\wemall\service\UserRebate;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\db\Query;

/**
 * 代理返佣管理.
 * @class Rebate
 */
class Rebate extends Controller
{
    /**
     * 代理返佣管理.
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        PluginWemallUserRebate::mQuery()->layTable(function () {
            $this->title = '代理返佣管理';
            $this->rebate = UserRebate::recount(0);
        }, static function (QueryHelper $query) {
            // 删除状态
            // 数据关联
            $query->equal('type,status')->like('name,order_no')->dateBetween('create_time')->with([
                'user' => function (Query $query) {
                    $query->field('id,code,phone,nickname,headimg');
                },
                'ouser' => function (Query $query) {
                    $query->field('id,code,phone,nickname,headimg');
                },
            ]);
            // 代理条件查询
            $db = PluginAccountUser::mQuery()->like('nickname|phone#agent')->db();
            if (!empty($db->getOptions()['where'] ?? [])) {
                $query->whereRaw("unid in {$db->field('id')->buildSql()}");
            }
            // 会员条件查询
            $db = PluginAccountUser::mQuery()->like('nickname|phone#user')->db();
            if (!empty($db->getOptions()['where'] ?? [])) {
                $query->whereRaw("order_unid in {$db->field('id')->buildSql()}");
            }
        });
    }
}
