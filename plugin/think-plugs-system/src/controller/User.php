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

use plugin\system\builder\UserBuilder;
use plugin\system\model\SystemUser;
use plugin\system\service\UserService;
use think\admin\Controller;
use think\admin\Exception;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\HttpResponseException;

class User extends Controller
{
    /**
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $context = UserService::buildIndexContext();
        SystemUser::mQuery()->layTable(function () use ($context) {
            $this->respondWithPageBuilder(UserBuilder::buildIndexPage($context), $context);
        }, static function (QueryHelper $query) use ($context) {
            UserService::applyIndexQuery($query, $context);
        });
    }

    /**
     * @auth true
     */
    public function add()
    {
        $this->handleForm('add');
    }

    /**
     * @auth true
     */
    public function edit()
    {
        $this->handleForm('edit');
    }

    /**
     * @auth true
     */
    public function pass()
    {
        $context = ['withOldPassword' => false];
        $builder = UserBuilder::buildPassForm($context);

        if ($this->request->isGet()) {
            $this->verify = false;
            $this->respondWithFormBuilder($builder, $context, UserService::loadPassUser(intval($this->request->param('id', 0))));
        } else {
            $data = $builder->validate();
            $data['id'] = intval($this->request->post('id', 0));
            if ($data['id'] < 1) {
                $this->error('用户ID不能为空！');
            }

            $user = SystemUser::mk()->findOrEmpty($data['id']);
            if ($user->isExists() && $user->save(['password' => UserService::hashPassword($data['password'])])) {
                $this->app->event->trigger('PluginAdminChangePassword', [
                    'uuid' => $data['id'], 'pass' => $data['password'],
                ]);
                sysoplog('系统用户管理', "修改用户[{$data['id']}]密码成功");
                $this->success('密码修改成功，请使用新密码登录！', '');
            }

            $this->error('密码修改失败，请稍候再试！');
        }
    }

    /**
     * @auth true
     */
    public function state()
    {
        $this->_checkInput();
        SystemUser::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * @auth true
     */
    public function remove()
    {
        $this->_checkInput();
        SystemUser::mDelete();
    }

    private function _checkInput()
    {
        if (in_array('10000', str2arr(strval(input('id', ''))), true)) {
            $this->error('系统超级账号禁止删除！');
        }
    }

    private function handleForm(string $action): void
    {
        try {
            $context = UserService::buildFormContext($action);
            $builder = UserBuilder::buildForm($context);

            if ($this->request->isGet()) {
                $this->respondWithFormBuilder($builder, $context, UserService::loadFormData($context));
            }

            $data = UserService::prepareFormData($builder->validate(), $context);
            UserService::saveFormData($data);
            $this->success('数据保存成功！');
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (Exception|\Throwable $exception) {
            $this->error($exception->getMessage());
        }
    }
}
