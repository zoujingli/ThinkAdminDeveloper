<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
 * +----------------------------------------------------------------------
 * | 版权所有 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | 官方网站: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | 开源协议 ( https://mit-license.org )
 * | 免责声明 ( https://thinkadmin.top/disclaimer )
 * | 会员特权 ( https://thinkadmin.top/vip-introduce )
 * +----------------------------------------------------------------------
 * | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
 * | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace think\admin\tests;

use PHPUnit\Framework\TestCase;
use think\admin\Controller;
use think\admin\helper\PageBuilder;

/**
 * @internal
 * @coversNothing
 */
class PageBuilderTest extends TestCase
{
    public function testCanCollectSchemaAndRenderListPage()
    {
        $builder = $this->newBuilder();
        $builder->setTitle('短信管理')->setTable('MessageData', '/message')->setSearchAttrs(['action' => '/message']);
        $builder->addModalButton('短信配置', '/config', '', [], 'config');
        $builder->addSearchInput('smsid', '消息编号', '请输入消息编号');
        $builder->addSearchSelect('status', '执行结果', [0 => '失败', 1 => '成功']);
        $builder->addSearchDateRange('create_time', '发送时间', '请选择发送时间');
        $builder->addCheckboxColumn();
        $builder->addColumn(['field' => 'smsid', 'title' => '消息编号', 'sort' => true]);
        $builder->addColumn(['field' => 'scene', 'title' => '业务场景', 'templet' => PageBuilder::raw('function(d){ return d.scene; }')]);
        $builder->addRowModalAction('编辑', '/edit?id={{d.id}}', '编辑', [], 'edit');
        $builder->addToolbarColumn();
        $builder->addBootScript('let scenes = {};');

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, 'render');

        $this->assertSame('短信管理', $schema['title']);
        $this->assertSame('MessageData', $schema['table']['id']);
        $this->assertSame('js', $schema['table']['columns'][2]['templet']['type']);
        $this->assertStringContainsString('form-search', $html);
        $this->assertStringContainsString('data-url="/message"', $html);
        $this->assertStringContainsString('function(d){ return d.scene; }', $html);
        $this->assertStringContainsString('<script type="text/html" id="toolbar">', $html);
        $this->assertStringContainsString("<!--{if auth('config')}-->", $html);
        $this->assertStringContainsString("<!--{if auth('edit')}-->", $html);
        $this->assertStringContainsString('page-builder-schema', $html);
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
}
