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

use think\admin\builder\BuilderLang;
use think\admin\builder\form\FormBuilder;
use think\admin\builder\form\FormBlocks;
use think\admin\builder\form\FormNode;
use think\admin\builder\form\module\FormModules;
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

        return PageBuilder::tablePage()
            ->define(function ($page) use ($context, $type, $requestBaseUrl, $bases) {
                SystemListPage::apply($page, strval($context['title'] ?? '系统用户管理'), $requestBaseUrl)
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
                        $search->select('base_code', '角色身份', self::buildBaseOptions($bases), ['lay-search' => null]);
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
                            'field' => 'base_code',
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
        return FormBuilder::dialogForm()
            ->define(function ($form) use ($withOldPassword, $passwordPattern) {
                $form->class('system-user-pass-form');

                FormModules::section($form, [
                    'title' => '账号确认',
                    'description' => $withOldPassword
                        ? '先确认当前登录账号并完成旧密码验证，再继续设置新密码。'
                        : '当前操作用于后台重置用户密码，请先确认账号无误后再设置新密码。',
                ], function ($section) use ($withOldPassword) {
                    $section->fields(function ($fields) use ($withOldPassword) {
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
                    });
                });

                FormModules::section($form, [
                    'title' => '新密码设置',
                    'description' => '建议使用字母、数字和符号的组合，并避免与其它系统密码重复。',
                ], function ($section) use ($passwordPattern) {
                    $section->fields(function ($fields) use ($passwordPattern) {
                        $fields->password('password', '新的登录密码', 'New Password', true, '密码必须包含大小写字母、数字、符号的任意两者组合。', $passwordPattern, [
                            'maxlength' => 32,
                            'required-error' => '登录密码不能为空！',
                            'pattern-error' => '登录密码格式错误！',
                        ])->password('repassword', '重复登录密码', 'Repeat Password', true, '密码必须包含大小写字母、数字、符号的任意两者组合。', $passwordPattern, [
                            'maxlength' => 32,
                            'required-error' => '重复密码不能为空！',
                            'pattern-error' => '重复密码格式错误！',
                        ]);
                    });
                });

                $form->rule('repassword', 'confirm:password', '两次输入的密码不一致！')
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
        $manageAccount = !isset($context['manageAccount']) || !empty($context['manageAccount']);
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

        return FormBuilder::dialogForm()
            ->define(function ($form) use ($context, $usernamePattern, $usernameAttrs, $manageAccount, $isEdit) {
                $form->action(strval($context['actionUrl'] ?? ''))
                    ->attrs(['id' => 'UserForm', 'data-table-id' => 'UserTable'])
                    ->class($manageAccount ? 'system-user-form' : 'system-user-form system-user-info-form');

                self::buildAccountSection($form, $usernamePattern, $usernameAttrs, $manageAccount, $isEdit);
                self::buildPasswordSection($form, $manageAccount, $isEdit);
                self::buildPermissionSection($form, $context);
                self::buildContactSection($form);
                self::buildManageSection($form, $manageAccount);

                $form->actions(function ($actions) {
                        $actions->submit()->cancel();
                    });

                if (count((array)($context['baseGroups'] ?? [])) > 0 || count((array)($context['authGroups'] ?? [])) > 0) {
                    $form->script(self::renderFormScript(strval($context['super'] ?? '')));
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

    private static function buildAccountSection(FormNode $form, ?string $usernamePattern, array $usernameAttrs, bool $manageAccount, bool $isEdit): void
    {
        FormModules::section($form, [
            'title' => '账号信息',
            'description' => $manageAccount
                ? ($isEdit ? '维护头像、账号标识与展示名称。登录账号创建后保持只读。' : '先创建头像、登录账号和用户名称，账号创建后即作为后台登录标识。')
                : '当前页面只维护你自己的头像、展示名称与联系资料，登录账号保持只读。',
        ], function (FormNode $section) use ($usernamePattern, $usernameAttrs) {
            $section->fields(function ($fields) use ($usernamePattern, $usernameAttrs) {
                $fields->field([
                    'type' => 'image',
                    'name' => 'headimg',
                    'title' => '用户头像',
                    'subtitle' => 'Head Image',
                    'remark' => '请输入头像地址，或点击右侧上传图标直接上传。',
                    'attrs' => [
                        'placeholder' => '请输入用户头像地址',
                    ],
                ])->types('gif,png,jpg,jpeg');
                $fields->text('username', '登录账号', 'User Name', true, '登录账号不能少于 4 位字符，创建后不能再次修改。', $usernamePattern, $usernameAttrs);
                $fields->text('nickname', '用户名称', 'Nick Name', true, '用于后台展示和日志区分，建议保持唯一且便于识别。', null, [
                    'maxlength' => 64,
                    'placeholder' => '请输入用户名称',
                    'required-error' => '用户名称不能为空！',
                ]);
            });
        });
    }

    private static function buildPasswordSection(FormNode $form, bool $manageAccount, bool $isEdit): void
    {
        if (!$manageAccount) {
            return;
        }

        FormModules::section($form, [
            'title' => '登录密码',
            'description' => $isEdit
                ? '默认显示 6 个星号，保留星号表示不修改当前密码；输入新密码后需再次确认。'
                : '可直接设置初始登录密码；如果留空，则默认使用登录账号作为初始密码。',
        ], function (FormNode $section) use ($isEdit) {
            $section->fields(function ($fields) use ($isEdit) {
                $mask = $isEdit ? password_mask() : '';
                $help = $isEdit
                    ? '保留默认星号则不修改密码，输入新密码后需再次确认。'
                    : '可选。留空时默认使用登录账号作为初始密码。';

                $fields->password('password', '登录密码', 'Password', false, $help, null, [
                    'maxlength' => 32,
                    'autocomplete' => 'new-password',
                    'placeholder' => $isEdit ? '保留默认星号则不修改密码' : '请输入登录密码，留空则默认使用登录账号',
                ])->defaultValue($mask)->password('repassword', '重复密码', 'Repeat Password', false, $help, null, [
                    'maxlength' => 32,
                    'autocomplete' => 'new-password',
                    'placeholder' => $isEdit ? '保留默认星号则不修改密码' : '请再次输入登录密码',
                ])->defaultValue($mask);
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

        FormModules::section($form, [
            'title' => '身份与权限',
            'description' => '先选择角色身份，再补充访问权限。分组筛选只影响显示，不会影响已经勾选的内容。',
        ], function (FormNode $fieldset) use ($baseGroups, $authGroups, $context) {
            if (count($baseGroups) > 0) {
                FormBlocks::selectFilter(
                    $fieldset,
                    'usertype_plugin_filter',
                    'UserBasePluginFilter',
                    $baseGroups,
                    '角色身份',
                    'Role Identity',
                    '切换显示的身份分组，不会影响当前已选中的角色身份。'
                );
                FormBlocks::groupedTemplateChoices($fieldset, $baseGroups, 'radio', 'base_code', 'user-base-group', 'base-group', 'base_code');
            }

            if (count($authGroups) > 0) {
                FormBlocks::selectFilter(
                    $fieldset,
                    'authorize_plugin_filter',
                    'UserAuthPluginFilter',
                    $authGroups,
                    '访问权限',
                    'Role Permission',
                    '切换显示的权限分组，不会影响当前已选中的其它插件权限。'
                );
                if (strval($context['super'] ?? '') !== '') {
                    $fieldset->div(function (FormNode $notice) {
                        $notice->node('span', function (FormNode $span) {
                            $span->class('color-desc pl5')->textNode(BuilderLang::text('超级用户拥有所有访问权限，不需要配置权限。'));
                        });
                    })->class('layui-form-item layui-hide user-super-notice');
                }
                $authWrap = $fieldset->div()->class('user-auth-wrap');
                FormBlocks::groupedTemplateChoices($authWrap, $authGroups, 'checkbox', 'auth_ids', 'user-auth-group', 'auth-group', 'auth_ids');
            }
        });
    }

    private static function buildContactSection(FormNode $form): void
    {
        FormModules::section($form, [
            'title' => '联系资料',
            'description' => '联系资料用于运维沟通和身份识别，备注可补充职责、值班说明或交接信息。',
        ], function (FormNode $fieldset) {
            $fieldset->fields(function ($fields) {
                $fields->text('contact_mail', '联系邮箱', 'Contact Email', false, '可选，请填写用户常用的电子邮箱。', 'email', [
                    'placeholder' => '请输入联系电子邮箱',
                    'pattern-error' => '联系邮箱格式错误！',
                ])->text('contact_phone', '联系手机', 'Contact Mobile', false, '可选，请填写用户常用的联系手机号。', 'phone', [
                    'maxlength' => 11,
                    'placeholder' => '请输入用户联系手机',
                    'type' => 'tel',
                    'pattern-error' => '联系手机格式错误！',
                ])->text('contact_qq', '联系QQ', 'Contact QQ', false, '可选，请填写用户常用的联系 QQ 号。', 'qq', [
                    'maxlength' => 11,
                    'placeholder' => '请输入常用的联系QQ',
                    'pattern-error' => '联系QQ格式错误！',
                ])->textarea('remark', '用户描述', 'User Remark', false, '可选，用于补充岗位职责、值班说明或交接备注。', [
                    'placeholder' => '请输入用户描述',
                ]);
            });
        });
    }

    private static function buildManageSection(FormNode $form, bool $withManageFields = true): void
    {
        if (!$withManageFields) {
            return;
        }

        FormModules::section($form, [
            'title' => '管理设置',
            'description' => '管理页专用参数。排序越大越靠前，禁用后该账号将无法继续登录后台。',
        ], function (FormNode $section) {
            $section->fields(function ($fields) {
                $fields->text('sort', '排序权重', 'Sort Order', false, '数值越大越靠前，默认按 0 处理。', '^[0-9]{1,10}$', [
                    'type' => 'number',
                    'min' => 0,
                    'step' => 1,
                    'placeholder' => '请输入排序权重',
                    'pattern-error' => '排序权重格式错误！',
                ])->defaultValue(0)->radio('status', '账号状态', 'Account Status', '', true, [
                    'required-error' => '请选择账号状态！',
                ])->options([
                    '1' => '已启用',
                    '0' => '已禁用',
                ])->defaultValue('1');
            });
        });
    }

    private static function renderFormScript(string $super): string
    {
        $superLiteral = addslashes($super);
        return <<<SCRIPT
$.module.use([], function () {
    var superUser = '{$superLiteral}';
    var \$username = $('[name="username"]');
    var \$superNotice = $('.user-super-notice');
    var \$authWrap = $('.user-auth-wrap');

    var syncSuperUser = function () {
        if (!superUser || \$superNotice.length < 1 || \$authWrap.length < 1) return;
        var active = $.trim(String(\$username.val() || '')) === superUser;
        \$superNotice[active ? 'show' : 'hide']();
        \$authWrap[active ? 'hide' : 'show']();
    };

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

    syncSuperUser();
    \$username.on('change input', syncSuperUser);
});
SCRIPT;
    }
}
