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

namespace plugin\system\controller;

use plugin\system\builder\ThemeBuilder;
use plugin\system\builder\UserBuilder;
use plugin\system\model\SystemUser;
use plugin\system\service\AuthService;
use plugin\system\service\MenuService;
use plugin\system\service\UserService;
use think\admin\Controller;
use think\admin\Exception;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\HttpResponseException;

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
        AuthService::apply($this->app->isDebug());
        $this->menus = MenuService::getTree();
        $this->login = AuthService::isLogin();
        if (empty($this->menus) && empty($this->login)) {
            $this->redirect(sysuri('system/login/index'));
        } else {
            $this->title = '系统管理后台';
            $this->super = AuthService::isSuper();
            $this->theme = AuthService::getUserTheme();
            $this->tokenValueJson = json_encode(AuthService::buildToken(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
        $scene = strval($this->request->param('scene', 'user'));
        $theme = strval($this->request->param('value', ''));
        $themes = Config::themeCatalog;
        if (!isset($themes[$theme])) {
            $theme = $scene === 'config' ? strval(sysdata('system.site.theme') ?: 'default') : AuthService::getUserTheme();
        }
        if (!isset($themes[$theme])) {
            $theme = 'default';
        }

        $context = [
            'scene' => $scene === 'config' ? 'config' : 'user',
            'picker' => strval($this->request->param('picker', '')),
            'theme' => $theme,
            'themes' => $themes,
        ];
        $builder = $context['scene'] === 'config'
            ? ThemeBuilder::buildConfigThemeForm($context)
            : ThemeBuilder::buildUserThemeForm($context);

        if ($this->request->isGet()) {
            $this->respondWithFormBuilder($builder, $context, ['site_theme' => $theme]);
            return;
        }

        $data = $this->_vali(['site_theme.require' => '主题名称不能为空！']);
        if (AuthService::setUserTheme($data['site_theme'])) {
            $this->success('主题配置保存成功！');
        } else {
            $this->error('主题配置保存失败！');
        }
    }

    /**
     * 修改用户资料.
     * @login true
     */
    public function info()
    {
        $id = intval($this->request->param('id', 0));
        if (AuthService::getUserId() !== $id) {
            $this->error('只能修改自己的资料！');
        }

        try {
            $context = UserService::buildInfoContext($id);
            $builder = UserBuilder::buildInfoForm($context);

            if ($this->request->isGet()) {
                $this->respondWithFormBuilder($builder, $context, UserService::loadFormData($context));
            }

            $data = UserService::prepareInfoData($builder->validate(), $context);
            UserService::saveFormData($data);
            $this->success('用户资料修改成功！', 'javascript:location.reload()');
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (Exception|\Throwable $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 修改当前用户密码
     * @login true
     * @throws Exception
     */
    public function pass()
    {
        $id = intval($this->request->param('id', 0));
        if (AuthService::getUserId() !== $id) {
            $this->error('禁止修改他人密码！');
        }

        $context = ['withOldPassword' => true];
        $builder = UserBuilder::buildPassForm($context);
        if ($this->request->isGet()) {
            $this->verify = true;
            $this->respondWithFormBuilder($builder, $context, UserService::loadPassUser($id));
        } else {
            $data = $builder->validate();
            $user = SystemUser::mk()->findOrEmpty($id);
            if ($user->isEmpty()) {
                $this->error('用户不存在！');
            }
            if (!UserService::verifyPassword($data['oldpassword'], strval($user['password']))) {
                $this->error('旧密码验证失败，请重新输入！');
            }
            if ($user->save(['password' => UserService::hashPassword($data['password'])])) {
                sysoplog('系统用户管理', "修改用户[{$user['id']}]密码成功");
                $this->app->event->trigger('PluginAdminChangePassword', [
                    'uuid' => intval($user['id']), 'pass' => $data['password'],
                ]);
                $this->success('密码修改成功，下次请使用新密码登录！', '');
            }

            $this->error('密码修改失败，请稍候再试！');
        }
    }
}
