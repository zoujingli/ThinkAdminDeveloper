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
use think\admin\builder\form\module\FormModules;
use think\admin\builder\page\PageBuilder;

/**
 * 系统菜单页面视图构建器.
 * @class MenuBuilder
 */
class MenuBuilder
{
    /**
     * 渲染菜单列表页.
     * @param array<string, mixed> $context
     */
    public static function renderIndex(array $context): void
    {
        self::buildIndexPage($context)->fetch($context);
    }

    /**
     * 渲染菜单表单页.
     * @param array<string, mixed> $context
     * @param array<string, mixed> $data
     */
    public static function renderForm(array $context, array $data): void
    {
        $builder = self::buildForm($context);
        self::renderBuiltForm($builder, $context, $data);
    }

    /**
     * 渲染已构建的菜单表单页.
     * @param array<string, mixed> $context
     * @param array<string, mixed> $data
     */
    public static function renderBuiltForm(FormBuilder $builder, array $context, array $data): void
    {
        $builder->fetch(array_merge($context, ['vo' => $data]));
    }

    /**
     * 构建菜单列表页.
     * @param array<string, mixed> $context
     */
    public static function buildIndexPage(array $context): PageBuilder
    {
        $type = strval($context['type'] ?? 'index');
        $pid = trim(strval($context['pid'] ?? ''));
        $requestBaseUrl = strval($context['requestBaseUrl'] ?? '');
        $sortActionUrl = $requestBaseUrl !== '' ? $requestBaseUrl : strval($context['indexUrl'] ?? url('index')->build());
        $stateUrl = addslashes(url('state')->build());

        return PageBuilder::tablePage()
            ->define(function ($page) use ($context, $type, $pid, $requestBaseUrl, $stateUrl, $sortActionUrl) {
                SystemListPage::apply($page, strval($context['title'] ?? '系统菜单管理'), $requestBaseUrl)
                    ->buttons(function ($buttons) use ($type, $stateUrl) {
                        if ($type === 'index') {
                            $buttons->modal('添加菜单', url('add')->build(), '添加系统菜单', ['data-table-id' => 'MenuTable'], 'add')
                                ->batchAction('禁用菜单', $stateUrl, 'id#{sps};status#0', '', [], 'state');
                        } else {
                            $buttons->batchAction('激活菜单', $stateUrl, 'id#{spp};status#1', '', [], 'state');
                        }
                    });

                $page->tabsList(SystemListTabs::menu(
                    strval($context['indexUrl'] ?? ''),
                    $type,
                    $pid,
                    is_array($context['menuRootList'] ?? null) ? $context['menuRootList'] : []
                ), 'MenuTable', $requestBaseUrl, null, function ($table) use ($type, $pid, $stateUrl, $sortActionUrl) {
                    $table->options([
                            'even' => true,
                            'height' => 'full',
                            'page' => false,
                            'sort' => ['field' => 'sort desc,id', 'type' => 'asc'],
                            'where' => ['type' => $type, 'pid' => $pid],
                            'filter' => PageBuilder::js(<<<'SCRIPT'
function (items) {
    var type = this.where.type;
    return items.filter(function (item) {
        return !(type === 'index' && parseInt(item.status) === 0);
    });
}
SCRIPT),
                        ])->checkbox(['field' => 'sps'])
                        ->sortInput($sortActionUrl)
                        ->column(SystemTablePreset::iconColumn())
                        ->column(['field' => 'title', 'title' => '菜单名称', 'minWidth' => 220, 'templet' => '<div><span class="color-desc">{{d.spl}}</span>{{d.title}}</div>'])
                        ->column(SystemTablePreset::linkColumn('url', '跳转链接'))
                        ->column(['field' => 'status', 'title' => '使用状态', 'minWidth' => 120, 'align' => 'center', 'templet' => PageBuilder::js(self::renderStatusScript($type))])
                        ->column([
                            'field' => 'toolbar',
                            'title' => '操作面板',
                            'align' => 'center',
                            'minWidth' => 150,
                            'fixed' => 'right',
                            'templet' => PageBuilder::js(self::renderToolbarScript(
                                $type,
                                url('add')->build(),
                                url('edit')->build(),
                                url('remove')->build(),
                            )),
                        ])
                        ->script(<<<SCRIPT
layui.form.on('switch(MenuStatusSwitch)', function (obj) {
    var data = {status: obj.elem.checked > 0 ? 1 : 0};
    data.id = obj.value.split('|')[data.status] || obj.value;
    $.form.load("{$stateUrl}", data, 'post', function (ret) {
        if (ret.code < 1) $.msg.error(ret.info, 3, function () {
            $('#MenuTable').trigger('reload');
        });
        else $('#MenuTable').trigger('reload');
        return false;
    }, false);
});
SCRIPT);
                });
            })
            ->build();
    }

