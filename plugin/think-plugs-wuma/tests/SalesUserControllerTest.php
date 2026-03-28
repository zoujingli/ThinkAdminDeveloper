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

use plugin\wuma\controller\sales\User as SalesUserController;
use think\admin\runtime\RequestContext;
use think\admin\tests\Support\SqliteIntegrationTestCase;

/**
 * @internal
 * @coversNothing
 */
class SalesUserControllerTest extends SqliteIntegrationTestCase
{
    public function testEditFilterTreatsStarMaskAsKeepPassword(): void
    {
        $this->executeStatements([
            <<<'SQL'
CREATE TABLE plugin_wuma_sales_user (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    phone TEXT DEFAULT '',
    password TEXT DEFAULT '',
    date_start TEXT DEFAULT NULL,
    date_after TEXT DEFAULT NULL,
    delete_time TEXT DEFAULT NULL
)
SQL,
        ]);

        $this->connection()->table('plugin_wuma_sales_user')->insert([
            'id' => 1,
            'phone' => '13800138000',
            'password' => 'legacy-pass',
        ]);

        $request = $this->app->request
            ->withGet([])
            ->withPost([
                'id' => 1,
                'phone' => '13800138000',
                'password' => password_mask(),
                'date' => '2026-03-28 - 2027-03-28',
            ])
            ->setMethod('POST')
            ->setController('sales.user')
            ->setAction('edit');

        RequestContext::clear();
        $this->app->instance('request', $request);

        $controller = new SalesUserController($this->app);
        $method = new \ReflectionMethod($controller, '_form_filter');
        $method->setAccessible(true);

        $data = [
            'id' => 1,
            'phone' => '13800138000',
            'password' => password_mask(),
            'date' => '2026-03-28 - 2027-03-28',
        ];
        $method->invokeArgs($controller, [&$data]);

        $this->assertArrayNotHasKey('password', $data);
        $this->assertSame('2026-03-28', $data['date_start'] ?? '');
        $this->assertSame('2027-03-28', $data['date_after'] ?? '');
    }

    protected function defineSchema(): void
    {
    }
}
