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
use think\admin\builder\page\PageBuilder;

/**
 * 系统权限列表视图构建器.
 * @class AuthBuilder
 */
class AuthBuilder
{
    /**
     * 渲染权限列表页.
     * @param array<string, mixed> $context
     */
    public static function renderIndex(array $context): void
    {
        self::buildIndexPage($context)->fetch($context);
    }

    /**
     * 渲染权限表单页.
     * @param array<string, mixed> $context
     * @param array<string, mixed> $data
     */
    public static function renderForm(array $context, array $data): void
    {
        $builder = self::buildForm($context);
        self::renderBuiltForm($builder, $context, $data);
    }

    /**
     * 渲染已构建的权限表单页.
     * @param array<string, mixed> $context
     * @param array<string, mixed> $data
     */
    public static function renderBuiltForm(FormBuilder $builder, array $context, array $data): void
    {
        $builder->fetch(array_merge($context, ['vo' => $data]));
    }

    /**
     * 构建权限列表页.
     * @param array<string, mixed> $context
     */
    public static function buildIndexPage(array $context): PageBuilder
    {
        $type = strval($context['type'] ?? 'index');
        $requestBaseUrl = strval($context['requestBaseUrl'] ?? '');
        $pluginGroup = trim(strval($context['pluginGroup'] ?? ''));
        $pluginGroupOptions = is_array($context['pluginGroupOptions'] ?? null) ? $context['pluginGroupOptions'] : [];

        return PageBuilder::make()
            ->define(function ($page) use ($context, $type, $requestBaseUrl, $pluginGroup, $pluginGroupOptions) {
                $page->title(strval($context['title'] ?? '系统权限管理'))
                    ->contentClass('')
                    ->showSearchLegend(false)
                    ->buttons(function ($buttons) use ($type, $pluginGroup) {
                        if ($type === 'index') {
                            $buttons->open('添加权限', self::buildAddUrl($pluginGroup), ['data-table-id' => 'RoleTable'], 'add')
                                ->batchAction('批量禁用', url('state')->build(), 'id#{id};status#0', '确定要禁用这些权限吗？', [], 'state');
                        } else {
                            $buttons->batchAction('批量恢复', url('state')->build(), 'id#{id};status#1', '确定要恢复这些权限吗？', [], 'state')
                                ->batchAction('批量删除', url('remove')->build(), 'id#{id}', '确定要永久删除这些权限吗？', [], 'remove');
                        }
                    });

                $page->tabsList(SystemListTabs::indexRecycle($type, url('index')->build(), '系统权限'), 'RoleTable', $requestBaseUrl, function ($search) use ($type, $pluginGroupOptions) {
                    $search->hidden('type', $type)
                        ->input('title', '权限名称', '请输入权限名称')
                        ->input('desc', '权限描述', '请输入权限描述')
                        ->select('plugin_group', '所属插件', ['' => '全部插件'] + $pluginGroupOptions);
                    $search->dateRange('create_time', '创建时间', '请选择创建时间');
                }, function ($table) use ($requestBaseUrl, $type) {
                        $table->options([
                            'even' => true,
                            'height' => 'full',
                            'sort' => ['field' => 'sort desc,id', 'type' => 'desc'],
                        ])->checkbox()
                            ->sortInput($requestBaseUrl)
                            ->column(SystemTablePreset::textColumn('title', '权限名称', 140, 'left'))
                            ->column(SystemTablePreset::pluginColumn('plugin_title', '所属插件', 140))
                            ->column(SystemTablePreset::textColumn('desc', '权限描述', 110))
                            ->statusSwitch(url('state')->build(), SystemTablePreset::statusOptions())
                            ->column(SystemTablePreset::timeColumn())
                            ->rows(function ($rows) use ($type) {
                                $rows->open('编 辑', url('edit')->build() . '?id={{d.id}}{{ d.plugin_group && ["mixed","common"].indexOf(d.plugin_group) < 0 ? "&plugin=" + d.plugin_group : "" }}', '', ['class' => 'layui-btn-primary'], 'edit');
                                if ($type === 'recycle') {
                                    $rows->action('删 除', url('remove')->build(), 'id#{{d.id}}', '确定要永久删除权限吗?', [], 'remove');
                                }
                            })
                            ->toolbar('操作面板', SystemTablePreset::toolbar('操作面板', 210));
                });
            })
            ->build();
    }

