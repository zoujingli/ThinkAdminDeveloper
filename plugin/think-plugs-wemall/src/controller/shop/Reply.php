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

namespace plugin\wemall\controller\shop;

use plugin\account\model\PluginAccountUser;
use plugin\wemall\model\PluginWemallGoods;
use plugin\wemall\model\PluginWemallUserActionComment;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 商品评论管理.
 * @class Reply
 */
class Reply extends Controller
{
    /**
     * 商品评论管理.
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $this->type = $this->request->get('type', 'index');
        PluginWemallUserActionComment::mQuery()->layTable(function () {
            $this->title = '商品评论管理';
        }, function (QueryHelper $query) {
            // 用户查询
            $db = PluginAccountUser::mQuery()->like('phone|nickname#user_keys')->db();
            if ($db->getOptions('where')) {
                $query->whereRaw("unid in {$db->field('id')->buildSql()}");
            }
            // 商品查询
            $db = PluginWemallGoods::mQuery()->like('code|name#goods_keys')->db();
            if ($db->getOptions('where')) {
                $query->whereRaw("gcode in {$db->field('code')->buildSql()}");
            }
            // 数据过滤
            $query->like('order_no')->where(['status' => intval($this->type === 'index'), 'deleted' => 0]);
            $query->with(['bindUser', 'bindGoods'])->dateBetween('create_time');
        });
    }

    /**
     * 修改评论内容.
     * @auth true
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
            'status.in:0,1' => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }
}
