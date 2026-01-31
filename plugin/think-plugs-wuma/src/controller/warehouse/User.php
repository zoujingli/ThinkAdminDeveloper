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

namespace plugin\wuma\controller\warehouse;

use plugin\wuma\model\PluginWumaWarehouseUser;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 仓库用户管理.
 * @class User
 */
class User extends Controller
{
    /**
     * 仓库用户管理.
     * @menu true
     * @auth true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $this->type = $this->get['type'] ?? 'index';
        PluginWumaWarehouseUser::mQuery()->layTable(function () {
            $this->title = '仓库用户管理';
        }, function (QueryHelper $query) {
            $query->like('username,nickname')->dateBetween('login_time,create_time');
            $query->where(['deleted' => 0, 'status' => intval($this->type === 'index')]);
        });
    }

    /**
     * 添加仓库用户.
     * @auth true
     */
    public function add()
    {
        PluginWumaWarehouseUser::mForm('form');
    }

    /**
     * 编辑仓库用户.
     * @auth true
     */
    public function edit()
    {
        PluginWumaWarehouseUser::mForm('form');
    }

    /**
     * 修改仓库用户状态
     * @auth true
     */
    public function state()
    {
        PluginWumaWarehouseUser::mSave();
    }

    /**
     * 修改用户密码
     * @auth true
     */
    public function pass()
    {
        if ($this->request->isGet()) {
            PluginWumaWarehouseUser::mForm('pass');
        } else {
            $data = $this->_vali([
                'id.require' => '用户ID不能为空！',
                'password.require' => '登录密码不能为空！',
                'repassword.require' => '重复密码不能为空！',
                'repassword.confirm:password' => '两次输入的密码不一致！',
            ]);
            unset($data['repassword']);
            $data['password'] = md5($data['password']);
            if (PluginWumaWarehouseUser::mUpdate($data)) {
                $this->success('密码修改成功！', '');
            } else {
                $this->error('密码修改失败！');
            }
        }
    }

    /**
     * 表单数据处理.
     * @throws DbException
     * @throws DbException
     */
    protected function _form_filter(array &$data)
    {
        if ($this->request->isPost()) {
            if (isset($data['id']) && $data['id'] > 0) {
                unset($data['username']);
            } else {
                $map = ['username' => $data['username']];
                if (PluginWumaWarehouseUser::mk()->where($map)->count() > 0) {
                    $this->error('账号已经存在！');
                }
                $data['password'] = md5($data['username']);
            }
        }
    }
}
