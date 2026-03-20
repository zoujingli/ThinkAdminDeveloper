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

use plugin\account\model\PluginAccountBind;
use plugin\account\service\Account;
use think\admin\Controller;
use think\admin\Exception;
use think\admin\helper\FormBuilder;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class Device extends Controller
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
        PluginAccountBind::mQuery()->layTable(function () {
            $this->title = '终端账号管理';
            $this->types = Account::types(1);
        }, function (QueryHelper $query) {
            $query->where(['status' => intval($this->type === 'index')]);
            $query->with('user');
            $query->equal('type#utype')->like('phone,nickname,username')->dateBetween('create_time');
        });
    }

    /**
     * @auth true
     * @throws Exception
     */
    public function config()
    {
        $this->types = Account::types();
        $builder = $this->buildConfigForm();

        if ($this->request->isGet()) {
            $builder->fetch([
                'vo' => $this->loadConfigFormData(),
                'registerModes' => [
                    0 => '启用自动注册',
                    1 => '禁止自动注册',
                ],
                'typeLabels' => $this->buildTypeLabels(),
            ]);
            return;
        }

        $data = $builder->validate();
        Account::config($data);
        Account::expire($data['expire'] ?: 0, $data['headimg'] ?: null);

        $types = $data['types'] ?? [];
        foreach ($this->types as $key => $item) {
            Account::set($key, intval(in_array($key, $types, true)));
        }

        if (Account::save()) {
            $this->success('配置保存成功！');
        }

        $this->error('配置保存失败！');
    }

    /**
     * @auth true
     */
    public function state()
    {
        PluginAccountBind::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * @auth true
     */
    public function remove()
    {
        PluginAccountBind::mDelete();
    }

    private function buildConfigForm(): FormBuilder
    {
        return FormBuilder::mk()
            ->addTextInput('expire', '认证有效时间', 'Expire Time', true, '设置为 0 表示永不过期，建议设置有效时间达到系统自动回收令牌。', '^[0-9]+$', [
                'type' => 'number',
                'min' => 0,
                'data-blur-number' => '0',
                'required-error' => '认证有效时间不能为空！',
                'pattern-error' => '认证有效时间格式错误！',
            ])
            ->addField([
                'type' => 'radio',
                'name' => 'disRegister',
                'title' => '登录自动注册',
                'subtitle' => 'Auto Register',
                'required' => true,
                'remark' => '启用自动登录时，通过验证码登录时账号不存在会自动创建。',
                'attrs' => ['required-error' => '登录自动注册不能为空！'],
                'vname' => 'registerModes',
            ])
            ->addTextInput('userPrefix', '默认昵称前缀', 'NickName Prefix', true, '用户绑定账号后会自动使用此前缀与手机号后 4 位拼接为新默认昵称。', null, [
                'maxlength' => 20,
                'required-error' => '默认昵称前缀不能为空！',
            ])
            ->addField([
                'type' => 'image',
                'name' => 'headimg',
                'title' => '默认用户头像',
                'subtitle' => 'Default Headimg',
                'required' => true,
                'remark' => '当用户未设置头像时，自动使用此头像设置的图片链接。',
                'attrs' => [
                    'required-error' => '默认用户头像不能为空！',
                    'data-tips-hover' => null,
                    'data-tips-image' => null,
                ],
            ])
            ->addField([
                'type' => 'checkbox',
                'name' => 'types',
                'title' => '开放接口通道',
                'subtitle' => 'Interface Types',
                'remark' => '选择开放接口通道。',
                'vname' => 'typeLabels',
            ])
            ->addSubmitButton()
            ->addCancelButton();
    }

    private function loadConfigFormData(): array
    {
        $data = Account::config() ?: [];
        $data['expire'] = strval($data['expire'] ?? Account::expire());
        $data['disRegister'] = intval($data['disRegister'] ?? 0);
        $data['userPrefix'] = strval($data['userPrefix'] ?? '用户');
        $data['headimg'] = strval(Account::headimg());
        $data['types'] = array_keys(array_filter($this->types, static function (array $item): bool {
            return !empty($item['status']);
        }));
        return $data;
    }

    private function buildTypeLabels(): array
    {
        $labels = [];
        foreach ($this->types as $key => $item) {
            $labels[$key] = strval($item['name'] ?? $key);
        }
        return $labels;
    }
}
