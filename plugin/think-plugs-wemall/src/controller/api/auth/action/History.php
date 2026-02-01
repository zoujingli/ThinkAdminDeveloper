<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
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

namespace plugin\wemall\controller\api\auth\action;

use plugin\wemall\controller\api\Auth;
use plugin\wemall\model\PluginWemallGoods;
use plugin\wemall\model\PluginWemallUserActionHistory;
use plugin\wemall\service\UserAction;
use think\admin\helper\QueryHelper;
use think\db\exception\DbException;
use think\db\Query;

/**
 * 用户足迹数据.
 * @class History
 */
class History extends Auth
{
    /**
     * 提交搜索记录.
     * @throws DbException
     */
    public function set()
    {
        $data = $this->_vali([
            'unid.value' => $this->unid,
            'gcode.require' => '商品不能为空！',
        ]);
        $map = ['code' => $data['gcode'], 'deleted' => 0];
        if (PluginWemallGoods::mk()->where($map)->findOrEmpty()->isExists()) {
            UserAction::set($this->unid, $data['gcode'], 'history');
            $this->success('添加成功！');
        } else {
            $this->error('添加失败！');
        }
    }

    /**
     * 获取我的访问记录.
     */
    public function get()
    {
        PluginWemallUserActionHistory::mQuery(null, function (QueryHelper $query) {
            // 搜索商品信息
            $db = PluginWemallGoods::mQuery()->like('name#keys');
            $query->whereRaw("gcode in {$db->field('code')->buildSql()}");
            // 关联商品信息
            $query->order('sort desc')->with(['goods' => function (Query $query) {
                $query->field('code,name,cover,stock_sales,stock_virtual,price_selling,status,deleted');
            }]);
            $query->where(['unid' => $this->unid])->like('gcode');
            [$page, $limit] = [intval(input('page', 1)), intval(input('limit', 10))];
            $this->success('我的访问记录！', $query->page($page, false, false, $limit));
        });
    }

    /**
     * 删除收藏记录.
     * @throws DbException
     */
    public function del()
    {
        $data = $this->_vali(['gcode.require' => '编号不能为空！']);
        UserAction::del($this->unid, $data['gcode'], 'history');
        $this->success('删除记录成功！');
    }

    /**
     * 清空访问记录.
     * @throws DbException
     */
    public function clear()
    {
        UserAction::clear($this->unid, 'history');
        $this->success('清理记录成功！');
    }
}
