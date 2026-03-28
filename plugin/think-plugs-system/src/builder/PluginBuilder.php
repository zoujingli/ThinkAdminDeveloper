<?php

declare(strict_types=1);

namespace plugin\system\builder;

use think\admin\builder\BuilderLang;
use think\admin\builder\page\PageBuilder;
use think\admin\builder\page\PageNode;
use think\admin\builder\page\module\PageModules;

/**
 * 插件中心页面构建器。
 * @class PluginBuilder
 */
class PluginBuilder
{
    /**
     * @param array<string, mixed> $context
     */
    public static function buildIndexPage(array $context): PageBuilder
    {
        $items = array_values(is_array($context['items'] ?? null) ? $context['items'] : []);
        $summary = array_values(is_array($context['summary'] ?? null) ? $context['summary'] : []);
        $overview = array_values(is_array($context['overview'] ?? null) ? $context['overview'] : []);

        return PageBuilder::domPage()
            ->define(function ($page) use ($items, $summary, $overview) {
                $page->title('插件应用中心')
                    ->contentClass('')
                    ->buttons(function ($buttons) {
                        $buttons->modal('中心配置', sysuri('system/config/system'), '中心配置')
                            ->open('系统参数', sysuri('system/config/index'));
                    });

                PageModules::hero($page, [
                    'class' => 'layui-card mb15',
                    'eyebrow' => 'SYSTEM / Plugin Center',
                    'title' => '插件应用中心',
                    'description' => '统一查看已接入应用与扩展插件，工作台入口、菜单呈现和鉴权规则全部收敛到系统配置与注释式 RBAC 模型。',
                    'stats' => $summary,
                ]);

                PageModules::card($page, [
                    'title' => '接入概览',
                    'remark' => '插件中心当前运行策略',
                    'class' => 'layui-card mb15',
                ], function (PageNode $body) use ($overview) {
                    PageModules::paragraphs($body, [
                        '插件中心只负责统一入口与工作台容器，不再承载旧式散落配置；菜单是否可见、能否进入，均按当前插件声明和权限节点动态计算。',
                    ], ['class' => 'ta-desc mt0']);
                    PageModules::kvGrid($body, $overview);
                });

                PageModules::card($page, [
                    'title' => '插件列表',
                    'remark' => BuilderLang::format('当前共有 %d 个可进入工作台的应用', [count($items)]),
                    'class' => 'layui-card',
                ], function (PageNode $body) use ($items) {
                    PageModules::pluginCenterCards($body, $items);
                });

                $page->script(<<<'SCRIPT'
$(function () {
    $('body').off('click', '[data-plugs-click]').on('click', '[data-plugs-click]', function () {
        $('#p' + (this.dataset.plugsClick || 'plugin-encode')).trigger('click');
    });
});
SCRIPT);
            })
            ->build();
    }

    public static function buildDisabledPage(): PageBuilder
    {
        return PageBuilder::domPage()
            ->define(function ($page) {
                $page->title('插件应用中心')
                    ->contentClass('')
                    ->buttons(function ($buttons) {
                        $buttons->modal('系统参数配置', sysuri('system/config/system'), '系统参数配置');
                    });

                PageModules::hero($page, [
                    'class' => 'layui-card mb15',
                    'eyebrow' => 'SYSTEM / Plugin Center',
                    'title' => '插件中心已禁用',
                    'description' => '当前入口已并入系统管理，可在“系统参数配置”中重新启用，或继续保留为隐藏入口模式。',
                    'stats' => [
                        ['label' => '中心状态', 'value' => '已禁用'],
                        ['label' => '入口位置', 'value' => 'SYSTEM / 插件中心'],
                    ],
                ]);

                PageModules::card($page, [
                    'title' => '处理建议',
                    'class' => 'layui-card',
                ], function (PageNode $body) {
                    PageModules::paragraphs($body, [
                        '如需重新开放插件入口，请前往系统参数中的“插件中心状态”切换为启用。',
                        '如只想隐藏左侧菜单，可保留启用状态，再将“菜单显示策略”调整为隐藏入口。',
                    ]);
                });
            })
            ->build();
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function buildErrorPage(array $context): PageBuilder
    {
        $content = trim(strval($context['content'] ?? ''));
        $returnUrl = strval($context['returnUrl'] ?? sysuri('system/plugin/index'));

        return PageBuilder::domPage()
            ->define(function ($page) use ($content, $returnUrl) {
                $page->title('插件页面暂时无法打开')
                    ->contentClass('')
                    ->buttons(function ($buttons) use ($returnUrl) {
                        $buttons->open('返回插件中心', $returnUrl);
                    });

                PageModules::hero($page, [
                    'class' => 'layui-card mb15',
                    'eyebrow' => 'SYSTEM / Plugin Workbench',
                    'title' => '插件页面暂时无法打开',
                    'description' => $content === '' ? '当前插件页面暂时不可用，请稍后再试。' : $content,
                    'stats' => [
                        ['label' => '处理方式', 'value' => '返回插件中心'],
                        ['label' => '工作台容器', 'value' => '系统统一插件布局'],
                    ],
                ]);
                PageModules::card($page, [
                    'title' => '排查建议',
                    'class' => 'layui-card',
                ], function (PageNode $body) {
                    PageModules::paragraphs($body, [
                        '确认插件已经安装、启用，并且当前账号具备至少一个可见菜单节点。',
                        '如果插件菜单依赖注释式 RBAC 节点，请同步检查节点声明与角色授权是否一致。',
                    ]);
                });
            })
            ->build();
    }
}
