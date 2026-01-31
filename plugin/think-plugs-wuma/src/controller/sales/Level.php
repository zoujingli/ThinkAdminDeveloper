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

namespace plugin\wuma\controller\sales;

use plugin\wuma\model\PluginWumaSalesUserLevel as UserLevel;
use think\admin\Controller;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 代理等级管理.
 * @class Level
 */
class Level extends Controller
{
    /**
     * 代理等级管理.
     * @menu true
     * @auth true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        UserLevel::mQuery()->layTable(function () {
            $this->title = '代理等级管理';
        });
    }

    /**
     * 添加代理等级.
     * @auth true
     * @throws DbException
     */
    public function add()
    {
        $this->max = UserLevel::stepMax() + 1;
        UserLevel::mForm('form');
    }

    /**
     * 编辑代理等级.
     * @auth true
     * @throws DbException
     */
    public function edit()
    {
        $this->max = UserLevel::stepMax();
        UserLevel::mForm('form');
    }

    /**
     * 表单结果处理.
     * @throws DbException
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
            'status.in:0,1' => '修改状态不在范围！',
        ]));
    }

    /**
     * 删除代理等级.
     * @auth true
     */
    public function remove()
    {
        UserLevel::mDelete();
    }

    /**
     * 表单数据处理.
     * @throws DbException
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
     * 状态变更处理.
     * @throws DbException
     */
    protected function _save_result()
    {
        $this->_form_result(true);
    }

    /**
     * 删除结果处理.
     * @throws DbException
     */
    protected function _delete_result()
    {
        $this->_form_result(true);
    }
}
