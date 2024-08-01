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

namespace plugin\wemall\controller\shop\goods;

use plugin\wemall\model\PluginWemallGoodsCate;
use think\admin\Controller;
use think\admin\extend\DataExtend;
use think\admin\helper\QueryHelper;

/**
 * 商品分类管理
 * @class Cate
 * @package plugin\wemall\controller\shop\goods
 */
class Cate extends Controller
{
    /**
     * 最大级别
     * @var integer
     */
    protected $maxLevel = 3;

    /**
     * 商品分类管理
     * @auth true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        PluginWemallGoodsCate::mQuery($this->get)->layTable(function () {
            $this->title = "商品分类管理";
        }, static function (QueryHelper $query) {
            $query->where(['deleted' => 0]);
            $query->like('name')->equal('status')->dateBetween('create_time');
        });
    }

    /**
     * 列表数据处理
     * @param array $data
     */
    protected function _page_filter(array &$data)
    {
        $data = DataExtend::arr2table($data);
    }

    /**
     * 添加商品分类
     * @auth true
     */
    public function add()
    {
        PluginWemallGoodsCate::mForm('form');
    }

    /**
     * 编辑商品分类
     * @auth true
     */
    public function edit()
    {
        PluginWemallGoodsCate::mForm('form');
    }

    /**
     * 表单数据处理
     * @param array $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
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

    /**
     * 修改商品分类状态
     * @auth true
     */
    public function state()
    {
        PluginWemallGoodsCate::mSave($this->_vali([
            'status.in:0,1'  => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除商品分类
     * @auth true
     */
    public function remove()
    {
        PluginWemallGoodsCate::mDelete();
    }

    /**
     * 商品分类选择器
     * @login true
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function select()
    {
        $this->get['status'] = 1;
        $this->get['deleted'] = 0;
        $this->index();
    }
}