    /**
     * 构建菜单表单定义.
     * @param array<string, mixed> $context
     */
    public static function buildForm(array $context): FormBuilder
    {
        return FormBuilder::dialogForm()
            ->define(function ($form) use ($context) {
                $form->action(strval($context['actionUrl'] ?? ''))
                    ->attrs(['id' => 'MenuForm', 'data-table-id' => 'MenuTable'])
                    ->class('system-menu-form');

                FormModules::section($form, [
                    'title' => '层级归属',
                    'description' => '先确认上级菜单与菜单名称。分组菜单建议将链接留空或填写 #，业务页面建议直接指向真实页面节点。',
                ], function ($section) use ($context) {
                    $grid = $section->div()->class('layui-row layui-col-space15');

                    $left = $grid->div()->class('layui-col-xs12 layui-col-md6');
                    $left->fields(function ($fields) use ($context) {
                        $fields->select('pid', '上级菜单', 'Parent Menu', true, '请选择上级菜单或顶级菜单。当前仅支持三级菜单结构，叶子菜单不能继续挂载子节点。', self::buildParentOptions(is_array($context['menus'] ?? null) ? $context['menus'] : []), '', ['lay-search' => null])
                            ->text('title', '菜单名称', 'Menu Title', true, '请填写菜单名称，建议控制在 4-8 个汉字，便于导航栏展示。', null, [
                                'placeholder' => '请输入菜单名称',
                                'required-error' => '菜单名称不能为空！',
                            ]);
                    });

                    $right = $grid->div()->class('layui-col-xs12 layui-col-md6');
                    FormModules::readonlyField($right, [
                        'title' => '结构约束',
                        'subtitle' => 'Structure Rules',
                        'value' => '顶级菜单 / 二级分组 / 三级页面',
                        'help' => '系统会在保存时校验父子关系，禁止挂到叶子菜单下，也禁止形成循环层级。',
                    ]);
                });

                FormModules::section($form, [
                    'title' => '跳转与权限',
                    'description' => '菜单链接建议填写完整页面节点，如 system/user/index。权限节点为空时，系统会优先根据菜单链接解析访问控制节点。',
                ], function ($section) {
                    $grid = $section->div()->class('layui-row layui-col-space15');

                    $route = $grid->div()->class('layui-col-xs12 layui-col-md6');
                    $route->fields(function ($fields) {
                        $fields->text('url', '菜单链接', 'Menu Url', false, '支持系统页面节点、外部地址、插件布局地址或 # 分组节点。保存时会自动标准化链接格式。', null, [
                            'placeholder' => '例如：system/user/index、https://example.com/docs 或 #',
                        ])->text('params', '链接参数', 'Query Params', false, '设置菜单链接的 GET 访问参数，如 tab=user&mode=list。', null, [
                            'placeholder' => '请输入链接参数',
                        ]);
                    });

                    $permission = $grid->div()->class('layui-col-xs12 layui-col-md6');
                    $permission->fields(function ($fields) {
                        $fields->text('node', '权限节点', 'Permission Node', false, '显式指定注释式 RBAC 节点。若留空，将按菜单链接推导默认访问节点。', null, [
                            'placeholder' => '例如：system/user/index',
                        ])->select('target', '打开方式', 'Target Window', true, '设置菜单链接的打开方式。', [
                            '_self' => '当前窗口',
                            '_blank' => '新窗口',
                        ]);
                    });

                    $preview = $section->div()->class('layui-row layui-col-space15');
                    $previewUrl = $preview->div()->class('layui-col-xs12 layui-col-md6');
                    FormModules::readonlyField($previewUrl, [
                        'title' => '标准化链接预览',
                        'subtitle' => 'Normalized Target',
                        'value' => '',
                        'input_attrs' => [
                            'data-menu-url-preview' => null,
                            'placeholder' => '根据菜单链接与参数自动生成',
                        ],
                        'help' => '预览仅用于提示保存结果，实际写库仍以后端校验为准。',
                    ]);
                    $previewNode = $preview->div()->class('layui-col-xs12 layui-col-md6');
                    FormModules::readonlyField($previewNode, [
                        'title' => '鉴权节点预览',
                        'subtitle' => 'Resolved Node',
                        'value' => '',
                        'input_attrs' => [
                            'data-menu-node-preview' => null,
                            'placeholder' => '未显式设置时按菜单链接推导',
                        ],
                        'help' => '权限节点必须来自注释式 RBAC 的有效 @auth 节点。',
                    ]);
                });

                FormModules::section($form, [
                    'title' => '展示策略',
                    'description' => '统一维护菜单图标、排序权重与启停状态。图标支持 layui 与 iconfont 字体类名。',
                ], function ($section) {
                    $grid = $section->div()->class('layui-row layui-col-space15');

                    $fields = $grid->div()->class('layui-col-xs12 layui-col-md6');
                    $fields->fields(function ($fields) {
                        $fields->text('icon', '菜单图标', 'Menu Icon', false, '可手动输入图标类名，或通过右侧图标选择器回填。', null, [
                            'placeholder' => '例如：layui-icon layui-icon-set-fill',
                        ])->text('sort', '排序权重', 'Sort Order', false, '数值越大越靠前。', null, [
                            'type' => 'number',
                            'min' => 0,
                            'placeholder' => '请输入排序权重',
                        ])->defaultValue(0)->radio('status', '使用状态', 'Status', '', true, [
                            'required-error' => '请选择当前菜单的启用状态。',
                        ])->options([
                            1 => '已启用',
                            0 => '已禁用',
                        ])->defaultValue('1');
                    });

                    $picker = $grid->div()->class('layui-col-xs12 layui-col-md6');
                    FormModules::pickerField($picker, [
                        'title' => '图标选择器',
                        'subtitle' => 'Icon Picker',
                        'value' => '点击打开图标选择器',
                        'attrs' => ['data-open-menu-icon' => null],
                        'input_attrs' => [
                            'data-open-menu-icon' => null,
                            'data-menu-icon-picker-text' => null,
                            'placeholder' => '点击选择图标并自动回填',
                        ],
                        'icon_attrs' => ['data-open-menu-icon' => null],
                        'help' => '选择后会自动同步到“菜单图标”字段，并实时预览当前图标样式。',
                    ]);
                    $picker->div()->class('layui-form-mid color-desc')->html(sprintf(
                        '<span class="inline-flex items-center"><i class="layui-icon layui-icon-set-fill font-s14 mr-8" data-menu-icon-preview></i><span data-menu-icon-preview-text>%s</span></span>',
                        self::escape(BuilderLang::text('未设置图标'))
                    ));
                });

                $form->data('menu-nodes', self::encodeJson(is_array($context['nodes'] ?? null) ? $context['nodes'] : []))
                    ->data('menu-auths', self::encodeJson(is_array($context['auths'] ?? null) ? $context['auths'] : []))
                    ->data('menu-icon-picker-url', strval($context['iconPickerUrl'] ?? ''));
                $form->actions(function ($actions) {
                    $actions->submit()->cancel();
                })->rules([
                        'title.require' => '菜单名称不能为空！',
                        'target.in:_self,_blank' => '打开方式异常！',
                        'status.in:0,1' => '状态值范围异常！',
                    ])->script(self::renderFormScript());
            })
            ->build();
    }

