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

namespace plugin\wemall\controller\shop;

use plugin\account\model\PluginAccountUser;
use plugin\wemall\model\PluginWemallGoods;
use plugin\wemall\model\PluginWemallUserActionComment;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

/**
 * 商品评论管理
 * @class Reply
 * @package plugin\wemall\controller\shop
 */
class Reply extends Controller
{
    /**
     * 商品评论管理
     * @auth true
     * @menu true
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->type = $this->request->get('type', 'index');
        PluginWemallUserActionComment::mQuery()->layTable(function () {
            $this->title = '商品评论管理';
        }, function (QueryHelper $query) {
            // 用户查询
            $db = PluginAccountUser::mQuery()->like('phone|nickname#user_keys')->db();
            if ($db->getOptions('where')) $query->whereRaw("unid in {$db->field('id')->buildSql()}");
            // 商品查询
            $db = PluginWemallGoods::mQuery()->like('code|name#goods_keys')->db();
            if ($db->getOptions('where')) $query->whereRaw("gcode in {$db->field('code')->buildSql()}");
            // 数据过滤
            $query->like('order_no')->where(['status' => intval($this->type === 'index'), 'deleted' => 0]);
            $query->with(['bindUser', 'bindGoods'])->dateBetween('create_time');
        });
    }

    /**
     * 修改评论内容
     * @auth true
     * @return void
     */
    public function edit()
    {
        PluginWemallUserActionComment::mQuery()->with(['user', 'goods', 'orderinfo'])->mForm('form');
    }

    /**
     * 修改评论状态
     * @auth true
     */
    public function state()
    {
        PluginWemallUserActionComment::mSave($this->_vali([
            'status.in:0,1'  => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }
}