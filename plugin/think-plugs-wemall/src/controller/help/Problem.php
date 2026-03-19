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

namespace plugin\wemall\controller\help;

use plugin\wemall\model\PluginWemallHelpProblem;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 常见问题管理
 * class Problem.
 */
class Problem extends Controller
{
    /**
     * 常见问题管理.
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $this->type = $this->get['type'] ?? 'index';
        PluginWemallHelpProblem::mQuery($this->get)->layTable(function () {
            $this->title = '常见问题管理';
        }, function (QueryHelper $query) {
            $query->like('name,content')->dateBetween('create_time');
            $query->where(['status' => intval($this->type === 'index')]);
        });
    }

    /**
     * 添加常见问题.
     * @auth true
     */
    public function add()
    {
        $this->title = '添加常见问题';
        PluginWemallHelpProblem::mForm('form');
    }

    /**
     * 编辑常见问题.
     * @auth true
     */
    public function edit()
    {
        $this->title = '编辑常见问题';
        PluginWemallHelpProblem::mForm('form');
    }

    /**
     * 修改问题状态
     * @auth true
     */
    public function state()
    {
        PluginWemallHelpProblem::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除常见问题.
     * @auth true
     */
    public function remove()
    {
        PluginWemallHelpProblem::mDelete();
    }

    /**
     * 选择常见问题.
     * @login true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function select()
    {
        $this->get['status'] = 1;
        $this->index();
    }

    /**
     * 表单结果处理.
     */
    protected function _form_result(bool $state)
    {
        if ($state) {
            $this->success('修改成功!', 'javascript:history.back();');
        }
    }
}