    /**
     * 构建父级菜单选项.
     * @param array<int, array<string, mixed>> $menus
     * @return array<string, string>
     */
    private static function buildParentOptions(array $menus): array
    {
        $options = [];
        foreach ($menus as $menu) {
            $options[strval($menu['id'] ?? 0)] = trim(strip_tags(strval($menu['spl'] ?? '') . strval($menu['title'] ?? '')));
        }
        return $options;
    }

    /**
     * 渲染状态模板.
     */
    private static function renderStatusScript(string $type): string
    {
        $typeLiteral = addslashes($type);
        return <<<SCRIPT
function (d) {
    if ('{$typeLiteral}' === 'index' || Number(d.spc || 0) < 1 || Number(d.status || 0) < 1) {
        return '<input type="checkbox" value="' + (d.sps || '') + '|' + (d.spp || '') + '" lay-text="已激活|已禁用" lay-filter="MenuStatusSwitch" lay-skin="switch"' + (Number(d.status || 0) > 0 ? ' checked' : '') + '>';
    }
    return Number(d.status || 0) > 0 ? '<b class="color-green">已激活</b>' : '<b class="color-red">已禁用</b>';
}
SCRIPT;
    }

    private static function renderToolbarScript(string $type, string $addUrl, string $editUrl, string $removeUrl): string
    {
        $typeLiteral = addslashes($type);
        $addLiteral = addslashes($addUrl);
        $editLiteral = addslashes($editUrl);
        $removeLiteral = addslashes($removeUrl);
        return <<<SCRIPT
function (d) {
    var html = [];
    if ('{$typeLiteral}' === 'index') {
        if (Number(d.spt || 0) < 2) {
            html.push('<a class="layui-btn layui-btn-sm layui-btn-primary" data-title="添加系统菜单" data-modal="{$addLiteral}?pid=' + d.id + '">添加</a>');
        } else {
            html.push('<a class="layui-btn layui-btn-sm layui-btn-disabled">添加</a>');
        }
        html.push('<a class="layui-btn layui-btn-sm" data-title="编辑系统菜单" data-modal="{$editLiteral}?id=' + d.id + '">编辑</a>');
        return html.join('');
    }
    if (Number(d.spc || 0) < 1 || Number(d.status || 0) < 1) {
        return '<a class="layui-btn layui-btn-sm layui-btn-danger" data-confirm="确定要删除菜单吗？" data-action="{$removeLiteral}" data-value="id#' + (d.sps || '') + '">删除</a>';
    }
    return '<a class="layui-btn layui-btn-disabled layui-btn-sm">删除</a>';
}
SCRIPT;
    }

