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

namespace plugin\wemall\controller\api\help;

use plugin\wemall\model\PluginWemallHelpProblem;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

/**
 * 常见问题数据接口
 * @class Problem
 * @package app\data\controller\api\auth
 */
class Problem extends Controller
{

    /**
     * 获取反馈意见
     * @return void
     */
    public function get()
    {
        PluginWemallHelpProblem::mQuery(null, function (QueryHelper $query) {
            $query->like('name')->equal('id');
            $this->success('获取反馈意见', $query->order('sort desc,id desc')->page(true, false, false, 10));
        });
    }
}