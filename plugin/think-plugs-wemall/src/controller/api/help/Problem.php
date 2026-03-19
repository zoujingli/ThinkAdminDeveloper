<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdminDeveloper
 * +----------------------------------------------------------------------
 * | Copyright (c) 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | Official Website: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | Licensed: https://mit-license.org
 * | Disclaimer: https://thinkadmin.top/disclaimer
 * | Vip Rights: https://thinkadmin.top/vip-introduce
 * +----------------------------------------------------------------------
 * | Gitee Repository: https://gitee.com/zoujingli/ThinkAdmin
 * | Github Repository: https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace plugin\wemall\controller\api\help;

use plugin\wemall\model\PluginWemallHelpProblem;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

/**
 * 常见问题数据接口.
 * @class Problem
 */
class Problem extends Controller
{
    /**
     * 获取反馈意见
     */
    public function get()
    {
        PluginWemallHelpProblem::mQuery(null, function (QueryHelper $query) {
            $query->like('name')->equal('id');
            $this->success('获取反馈意见', $query->order('sort desc,id desc')->page(true, false, false, 10));
        });
    }
}