    private static function encodeJson(mixed $value): string
    {
        return strval(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT));
    }

    private static function renderFormScript(): string
    {
        return sprintf(<<<'SCRIPT'
$.module.use(['jquery.autocompleter'], function () {
    var menuForm = document.getElementById('MenuForm') || {};
    var menuFormNodes = JSON.parse((menuForm.dataset || {}).menuNodes || '[]');
    var menuFormAuths = JSON.parse((menuForm.dataset || {}).menuAuths || '[]');
    var iconPickerUrl = String((menuForm.dataset || {}).menuIconPickerUrl || '');
    var menuI18n = %s;
    var normalizeNode = function (value) {
        value = $.trim(String(value || '')).replace(/\\/g, '/').replace(/[?#].*$/, '').replace(/^\/+|\/+$/g, '');
        if (!value) return '';
        var parts = value.split('/').filter(Boolean);
        if (parts[0]) parts[0] = parts[0].toLowerCase();
        if (parts[1]) parts[1] = parts[1].replace(/([a-z0-9])([A-Z])/g, '$1_$2').toLowerCase();
        if (parts[2]) parts[2] = parts[2].toLowerCase();
        return parts.join('/');
    };
    var normalizeTarget = function (value) {
        value = $.trim(String(value || ''));
        if (!value || value === '#') return '#';
        if (/^(https?:\/\/|\/\/|@|\[)/i.test(value)) return value;
        value = value.replace(/\\/g, '/');
        var queryIndex = value.indexOf('?');
        if (queryIndex >= 0) value = value.substring(0, queryIndex);
        value = value.replace(/^\/+|\/+$/g, '').replace(/\.[a-z0-9]+$/i, '');
        var parts = value.split('/').filter(Boolean);
        if (!parts.length) return '#';
        if (parts.length === 1) parts.push('index', 'index');
        else if (parts.length === 2) parts.push('index');
        if (parts[0]) parts[0] = parts[0].toLowerCase();
        if (parts[1]) parts[1] = parts[1].replace(/([a-z0-9])([A-Z])/g, '$1_$2').toLowerCase();
        if (parts[2]) parts[2] = parts[2].toLowerCase();
        return parts.join('/');
    };
    var syncIconPreview = function () {
        var value = $.trim(String($('[name="icon"]').val() || ''));
        var preview = $('[data-menu-icon-preview]').get(0);
        if (preview) {
            preview.className = value || 'layui-icon layui-icon-set-fill';
        }
        $('[data-menu-icon-preview-text]').text(value || menuI18n.iconNotSet);
        $('[data-menu-icon-picker-text]').val(value || '').attr('title', value || '');
    };
    var syncTargetPreview = function () {
        var url = $.trim(String($('[name="url"]').val() || ''));
        var params = $.trim(String($('[name="params"]').val() || '')).replace(/^[?&]+/, '');
        var target = normalizeTarget(url);
        if (target !== '#' && params) {
            target += (/^(https?:\/\/|\/\/)/i.test(target) && target.indexOf('?') >= 0 ? '&' : '?') + params;
        }
        $('[data-menu-url-preview]').val(target || '#');

        var node = normalizeNode($('[name="node"]').val());
        if (!node && target !== '#' && !/^(https?:\/\/|\/\/|@|\[)/i.test(target)) {
            node = target.split('?')[0].split('/').slice(0, 3).join('/');
        }
        $('[data-menu-node-preview]').val(node || menuI18n.nodeNotExplicitlySet);
    };
    $('body').off('click', '[data-open-menu-icon]').on('click', '[data-open-menu-icon]', function () {
        if (!iconPickerUrl) return false;
        $.form.modal(iconPickerUrl + (iconPickerUrl.indexOf('?') >= 0 ? '&' : '?') + 'field=' + encodeURIComponent('icon'), {}, menuI18n.chooseMenuIcon, undefined, undefined, undefined, '900px');
        return false;
    });
    $('[name="icon"]').on('change input', syncIconPreview);
    $('[name="url"], [name="params"], [name="node"]').on('change input', syncTargetPreview);
    $('input[name=url]').autocompleter({
        limit: 6,
        highlightMatches: true,
        template: '{{ label }} <span> {{ title }} </span>',
        callback: function (node) {
            if (!$('input[name=node]').val()) $('input[name=node]').val(node).trigger('change');
        },
        source: (function (subjects, data) {
            for (var i in subjects) data.push({value: subjects[i].node, label: subjects[i].node, title: subjects[i].title});
            return data;
        })(menuFormNodes, [])
    });
    $('input[name=node]').autocompleter({
        limit: 5,
        highlightMatches: true,
        template: '{{ label }} <span> {{ title }} </span>',
        source: (function (subjects, data) {
            for (var i in subjects) data.push({value: subjects[i].node, label: subjects[i].node, title: subjects[i].title});
            return data;
        })(menuFormAuths, [])
    });
    syncIconPreview();
    syncTargetPreview();
});
SCRIPT, self::encodeJson([
            'iconNotSet' => BuilderLang::text('未设置图标'),
            'nodeNotExplicitlySet' => BuilderLang::text('未显式设置'),
            'chooseMenuIcon' => BuilderLang::text('选择菜单图标'),
        ]));
    }

    private static function escape(string $content): string
    {
        return htmlentities($content, ENT_QUOTES, 'UTF-8');
    }
}
