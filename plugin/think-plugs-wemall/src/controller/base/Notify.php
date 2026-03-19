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

namespace plugin\wemall\controller\base;

use plugin\wemall\model\PluginWemallConfigLevel;
use plugin\wemall\model\PluginWemallConfigNotify;
use think\admin\Controller;
use think\admin\extend\CodeToolkit;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 系统通知管理.
 * @class Notify
 */
class Notify extends Controller
{
    /**
     * 系统通知管理.
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $this->type = $this->get['type'] ?? 'index';
        PluginWemallConfigNotify::mQuery()->layTable(function () {
            $this->title = '系统通知管理';
        }, function (QueryHelper $query) {
            $query->like('name,code')->equal('status')->dateBetween('create_time');
            $query->where(['status' => intval($this->type === 'index')]);
        });
    }

    /**
     * 添加系统通知.
     * @auth true
     */
    public function add()
    {
        $this->title = '添加系统通知';
        PluginWemallConfigNotify::mForm('form');
    }

    /**
     * 编辑系统通知.
     * @auth true
     */
    public function edit()
    {
        $this->title = '编辑系统通知';
        PluginWemallConfigNotify::mForm('form');
    }

    /**
     * 修改通知状态
     * @auth true
     */
    public function state()
    {
        PluginWemallConfigNotify::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除系统通知.
     * @auth true
     */
    public function remove()
    {
        PluginWemallConfigNotify::mDelete();
    }

    /**
     * 表单数据处理.
     */
    protected function _form_filter(array &$data)
    {
        if (empty($data['code'])) {
            $data['code'] = CodeToolkit::uniqidNumber(16, 'N');
        }
        if ($this->request->isGet()) {
            $this->levels = PluginWemallConfigLevel::items();
            array_unshift($this->levels, ['name' => '全部', 'number' => '-']);
        } else {
            $data['levels'] = arr2str($data['levels'] ?? []);
        }
    }

    /**
     * 表单结果处理.
     */
    protected function _form_result(bool $result)
    {
        if ($result) {
            $this->success('通知保存成功！', 'javascript:history.back()');
        } else {
            $this->error('通知保存失败！');
        }
    }
}
