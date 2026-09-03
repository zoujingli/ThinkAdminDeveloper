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

namespace app\admin\tests;

use app\admin\controller\api\System;
use app\admin\controller\Config;
use PHPUnit\Framework\TestCase;
use think\admin\Library;
use think\exception\HttpResponseException;
use think\Request;

/**
 * @internal
 * @coversNothing
 */
class DemoWriteProtectionTest extends TestCase
{
    /**
     * @dataProvider writeActionProvider
     */
    public function testDemoWriteActionsAreRejectedBeforeMutation(string $controllerClass, string $action, array $post): void
    {
        $request = (new Request())->withPost($post)->withServer([
            'REQUEST_METHOD' => 'PUT',
            'HTTP_HOST' => 'v6.thinkadmin.top',
            'SERVER_NAME' => 'v6.thinkadmin.top',
        ])->setAction($action);
        $app = Library::$sapp;
        $app->instance('request', $request);
        $app->instance('session', new class {
            public function get(string $key, $default = null)
            {
                return $key === 'user.username' ? 'admin' : ($key === 'user.id' ? 1 : $default);
            }
        });

        try {
            (new $controllerClass($app))->{$action}();
            self::fail('Demo write action should return an HTTP response.');
        } catch (HttpResponseException $exception) {
            $response = json_decode($exception->getResponse()->getContent(), true);
            self::assertIsArray($response);
            self::assertSame(0, $response['code']);
            self::assertSame('演示环境禁止修改系统配置！', $response['info']);
        }
    }

    public static function writeActionProvider(): array
    {
        return [
            'system config' => [Config::class, 'system', ['base' => ['site_name' => 'changed']]],
            'storage config' => [Config::class, 'storage', ['storage' => ['allow_exts' => 'jpg,php']]],
            'debug mode' => [System::class, 'debug', ['state' => 0]],
        ];
    }
}