    /**
     * 构建权限表单定义.
     * @param array<string, mixed> $context
     */
    public static function buildForm(array $context): FormBuilder
    {
        return FormBuilder::make('form', 'page')
            ->define(function ($form) use ($context) {
                $form->title(strval($context['title'] ?? '系统权限编辑'))
                    ->headerButton('返回列表', 'button', '', ['data-target-backup' => null], 'layui-btn-primary layui-btn-sm')
                    ->action(strval($context['actionUrl'] ?? ''))
                    ->attrs(['id' => 'RoleForm'])
                    ->fields(function ($fields) {
                        $fields->text('title', '权限名称', 'Auth Name', true, '访问权限名称需要保持不重复，在给用户授权时需要根据名称选择！', null, [
                            'maxlength' => 100,
                            'placeholder' => '请输入权限名称',
                            'required-error' => '权限名称不能为空！',
                        ])->textarea('desc', '权限描述', 'Auth Remark', false, '请输入权限描述', [
                            'maxlength' => 200,
                            'placeholder' => '请输入权限描述',
                        ]);
                    });

                FormBlocks::selectFilter(
                    $form,
                    'plugin_filter',
                    'AuthPluginFilter',
                    [],
                    '插件筛选',
                    'Plugin Filter',
                    '仅切换当前显示的权限树分组，不会丢失其它插件已勾选的授权节点。'
                );

                $tree = $form->div()->class('layui-form-item');
                $tree->html('<span class="help-label label-required-prev"><b>功能节点</b>Auth Nodes</span>');
                $tree->div()->class('system-auth-tree')->html('<div class="auth-tree-toolbar"><div class="auth-tree-search"><input type="text" class="layui-input" id="AuthTreeKeyword" placeholder="搜索权限节点名称"></div><div class="auth-tree-toolbar-group auth-tree-toolbar-group-mode"><button type="button" class="layui-btn layui-btn-xs layui-btn-primary auth-tree-mode" id="AuthTreeSelectedOnly" data-selected-only="false">只看已选</button><button type="button" class="layui-btn layui-btn-xs layui-btn-primary auth-tree-ghost" data-tree-action="expand-visible">展开全部分组</button><button type="button" class="layui-btn layui-btn-xs layui-btn-primary auth-tree-ghost" data-tree-action="collapse-visible">收起未选分组</button></div><div class="auth-tree-toolbar-group auth-tree-toolbar-group-batch"><button type="button" class="layui-btn layui-btn-xs layui-btn-normal" data-tree-action="select-visible">全选当前视图</button><button type="button" class="layui-btn layui-btn-xs layui-btn-primary auth-tree-danger" data-tree-action="clear-visible">取消当前视图</button></div></div><div id="AuthTreePanel" class="auth-tree-panel"></div>');
                $form->html(self::renderFormStyle());
                $form->div()->html('<input type="hidden" name="id" value="{$vo.id|default=\'\'}">');
                $form->actions(function ($actions) {
                    $actions->submit()->cancel();
                })->rules([
                        'title.max:100' => '权限名称不能超过100字符！',
                        'desc.max:200' => '权限描述不能超过200字符！',
                    ])
                    ->script(self::renderFormScript());
            })
            ->build();
    }

    /**
     * 构建新增地址.
     */
    private static function buildAddUrl(string $pluginGroup): string
    {
        if ($pluginGroup !== '' && !in_array($pluginGroup, ['mixed', 'common'], true)) {
            return url('add', ['plugin' => $pluginGroup])->build();
        }
        return url('add')->build();
    }

