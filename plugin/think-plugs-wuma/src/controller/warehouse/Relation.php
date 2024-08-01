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

namespace plugin\wuma\controller\warehouse;

use plugin\wuma\model\PluginWumaWarehouseRelation;
use plugin\wuma\model\PluginWumaWarehouseRelationData;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\exception\HttpResponseException;

/**
 * 物码后关联管理
 * @class Relation
 * @package plugin\wuma\controller\warehouse
 */
class Relation extends Controller
{
    /**
     * 物码后关联管理
     * @menu true
     * @auth true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        PluginWumaWarehouseRelationData::mQuery()->layTable(function () {
            $this->title = '物码后关联管理';
        }, static function (QueryHelper $helper) {
            $helper->equal('max,mid,min')->dateBetween('create_time');
        });
    }

    /**
     * 删除关联数据
     * @auth true
     */
    public function remove()
    {
        try {
            // 检查数据是否已经使用
            $ids = str2arr($this->request->post('id'));
            $where = [['id', 'in', $ids], ['lock', '=', 2]];
            if (PluginWumaWarehouseRelationData::mk()->where($where)->findOrEmpty()->isExists()) {
                $this->error('待删除关联数据已经使用！');
            }
            // 获取关联的操作单号
            $rids = PluginWumaWarehouseRelationData::mk()->whereIn('id', $ids)->column('rid');
            foreach ($rids as $k => $v) if (empty($v)) unset($rids[$k]);
            if (count($rids) > 0) $this->app->db->transaction(static function () use ($rids) {
                PluginWumaWarehouseRelation::mk()->whereIn('id', $rids)->delete();
                PluginWumaWarehouseRelationData::mk()->whereIn('rid', $rids)->delete();
            });
            $this->success('删除关联批次数据！');
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception);
        }
    }
}