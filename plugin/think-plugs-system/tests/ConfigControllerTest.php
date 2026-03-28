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

use plugin\system\controller\Config as ConfigController;
use plugin\system\model\SystemData;
use plugin\system\model\SystemOplog;
use plugin\system\service\ConfigService;
use plugin\system\service\SystemContext as PluginSystemContext;
use plugin\system\storage\StorageConfig;
use think\admin\contract\SystemContextInterface;
use think\admin\runtime\RequestContext;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\Container;
use think\exception\HttpResponseException;
use think\Request;

/**
 * @internal
 * @coversNothing
 */
class ConfigControllerTest extends SqliteIntegrationTestCase
{
    public function testSystemPostPersistsNormalizedStructuredConfig(): void
    {
        $this->bindAdminUser();

        $result = $this->callActionController('system', [
            'site' => [
                'login_title' => '  ',
                'login_entry' => ' Admin-Entry/login.html ',
                'theme' => 'unknown-theme',
                'browser_icon' => '/static/app-icon.png',
                'website_name' => '',
                'application_name' => ' Admin Center ',
                'application_version' => ' v10 ',
                'public_security_filing' => ' 粤公网安备测试 ',
                'miit_filing' => ' ',
                'copyright' => ' ',
                'host' => ' https://admin.example.com/ ',
                'login_background_images' => [' https://static.example.com/a.png ', '', 'https://static.example.com/b.png'],
            ],
            'security' => [
                'jwt_secret' => 'short',
            ],
            'runtime' => [
                'editor_driver' => 'invalid-driver',
                'queue_retain_days' => 0,
            ],
            'plugin_center' => [
                'enabled' => '',
                'show_menu' => '1',
            ],
        ]);

        $site = (array)SystemData::mk()->where(['name' => 'system.site'])->findOrEmpty()->getAttr('value');
        $security = (array)SystemData::mk()->where(['name' => 'system.security'])->findOrEmpty()->getAttr('value');
        $runtime = (array)SystemData::mk()->where(['name' => 'system.runtime'])->findOrEmpty()->getAttr('value');
        $pluginCenter = (array)SystemData::mk()->where(['name' => 'system.plugin_center'])->findOrEmpty()->getAttr('value');
        $oplog = SystemOplog::mk()->order('id desc')->findOrEmpty();

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('系统参数保存成功。', $result['info'] ?? '');

        $this->assertSame('系统管理', $site['login_title'] ?? '');
        $this->assertSame('admin-entry', $site['login_entry'] ?? '');
        $this->assertSame('default', $site['theme'] ?? '');
        $this->assertSame('/static/app-icon.png', $site['browser_icon'] ?? '');
        $this->assertSame('ThinkAdmin', $site['website_name'] ?? '');
        $this->assertSame('Admin Center', $site['application_name'] ?? '');
        $this->assertSame('v10', $site['application_version'] ?? '');
        $this->assertSame('粤公网安备测试', $site['public_security_filing'] ?? '');
        $this->assertSame('', $site['miit_filing'] ?? 'x');
        $this->assertSame('https://admin.example.com', $site['host'] ?? '');
        $this->assertSame([
            'https://static.example.com/a.png',
            'https://static.example.com/b.png',
        ], $site['login_background_images'] ?? []);

        $this->assertIsString($security['jwt_secret'] ?? null);
        $this->assertSame(32, strlen(strval($security['jwt_secret'] ?? '')));

        $this->assertSame('ckeditor5', $runtime['editor_driver'] ?? '');
        $this->assertSame(1, intval($runtime['queue_retain_days'] ?? 0));

        $this->assertSame(0, intval($pluginCenter['enabled'] ?? 1));
        $this->assertSame(1, intval($pluginCenter['show_menu'] ?? 0));

        $this->assertSame('https://admin.example.com', ConfigService::getSiteHost());
        $this->assertSame('/admin-entry/login.html', sysuri('system/login/index'));
        $this->assertSame('/admin-entry.html#/admin-entry/config.html', strval($result['data'] ?? ''));
        $this->assertTrue($oplog->isExists());
        $this->assertSame('系统参数配置', $oplog->getAttr('action'));
        $this->assertSame('更新系统参数', $oplog->getAttr('content'));
    }

    public function testSystemPostKeepsMaskedJwtSecret(): void
    {
        $this->bindAdminUser();
        sysdata('system.security', ['jwt_secret' => '1234567890abcdef1234567890abcdef']);

        $result = $this->callActionController('system', [
            'site' => [
                'login_title' => '系统管理',
                'theme' => 'default',
                'browser_icon' => 'https://example.com/icon.png',
                'website_name' => 'ThinkAdmin',
                'application_name' => 'ThinkAdmin',
                'application_version' => 'v8',
                'host' => 'https://admin.example.com',
            ],
            'security' => [
                'jwt_secret' => password_mask(32),
            ],
            'runtime' => [
                'editor_driver' => 'ckeditor5',
                'queue_retain_days' => 7,
            ],
            'plugin_center' => [
                'enabled' => '1',
                'show_menu' => '1',
            ],
        ]);

        $security = (array)SystemData::mk()->where(['name' => 'system.security'])->findOrEmpty()->getAttr('value');

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('1234567890abcdef1234567890abcdef', strval($security['jwt_secret'] ?? ''));
    }

