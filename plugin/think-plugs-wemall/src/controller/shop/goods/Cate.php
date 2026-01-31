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

namespace plugin\wemall\controller\shop\goods;

use plugin\wemall\model\PluginWemallGoodsCate;
use think\admin\Controller;
use think\admin\extend\DataExtend;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 商品分类管理.
 * @class Cate
 */
class Cate extends Controller
{
    /**
     * 最大级别.
     * @var int
     */
    protected $maxLevel = 3;

    /**
     * 商品分类管理.
     * @auth true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        PluginWemallGoodsCate::mQuery($this->get)->layTable(function () {
            $this->title = '商品分类管理';
        }, static function (QueryHelper $query) {
            $query->where(['deleted' => 0]);
            $query->like('name')->equal('status')->dateBetween('create_time');
        });
    }

    /**
     * 添加商品分类.
     * @auth true
     */
    public function add()
    {
        PluginWemallGoodsCate::mForm('form');
    }

    /**
     * 编辑商品分类.
     * @auth true
     */
    public function edit()
    {
        PluginWemallGoodsCate::mForm('form');
    }

    /**
     * 修改商品分类状态
     * @auth true
     */
    public function state()
    {
        PluginWemallGoodsCate::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除商品分类.
     * @auth true
     */
    public function remove()
    {
        PluginWemallGoodsCate::mDelete();
    }

    /**
     * 商品分类选择器.
     * @login true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function select()
    {
        $this->get['status'] = 1;
        $this->get['deleted'] = 0;
        $this->index();
    }

    /**
     * 列表数据处理.
     */
    protected function _page_filter(array &$data)
    {
        $data = DataExtend::arr2table($data);
    }

    /**
     * 表单数据处理.
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function _form_filter(array &$data)
    {
        if ($this->request->isGet()) {
            $data['pid'] = intval($data['pid'] ?? input('pid', '0'));
            $this->cates = PluginWemallGoodsCate::pdata($this->maxLevel, $data, [
                'id' => '0', 'pid' => '-1', 'name' => '顶部分类',
            ]);
        }
    }
}