    private static function renderFormScript(): string
    {
        return <<<'SCRIPT'
$.module.use([], function () {
    new function () {
        let that = this;
        this.storageKey = 'system-auth-tree-state';
        this.data = [];
        this.states = {};
        this.initialStates = {};
        this.expanded = {};
        this.filter = '{$plugin|default=""}';
        this.keyword = '';
        this.selectedOnly = false;
        this.actionUrl = '{$actionUrl|default=""}';
        this.escape = function (text) {
            return String(text || '').replace(/[&<>"']/g, function (char) {
                return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[char];
            });
        };
        this.loadViewState = function () {
            try {
                let cache = JSON.parse(localStorage.getItem(this.storageKey) || '{}');
                this.filter = String(cache.filter || this.filter || '');
                this.keyword = String(cache.keyword || '');
                this.selectedOnly = !!cache.selectedOnly;
                this.expanded = typeof cache.expanded === 'object' && cache.expanded ? cache.expanded : {};
            } catch (e) {
                this.expanded = {};
            }
        };
        this.saveViewState = function () {
            try {
                localStorage.setItem(this.storageKey, JSON.stringify({
                    filter: this.filter,
                    keyword: this.keyword,
                    selectedOnly: this.selectedOnly,
                    expanded: this.expanded
                }));
            } catch (e) {
            }
        };
        this.normalizeChildren = function (list, plugin) {
            let children = [];
            for (let i in list) {
                let item = list[i], code = item.plugin || plugin || item.node || '';
                if (item.node && item.checked) {
                    this.states[item.node] = true;
                    this.initialStates[item.node] = true;
                }
                children.push({
                    plugin: code,
                    node: item.node || '',
                    title: item.title || item.node || '',
                    children: this.normalizeChildren(item._sub_ || [], code)
                });
            }
            return children;
        };
        this.renderOptions = function () {
            let html = ['<option value="">全部插件</option>'];
            for (let i in this.data) {
                if (!this.data[i].plugin) continue;
                html.push('<option value="' + this.data[i].plugin + '">' + this.data[i].title + ' [ ' + this.data[i].plugin + ' ]</option>');
            }
            $('[name=plugin_filter]').html(html.join('')).val(this.filter);
            layui.form.render('select');
        };
        this.collectDescendants = function (node) {
            let values = [];
            if (node.node) values.push(node.node);
            for (let i in (node.children || [])) {
                values = values.concat(this.collectDescendants(node.children[i]));
            }
            return values;
        };
        this.collectLeafMap = function (list, results) {
            results = results || {};
            for (let i in list) {
                let item = list[i];
                let children = item.children || [];
                if (children.length < 1) {
                    results[item.node] = item.title || item.node || '';
                    continue;
                }
                this.collectLeafMap(children, results);
            }
            return results;
        };
        this.setListState = function (list, checked) {
            for (let i in list) {
                this.setBranchState(list[i], checked);
            }
        };
        this.filterTree = function (list) {
            let keyword = $.trim(this.keyword).toLowerCase();
            if (!keyword) return list;
            let results = [];
            for (let i in list) {
                let item = $.extend(true, {}, list[i]);
                item.children = this.filterTree(item.children || []);
                let title = String(item.title || '').toLowerCase();
                let matched = title.indexOf(keyword) >= 0 || String(item.node || '').toLowerCase().indexOf(keyword) >= 0;
                if (matched || item.children.length > 0) results.push(item);
            }
            return results;
        };
        this.filterSelectedTree = function (list) {
            if (!this.selectedOnly) return list;
            let results = [];
            for (let i in list) {
                let item = $.extend(true, {}, list[i]);
                item.children = this.filterSelectedTree(item.children || []);
                let status = this.computeStatus(item);
                if (status.checked || status.indeterminate || item.children.length > 0) {
                    results.push(item);
                }
            }
            return results;
        };
        this.computeStatus = function (node) {
            let checked = !!this.states[node.node];
            let children = node.children || [];
            if (children.length < 1) {
                return {checked: checked, indeterminate: false};
            }
            let all = true, any = checked;
            for (let i in children) {
                let child = this.computeStatus(children[i]);
                if (!child.checked || child.indeterminate) all = false;
                if (child.checked || child.indeterminate) any = true;
            }
            return {checked: checked || all, indeterminate: any && !(checked || all)};
        };
        this.shouldExpand = function (node) {
            if (Object.prototype.hasOwnProperty.call(this.expanded, node.node)) {
                return !!this.expanded[node.node];
            }
            let status = this.computeStatus(node);
            return !!status.checked || !!status.indeterminate;
        };
        this.setBranchState = function (node, checked) {
            let values = this.collectDescendants(node);
            for (let i in values) this.states[values[i]] = checked;
        };
        this.updateParentState = function (node) {
            let children = node.children || [];
            if (children.length < 1) return;
            let all = true;
            for (let i in children) {
                let child = this.computeStatus(children[i]);
                if (!child.checked || child.indeterminate) {
                    all = false;
                    break;
                }
            }
            this.states[node.node] = all;
        };
        this.updateAncestors = function (path) {
            for (let i = path.length - 1; i >= 0; i--) {
                this.updateParentState(path[i]);
            }
        };
        this.renderLeaf = function (node, path) {
            let key = this.escape(node.node);
            let title = this.escape(node.title);
            let status = this.computeStatus(node);
            return '' +
                '<label class="auth-leaf-item">' +
                '<input type="checkbox" class="layui-input" data-node="' + key + '"' + (status.checked ? ' checked' : '') + '>' +
                '<span>' + title + '</span>' +
                '</label>';
        };
        this.renderGroup = function (node, path) {
            let status = this.computeStatus(node);
            let key = this.escape(node.node);
            let title = this.escape(node.title);
            let children = node.children || [];
            let expanded = this.shouldExpand(node);
            let html = '' +
                '<section class="auth-group' + (expanded ? ' is-expanded' : ' is-collapsed') + '">' +
                '<div class="auth-group-head">' +
                '<span class="auth-group-toggle">' +
                '<input type="checkbox" class="layui-input" data-node="' + key + '"' + (status.checked ? ' checked' : '') + (status.indeterminate ? ' data-indeterminate="true"' : '') + '>' +
                '</span>' +
                '<button type="button" class="auth-group-title" data-expand-node="' + key + '">' + title + '</button>' +
                '<div class="auth-group-actions">' +
                '<button type="button" class="auth-group-action" data-batch-node="' + key + '" data-batch-type="select">全选</button>' +
                '<button type="button" class="auth-group-action" data-batch-node="' + key + '" data-batch-type="clear">取消</button>' +
                '</div>' +
                '</div>';
            if (children.length > 0) {
                html += '<div class="auth-group-grid"' + (expanded ? '' : ' style="display:none"') + '>';
                for (let i in children) html += this.renderNode(children[i], path.concat(node));
                html += '</div>';
            }
            html += '</section>';
            return html;
        };
        this.renderNode = function (node, path) {
            if ((node.children || []).length < 1) return this.renderLeaf(node, path);
            return this.renderGroup(node, path);
        };
        this.renderTree = function (list) {
            list = this.filterTree(list);
            list = this.filterSelectedTree(list);
            let html = [];
            for (let i in list) {
                let item = list[i];
                if (this.filter && item.plugin !== this.filter) continue;
                let status = this.computeStatus(item);
                let expanded = this.shouldExpand(item);
                html.push(
                    '<section class="auth-plugin-card' + (expanded ? ' is-expanded' : ' is-collapsed') + '" data-plugin="' + this.escape(item.plugin) + '">' +
                    '<header class="auth-plugin-head">' +
                    '<span class="auth-plugin-toggle">' +
                    '<input type="checkbox" class="layui-input" data-node="' + this.escape(item.node) + '"' + (status.checked ? ' checked' : '') + (status.indeterminate ? ' data-indeterminate="true"' : '') + '>' +
                    '</span>' +
                    '<button type="button" class="auth-plugin-title" data-expand-node="' + this.escape(item.node) + '">' + this.escape(item.title) + '</button>' +
                    '<div class="auth-plugin-actions">' +
                    '<span class="auth-plugin-code">' + this.escape(item.plugin) + '</span>' +
                    '<button type="button" class="auth-group-action" data-batch-node="' + this.escape(item.node) + '" data-batch-type="select">全选</button>' +
                    '<button type="button" class="auth-group-action" data-batch-node="' + this.escape(item.node) + '" data-batch-type="clear">取消</button>' +
                    '</div>' +
                    '</header>' +
                    '<div class="auth-plugin-body"' + (expanded ? '' : ' style="display:none"') + '>' + $.map(item.children || [], function (child) { return that.renderNode(child, [item]); }).join('') + '</div>' +
                    '</section>'
                );
            }
            return html.join('');
        };
        this.syncData = function () {
            $.form.load(this.actionUrl, {id: '{$vo.id|default=0}', action: 'json'}, 'post', function (ret) {
                that.data = that.normalizeChildren(ret.data, '');
                that.renderOptions();
                return that.showTree(), false;
            });
        };
        this.findPath = function (list, target, parents) {
            parents = parents || [];
            for (let i in list) {
                let item = list[i];
                if (item.node === target) return {node: item, parents: parents};
                let found = this.findPath(item.children || [], target, parents.concat(item));
                if (found) return found;
            }
            return null;
        };
        this.applyCheckStates = function () {
            $('#AuthTreePanel input[type=checkbox][data-indeterminate=true]').each(function () {
                this.indeterminate = true;
            });
        };
        this.renderDiffTags = function (titles, values) {
            if (values.length < 1) {
                return '<div class="color-desc">无</div>';
            }
            let html = [];
            for (let i in values) {
                let node = values[i];
                html.push('<span class="auth-diff-tag">' + this.escape(titles[node] || node) + '</span>');
            }
            return html.join('');
        };
        this.buildSubmitDiff = function () {
            let titles = this.collectLeafMap(this.data, {});
            let current = [];
            let initial = [];
            for (let node in titles) {
                if (this.states[node]) current.push(node);
                if (this.initialStates[node]) initial.push(node);
            }
            current.sort();
            initial.sort();
            return {
                current: current,
                added: $.grep(current, function (node) { return initial.indexOf(node) < 0; }),
                removed: $.grep(initial, function (node) { return current.indexOf(node) < 0; }),
                titles: titles
            };
        };
        this.showTree = function () {
            let html = this.renderTree(this.data);
            $('#AuthTreePanel').html(html || '<div class="auth-tree-empty">当前筛选下暂无权限节点</div>');
            this.applyCheckStates();
            $('#AuthTreeKeyword').val(this.keyword);
            $('#AuthTreeSelectedOnly')
                .attr('data-selected-only', this.selectedOnly ? 'true' : 'false')
                .toggleClass('layui-btn-normal', this.selectedOnly)
                .toggleClass('layui-btn-primary', !this.selectedOnly);
            this.saveViewState();
        };
        this.loadViewState();
        this.syncData();
        $('#AuthTreeKeyword').on('input', function () {
            that.keyword = String(this.value || '');
            that.showTree();
        });
        $('#AuthTreeSelectedOnly').on('click', function () {
            that.selectedOnly = !that.selectedOnly;
            $(this)
                .attr('data-selected-only', that.selectedOnly ? 'true' : 'false')
                .toggleClass('layui-btn-normal', that.selectedOnly)
                .toggleClass('layui-btn-primary', !that.selectedOnly);
            that.showTree();
        });
        $('#AuthTreePanel').on('change', 'input[type=checkbox][data-node]', function () {
            let target = String(this.getAttribute('data-node') || '');
            let found = that.findPath(that.data, target, []);
            if (!found) return;
            that.setBranchState(found.node, this.checked);
            that.expanded[found.node.node] = true;
            that.updateAncestors(found.parents);
            that.showTree();
        });
        $('#AuthTreePanel').on('click', '[data-expand-node]', function () {
            let target = String(this.getAttribute('data-expand-node') || '');
            let found = that.findPath(that.data, target, []);
            if (!found) return;
            that.expanded[target] = !that.shouldExpand(found.node);
            that.showTree();
        });
        $('#AuthTreePanel').on('click', '[data-batch-node][data-batch-type]', function () {
            let target = String(this.getAttribute('data-batch-node') || '');
            let type = String(this.getAttribute('data-batch-type') || '');
            let found = that.findPath(that.data, target, []);
            if (!found) return;
            that.setBranchState(found.node, type === 'select');
            that.expanded[target] = true;
            that.updateAncestors(found.parents);
            that.showTree();
        });
        $('.system-auth-tree').on('click', '[data-tree-action]', function () {
            let action = String(this.getAttribute('data-tree-action') || '');
            let visible = that.filter ? $.grep(that.data, function (item) { return item.plugin === that.filter; }) : that.data.slice();
            if (action === 'select-visible') {
                that.setListState(visible, true);
            } else if (action === 'clear-visible') {
                that.setListState(visible, false);
            } else if (action === 'expand-visible') {
                let mark = function (list) {
                    for (let i in list) {
                        if ((list[i].children || []).length > 0) {
                            that.expanded[list[i].node] = true;
                            mark(list[i].children || []);
                        }
                    }
                };
                mark(visible);
            } else if (action === 'collapse-visible') {
                let mark = function (list) {
                    for (let i in list) {
                        if ((list[i].children || []).length > 0) {
                            let status = that.computeStatus(list[i]);
                            that.expanded[list[i].node] = !!status.checked || !!status.indeterminate;
                            mark(list[i].children || []);
                        }
                    }
                };
                mark(visible);
            }
            that.showTree();
        });
        $('#RoleForm').vali(function (form) {
            let diff = that.buildSubmitDiff();
            Object.assign(form, {nodes: diff.current, action: 'save'});
            let content = '' +
                '<div class="auth-submit-diff">' +
                '<div class="auth-submit-diff-head">本次权限变更</div>' +
                '<div class="auth-submit-diff-section">' +
                '<div class="auth-submit-diff-title">新增权限 (' + diff.added.length + ')</div>' +
                '<div class="auth-submit-diff-tags">' + that.renderDiffTags(diff.titles, diff.added) + '</div>' +
                '</div>' +
                '<div class="auth-submit-diff-section">' +
                '<div class="auth-submit-diff-title">移除权限 (' + diff.removed.length + ')</div>' +
                '<div class="auth-submit-diff-tags">' + that.renderDiffTags(diff.titles, diff.removed) + '</div>' +
                '</div>' +
                '</div>';
            layer.confirm(content, {title: '确认保存权限变更', area: ['720px', 'auto']}, function (index) {
                layer.close(index);
                $.form.load(that.actionUrl, form, 'post');
            });
        });

        layui.form.on('select(AuthPluginFilter)', function (object) {
            that.filter = object.value;
            that.showTree();
        });
    };
});
SCRIPT;
    }

