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

namespace plugin\account\controller;

use plugin\account\model\PluginAccountUser;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 用户账号管理.
 * @class Master
 */
class Master extends Controller
{
    /**
     * 用户账号管理.
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $this->type = $this->get['type'] ?? 'index';
        PluginAccountUser::mQuery()->layTable(function () {
            $this->title = '用户账号管理';
        }, function (QueryHelper $query) {
            $query->where(['status' => intval($this->type === 'index')]);
            $query->like('code,phone,email,username,nickname')->dateBetween('create_time');
        });
    }

    /**
     * 修改主账号状态
     * @auth true
     */
    public function state()
    {
        PluginAccountUser::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除主账号.
     * @auth true
     */
    public function remove()
    {
        PluginAccountUser::mDelete();
    }
}
