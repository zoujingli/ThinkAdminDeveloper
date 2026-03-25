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

namespace plugin\system\builder;

use think\admin\builder\form\FormBuilder;
use think\admin\builder\form\FormBlocks;
use think\admin\builder\form\FormNode;
use think\admin\builder\page\PageBuilder;

/**
 * 系统用户列表视图构建器.
 * @class UserBuilder
 */
class UserBuilder
{
    /**
     * 渲染用户列表页.
     * @param array<string, mixed> $context
     */
    public static function renderIndex(array $context): void
    {
        self::buildIndexPage($context)->fetch($context);
    }

    /**
     * 渲染密码表单页.
     * @param array<string, mixed> $context
     * @param array<string, mixed> $data
     */
    public static function renderPassForm(array $context, array $data): void
    {
        self::buildPassForm($context)->fetch(array_merge($context, ['vo' => $data]));
    }

    /**
     * 渲染用户表单页.
     * @param array<string, mixed> $context
     * @param array<string, mixed> $data
     */
    public static function renderForm(array $context, array $data): void
    {
        self::buildForm($context)->fetch(array_merge($context, ['vo' => $data]));
    }

    /**
     * 渲染已构建的用户表单页.
     * @param array<string, mixed> $context
     * @param array<string, mixed> $data
     */
    public static function renderBuiltForm(FormBuilder $builder, array $context, array $data): void
    {
        $builder->fetch(array_merge($context, ['vo' => $data]));
    }

    /**
     * 渲染当前用户资料表单页.
     * @param array<string, mixed> $context
     * @param array<string, mixed> $data
     */
    public static function renderInfoForm(array $context, array $data): void
    {
        self::buildInfoForm($context)->fetch(array_merge($context, ['vo' => $data]));
    }

    /**
     * 渲染已构建的当前用户资料表单页.
     * @param array<string, mixed> $context
     * @param array<string, mixed> $data
     */
    public static function renderBuiltInfoForm(FormBuilder $builder, array $context, array $data): void
    {
        $builder->fetch(array_merge($context, ['vo' => $data]));
    }

