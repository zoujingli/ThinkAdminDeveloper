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

namespace think\admin\tests;

use PHPUnit\Framework\TestCase;
use think\admin\Controller;
use think\admin\builder\page\PageBuilder;
use think\admin\builder\page\module\PageModules;

/**
 * @internal
 * @coversNothing
 */
class PageBuilderTest extends TestCase
{
    public function testCanCollectSchemaAndRenderListPage()
    {
        $builder = $this->newBuilder();
        $builder->define(function ($page) {
            $page->title('短信管理')
                ->searchAttrs(['action' => '/message', 'class' => 'form-search search-panel'])
                ->buttons(function ($buttons) {
                    $buttons->modal('短信配置', '/config', '', [], 'config')
                        ->open('查看报表', '/report', [], 'report')
                        ->batchAction('批量删除', '/remove', 'id#{key}', '确定删除吗？', ['data-scene' => 'batch']);
                })
                ->bootScript('let scenes = {};')
                ->search(function ($search) {
                    $search->input('smsid', '消息编号', '请输入消息编号')
                        ->field(['type' => 'input', 'name' => 'scene_keyword', 'label' => '业务场景', 'class' => 'search-scene-keyword'])
                        ->select('status', '执行结果', [0 => '失败', 1 => '成功'])
                        ->dateRange('create_time', '发送时间', '请选择发送时间')
                        ->hidden('source', 'system')
                        ->submit('筛选', ['data-scene' => 'search-submit']);
                })
                ->table('MessageData', '/message', function ($table) {
                    $table->checkbox()
                        ->sortInput('/sort')
                        ->column(['field' => 'smsid', 'title' => '消息编号', 'sort' => true])
                        ->column(['field' => 'scene', 'title' => '业务场景', 'templet' => PageBuilder::js('function(d){ return d.scene; }')])
                        ->statusSwitch('/state', [
                            'title' => '状态',
                            'activeHtml' => '<b class="color-green">成功</b>',
                            'inactiveHtml' => '<b class="color-red">失败</b>',
                            'text' => '成功|失败',
                        ])
                        ->rows(function ($rows) {
                            $rows->open('查看', '/detail?id={{d.id}}', '查看详情', [], 'view')
                                ->modal('编辑', '/edit?id={{d.id}}', '编辑', [], 'edit');
                        })
                        ->toolbar();
                });
        });
        $builder->addInitScript('window.pageReady = true;')->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, 'render');

