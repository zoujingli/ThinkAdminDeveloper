<?php

// +----------------------------------------------------------------------
// | Wuma Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
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

namespace plugin\wuma\controller\warehouse;

use plugin\wuma\model\PluginWumaWarehouseReplace;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

/**
 * 毁损标签替换
 * @class Replace
 * @package plugin\wuma\controller\warehouse
 */
class Replace extends Controller
{
    /**
     * 商品码替换管理
     * @menu true
     * @auth true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        PluginWumaWarehouseReplace::mQuery()->layTable(function () {
            $this->title = '商品码替换管理';
        }, static function (QueryHelper $query) {
            $query->equal('type,lock,source,target')->dateBetween('create_time');
        });
    }
}