    private static function renderFormStyle(): string
    {
        return <<<'HTML'
<style>
    .system-auth-tree {
        padding: 18px 20px;
        border: 1px solid #e8eef5;
        border-radius: 10px;
        background: #f8fbfd;
    }

    .auth-tree-panel {
        display: grid;
        gap: 16px;
    }

    .auth-tree-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 12px;
    }

    .auth-tree-toolbar-group {
        display: inline-flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .auth-tree-toolbar-group-mode {
        margin-left: auto;
    }

    .auth-tree-search {
        flex: 1;
        min-width: 240px;
    }

    .auth-tree-mode[data-selected-only="true"] {
        color: #fff;
        background: #0f766e;
        border-color: #0f766e;
    }

    .auth-tree-ghost {
        color: #475569;
        background: #fff;
        border-color: #d7e1ea;
    }

    .auth-tree-danger {
        color: #b42318;
        background: #fff;
        border-color: #f1c7c4;
    }

    .auth-submit-diff {
        padding-top: 6px;
    }

    .auth-submit-diff-head {
        margin-bottom: 12px;
        font-weight: 600;
        color: #334155;
    }

    .auth-submit-diff-section + .auth-submit-diff-section {
        margin-top: 12px;
    }

    .auth-submit-diff-title {
        margin-bottom: 8px;
        color: #475569;
        font-size: 12px;
    }

    .auth-submit-diff-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        max-height: 180px;
        overflow: auto;
    }