    /**
     * 构建用户列表页.
     * @param array<string, mixed> $context
     */
    public static function buildIndexPage(array $context): PageBuilder
    {
        $type = strval($context['type'] ?? 'index');
        $requestBaseUrl = strval($context['requestBaseUrl'] ?? '');
        $bases = is_array($context['bases'] ?? null) ? $context['bases'] : [];

        return PageBuilder::make()
            ->define(function ($page) use ($context, $type, $requestBaseUrl, $bases) {
                $page->title(strval($context['title'] ?? '系统用户管理'))
                    ->contentClass('')
                    ->showSearchLegend(false)
                    ->searchAttrs(['action' => $requestBaseUrl])
                    ->buttons(function ($buttons) use ($type) {
                        if ($type === 'index') {
                            $buttons->modal('添加用户', url('add')->build(), '添加用户', [], 'add')
                                ->batchAction('批量禁用', url('state')->build(), 'id#{id};status#0', '确定要禁用这些用户吗？', [], 'state');
                        } else {
                            $buttons->batchAction('批量恢复', url('state')->build(), 'id#{id};status#1', '确定要恢复这些账号吗？', [], 'state')
                                ->batchAction('批量删除', url('remove')->build(), 'id#{id}', '确定永久删除这些账号吗？', [], 'remove');
                        }
                    });

                $page->tabsList(SystemListTabs::indexRecycle($type, url('index')->build(), lang('系统用户')), 'UserTable', sysuri('index'), function ($search) use ($type, $bases) {
                    $search->hidden('type', $type)
                        ->input('username', '账号名称', '请输入账号或名称');
                    if (count($bases) > 0) {
                        $search->select('usertype', '角色身份', self::buildBaseOptions($bases), ['lay-search' => null]);
                    }
                    $search->dateRange('login_at', '最后登录', '请选择登录时间')
                        ->dateRange('create_time', '创建时间', '请选择创建时间');
                }, function ($table) use ($type, $bases) {
                    $table->options([
                        'even' => true,
                        'height' => 'full',
                        'sort' => ['field' => 'sort desc,id', 'type' => 'desc'],
                        'where' => ['type' => $type],
                    ])->checkbox()
                        ->sortInput('{:sysuri()}')
                        ->column(SystemTablePreset::avatarColumn())
                        ->column(SystemTablePreset::textColumn('username', '登录账号'))
                        ->column(SystemTablePreset::textColumn('nickname', '用户名称'));
                    if (count($bases) > 0) {
                        $table->column([
                            'field' => 'usertype',
                            'title' => '角色身份',
                            'minWidth' => 100,
                            'align' => 'center',
                            'templet' => PageBuilder::js(<<<'SCRIPT'
function (d) {
    d.userinfo = d.userinfo || {};
    return d.userinfo.code ? (d.userinfo.name + ' ( ' + d.userinfo.code + ' ) ') : '-';
}
SCRIPT),
                        ]);
                    }

                    $table->statusSwitch(url('state')->build(), SystemTablePreset::statusOptions())
                        ->column(['field' => 'login_num', 'title' => '登录次数', 'align' => 'center', 'minWidth' => 100, 'sort' => true])
                        ->column(SystemTablePreset::timeColumn('login_at', '最后登录'))
                        ->column(SystemTablePreset::timeColumn())
                        ->rows(function ($rows) use ($type) {
                            if ($type === 'index') {
                                $rows->modal('编 辑', url('edit')->build() . '?id={{d.id}}', '编辑用户', ['data-event-dbclick' => null], 'edit')
                                    ->modal('密 码', url('pass')->build() . '?id={{d.id}}', '设置密码', ['class' => 'layui-btn-normal'], 'pass');
                            } else {
                                $rows->modal('编 辑', url('edit')->build() . '?id={{d.id}}', '编辑用户', ['data-event-dbclick' => null], 'edit')
                                    ->action('删 除', url('remove')->build(), 'id#{{d.id}}', '确定要永久删除吗？', [], 'remove');
                            }
                        })
                        ->toolbar('操作面板', SystemTablePreset::toolbar('操作面板', 180));
                });
            })
            ->build();
    }

    /**
     * 构建密码表单.
     * @param array<string, mixed> $context
     */
    public static function buildPassForm(array $context = []): FormBuilder
    {
        $withOldPassword = !empty($context['withOldPassword']);
        $passwordPattern = '^(?![\\d]+$)(?![a-zA-Z]+$)(?![^\\da-zA-Z]+$).{6,32}$';
        return FormBuilder::make()
            ->define(function ($form) use ($withOldPassword, $passwordPattern) {
                $form->bodyClass('pa20')
                    ->fields(function ($fields) use ($withOldPassword, $passwordPattern) {
                    $fields->text('username', '登录用户账号', 'Username', false, '登录用户账号创建后，不允许再次修改。', null, [
                        'readonly' => null,
                        'class' => 'think-bg-gray',
                    ]);
                    if ($withOldPassword) {
                        $fields->password('oldpassword', '当前登录密码', 'Current Password', true, '请先输入当前登录密码完成验证。', null, [
                            'maxlength' => 32,
                            'required-error' => '旧的密码不能为空！',
                        ]);
                    }
                    $fields->password('password', '新的登录密码', 'New Password', true, '密码必须包含大小写字母、数字、符号的任意两者组合。', $passwordPattern, [
                        'maxlength' => 32,
                        'required-error' => '登录密码不能为空！',
                        'pattern-error' => '登录密码格式错误！',
                    ])->password('repassword', '重复登录密码', 'Repeat Password', true, '密码必须包含大小写字母、数字、符号的任意两者组合。', $passwordPattern, [
                        'maxlength' => 32,
                        'required-error' => '重复密码不能为空！',
                        'pattern-error' => '重复密码格式错误！',
                    ]);
                })->rule('repassword', 'confirm:password', '两次输入的密码不一致！')
                    ->actions(function ($actions) {
                        $actions->submit()->cancel();
                    });
            })
            ->build();
    }

