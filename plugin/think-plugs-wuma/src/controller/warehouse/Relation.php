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

namespace plugin\wuma\controller\warehouse;

use plugin\wuma\model\PluginWumaWarehouseRelation;
use plugin\wuma\model\PluginWumaWarehouseRelationData;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\HttpResponseException;

/**
 * 物码后关联管理.
 * @class Relation
 */
class Relation extends Controller
{
    /**
     * 物码后关联管理.
     * @menu true
     * @auth true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
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
     * 删除关联数据.
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
            foreach ($rids as $k => $v) {
                if (empty($v)) {
                    unset($rids[$k]);
                }
            }
            if (count($rids) > 0) {
                $this->app->db->transaction(static function () use ($rids) {
                    PluginWumaWarehouseRelation::mk()->whereIn('id', $rids)->delete();
                    PluginWumaWarehouseRelationData::mk()->whereIn('rid', $rids)->delete();
                });
            }
            $this->success('删除关联批次数据！');
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception);
        }
    }
}