    .auth-diff-tag {
        display: inline-flex;
        align-items: center;
        padding: 4px 8px;
        color: #334155;
        font-size: 12px;
        border-radius: 999px;
        background: #f1f5f9;
    }

    .auth-tree-empty {
        padding: 18px;
        color: #6b7280;
        text-align: center;
        background: #fff;
        border-radius: 8px;
    }

    .auth-plugin-card {
        overflow: hidden;
        border: 1px solid #e5edf5;
        border-radius: 8px;
        background: #fff;
    }

    .auth-plugin-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 16px;
        background: linear-gradient(180deg, #ffffff 0%, #f7fafc 100%);
        border-bottom: 1px solid #eef3f8;
    }

    .auth-plugin-toggle,
    .auth-group-toggle,
    .auth-leaf-item {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        color: #1f2937;
    }

    .auth-plugin-title,
    .auth-group-title {
        padding: 0;
        color: #1f2937;
        font-weight: 600;
        text-align: left;
        border: none;
        background: transparent;
        cursor: pointer;
    }

    .auth-plugin-toggle {
        flex: 0 0 auto;
    }

    .auth-plugin-title {
        flex: 1 1 auto;
        justify-content: flex-start;
        margin-right: auto;
    }

    .auth-plugin-card.is-collapsed .auth-plugin-title::after,
    .auth-group.is-collapsed .auth-group-title::after {
        content: '展开';
        margin-left: 8px;
        color: #94a3b8;
        font-size: 12px;
        font-weight: 400;
    }