    /**
     * 构建用户表单定义.
     * @param array<string, mixed> $context
     */
    public static function buildForm(array $context = []): FormBuilder
    {
        $isEdit = !empty($context['isEdit']);
        $usernameAttrs = [
            'maxlength' => 64,
            'placeholder' => '请输入登录账号',
            'required-error' => '登录账号不能为空！',
        ];
        $usernamePattern = '^.{4,}$';
        if ($isEdit) {
            $usernameAttrs['readonly'] = null;
            $usernameAttrs['class'] = 'think-bg-gray';
            $usernamePattern = null;
        }

        return FormBuilder::make()
            ->define(function ($form) use ($context, $usernamePattern, $usernameAttrs) {
                $form->action(strval($context['actionUrl'] ?? ''))
                    ->attrs(['id' => 'UserForm', 'data-table-id' => 'UserTable'])
                    ->bodyClass('pa20');

                self::buildAccountSection($form, $usernamePattern, $usernameAttrs);
                self::buildPermissionSection($form, $context);
                self::buildProfileSection($form);

                $form->div()->html('<input type="hidden" name="id" value="{$vo.id|default=\'\'}">')
                    ->actions(function ($actions) {
                        $actions->submit()->cancel();
                    });

                if (count((array)($context['baseGroups'] ?? [])) > 0 || count((array)($context['authGroups'] ?? [])) > 0) {
                    $form->script(self::renderFormScript());
                }
            })
            ->build();
    }

    /**
     * 构建当前用户资料表单定义.
     * @param array<string, mixed> $context
     */
    public static function buildInfoForm(array $context = []): FormBuilder
    {
        return self::buildForm($context);
    }

    /**
     * 构建身份选项.
     * @param array<int, array<string, mixed>> $bases
     * @return array<string, string>
     */
    public static function buildBaseOptions(array $bases): array
    {
        $options = [];
        foreach ($bases as $base) {
            $code = trim(strval($base['code'] ?? ''));
            if ($code !== '') {
                $name = strval($base['name'] ?? $code);
                $options[$code] = "{$name} ( {$code} )";
            }
        }
        return $options;
    }

