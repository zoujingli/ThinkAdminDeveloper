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

namespace plugin\wuma\controller\warehouse;

use plugin\wuma\model\PluginWumaWarehouseUser;
use think\admin\Controller;
use think\admin\helper\FormBuilder;
use think\admin\helper\QueryHelper;
use think\admin\service\AppService;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class User extends Controller
{
    /**
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
            $query->where(['status' => intval($this->type === 'index')]);
        });
    }

    /**
     * @auth true
     */
    public function add()
    {
        $this->handleForm();
    }

    /**
     * @auth true
     */
    public function edit()
    {
        $this->handleForm();
    }

    /**
     * @auth true
     */
    public function state()
    {
        PluginWumaWarehouseUser::mSave();
    }

    /**
     * @auth true
     */
    public function pass()
    {
        $builder = $this->buildPassForm();
        if ($this->request->isGet()) {
            $builder->fetch(['vo' => $this->loadFormUser()]);
            return;
        }

        $data = $builder->validate();
        $data['id'] = intval($this->request->post('id', 0));
        if ($data['id'] < 1) {
            $this->error('用户ID不能为空！');
        }

        unset($data['repassword']);
        $data['password'] = $this->hashPassword($data['password']);
        if (PluginWumaWarehouseUser::mUpdate($data)) {
            $this->success('密码修改成功！', '');
        }

        $this->error('密码修改失败！');
    }

    /**
     * @throws DbException
     */
    protected function _form_filter(array &$data)
    {
        if (!$this->request->isPost()) {
            return;
        }

        if (isset($data['id']) && intval($data['id']) > 0) {
            unset($data['username']);
            return;
        }

        if (PluginWumaWarehouseUser::mk()->where(['username' => $data['username']])->count() > 0) {
            $this->error('账号已经存在！');
        }

        $data['password'] = $this->hashPassword($data['username']);
    }

    private function handleForm(): void
    {
        $vo = $this->loadFormUser();
        if ($this->request->isGet()) {
            $this->buildUserForm($vo)->fetch(['vo' => $vo]);
            return;
        }

        $id = intval($this->request->post('id', 0));
        $data = $this->buildUserForm(['id' => $id])->validate();
        if ($id > 0) {
            $data['id'] = $id;
        }

        if ($this->callback('_form_filter', $data) === false) {
            return;
        }

        $result = AppService::save(PluginWumaWarehouseUser::mk(), $data) !== false;
        if ($this->callback('_form_result', $result, $data) === false) {
            return;
        }

        $result ? $this->success('数据保存成功！') : $this->error('数据保存失败！');
    }

    private function buildUserForm(array $vo = []): FormBuilder
    {
        $builder = FormBuilder::mk();
        if (!empty($vo['id'])) {
            $builder->addTextInput('username', '登录账号', 'Username', false, '登录账号创建后不能再次修改。', null, [
                'readonly' => null,
                'class' => 'layui-disabled',
            ]);
        } else {
            $builder->addTextInput('username', '登录账号', 'Username', true, '登录账号不能重复，账号创建后不能再次修改。', '^.{4,}$', [
                'required-error' => '登录账号不能为空！',
                'pattern-error' => '登录账号格式错误！',
            ]);
        }

        return $builder
            ->addTextInput('nickname', '账号昵称', 'Nickname', true, '账号昵称用于显示区分，请尽量保持唯一不要重复。', null, [
                'required-error' => '账号昵称不能为空！',
            ])
            ->addTextArea('remark', '账号描述', 'User Remark', false, '')
            ->addSubmitButton()
            ->addCancelButton();
    }

    private function buildPassForm(): FormBuilder
    {
        return FormBuilder::mk()
            ->addTextInput('username', '登录用户账号', 'Username', false, '登录用户账号创建后，不允许再次修改。', null, [
                'readonly' => null,
                'class' => 'layui-bg-gray',
            ])
            ->addPassInput('password', '新的登录密码', 'New Password', true, '密码必须包含大小写字母、数字、符号的任意两者组合。', '^(?![\d]+$)(?![a-zA-Z]+$)(?![^\da-zA-Z]+$).{6,32}$', [
                'maxlength' => 32,
                'required-error' => '登录密码不能为空！',
                'pattern-error' => '登录密码格式错误！',
            ])
            ->addPassInput('repassword', '重复登录密码', 'Repeat Password', true, '密码必须包含大小写字母、数字、符号的任意两者组合。', '^(?![\d]+$)(?![a-zA-Z]+$)(?![^\da-zA-Z]+$).{6,32}$', [
                'maxlength' => 32,
                'required-error' => '重复密码不能为空！',
                'pattern-error' => '重复密码格式错误！',
            ])
            ->addValidateRule('repassword', 'confirm:password', '两次输入的密码不一致！')
            ->addSubmitButton()
            ->addCancelButton();
    }

    private function loadFormUser(): array
    {
        $id = intval($this->request->param('id', 0));
        if ($id < 1) {
            return [];
        }

        $user = PluginWumaWarehouseUser::mk()->findOrEmpty($id);
        return $user->isEmpty() ? [] : $user->toArray();
    }

    private function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}