    .auth-plugin-card.is-expanded .auth-plugin-title::after,
    .auth-group.is-expanded .auth-group-title::after {
        content: '收起';
        margin-left: 8px;
        color: #94a3b8;
        font-size: 12px;
        font-weight: 400;
    }

    .auth-plugin-code {
        padding: 2px 8px;
        color: #0369a1;
        font-size: 12px;
        background: #e0f2fe;
        border-radius: 999px;
    }

    .auth-plugin-actions,
    .auth-group-actions {
        display: inline-flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }

    .auth-plugin-body {
        display: grid;
        gap: 14px;
        padding: 14px 16px 16px;
    }

    .auth-group {
        padding: 12px 14px;
        border: 1px solid #eef3f8;
        border-radius: 8px;
        background: #fbfdff;
    }

    .auth-group-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .auth-group-toggle {
        flex: 0 0 auto;
    }

    .auth-group-title {
        flex: 1 1 auto;
        justify-content: flex-start;
        margin-right: auto;
    }

    .auth-group-action {
        padding: 0;
        color: #0369a1;
        border: none;
        background: transparent;
        cursor: pointer;
    }

    .auth-plugin-title:hover,
    .auth-group-title:hover,
    .auth-group-action:hover,
    .auth-group-switch:hover {
        color: #0f766e;
    }

