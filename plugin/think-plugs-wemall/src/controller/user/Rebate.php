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

namespace plugin\wemall\controller\user;

use plugin\account\model\PluginAccountUser;
use plugin\wemall\model\PluginWemallUserRebate;
use plugin\wemall\service\UserRebate;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\db\Query;

/**
 * 代理返佣管理
 * @class Rebate
 * @package plugin\wemall\controller\user
 */
class Rebate extends Controller
{
    /**
     * 代理返佣管理
     * @auth true
     * @menu true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        PluginWemallUserRebate::mQuery()->layTable(function () {
            $this->title = '代理返佣管理';
            $this->rebate = UserRebate::recount(0);
        }, static function (QueryHelper $query) {
            // 数据关联
            $query->equal('type,status')->like('name,order_no')->dateBetween('create_time')->with([
                'user'  => function (Query $query) {
                    $query->field('id,code,phone,nickname,headimg');
                },
                'ouser' => function (Query $query) {
                    $query->field('id,code,phone,nickname,headimg');
                }
            ]);
            // 代理条件查询
            $db = PluginAccountUser::mQuery()->like('nickname|phone#agent')->db();
            if ($db->getOptions('where')) $query->whereRaw("unid in {$db->field('id')->buildSql()}");
            // 会员条件查询
            $db = PluginAccountUser::mQuery()->like('nickname|phone#user')->db();
            if ($db->getOptions('where')) $query->whereRaw("order_unid in {$db->field('id')->buildSql()}");
        });
    }
}