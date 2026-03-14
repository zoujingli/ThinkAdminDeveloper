<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
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

namespace plugin\system\controller;

use plugin\system\model\SystemUser;
use plugin\system\service\MenuService;
use plugin\system\service\SystemAuthService;
use think\admin\Controller;
use think\admin\Exception;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 后台界面入口.
 * @class Index
 */
class Index extends Controller
{
    /**
     * 显示后台首页.
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        /* ! 根据运行模式刷新权限 */
        SystemAuthService::apply($this->app->isDebug());
        /* ! 读取当前用户权限菜单树 */
        $this->menus = MenuService::getTree();
        /* ! 判断当前用户的登录状态 */
        $this->login = SystemAuthService::isLogin();
        /* ! 菜单为空且未登录跳转到登录页 */
        if (empty($this->menus) && empty($this->login)) {
            $this->redirect(sysuri('system/login/index'));
        } else {
            $this->title = '系统管理后台';
            $this->super = SystemAuthService::isSuper();
            $this->theme = SystemAuthService::getUserTheme();
            $this->tokenValueJson = json_encode(SystemAuthService::buildToken(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $this->fetch();
        }
    }

    /**
     * 后台主题切换.
     * @login true
     * @throws Exception
     */
    public function theme()
    {
        if ($this->request->isGet()) {
            $this->theme = SystemAuthService::getUserTheme();
            $this->themes = Config::themes;
            $this->fetch();
        } else {
            $data = $this->_vali(['site_theme.require' => '主题名称不能为空！']);
            if (SystemAuthService::setUserTheme($data['site_theme'])) {
                $this->success('主题配置保存成功！');
            } else {
                $this->error('主题配置保存失败！');
            }
        }
    }

    /**
     * 修改用户资料.
     * @login true
     */
    public function info()
    {
        $id = $this->request->param('id');
        if (SystemAuthService::getUserId() == intval($id)) {
            SystemUser::mForm('user/form', 'id', [], ['id' => $id]);
        } else {
            $this->error('只能修改自己的资料！');
        }
    }

    /**
     * 修改当前用户密码
     * @login true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function pass()
    {
        $id = $this->request->param('id');
        if (SystemAuthService::getUserId() !== intval($id)) {
            $this->error('禁止修改他人密码！');
        }
        if ($this->app->request->isGet()) {
            $this->verify = true;
            SystemUser::mForm('user/pass', 'id', [], ['id' => $id]);
        } else {
            $data = $this->_vali([
                'password.require' => '登录密码不能为空！',
                'repassword.require' => '重复密码不能为空！',
                'oldpassword.require' => '旧的密码不能为空！',
                'password.confirm:repassword' => '两次输入的密码不一致！',
            ]);
            $user = SystemUser::mk()->find($id);
            if (empty($user)) {
                $this->error('用户不存在！');
            }
            if (md5($data['oldpassword']) !== $user['password']) {
                $this->error('旧密码验证失败，请重新输入！');
            }
            if ($user->save(['password' => md5($data['password'])])) {
                sysoplog('系统用户管理', "修改用户[{$user['id']}]密码成功");
                // 修改密码同步事件处理
                $this->app->event->trigger('PluginAdminChangePassword', [
                    'uuid' => intval($user['id']), 'pass' => $data['password'],
                ]);
                $this->success('密码修改成功，下次请使用新密码登录！', '');
            } else {
                $this->error('密码修改失败，请稍候再试！');
            }
        }
    }

    /**
     * 资料修改表单处理.
     */
    protected function _info_form_filter(array &$data)
    {
        if ($this->request->isPost()) {
            unset($data['username'], $data['authorize']);
        }
    }

    /**
     * 资料修改结果处理.
     */
    protected function _info_form_result(bool $status)
    {
        if ($status) {
            $this->success('用户资料修改成功！', 'javascript:location.reload()');
        }
    }
}