        $this->assertSame('短信管理', $schema['title']);
        $this->assertSame('MessageData', $schema['table']['id']);
        $this->assertSame('#SortInputMessageDataTpl', $schema['table']['columns'][1]['templet']);
        $this->assertSame('js', $schema['table']['columns'][3]['templet']['type']);
        $this->assertSame('#toolbar', $schema['table']['columns'][5]['toolbar'] ?? null);
        $this->assertSame('config', $schema['buttons'][0]['auth'] ?? null);
        $this->assertSame('open', $schema['buttons'][1]['type'] ?? null);
        $this->assertSame('/report', $schema['buttons'][1]['url'] ?? null);
        $this->assertSame('batch-action', $schema['buttons'][2]['type'] ?? null);
        $this->assertSame('PageDataTable', $schema['buttons'][2]['attrs']['data-table-id'] ?? null);
        $this->assertSame('hidden', $schema['searchFields'][4]['type'] ?? null);
        $this->assertSame('system', $schema['searchFields'][4]['attrs']['value'] ?? null);
        $this->assertSame('submit', $schema['searchFields'][5]['type'] ?? null);
        $this->assertStringContainsString('layui-card-header', $html);
        $this->assertStringContainsString('layui-card-table', $html);
        $this->assertStringContainsString('form-search', $html);
        $this->assertStringContainsString('class="form-search search-panel layui-form layui-form-pane"', $html);
        $this->assertStringContainsString('data-scene="batch"', $html);
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('name="source"', $html);
        $this->assertStringContainsString('value="system"', $html);
        $this->assertStringContainsString('<option value="">-- 全部 --</option>', $html);
        $this->assertStringContainsString('data-scene="search-submit"', $html);
        $this->assertStringContainsString('data-action="/remove"', $html);
        $this->assertStringContainsString('data-rule="id#{key}"', $html);
        $this->assertStringContainsString('data-confirm="确定删除吗？"', $html);
        $this->assertStringContainsString('search-scene-keyword', $html);
        $this->assertStringContainsString('data-url="/message"', $html);
        $this->assertStringContainsString('data-target-search="form.form-search"', $html);
        $this->assertStringContainsString('id="SortInputMessageDataTpl"', $html);
        $this->assertStringContainsString('id="StatusSwitchMessageDataTpl"', $html);
        $this->assertStringContainsString('lay-filter="StatusSwitchMessageData"', $html);
        $this->assertStringContainsString('$.form.load("/state"', $html);
        $this->assertTrue(
            strpos($html, 'let scenes = {};') < strpos($html, "let \$table = \$('#MessageData').layTable(")
        );
        $this->assertTrue(
            strpos($html, "let \$table = \$('#MessageData').layTable(") < strpos($html, 'window.pageReady = true;')
        );
        $this->assertStringContainsString('function(d){ return d.scene; }', $html);
        $this->assertStringContainsString('<script type="text/html" id="toolbar">', $html);
        $this->assertStringContainsString('page-builder-schema', $html);
    }

    public function testCanRenderNodeTreeAndStructuredActions(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($page) {
            $page->title('节点页面')
                ->buttons(function ($buttons) {
                    $buttons->item([
                        'type' => 'action',
                        'label' => '刷新',
                        'url' => '/refresh',
                        'confirm' => '确定刷新吗？',
                        'attrs' => ['data-scene' => 'head'],
                        'class' => 'layui-btn-warm',
                    ]);
                });

            $shell = $page->section()->class('page-shell')->class(['page-shell', 'page-shell-outer'])->data('scene', 'page')->module('tabs', ['filter' => 'demo']);
            $shell->html('<header class="page-head">节点页</header>');
            $body = $shell->div()->class('page-body');
            $body->searchNode(function ($search) {
                $search->class('node-search')->input('keyword', '关键词');
            });
            $body->tableNode('NodeTable', '/node', function ($table) {
                $table->class('node-table')
                    ->rows(function ($rows) {
                        $rows->item([
                            'type' => 'action',
                            'label' => '删除',
                            'url' => '/remove',
                            'value' => 'id#{{d.id}}',
                            'confirm' => '确定删除节点吗？',
                            'attrs' => ['data-scene' => 'row'],
                            'class' => 'layui-btn-danger',
                        ]);
                    })->toolbar();
            });
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, 'render');

        $this->assertSame('element', $schema['content'][0]['type']);
        $this->assertSame('section', $schema['content'][0]['tag']);
        $this->assertSame('page-shell page-shell-outer', $schema['content'][0]['attrs']['class']);
        $this->assertSame('page', $schema['content'][0]['attrs']['data-scene']);
        $this->assertSame('tabs', $schema['content'][0]['modules'][0]['name']);
        $this->assertSame('html', $schema['content'][0]['children'][0]['type']);
        $this->assertSame('action', $schema['buttons'][0]['type']);
        $this->assertStringContainsString('data-builder-modules=', $html);
        $this->assertStringContainsString('data-builder-scope="page"', $html);
        $this->assertStringContainsString('<header class="page-head">节点页</header>', $html);
        $this->assertStringContainsString('data-scene="head"', $html);
        $this->assertStringContainsString('data-scene="row"', $html);
        $this->assertStringContainsString('data-confirm="确定删除节点吗？"', $html);
        $this->assertStringContainsString('class="page-shell page-shell-outer"', $html);
        $this->assertStringContainsString('id="NodeTable"', $html);
        $this->assertStringContainsString('data-url="/node"', $html);
        $this->assertStringContainsString('搜 索', $html);
        $this->assertStringContainsString('data-target-search="form.form-search"', $html);
    }

    public function testButtonHelpersCanRenderGenericButtonsAndTags(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($page) {
            $page->title('按钮辅助')
                ->buttons(function ($buttons) {
                    $buttons->button('导出', ['type' => 'button', 'data-export' => '/export'], null, 'button');
                })
                ->table('ButtonTable', '/button', function ($table) {
                    $table->rows(function ($rows) {
                        $rows->button('日志', ['onclick' => "$.loadQueue('{{d.code}}',false,this)", 'class' => 'layui-btn-normal']);
                    })->toolbar();
                });
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, 'render');

        $this->assertSame('button', $schema['buttons'][0]['type'] ?? null);
        $this->assertSame('button', $schema['buttons'][0]['tag'] ?? null);
        $this->assertSame('/export', $schema['buttons'][0]['attrs']['data-export'] ?? null);
        $this->assertStringContainsString('<button', $html);
        $this->assertStringContainsString('data-export="/export"', $html);
        $this->assertStringContainsString('onclick="$.loadQueue(&#039;{{d.code}}&#039;,false,this)"', $html);
    }

    public function testTabsCardHelperCanRenderSearchAndTableLayout(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($page) {
            $page->title('标准列表')
                ->showSearchLegend(false);

            $content = $page->tabsCard('<ul class="layui-tab-title"><li class="layui-this">全部数据</li></ul>');
            $content->searchNode(function ($search) {
                $search->input('keyword', '关键词');
            });
            $content->tableNode('TabsTable', '/tabs', function ($table) {
                $table->column(['field' => 'name', 'title' => '名称']);
            }, ['class' => 'mt10']);
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, 'render');

        $this->assertSame('layui-tab layui-tab-card', $schema['content'][0]['attrs']['class'] ?? null);
        $this->assertSame('div', $schema['content'][0]['children'][1]['tag'] ?? null);
        $this->assertSame('layui-tab-content', $schema['content'][0]['children'][1]['attrs']['class'] ?? null);
        $this->assertSame('mt10', $schema['table']['attrs']['class'] ?? null);
        $this->assertStringContainsString('layui-tab layui-tab-card', $html);
        $this->assertStringContainsString('layui-tab-content', $html);
        $this->assertStringContainsString('id="TabsTable"', $html);
    }

    public function testTabsListHelperCanRenderStandardListLayout(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($page) {
            $page->title('标准列表页')
                ->showSearchLegend(false)
                ->tabsList(
                    '<ul class="layui-tab-title"><li class="layui-this">全部数据</li></ul>',
                    'StandardTable',
                    '/standard',
                    function ($search) {
                        $search->input('keyword', '关键词');
                    },
                    function ($table) {
                        $table->column(['field' => 'name', 'title' => '名称']);
                    }
                );
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, 'render');

        $this->assertSame('StandardTable', $schema['table']['id'] ?? null);
        $this->assertSame('mt10', $schema['table']['attrs']['class'] ?? null);
        $this->assertStringContainsString('id="StandardTable"', $html);
        $this->assertStringContainsString('layui-tab-content', $html);
        $this->assertStringContainsString('data-target-search="form.form-search"', $html);
    }

    public function testTableWithoutSearchDoesNotInjectTargetSearch(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($page) {
            $page->title('无搜索列表')
                ->table('PlainTable', '/plain');
        })->build();

        $html = $this->invokePrivate($builder, 'render');

        $this->assertStringContainsString('id="PlainTable"', $html);
        $this->assertStringContainsString('data-url="/plain"', $html);
        $this->assertStringNotContainsString('data-target-search="form.form-search"', $html);
    }

    public function testShellCanRenderNotifyAndCustomContentWrapper(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($page) {
            $page->title('提示页面')
                ->contentClass('page-content-wrap')
                ->table('AlertTable', '/alert');
        })->build();

        $this->setPrivateProperty($builder, 'renderVars', ['showErrorMessage' => '<span>配置未完成</span>']);
        $html = $this->invokePrivate($builder, 'render');

        $this->assertStringContainsString('think-box-notify', $html);
        $this->assertStringContainsString('<span>配置未完成</span>', $html);
        $this->assertStringContainsString('class="page-content-wrap"', $html);
        $this->assertStringContainsString('layui-card-body', $html);
    }

    public function testModuleObjectsCanMutateNodeSchemaAndHtml(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($page) {
            $shell = $page->section()->class('module-shell');
            $shell->moduleItem('tabs', ['filter' => 'demo'])
                ->option('scene', 'builder')
                ->option('index', 2);
            $shell->html('<div class="module-body">模块节点</div>');
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, 'render');

        $this->assertSame('tabs', $schema['content'][0]['modules'][0]['name'] ?? null);
        $this->assertSame('demo', $schema['content'][0]['modules'][0]['config']['filter'] ?? null);
        $this->assertSame('builder', $schema['content'][0]['modules'][0]['config']['scene'] ?? null);
        $this->assertSame(2, $schema['content'][0]['modules'][0]['config']['index'] ?? null);
        $this->assertStringContainsString('data-builder-modules=', $html);
        $this->assertStringContainsString('tabs', $html);
        $this->assertStringContainsString('builder', $html);
    }

    public function testPageModulesCanRenderStructuredHeroAndCards(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($page) {
            PageModules::hero($page, [
                'eyebrow' => 'System Console',
                'title' => '统一管理运行模式',
                'description' => '页面模块由公共 Builder 输出。',
                'stats' => [
                    ['label' => '运行模式', 'value' => '开发模式'],
                    ['label' => '默认驱动', 'value' => 'local'],
                ],
            ]);
            PageModules::card($page, ['title' => '系统参数'], function ($body) {
                PageModules::readonlyFields($body, [[
                    'label' => '网站名称',
                    'meta' => 'Website',
                    'value' => 'ThinkAdmin',
                    'copy' => 'ThinkAdmin',
                    'help' => '显示在浏览器标题中。',
                ]]);
            });
        })->build();

        $html = $this->invokePrivate($builder, 'render');

        $this->assertStringContainsString('System Console', $html);
        $this->assertStringContainsString('统一管理运行模式', $html);
        $this->assertStringContainsString('页面模块由公共 Builder 输出。', $html);
        $this->assertStringContainsString('运行模式', $html);
        $this->assertStringContainsString('开发模式', $html);
        $this->assertStringContainsString('网站名称', $html);
        $this->assertStringContainsString('data-copy="ThinkAdmin"', $html);
    }

    public function testAttributeObjectsCanMutateNodeSearchAndAction(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($page) {
            $page->section()->attrsItem()
                ->id('AttrShell')
                ->class('attr-shell')
                ->data('scene', 'page');

            $page->searchArea()->inputField('keyword', '关键词')
                ->attrsItem()
                ->class('keyword-attr')
                ->attr('placeholder', '通过属性对象输入')
                ->data('scene', 'search');

            $page->buttonsBar()->actionItem('刷新', '/refresh')
                ->attrsItem()
                ->class('layui-btn-warm')
                ->data('scene', 'refresh');

            $page->table('AttrBagTable', '/attr-bag');
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, 'render');

        $this->assertSame('AttrShell', $schema['content'][0]['attrs']['id'] ?? null);
        $this->assertSame('page', $schema['content'][0]['attrs']['data-scene'] ?? null);
        $this->assertSame('keyword-attr', $schema['searchFields'][0]['class'] ?? null);
        $this->assertSame('通过属性对象输入', $schema['searchFields'][0]['attrs']['placeholder'] ?? null);
        $this->assertSame('search', $schema['searchFields'][0]['attrs']['data-scene'] ?? null);
        $this->assertSame('layui-btn-warm', $schema['buttons'][0]['class'] ?? null);
        $this->assertSame('refresh', $schema['buttons'][0]['attrs']['data-scene'] ?? null);
        $this->assertStringContainsString('id="AttrShell"', $html);
        $this->assertStringContainsString('class="attr-shell"', $html);
        $this->assertStringContainsString('placeholder="通过属性对象输入"', $html);
        $this->assertStringContainsString('data-scene="refresh"', $html);
        $this->assertStringContainsString('class="layui-btn-warm layui-btn layui-btn-sm layui-btn-primary"', $html);
    }

    public function testExplicitToolbarTemplateHasPriorityOverGeneratedRowActions(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($page) {
            $page->title('自定义模板')
                ->table('CustomToolbarTable', '/custom', function ($table) {
                    $table->rows(function ($rows) {
                        $rows->open('查看', '/detail?id={{d.id}}');
                    })->toolbar();
                    $table->template('toolbar', '<span class="custom-toolbar">自定义操作</span>');
                });
        })->build();

        $html = $this->invokePrivate($builder, 'render');

        $this->assertStringContainsString('<span class="custom-toolbar">自定义操作</span>', $html);
        $this->assertStringNotContainsString('/detail?id={{d.id}}', $html);
    }

    public function testPageCanUseDirectObjectAccessors(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($page) {
            $page->title('对象写法');
            $page->buttonsBar()->open('新增数据', '/create', ['data-scene' => 'direct']);
            $page->searchArea()->input('keyword', '关键词');

            $table = $page->tableArea('DirectTable', '/direct');
            $table->column(['field' => 'title', 'title' => '标题'])->toolbar();
            $table->rowActions()->action('删除', '/remove');
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, 'render');

        $this->assertSame('/create', $schema['buttons'][0]['url'] ?? null);
        $this->assertSame('keyword', $schema['searchFields'][0]['name'] ?? null);
        $this->assertSame('DirectTable', $schema['table']['id'] ?? null);
        $this->assertStringContainsString('data-scene="direct"', $html);
        $this->assertStringContainsString('id="DirectTable"', $html);
        $this->assertStringContainsString('data-action="/remove"', $html);
    }

    public function testSearchFieldObjectsCanMutateSchemaAndHtml(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($page) {
            $page->title('搜索字段对象');
            $search = $page->searchArea();
            $search->inputField('keyword', '关键词')
                ->class('keyword-input')
                ->wrapClass('search-wrap')
                ->attr('data-scene', 'keyword')
                ->placeholder('请输入关键词');
            $search->selectField('status', '状态')
                ->option(1, '启用')
                ->option(0, '禁用')
                ->class('status-select');
            $search->hiddenField('source', 'system')->value('direct');
            $search->submitField('筛选')->class('search-submit')->attr('data-mode', 'direct');
            $page->table('SearchFieldTable', '/search');
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, 'render');

        $this->assertSame('keyword-input', $schema['searchFields'][0]['class'] ?? null);
        $this->assertSame('search-wrap', $schema['searchFields'][0]['wrapClass'] ?? null);
        $this->assertSame('请输入关键词', $schema['searchFields'][0]['placeholder'] ?? null);
        $this->assertSame('keyword', $schema['searchFields'][0]['attrs']['data-scene'] ?? null);
        $this->assertSame('启用', $schema['searchFields'][1]['options']['1'] ?? null);
        $this->assertSame('direct', $schema['searchFields'][2]['attrs']['value'] ?? null);
        $this->assertSame('search-submit', $schema['searchFields'][3]['class'] ?? null);
        $this->assertStringContainsString('class="layui-form-item layui-inline search-wrap"', $html);
        $this->assertStringContainsString('class="layui-input keyword-input"', $html);
        $this->assertStringContainsString('data-scene="keyword"', $html);
        $this->assertStringContainsString('class="layui-select status-select"', $html);
        $this->assertStringContainsString('value="direct"', $html);
        $this->assertStringContainsString('class="layui-btn layui-btn-primary search-submit"', $html);
        $this->assertStringContainsString('data-mode="direct"', $html);
    }

    public function testSearchOptionObjectsCanMutateSourceAndOptions(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($page) {
            $page->title('搜索选项对象');
            $page->searchArea()->selectField('status', '状态')
                ->optionsItem()
                ->option(1, '启用')
                ->option(0, '禁用')
                ->removeOption(0)
                ->source('statusMap');
            $page->table('SearchOptionTable', '/search-option');
        })->build();

        $this->setPrivateProperty($builder, 'renderVars', ['statusMap' => [3 => '草稿', 4 => '归档']]);
        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, 'render');

        $this->assertSame('statusMap', $schema['searchFields'][0]['source'] ?? null);
        $this->assertSame('启用', $schema['searchFields'][0]['options']['1'] ?? null);
        $this->assertArrayNotHasKey('0', $schema['searchFields'][0]['options']);
        $this->assertStringContainsString('草稿', $html);
        $this->assertStringContainsString('归档', $html);
        $this->assertStringNotContainsString('禁用', $html);
    }

    public function testActionObjectsCanMutateButtonAndRowSchema(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($page) {
            $page->title('动作对象');

            $page->buttonsBar()
                ->actionItem('导出', '/export')
                ->class('layui-btn-warm')
                ->confirm('确定导出吗？')
                ->attr('data-scene', 'export');

            $table = $page->tableArea('ActionObjectTable', '/action-object');
            $table->toolbar();
            $table->rowActions()
                ->actionItem('删除', '/remove')
                ->value('id#{{d.id}}')
                ->confirm('确定删除吗？')
                ->class('row-danger')
                ->attr('data-scene', 'row-delete');
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, 'render');

        $this->assertSame('导出', $schema['buttons'][0]['label'] ?? null);
        $this->assertSame('layui-btn-warm', $schema['buttons'][0]['class'] ?? null);
        $this->assertSame('确定导出吗？', $schema['buttons'][0]['confirm'] ?? null);
        $this->assertSame('export', $schema['buttons'][0]['attrs']['data-scene'] ?? null);
        $this->assertStringContainsString('class="layui-btn-warm layui-btn layui-btn-sm layui-btn-primary"', $html);
        $this->assertStringContainsString('data-confirm="确定导出吗？"', $html);
        $this->assertStringContainsString('data-scene="export"', $html);
        $this->assertStringContainsString('class="row-danger layui-btn layui-btn-sm layui-btn-danger"', $html);
        $this->assertStringContainsString('data-value="id#{{d.id}}"', $html);
        $this->assertStringContainsString('data-scene="row-delete"', $html);
    }

    public function testColumnObjectsCanMutateSchemaAndScript(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($page) {
            $page->title('列对象');
            $table = $page->tableArea('ColumnObjectTable', '/column-object');
            $table->columnItem(['field' => 'id'])
                ->title('编号')
                ->width(120)
                ->align('center')
                ->sort()
                ->option('event', 'view')
                ->templet(PageBuilder::js('function(d){ return d.id; }'));
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, 'render');

        $this->assertSame('id', $schema['table']['columns'][0]['field'] ?? null);
        $this->assertSame('编号', $schema['table']['columns'][0]['title'] ?? null);
        $this->assertSame(120, $schema['table']['columns'][0]['width'] ?? null);
        $this->assertSame('center', $schema['table']['columns'][0]['align'] ?? null);
        $this->assertTrue($schema['table']['columns'][0]['sort'] ?? false);
        $this->assertSame('view', $schema['table']['columns'][0]['event'] ?? null);
        $this->assertSame('js', $schema['table']['columns'][0]['templet']['type'] ?? null);
        $this->assertStringContainsString("$('#ColumnObjectTable').layTable(", $html);
        $this->assertStringContainsString('function(d){ return d.id; }', $html);
        $this->assertStringContainsString('"title":"编号"', $html);
    }

    public function testPresetColumnObjectsCanMutateSchemaTemplateAndScript(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($page) {
            $page->title('预设列对象');
            $table = $page->tableArea('PresetObjectTable', '/preset-object');
            $table->sortInputItem('/sort-object')
                ->title('排序值')
                ->width(88)
                ->inputAttr('data-scene', 'sort-object');
            $table->statusSwitchItem('/state-object')
                ->title('启用状态')
                ->toggleText('开|关')
                ->activeHtml('<b class="color-blue">开启</b>')
                ->inactiveHtml('<b class="color-grey">关闭</b>');
            $table->toolbarItem('操作列')->minWidth(220);
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, 'render');

        $this->assertSame('排序值', $schema['table']['columns'][0]['title'] ?? null);
        $this->assertSame(88, $schema['table']['columns'][0]['width'] ?? null);
        $this->assertSame('#SortInputPresetObjectTableTpl', $schema['table']['columns'][0]['templet'] ?? null);
        $this->assertSame('启用状态', $schema['table']['columns'][1]['title'] ?? null);
        $this->assertSame('#StatusSwitchPresetObjectTableTpl', $schema['table']['columns'][1]['templet'] ?? null);
        $this->assertSame('操作列', $schema['table']['columns'][2]['title'] ?? null);
        $this->assertSame(220, $schema['table']['columns'][2]['minWidth'] ?? null);
        $this->assertStringContainsString('data-scene="sort-object"', $html);
        $this->assertStringContainsString('data-action-blur="/sort-object"', $html);
        $this->assertStringContainsString('lay-text="开|关"', $html);
        $this->assertStringContainsString('color-blue', $html);
        $this->assertStringContainsString('color-grey', $html);
        $this->assertStringContainsString('$.form.load("/state-object"', $html);
    }

    public function testTableOptionsObjectCanMutateSchemaAndScript(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($page) {
            $page->title('表格配置对象');
            $table = $page->tableArea('OptionObjectTable', '/option-object');
            $table->config()
                ->height('full-160')
                ->even(false)
                ->limit(20)
                ->limits([20, 50, 100])
                ->page(['layout' => ['prev', 'page', 'next']])
                ->where(['scene' => 'builder'])
                ->toolbar('#OptionToolbar')
                ->defaultToolbar(['filter', 'exports'])
                ->skin('line');
            $table->column(['field' => 'name', 'title' => '名称']);
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, 'render');

        $this->assertSame('full-160', $schema['table']['options']['height'] ?? null);
        $this->assertFalse($schema['table']['options']['even'] ?? true);
        $this->assertSame(20, $schema['table']['options']['limit'] ?? null);
        $this->assertSame([20, 50, 100], $schema['table']['options']['limits'] ?? null);
        $this->assertSame(['layout' => ['prev', 'page', 'next']], $schema['table']['options']['page'] ?? null);
        $this->assertSame(['scene' => 'builder'], $schema['table']['options']['where'] ?? null);
        $this->assertSame('#OptionToolbar', $schema['table']['options']['toolbar'] ?? null);
        $this->assertSame(['filter', 'exports'], $schema['table']['options']['defaultToolbar'] ?? null);
        $this->assertSame('line', $schema['table']['options']['skin'] ?? null);
        $this->assertStringContainsString('"height":"full-160"', $html);
        $this->assertStringContainsString('"even":false', $html);
        $this->assertStringContainsString('"limit":20', $html);
        $this->assertStringContainsString('"limits":[20,50,100]', $html);
        $this->assertStringContainsString('"scene":"builder"', $html);
        $this->assertStringContainsString('"toolbar":"#OptionToolbar"', $html);
        $this->assertStringContainsString('"defaultToolbar":["filter","exports"]', $html);
    }

    private function newBuilder(): PageBuilder
    {
        $controller = $this->getMockBuilder(Controller::class)->disableOriginalConstructor()->getMock();
        return new PageBuilder($controller);
    }

    private function invokePrivate(object $object, string $method): mixed
    {
        $ref = new \ReflectionMethod($object, $method);
        $ref->setAccessible(true);
        return $ref->invoke($object);
    }

    private function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $ref = new \ReflectionProperty($object, $property);
        $ref->setAccessible(true);
        $ref->setValue($object, $value);
    }
}
