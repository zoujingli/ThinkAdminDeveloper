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
use plugin\system\service\AuthService;
use plugin\system\service\IndexService;
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
        $context = IndexService::buildShellContext($this->app->isDebug());
        if ($context['redirectUrl'] !== '') {
            $this->redirect(strval($context['redirectUrl']));
            return;
        }

        foreach ($context as $name => $value) {
            $this->{$name} = $value;
        }
        $this->fetch();
    }

    /**
     * 后台主题切换.
     * @login true
     * @throws Exception
     */
    public function theme()
    {
        $context = IndexService::buildThemeContext(Config::themeCatalog);
        $builder = $context['scene'] === 'config'
            ? ThemeBuilder::buildConfigThemeForm($context)
            : ThemeBuilder::buildUserThemeForm($context);

        if ($this->request->isGet()) {
            $this->respondWithFormBuilder($builder, $context, IndexService::buildThemeFormData($context));
            return;
        }

        try {
            $data = $this->_vali(['site_theme.require' => lang('主题名称不能为空！')]);
            IndexService::saveTheme(strval($data['site_theme'] ?? ''), Config::themeCatalog);
            $this->success(lang('主题配置保存成功！'));
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 修改用户资料.
     * @login true
     */
    public function info()
    {
        $id = intval($this->request->param('id', 0));
        try {
            IndexService::assertCurrentUser($id, '只能修改自己的资料！');
            $context = UserService::buildInfoContext($id);
            $builder = UserBuilder::buildInfoForm($context);

            if ($this->request->isGet()) {
                $this->respondWithFormBuilder($builder, $context, UserService::loadFormData($context));
            }

            $data = UserService::prepareInfoData($builder->validate(), $context);
            UserService::saveFormData($data);
            $this->success(lang('用户资料修改成功！'), 'javascript:location.reload()');
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
        try {
            IndexService::assertCurrentUser($id, '禁止修改他人密码！');
            $context = ['withOldPassword' => true];
            $builder = UserBuilder::buildPassForm($context);
            if ($this->request->isGet()) {
                $this->verify = true;
                $this->respondWithFormBuilder($builder, $context, UserService::loadPassUser($id));
                return;
            }

            IndexService::changeOwnPassword($id, $builder->validate());
            $this->success(lang('密码修改成功，下次请使用新密码登录！'), '');
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }
}
