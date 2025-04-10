<?php

// +----------------------------------------------------------------------
// | Wuma Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
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

namespace plugin\wuma\controller\scaner;

use plugin\wuma\model\PluginWumaSourceQuery;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

/**
 * 扫码查询管理
 * @class Query
 * @package  plugin\wuma\controller\scan
 */
class Query extends Controller
{
    /**
     * 扫码查询管理
     * @menu true
     * @auth true
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        PluginWumaSourceQuery::mQuery()->layTable(function () {
            $this->title = '扫码查询管理';
        }, static function (QueryHelper $query) {
            $query->like('prov|city|area#area,addr,encode')->dateBetween('create_time');
        });
    }
}