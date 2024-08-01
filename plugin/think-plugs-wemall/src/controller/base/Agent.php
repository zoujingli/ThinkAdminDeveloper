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

namespace plugin\wemall\controller\base;

use plugin\wemall\model\PluginWemallConfigAgent;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

/**
 * 代理等级管理
 * @class Agent
 * @package plugin\wemall\controller\base
 */
class Agent extends Controller
{
    /**
     * 代理等级管理
     * @auth true
     * @menu true
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        PluginWemallConfigAgent::mQuery()->layTable(function () {
            $this->title = '代理等级管理';
        }, static function (QueryHelper $query) {
            $query->like('name')->equal('status')->dateBetween('create_time');
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
        $this->max = PluginWemallConfigAgent::maxNumber() + 1;
        PluginWemallConfigAgent::mForm('form');
    }

    /**
     * 编辑代理等级
     * @auth true
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function edit()
    {
        $this->max = PluginWemallConfigAgent::maxNumber();
        PluginWemallConfigAgent::mForm('form');
    }

    /**
     * 表单数据处理
     * @param array $vo
     * @throws \think\db\exception\DbException
     */
    protected function _form_filter(array &$vo)
    {
        if ($this->request->isGet()) {
            if (empty($vo['extra'])) $vo['extra'] = [];
            $vo['number'] = $vo['number'] ?? PluginWemallConfigAgent::maxNumber();
        } else {
            $count = 0;
            foreach ($vo['extra'] as $k => $v) if (is_numeric(stripos($k, '_number'))) {
                $ats = explode('_', $k);
                $key = "{$ats[0]}_{$ats[1]}_status";
                if ($vo['number'] > 0) {
                    isset($vo['extra'][$key]) || $vo['extra'][$key] = 0;
                    floatval($v) > 0 ? $count += 1 : ($vo['extra'][$key] = 0);
                } else {
                    $vo['extra'][$k] = 0;
                    $vo['extra'][$key] = 0;
                }
            }
            if (empty($count) && $vo['number'] > 0) {
                $this->error('升级条件不能为空！');
            }
        }
    }

    /**
     * 表单结果处理
     * @param boolean $state
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function _form_result(bool $state)
    {
        if ($state) {
            $isasc = input('old_number', 0) <= input('number', 0);
            $order = $isasc ? 'number asc,utime asc' : 'number asc,utime desc';
            foreach (PluginWemallConfigAgent::mk()->order($order)->select() as $number => $upgrade) {
                $upgrade->save(['number' => $number]);
            }
        }
    }

    /**
     * 修改等级状态
     * @auth true
     */
    public function state()
    {
        PluginWemallConfigAgent::mSave();
    }

    /**
     * 删除代理等级
     * @auth true
     */
    public function remove()
    {
        PluginWemallConfigAgent::mDelete();
    }

    /**
     * 状态变更处理
     * @auth true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function _save_result()
    {
        $this->_form_result(true);
    }

    /**
     * 删除结果处理
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function _delete_result()
    {
        $this->_form_result(true);
    }
}