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

namespace plugin\system\service;

use plugin\system\model\SystemBase;
use think\admin\helper\PageBuilder;

/**
 * 数据字典页面构建器服务.
 * @class BaseBuilder
 */
class BaseBuilder
{
    /**
     * 构建数据字典列表页面.
     * @param string $type 当前类型
     * @param array $types 类型列表
     * @param array $pluginGroups 插件分组
     * @param string $requestUrl 请求 URL
     * @return PageBuilder
     */
    public static function buildIndexPage(string $type, array $types, array $pluginGroups, string $requestUrl): PageBuilder
    {
        return PageBuilder::mk()
            ->setTitle('数据字典管理')
            ->setContentClass('')
            ->withSearchLegend(false)
            ->setTable('BaseTable', $requestUrl)
            ->setSearchAttrs(['action' => $requestUrl])
            ->setTableOptions([
                'even' => true,
                'height' => 'full',
                'sort' => ['field' => 'sort desc,id', 'type' => 'asc'],
            ])
            ->addModalButton('添加数据', url('add', ['type' => $type])->build(), '', ['data-table-id' => 'BaseTable'], 'add')
            ->addBatchActionButton('批量删除', url('remove')->build(), 'id#{id}', '确定要批量删除数据吗？', [], 'remove')
            ->addBeforeTableHtml(self::renderTypeTabs())
            ->addAfterTableHtml('</div></div>')
            ->addSearchHidden('type', $type)
            ->addSearchInput('code', '数据编码', '请输入数据编码')
            ->addSearchInput('name', '数据名称', '请输入数据名称')
            ->addSearchSelect('status', '使用状态', [0 => '已禁用记录', 1 => '已激活记录'])
            ->addSearchSelect('plugin_group', '所属插件', self::buildPluginGroupOptions($pluginGroups))
            ->addSearchDateRange('create_time', '创建时间', '请选择创建时间')
            ->addCheckboxColumn()
            ->addColumn(['field' => 'sort', 'title' => '排序权重', 'width' => 100, 'align' => 'center', 'sort' => true, 'templet' => '#SortInputTpl'])
            ->addColumn(['field' => 'code', 'title' => '数据编码', 'width' => '20%', 'align' => 'left'])
            ->addColumn(['field' => 'name', 'title' => '数据名称', 'width' => '30%', 'align' => 'left'])
            ->addColumn(['field' => 'plugin_title', 'title' => '所属插件', 'minWidth' => 130, 'align' => 'center', 'templet' => '#PluginBaseTableTpl'])
            ->addColumn(['field' => 'status', 'title' => '数据状态', 'minWidth' => 110, 'align' => 'center', 'templet' => '#StatusSwitchTpl'])
            ->addColumn(['field' => 'create_time', 'title' => '创建时间', 'minWidth' => 170, 'align' => 'center', 'sort' => true])
            ->addRowModalAction('编辑', url('edit')->build() . '?id={{d.id}}', '编辑数据', [], 'edit')
            ->addRowActionButton('删除', url('remove')->build(), 'id#{{d.id}}', '确定要删除数据吗？', [], 'remove')
            ->addToolbarColumn('数据操作', ['minWidth' => 150])
            ->addTemplate('SortInputTpl', '<input type="number" min="0" data-blur-number="0" data-action-blur="{:sysuri()}" data-value="id#{{d.id}};action#sort;sort#{value}" data-loading="false" value="{{d.sort}}" class="layui-input text-center">')
            ->addTemplate('PluginBaseTableTpl', self::renderPluginTemplate())
            ->addTemplate('StatusSwitchTpl', '<!--{if auth("state")}--><input type="checkbox" value="{{d.id}}" lay-skin="switch" lay-text="已激活 | 已禁用" lay-filter="StatusSwitch" {{-d.status>0?\'checked\':\'\'}}><!--{else}-->{{-d.status ? \'<b class="color-green">已启用</b>\' : \'<b class="color-red">已禁用</b>\'}}<!--{/if}-->')
            ->addScript(<<<'SCRIPT'
layui.form.on('switch(StatusSwitch)', function (obj) {
    var data = {id: obj.value, status: obj.elem.checked > 0 ? 1 : 0};
    $.form.load("state", data, "post", function (ret) {
        if (ret.code < 1) $.msg.error(ret.info, 3, function () {
            $("#BaseTable").trigger("reload");
        });
        return false;
    }, false);
});
SCRIPT);
    }

    /**
     * 构建类型选项.
     * @param array $types 类型列表
     * @return array
     */
    public static function buildTypeOptions(array $types): array
    {
        $typeOptions = [];
        foreach ($types as $type) {
            $typeOptions[$type] = $type;
        }
        $typeOptions['--- 新增类型 ---'] = '--- 新增类型 ---';
        return $typeOptions;
    }

    /**
     * 构建插件选项.
     * @return array
     */
    public static function buildPluginOptions(): array
    {
        $options = [];
        foreach (SystemBase::pluginOptions() as $plugin) {
            $code = strval($plugin['code'] ?? '');
            $name = strval($plugin['name'] ?? $code);
            if ($code !== '') {
                $options[$code] = "{$name} [ {$code} ]";
            }
        }
        return $options;
    }

    /**
     * 构建插件分组选项.
     * @param array $groups 插件分组
     * @return array
     */
    private static function buildPluginGroupOptions(array $groups): array
    {
        $options = [];
        foreach ($groups as $group) {
            $code = strval($group['code'] ?? '');
            if ($code !== '') {
                $options[$code] = strval($group['name'] ?? $code);
            }
        }
        return $options;
    }

    /**
     * 渲染类型标签页.
     * @return string
     */
    private static function renderTypeTabs(): string
    {
        return <<<'HTML'
<div class="layui-tab layui-tab-card">
    <ul class="layui-tab-title">
        {foreach $types as $t}{if isset($type) and $type eq $t}
        <li class="layui-this" data-open="{:sysuri()}?type={$t}">{$t}</li>
        {else}
        <li data-open="{:sysuri()}?type={$t}">{$t}</li>
        {/if}{/foreach}
    </ul>
    <div class="layui-tab-content">
HTML;
    }

    /**
     * 渲染插件模板.
     * @return string
     */
    private static function renderPluginTemplate(): string
    {
        return <<<'HTML'
{{# if(d.plugin_group === 'mixed'){ }}
<span class="layui-badge layui-bg-orange">{{ d.plugin_title || '跨插件' }}</span>
{{# } else if(d.plugin_group === 'common') { }}
<span class="layui-badge layui-bg-gray">{{ d.plugin_title || '未绑定' }}</span>
{{# } else { }}
<span class="layui-badge layui-bg-blue">{{ d.plugin_title || '-' }}</span>
{{# } }}
{{# if(d.plugin_text && d.plugin_count > 1){ }}
<div class="color-desc nowrap">{{ d.plugin_text }}</div>
{{# } }}
HTML;
    }
}
