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

        return PageBuilder::make()
            ->define(function ($page) use ($context, $type, $pid, $requestBaseUrl, $stateUrl, $sortActionUrl) {
                $page->title(strval($context['title'] ?? '系统菜单管理'))
                    ->contentClass('')
                    ->showSearchLegend(false)
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
                    $table->toolbarId('MenuToolbarTpl')
                        ->options([
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
                        ->column(['field' => 'status', 'title' => '使用状态', 'minWidth' => 120, 'align' => 'center', 'templet' => '#MenuStatusSwitchTpl'])
                        ->toolbar('操作面板', SystemTablePreset::toolbar('操作面板'))
                        ->template('MenuStatusSwitchTpl', self::renderStatusTemplate($type))
                        ->template('MenuToolbarTpl', $type === 'index'
                            ? self::renderIndexToolbarTemplate(url('add')->build(), url('edit')->build())
                            : self::renderRecycleToolbarTemplate(url('remove')->build()))
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
        return FormBuilder::make()
            ->define(function ($form) use ($context) {
                $form->action(strval($context['actionUrl'] ?? ''))
                    ->attrs(['id' => 'MenuForm', 'data-table-id' => 'MenuTable'])
                    ->bodyClass('pa20')
                    ->fields(function ($fields) use ($context) {
                        $fields->select('pid', '上级菜单', 'Parent Menu', true, '请选择上级菜单或顶级菜单（目前最多支持三级菜单）', self::buildParentOptions(is_array($context['menus'] ?? null) ? $context['menus'] : []), '', ['lay-search' => null])
                            ->text('title', '菜单名称', 'Menu Title', true, '请填写菜单名称（如：系统管理），建议字数不要太长，一般 4-6 个汉字）', null, [
                                'placeholder' => '请输入菜单名称',
                                'required-error' => '菜单名称不能为空！',
                            ])
                            ->text('url', '菜单链接', 'Menu Url', true, '请填写链接地址或选择系统节点（如：https://domain.com/system/user/index.html 或 system/user/index）', null, [
                                'placeholder' => '请输入菜单链接',
                                'required-error' => '菜单链接不能为空！',
                            ])
                            ->text('params', '链接参数', 'Query Params', false, '设置菜单链接的 GET 访问参数（如：name=1&age=3）', null, [
                                'placeholder' => '请输入链接参数',
                            ])
                            ->text('node', '权限节点', 'Permission Node', false, '请填写系统权限节点（如：system/user/index），未填写时默认根据“菜单链接”判断是否拥有访问权限', null, [
                                'placeholder' => '请输入权限节点',
                            ])
                            ->text('icon', '菜单图标', 'Menu Icon', false, '设置菜单选项前置图标，目前支持 layui 字体图标和 iconfont 自定义字体图标', null, [
                                'placeholder' => '请输入或选择图标',
                            ])
                            ->select('target', '打开方式', 'Target Window', true, '设置菜单链接的打开方式。', [
                                '_self' => '当前窗口',
                                '_blank' => '新窗口',
                            ])
                            ->text('sort', '排序权重', 'Sort Order', false, '数值越大越靠前。', null, [
                                'type' => 'number',
                                'min' => 0,
                                'placeholder' => '请输入排序权重',
                            ])
                            ->select('status', '使用状态', 'Status', true, '请选择当前菜单的启用状态。', [
                                1 => '已启用',
                                0 => '已禁用',
                            ]);
                    });

                $form->div()->html('<input type="hidden" name="id" value="{$vo.id|default=\'\'}">');
                $form->div()->html(sprintf(
                    '<script type="application/json" id="MenuFormNodesJson">%s</script><script type="application/json" id="MenuFormAuthsJson">%s</script>',
                    self::encodeJson(is_array($context['nodes'] ?? null) ? $context['nodes'] : []),
                    self::encodeJson(is_array($context['auths'] ?? null) ? $context['auths'] : [])
                ));
                $form->actions(function ($actions) {
                    $actions->submit()->cancel();
                })->rules([
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
    private static function renderStatusTemplate(string $type): string
    {
        return sprintf(<<<'HTML'
{{# if( "%s"==='index' || (d.spc<1 || d.status<1)){ }}
<input type="checkbox" value="{{d.sps}}|{{d.spp}}" lay-text="已激活|已禁用" lay-filter="MenuStatusSwitch" lay-skin="switch" {{-d.status>0?'checked':''}}>
{{# }else{ }}
{{-d.status ? '<b class="color-green">已激活</b>' : '<b class="color-red">已禁用</b>'}}
{{# } }}
HTML, addslashes($type));
    }

    /**
     * 渲染列表工具条模板.
     */
    private static function renderIndexToolbarTemplate(string $addUrl, string $editUrl): string
    {
        return sprintf(<<<'HTML'
{{# if(d.spt<2){ }}
<a class="layui-btn layui-btn-sm layui-btn-primary" data-title="添加系统菜单" data-modal="%s?pid={{d.id}}">添加</a>
{{# }else{ }}
<a class="layui-btn layui-btn-sm layui-btn-disabled">添加</a>
{{# } }}
<a class="layui-btn layui-btn-sm" data-title="编辑系统菜单" data-modal="%s?id={{d.id}}">编辑</a>
HTML, addslashes($addUrl), addslashes($editUrl));
    }

    /**
     * 渲染回收站工具条模板.
     */
    private static function renderRecycleToolbarTemplate(string $removeUrl): string
    {
        return sprintf(<<<'HTML'
{{# if( (d.spc<1 || d.status<1)){ }}
<a class="layui-btn layui-btn-sm layui-btn-danger" data-confirm="确定要删除菜单吗？" data-action="%s" data-value="id#{{d.sps}}">删除</a>
{{# }else{ }}
<a class="layui-btn layui-btn-disabled layui-btn-sm">删除</a>
{{# } }}
HTML, addslashes($removeUrl));
    }

    private static function encodeJson(mixed $value): string
    {
        return strval(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT));
    }

    private static function renderFormScript(): string
    {
        return <<<'SCRIPT'
$.module.use(['jquery.autocompleter'], function () {
    var menuFormNodes = JSON.parse(((document.getElementById('MenuFormNodesJson') || {}).text || '[]'));
    var menuFormAuths = JSON.parse(((document.getElementById('MenuFormAuthsJson') || {}).text || '[]'));
    $('[name="icon"]').on('change', function () {
        $(this).closest('[data-field-name="icon"]').find('[data-menu-icon-preview]').get(0).className = this.value;
    });
    $('input[name=url]').autocompleter({
        limit: 6,
        highlightMatches: true,
        template: '{{ label }} <span> {{ title }} </span>',
        callback: function (node) {
            $('input[name=node]').val(node);
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
});
SCRIPT;
    }
}
