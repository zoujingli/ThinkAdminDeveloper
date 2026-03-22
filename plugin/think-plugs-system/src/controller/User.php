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

use plugin\system\model\SystemAuth;
use plugin\system\model\SystemBase;
use plugin\system\model\SystemUser;
use plugin\system\service\AuthService;
use plugin\system\service\UserService;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

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
        $this->type = $this->get['type'] ?? 'index';
        SystemUser::mQuery()->layTable(function () {
            $this->title = '系统用户管理';
            $this->bases = SystemBase::items('身份权限');
        }, function (QueryHelper $query) {
            $query->where(['status' => intval($this->type === 'index')]);
            $query->with(['userinfo' => static function ($query) {
                $query->field('code,name,content');
            }]);
            $query->equal('status,usertype')->dateBetween('login_at,create_time');
            $query->like('username|nickname#username,contact_phone#phone,contact_mail#mail');
        });
    }

    /**
     * @auth true
     */
    public function add()
    {
        SystemUser::mForm('form');
    }

    /**
     * @auth true
     */
    public function edit()
    {
        SystemUser::mForm('form');
    }

    /**
     * @auth true
     */
    public function pass()
    {
        $builder = UserService::buildPassForm();

        if ($this->request->isGet()) {
            $this->verify = false;
            $builder->fetch(['vo' => UserService::loadPassUser(intval($this->request->param('id', 0)))]);
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

    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function _form_filter(array &$data)
    {
        if ($this->request->isPost()) {
            empty($data['username']) && $this->error('登录账号不能为空！');
            if ($data['username'] !== AuthService::getSuperName()) {
                empty($data['authorize']) && $this->error('未配置权限！');
            }

            $data['authorize'] = arr2str($data['authorize'] ?? []);
            if (empty($data['id'])) {
                if (SystemUser::mk()->where(['username' => $data['username']])->count() > 0) {
                    $this->error('账号已经存在，请使用其它账号！');
                }
                $data['password'] = UserService::hashPassword($data['username']);
            } else {
                unset($data['username']);
            }
            return;
        }

        $data['authorize'] = str2arr($data['authorize'] ?? '');
        $this->auths = SystemAuth::itemsWithPlugins();
        $this->authGroups = $this->buildAuthGroups($this->auths);
        $this->bases = SystemBase::itemsWithPlugins('身份权限');
        $this->baseGroups = $this->buildBaseGroups($this->bases);
        $this->super = AuthService::getSuperName();
    }

    private function _checkInput()
    {
        if (in_array('10000', str2arr(strval(input('id', ''))), true)) {
            $this->error('系统超级账号禁止删除！');
        }
    }

    private function buildAuthGroups(array $auths): array
    {
        $groups = [];
        foreach ($auths as $auth) {
            $code = strval($auth['plugin_group'] ?? 'common');
            if (!isset($groups[$code])) {
                $groups[$code] = [
                    'code' => $code,
                    'name' => strval($auth['plugin_title'] ?? $code),
                    'items' => [],
                ];
            }
            $groups[$code]['items'][] = $auth;
        }

        $specials = [];
        foreach (['common', 'mixed'] as $code) {
            if (isset($groups[$code])) {
                $specials[$code] = $groups[$code];
                unset($groups[$code]);
            }
        }

        uasort($groups, static function (array $a, array $b): int {
            return strcmp(strval($a['name'] ?? ''), strval($b['name'] ?? ''));
        });

        foreach ($specials as $code => $group) {
            $groups[$code] = $group;
        }

        return $groups;
    }

    private function buildBaseGroups(array $bases): array
    {
        $groups = [];
        foreach ($bases as $base) {
            $code = strval($base['plugin_group'] ?? 'common');
            if (!isset($groups[$code])) {
                $groups[$code] = [
                    'code' => $code,
                    'name' => strval($base['plugin_title'] ?? $code),
                    'items' => [],
                ];
            }
            $groups[$code]['items'][] = $base;
        }

        $specials = [];
        foreach (['common', 'mixed'] as $code) {
            if (isset($groups[$code])) {
                $specials[$code] = $groups[$code];
                unset($groups[$code]);
            }
        }

        uasort($groups, static function (array $a, array $b): int {
            return strcmp(strval($a['name'] ?? ''), strval($b['name'] ?? ''));
        });

        foreach ($specials as $code => $group) {
            $groups[$code] = $group;
        }

        return $groups;
    }
}
