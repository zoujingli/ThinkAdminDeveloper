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

namespace plugin\wuma\controller;

use plugin\wuma\model\PluginWumaWarehouse;
use think\admin\Controller;
use think\admin\extend\CodeExtend;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 总部仓库管理.
 * @class Warehouse
 */
class Warehouse extends Controller
{
    /**
     * 总部仓库管理.
     * @menu true
     * @auth true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $this->type = $this->get['type'] ?? 'index';
        PluginWumaWarehouse::mQuery()->layTable(function () {
            $this->title = '总部仓库管理';
        }, function (QueryHelper $query) {
            $query->like('code,name,addr_prov|addr_city|addr_area|addr_text#addr')->dateBetween('create_time');
            $query->where(['deleted' => 0, 'status' => intval($this->type === 'index')]);
        });
    }

    /**
     * 添加总部仓库.
     * @auth true
     */
    public function add()
    {
        PluginWumaWarehouse::mForm('form');
    }

    /**
     * 修改总部仓库.
     * @auth true
     */
    public function edit()
    {
        PluginWumaWarehouse::mForm('form');
    }

    /**
     * 修改总部仓库状态
     * @auth true
     */
    public function state()
    {
        PluginWumaWarehouse::mSave();
    }

    /**
     * 表单数据处理.
     * @throws DbException
     */
    protected function _form_filter(array &$data)
    {
        if (empty($data['code'])) {
            $data['code'] = CodeExtend::uniqidNumber(16, 'W');
        }
        if ($this->request->isPost()) {
            // 检查产品编号是否出现重复
            $map = [['code', '=', $data['code']]];
            if (isset($data['id'])) {
                $map[] = ['id', '<>', $data['id']];
            }
            if (PluginWumaWarehouse::mk()->where($map)->count() > 0) {
                $this->error('仓库编号已经存在！');
            }
        }
    }
}