    private static function buildAccountSection(FormNode $form, ?string $usernamePattern, array $usernameAttrs): void
    {
        FormBlocks::fieldset($form, '用户账号', function (FormNode $fieldset) use ($usernamePattern, $usernameAttrs) {
            FormBlocks::row($fieldset, function (FormNode $row) use ($usernamePattern, $usernameAttrs) {
                FormBlocks::col($row, 'layui-col-xs2 text-center pt15', function (FormNode $col) {
                    $col->fields(function ($fields) {
                        $field = $fields->image('headimg', '用户头像', 'Head Image');
                        $field->label()->html('');
                    });
                });
                FormBlocks::col($row, 'layui-col-xs5', function (FormNode $col) use ($usernamePattern, $usernameAttrs) {
                    $col->fields(function ($fields) use ($usernamePattern, $usernameAttrs) {
                        $fields->text('username', '登录账号', 'User Name', true, '登录账号不能少于4位字符，创建后不能再次修改.', $usernamePattern, $usernameAttrs);
                    });
                });
                FormBlocks::col($row, 'layui-col-xs5', function (FormNode $col) {
                    $col->fields(function ($fields) {
                        $fields->text('nickname', '用户名称', 'Nick Name', true, '用于区分用户数据的用户名称，请尽量不要重复.', null, [
                            'maxlength' => 64,
                            'placeholder' => '请输入用户名称',
                            'required-error' => '用户名称不能为空！',
                        ]);
                    });
                });
            });
        });
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function buildPermissionSection(FormNode $form, array $context): void
    {
        $baseGroups = is_array($context['baseGroups'] ?? null) ? $context['baseGroups'] : [];
        $authGroups = is_array($context['authGroups'] ?? null) ? $context['authGroups'] : [];
        if (count($baseGroups) < 1 && count($authGroups) < 1) {
            return;
        }

        FormBlocks::fieldset($form, '用户权限', function (FormNode $fieldset) use ($baseGroups, $authGroups, $context) {
            if (count($baseGroups) > 0) {
                FormBlocks::selectFilter(
                    $fieldset,
                    'usertype_plugin_filter',
                    'UserBasePluginFilter',
                    $baseGroups,
                    '角色身份',
                    'Role Identity',
                    '只切换显示的身份分组，不会影响已选中的角色身份。'
                );
                FormBlocks::groupedTemplateChoices($fieldset, $baseGroups, 'radio', 'usertype', 'user-base-group', 'base-group', 'usertype');
            }

            if (count($authGroups) > 0) {
                FormBlocks::selectFilter(
                    $fieldset,
                    'authorize_plugin_filter',
                    'UserAuthPluginFilter',
                    $authGroups,
                    '访问权限',
                    'Role Permission',
                    '只切换显示的权限分组，不会影响已选中的其它插件权限。'
                );
                if (strval($context['super'] ?? '') !== '') {
                    $fieldset->html(sprintf(
                        '{if isset($vo.username) and $vo.username eq \'%s\'}<div class="layui-form-item"><span class="color-desc pl5">超级用户拥有所有访问权限，不需要配置权限。</span></div>{else}{/if}',
                        addslashes(strval($context['super']))
                    ));
                }
                $authWrap = $fieldset->div()->html(sprintf(
                    '{if !(isset($vo.username) and $vo.username eq \'%s\')}',
                    addslashes(strval($context['super'] ?? ''))
                ));
                FormBlocks::groupedTemplateChoices($authWrap, $authGroups, 'checkbox', 'authorize', 'user-auth-group', 'auth-group', 'authorize');
                $authWrap->html('{/if}');
            }
        });
    }

    private static function buildProfileSection(FormNode $form): void
    {
        FormBlocks::fieldset($form, '用户资料', function (FormNode $fieldset) {
            FormBlocks::row($fieldset, function (FormNode $row) {
                FormBlocks::col($row, 'layui-col-xs4', function (FormNode $col) {
                    $col->fields(function ($fields) {
                        $fields->text('contact_mail', '联系邮箱', 'Contact Email', false, '可选，请填写用户常用的电子邮箱', 'email', [
                            'placeholder' => '请输入联系电子邮箱',
                            'pattern-error' => '联系邮箱格式错误！',
                        ]);
                    });
                });
                FormBlocks::col($row, 'layui-col-xs4', function (FormNode $col) {
                    $col->fields(function ($fields) {
                        $fields->text('contact_phone', '联系手机', 'Contact Mobile', false, '可选，请填写用户常用的联系手机号', 'phone', [
                            'maxlength' => 11,
                            'placeholder' => '请输入用户联系手机',
                            'type' => 'tel',
                            'pattern-error' => '联系手机格式错误！',
                        ]);
                    });
                });
                FormBlocks::col($row, 'layui-col-xs4', function (FormNode $col) {
                    $col->fields(function ($fields) {
                        $fields->text('contact_qq', '联系QQ', 'Contact QQ', false, '可选，请填写用户常用的联系QQ号', 'qq', [
                            'maxlength' => 11,
                            'placeholder' => '请输入常用的联系QQ',
                            'pattern-error' => '联系QQ格式错误！',
                        ]);
                    });
                });
            });
            $fieldset->div()->class('mt10')->fields(function ($fields) {
                $fields->textarea('describe', '用户描述', 'User Remark', false, '请输入用户描述');
            });
        });
    }

    private static function renderFormScript(): string
    {
        return <<<'SCRIPT'
$.module.use([], function () {
    layui.form.on('select(UserBasePluginFilter)', function (object) {
        $('.user-base-group').each(function () {
            let active = !object.value || $(this).data('base-group') === object.value;
            $(this)[active ? 'show' : 'hide']();
        });
    });

    layui.form.on('select(UserAuthPluginFilter)', function (object) {
        $('.user-auth-group').each(function () {
            let active = !object.value || $(this).data('auth-group') === object.value;
            $(this)[active ? 'show' : 'hide']();
        });
    });
});
SCRIPT;
    }
}
