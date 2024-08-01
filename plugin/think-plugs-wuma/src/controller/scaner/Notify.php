<?php

// +----------------------------------------------------------------------
// | Wuma Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 收费插件 ( https://thinkadmin.top/fee-introduce.html )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wuma
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wuma
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wuma\controller\scaner;

use plugin\wuma\model\PluginWumaSourceQueryNotify;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

/**
 * 商品码窜货管理
 * @class Notify
 * @package plugin\wuma\controller\scan
 */
class Notify extends Controller
{
    /**
     * 窜货明细管理
     * @menu true
     * @auth true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
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
     * 窜货代理管理
     * @menu true
     * @auth true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function agent()
    {
        PluginWumaSourceQueryNotify::mQuery()->layTable(function () {
            $this->title = '窜货代理管理';
//            $this->levels = AgentLevel::items();
        }, static function (QueryHelper $qurey) {
            $qurey->with([
                'agent' => static function (\think\db\Query $query) {
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
     * 窜货区域管理
     * @menu true
     * @auth true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function area()
    {
        PluginWumaSourceQueryNotify::mQuery()->layTable(function () {
            $this->title = '窜货区域管理';
        }, static function (QueryHelper $helper) {
            $helper->field('*,count(distinct code) total,count(1) query');
            $helper->like('agent_prov#prov,agent_city#city,agent_area#area');

            // 根据条件分组数据
            if (!empty($this->get['city'])) $helper->group('agent_prov,agent_city,agent_area');
            elseif (!empty($this->get['prov'])) $helper->group('agent_prov,agent_city');
            else $helper->group('agent_prov');

            // 代理数据搜索
//            if (($db = AgentUser::mQuery()->like('phone,username')->db())->getOptions('where')) {
//                $helper->whereRaw("auid in {$db->field('id')->buildSql()}");
//            }
        });
    }
}