    .auth-group-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, max-content));
        gap: 8px 10px;
        margin-top: 12px;
        padding-left: 18px;
        align-items: start;
    }

    .auth-leaf-item {
        min-height: 30px;
        padding: 5px 10px;
        border: 1px solid #dbe7f3;
        border-radius: 999px;
        background: #fff;
        transition: border-color .2s ease, background-color .2s ease, box-shadow .2s ease;
    }

    .auth-leaf-item:hover {
        border-color: #93c5fd;
        background: #f8fbff;
    }

    .auth-leaf-item:has(input:checked) {
        border-color: #67b99a;
        background: #edf9f3;
        box-shadow: inset 0 0 0 1px rgba(0, 150, 136, 0.08);
    }

    .auth-plugin-toggle input,
    .auth-group-head input,
    .auth-leaf-item input {
        margin: 0;
    }

    .auth-leaf-item span {
        font-size: 12px;
        line-height: 18px;
        white-space: nowrap;
    }

    @media (max-width: 768px) {
        .auth-plugin-head {
            align-items: flex-start;
            flex-direction: column;
        }

        .auth-group-head {
            align-items: flex-start;
            flex-direction: column;
        }

        .auth-group-grid {
            grid-template-columns: 1fr;
            padding-left: 0;
        }
    }
</style>
HTML;
    }

}
