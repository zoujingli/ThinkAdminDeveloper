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

namespace plugin\wemall\controller\api\auth\action;

use plugin\wemall\controller\api\Auth;
use plugin\wemall\model\PluginWemallUserActionSearch;
use think\admin\helper\QueryHelper;
use think\db\exception\DbException;

/**
 * 用户搜索数据.
 * @class search
 */
class Search extends Auth
{
    /**
     * 提交搜索记录.
     * @throws DbException
     */
    public function set()
    {
        $data = $this->_vali(['keys.default' => '', 'unid.value' => $this->unid]);
        if (empty($data['keys'])) {
            $this->success('无需提交！');
        }
        // 统计 30 天内搜索次数
        $times = PluginWemallUserActionSearch::mk()->where(['keys' => $data['keys']])->whereTime('update_time', '-30 days')->count();
        PluginWemallUserActionSearch::mk()->where($data)->findOrEmpty()->save(array_merge($data, ['sort' => time(), 'times' => $times + 1]));
        $this->success('更新搜索词成功！');
    }

    /**
     * 获取我的搜索记录.
     */
    public function get()
    {
        PluginWemallUserActionSearch::mQuery(null, function (QueryHelper $query) {
            $query->where(['unid' => $this->unid]);
            $query->like('keys');
            $query->order('sort desc');
            [$page, $limit] = [intval(input('page', 1)), intval(input('limit', 10))];
            $this->success('我的搜索记录！', $query->page($page, false, false, $limit));
        });
    }
}
