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
            $query->where(['status' => intval($this->type === 'index'), 'deleted' => 0]);
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
