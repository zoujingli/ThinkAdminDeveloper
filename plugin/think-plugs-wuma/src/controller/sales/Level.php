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

namespace plugin\wuma\controller\sales;

use plugin\wuma\model\PluginWumaSalesUserLevel as UserLevel;
use think\admin\Controller;

/**
 * 代理等级管理
 * @class Level
 * @package plugin\wuma\controller\sales
 */
class Level extends Controller
{
    /**
     * 代理等级管理
     * @menu true
     * @auth true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        UserLevel::mQuery()->layTable(function () {
            $this->title = '代理等级管理';
        });
    }

    /**
     * 添加代理等级
     * @auth true
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function add()
    {
        $this->max = UserLevel::stepMax() + 1;
        UserLevel::mForm('form');
    }

    /**
     * 编辑代理等级
     * @auth true
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function edit()
    {
        $this->max = UserLevel::stepMax();
        UserLevel::mForm('form');
    }

    /**
     * 表单数据处理
     * @param array $vo
     * @throws \think\db\exception\DbException
     */
    protected function _form_filter(array &$vo)
    {
        if ($this->request->isGet()) {
            $vo['number'] = $vo['number'] ?? UserLevel::stepMax();
        } else {
            $vo['utime'] = time();
        }
    }

    /**
     * 表单结果处理
     * @param boolean $state
     * @throws \think\db\exception\DbException
     */
    public function _form_result(bool $state)
    {
        $state && UserLevel::stepSync();
    }

    /**
     * 修改等级状态
     * @auth true
     */
    public function state()
    {
        UserLevel::mSave($this->_vali([
            'status.require' => '修改状态不能为空！',
            'status.in:0,1'  => '修改状态不在范围！',
        ]));
    }

    /**
     * 删除代理等级
     * @auth true
     */
    public function remove()
    {
        UserLevel::mDelete();
    }

    /**
     * 状态变更处理
     * @throws \think\db\exception\DbException
     */
    protected function _save_result()
    {
        $this->_form_result(true);
    }

    /**
     * 删除结果处理
     * @throws \think\db\exception\DbException
     */
    protected function _delete_result()
    {
        $this->_form_result(true);
    }
}