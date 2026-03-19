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

namespace plugin\wemall\controller\user\rebate;

use plugin\wemall\model\PluginWemallConfigAgent;
use plugin\wemall\model\PluginWemallConfigRebate;
use plugin\wemall\service\UserRebate;
use think\admin\Controller;
use think\admin\extend\CodeToolkit;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 返佣规则配置.
 * @class Config
 */
class Config extends Controller
{
    /**
     * 返佣规则配置.
     * @auth true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        PluginWemallConfigRebate::mQuery()->layTable(function () {
            $this->title = '返佣规则配置';
            $this->prizes = UserRebate::prizes;
        }, function (QueryHelper $query) {
            $query->equal('type#mtype')->like('name')->dateBetween('create_time');
        });
    }

    /**
     * 添加返佣规则.
     * @auth true
     */
    public function add()
    {
        $this->title = '添加返佣规则';
        PluginWemallConfigRebate::mForm('form');
    }

    /**
     * 编辑返佣规则.
     * @auth true
     */
    public function edit()
    {
        $this->title = '编辑返佣规则';
        PluginWemallConfigRebate::mForm('form');
    }

    /**
     * 修改规则状态
     * @auth true
     */
    public function state()
    {
        PluginWemallConfigRebate::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除返佣规则.
     * @auth true
     */
    public function remove()
    {
        PluginWemallConfigRebate::mDelete();
    }

    /**
     * 表单数据处理.
     */
    protected function _form_filter(array &$data)
    {
        if (empty($data['code'])) {
            $data['code'] = CodeToolkit::uniqidNumber(16, 'R');
        }
        if ($this->request->isGet()) {
            $this->prizes = UserRebate::prizes;
            $this->levels = PluginWemallConfigAgent::items();
            array_unshift($this->levels, ['name' => '-> 无 <-', 'number' => -2], ['name' => '-> 任意 <-', 'number' => -1]);
        } else {
            $data['path'] = arr2str([$data['p3_level'], $data['p2_level'], $data['p1_level'], $data['p0_level']]);
        }
    }
}
