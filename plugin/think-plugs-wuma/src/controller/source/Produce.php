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

namespace plugin\wuma\controller\source;

use plugin\wemall\model\PluginWemallGoods;
use plugin\wemall\model\PluginWemallGoodsItem;
use plugin\wuma\model\PluginWumaSourceAssignItem;
use plugin\wuma\model\PluginWumaSourceProduce;
use plugin\wuma\model\PluginWumaSourceTemplate;
use think\admin\Controller;
use think\admin\extend\CodeExtend;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 生产批次管理.
 * @class Produce
 */
class Produce extends Controller
{
    /**
     * 生产批次管理.
     * @menu true
     * @auth true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $this->type = $this->get['type'] ?? 'index';
        PluginWumaSourceProduce::mQuery()->layTable(function () {
            $this->title = '生产批次管理';
        }, function (QueryHelper $query) {
            // 模型数据关联
            $query->with(['bindGoods', 'bindTemplate']);

            // 数据过滤条件
            $map = ['deleted' => intval($this->type !== 'index')];
            $query->like('batch')->dateBetween('create_time')->where($map);

            // 产品搜索查询
            $db1 = PluginWemallGoods::mQuery()->like('code|name#gname')->db();
            if ($db1->getOptions('where')) {
                $db2 = PluginWemallGoodsItem::mk()->whereRaw("gcode in {$db1->field('code')->buildSql()}");
                $query->whereRaw("ghash in {$db2->field('ghash')->buildSql()}");
            }

            // 溯源模板查询
            $db = PluginWumaSourceTemplate::mQuery()->field('code')->like('code|name#tname')->db();
            if ($db->getOptions('where')) {
                $query->whereRaw("tcode in {$db->buildSql()}");
            }
        });
    }

    /**
     * 添加生产批次
     * @auth true
     */
    public function add()
    {
        $this->mode = 'add';
        PluginWumaSourceProduce::mForm('form');
    }

    /**
     * 编辑生产批次
     * @auth true
     */
    public function edit()
    {
        $this->mode = 'edit';
        PluginWumaSourceProduce::mForm('form');
    }

    /**
     * 修改生产批次状态
     * @auth true
     * @throws DbException
     */
    public function state()
    {
        $data = $this->_vali(['deleted.require' => '删除状态不能为空！']);
        if ($data['deleted'] > 0) {
            $subsql = PluginWumaSourceProduce::mk()->whereIn('id', str2arr(input('id', '')))->field('batch')->buildSql();
            $batchs = PluginWumaSourceAssignItem::mk()->whereRaw("pbatch in {$subsql}")->distinct()->column('pbatch');
            if (count($batchs) > 0) {
                $this->error('删除失败，生产批次已经使用！<br><b>' . join('</b><br><b>', $batchs) . '</b>');
            }
        }
        PluginWumaSourceProduce::mSave();
    }

    /**
     * 表单数据处理.
     * @throws DbException
     * @throws DbException
     */
    protected function _form_filter(array &$data)
    {
        if (empty($data['batch'])) {
            $data['batch'] = CodeExtend::uniqidDate(16, 'P');
        }
        if ($this->request->isPost()) {
            // 检查批次编号是否出现重复
            $map = [['batch', '=', $data['batch']]];
            if (isset($data['id'])) {
                $map[] = ['id', '<>', $data['id']];
            }
            if (PluginWumaSourceProduce::mk()->where($map)->count() > 0) {
                $this->error('批次编号已经存在！');
            }
        } else {
            $this->products = PluginWemallGoods::lists();
            $this->templates = PluginWumaSourceTemplate::lists();
            if (empty($this->products)) {
                $this->error('无有效的产品数据！');
            }
            if (empty($this->templates)) {
                $this->error('无有效的溯源模板！');
            }
        }
    }
}
