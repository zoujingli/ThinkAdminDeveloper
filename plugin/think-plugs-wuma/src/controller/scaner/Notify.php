<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | Payment Plugin for ThinkAdmin
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

use plugin\wuma\model\PluginWumaSourceQueryNotify;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\db\Query;

/**
 * 商品码窜货管理.
 * @class Notify
 */
class Notify extends Controller
{
    /**
     * 窜货明细管理.
     * @menu true
     * @auth true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        PluginWumaSourceQueryNotify::mQuery()->layTable(function () {
            $this->title = '窜货明细管理';
        }, static function (QueryHelper $query) {
            $query->with(['agent', 'info']);
            $query->like('prov,city,area')->equal('code,encode')->dateBetween('create_time');
            // 代理数据搜索
            //            $db = AgentUser::mQuery()->like('phone|username#username,region_prov,region_city,region_area')->db();
            //            if ($db->getOptions('where')) $query->whereRaw("auid in {$db->field('id')->buildSql()}");
        });
    }

    /**
     * 窜货代理管理.
     * @menu true
     * @auth true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function agent()
    {
        PluginWumaSourceQueryNotify::mQuery()->layTable(function () {
            $this->title = '窜货代理管理';
            //            $this->levels = AgentLevel::items();
        }, static function (QueryHelper $qurey) {
            $qurey->with([
                'agent' => static function (Query $query) {
                    $query->with('levelinfo');
                },
            ]);
            $qurey->group('auid')->field('*,count(distinct code) total,count(1) query');
            // 代理数据搜索
            //            $md = AgentUser::mQuery()->like('phone,username,region_prov,region_city,region_area');
            //            if (($db = $md->field('id')->equal('level')->db())->getOptions('where')) {
            //                $qurey->whereRaw("auid in {$db->buildSql()}");
            //            }
        });
    }

    /**
     * 窜货区域管理.
     * @menu true
     * @auth true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function area()
    {
        PluginWumaSourceQueryNotify::mQuery()->layTable(function () {
            $this->title = '窜货区域管理';
        }, static function (QueryHelper $helper) {
            $helper->field('*,count(distinct code) total,count(1) query');
            $helper->like('agent_prov#prov,agent_city#city,agent_area#area');

            // 根据条件分组数据
            if (!empty($this->get['city'])) {
                $helper->group('agent_prov,agent_city,agent_area');
            } elseif (!empty($this->get['prov'])) {
                $helper->group('agent_prov,agent_city');
            } else {
                $helper->group('agent_prov');
            }

            // 代理数据搜索
            //            if (($db = AgentUser::mQuery()->like('phone,username')->db())->getOptions('where')) {
            //                $helper->whereRaw("auid in {$db->field('id')->buildSql()}");
            //            }
        });
    }
}