    public function testSystemPostRejectsConflictingLoginEntry(): void
    {
        $this->bindAdminUser();

        $result = $this->callActionController('system', [
            'site' => [
                'login_title' => '系统管理',
                'login_entry' => 'index',
                'theme' => 'default',
                'browser_icon' => 'https://example.com/icon.png',
                'website_name' => 'ThinkAdmin',
                'application_name' => 'ThinkAdmin',
                'application_version' => 'v8',
                'host' => 'https://admin.example.com',
            ],
            'security' => [
                'jwt_secret' => '1234567890abcdef1234567890abcdef',
            ],
            'runtime' => [
                'editor_driver' => 'ckeditor5',
                'queue_retain_days' => 7,
            ],
            'plugin_center' => [
                'enabled' => '1',
                'show_menu' => '1',
            ],
        ]);

        $site = SystemData::mk()->where(['name' => 'system.site'])->findOrEmpty();

        $this->assertSame(0, intval($result['code'] ?? 1));
        $this->assertSame('后台登录入口不能与本地应用名称冲突！', $result['info'] ?? '');
        $this->assertFalse($site->isExists());
    }

    public function testBootSystemLoginEntryBindingUsesSavedEntry(): void
    {
        sysdata('system.site', ['login_entry' => 'gateway']);

        ConfigService::bootSystemLoginEntryBinding();

        $this->assertSame('gateway', strval($this->app->config->get('app.plugin.bindings.system', '')));
        $this->assertSame('/gateway/login.html', sysuri('system/login/index'));
    }

    public function testStoragePostKeepsMaskedSensitiveValues(): void
    {
        $this->bindAdminUser();
        StorageConfig::save([
            'default_driver' => 'qiniu',
            'naming_rule' => 'xmd5',
            'link_mode' => 'none',
            'allowed_extensions' => ['png', 'jpg'],
            'drivers' => [
                'local' => ['protocol' => 'follow', 'domain' => ''],
                'alist' => ['protocol' => 'http', 'domain' => '', 'path' => '', 'username' => '', 'password' => ''],
                'qiniu' => ['protocol' => 'https', 'region' => 'z0', 'bucket' => 'demo', 'domain' => 'cdn.example.com', 'access_key' => 'ak-demo', 'secret_key' => 'sk-demo'],
                'upyun' => ['protocol' => 'http', 'bucket' => '', 'domain' => '', 'username' => '', 'password' => ''],
                'txcos' => ['protocol' => 'http', 'region' => '', 'bucket' => '', 'domain' => '', 'access_key' => '', 'secret_key' => ''],
                'alioss' => ['protocol' => 'http', 'region' => '', 'bucket' => '', 'domain' => '', 'access_key' => '', 'secret_key' => ''],
            ],
        ]);

        $result = $this->callActionController('storage', [
            'storage' => [
                'default_driver' => 'qiniu',
                'naming_rule' => 'xmd5',
                'link_mode' => 'none',
                'allowed_extensions_text' => 'png,jpg',
                'drivers' => [
                    'qiniu' => [
                        'protocol' => 'https',
                        'region' => 'z0',
                        'bucket' => 'demo',
                        'domain' => 'cdn.example.com',
                        'access_key' => password_mask(),
                        'secret_key' => password_mask(),
                    ],
                ],
            ],
        ]);

        $storage = StorageConfig::payload();

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('ak-demo', strval($storage['drivers']['qiniu']['access_key'] ?? ''));
        $this->assertSame('sk-demo', strval($storage['drivers']['qiniu']['secret_key'] ?? ''));
    }

    protected function defineSchema(): void
    {
        $this->createSystemDataTable();
        $this->createSystemOplogTable();
    }

    protected function afterSchemaCreated(): void
    {
        $context = new PluginSystemContext();
        Container::getInstance()->instance(SystemContextInterface::class, $context);
        $this->app->instance(SystemContextInterface::class, $context);
    }

    private function callActionController(string $action, array $post = []): array
    {
        $request = (new Request())
            ->withGet($post)
            ->withPost($post)
            ->setMethod('POST')
            ->setController('config')
            ->setAction($action);

        $this->setRequestPayload($request, $post);
        $this->app->instance('request', $request);

        try {
            $controller = new ConfigController($this->app);
            $controller->{$action}();
            self::fail("Expected ConfigController::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }

    private function bindAdminUser(): void
    {
        RequestContext::instance()->setAuth([
            'id' => 9101,
            'username' => 'admin',
            'password' => $this->hashSystemPassword('changed-password'),
        ], '', true);
    }

    private function setRequestPayload(Request $request, array $data): void
    {
        $property = new \ReflectionProperty(Request::class, 'request');
        $property->setAccessible(true);
        $property->setValue($request, $data);
    }
}
