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

use plugin\wemall\model\PluginWemallGoodsMark;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

/**
 * 商品标签管理
 * @class Mark
 * @package plugin\wemall\controller\shop\goods
 */
class Mark extends Controller
{
    /**
     * 商品标签管理
     * @auth true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        PluginWemallGoodsMark::mQuery($this->get)->layTable(function () {
            $this->title = '商品标签管理';
        }, static function (QueryHelper $query) {
            $query->like('name')->equal('status')->dateBetween('create_time');
        });
    }

    /**
     * 添加商品标签
     * @auth true
     */
    public function add()
    {
        PluginWemallGoodsMark::mForm('form');
    }

    /**
     * 编辑商品标签
     * @auth true
     */
    public function edit()
    {
        PluginWemallGoodsMark::mForm('form');
    }

    /**
     * 修改商品标签状态
     * @auth true
     */
    public function state()
    {
        PluginWemallGoodsMark::mSave();
    }

    /**
     * 删除商品标签
     * @auth true
     */
    public function remove()
    {
        PluginWemallGoodsMark::mDelete();
    }

    /**
     * 商品标签选择kkd
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