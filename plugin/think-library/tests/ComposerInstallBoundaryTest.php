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
use think\admin\Plugin;

/**
 * @internal
 * @coversNothing
 */
class ComposerInstallBoundaryTest extends TestCase
{
    public function testRootComposerKeepsServiceDiscoverHook(): void
    {
        $json = json_decode((string)file_get_contents(TEST_PROJECT_ROOT . '/composer.json'), true);
        $this->assertIsArray($json);

        $scripts = $json['scripts'] ?? [];
        $this->assertIsArray($scripts);
        $this->assertArrayHasKey('post-autoload-dump', $scripts);

        $commands = $scripts['post-autoload-dump'];
        $commands = is_array($commands) ? $commands : [$commands];

        $this->assertContains('@php think service:discover', $commands);
    }

    public function testPluginServicesDoNotDeclareMenuMethod(): void
    {
        $violations = [];

        foreach (glob(TEST_PROJECT_ROOT . '/plugin/*/src/Service.php') ?: [] as $file) {
            $source = (string)file_get_contents($file);
            if (preg_match('/function\s+menu\s*\(/i', $source) === 1) {
                $violations[] = str_replace(TEST_PROJECT_ROOT . '/', '', $file);
            }
        }

        $this->assertSame([], $violations, 'Plugin menus must be declared in composer.json: ' . implode(', ', $violations));
    }

    public function testPluginComposerMetadataUsesXadminAppOnly(): void
    {
        $allowed = ['code', 'name', 'prefix', 'prefixes', 'alias', 'space', 'document', 'description', 'platforms', 'license', 'icon', 'cover', 'super'];
        $legacy = [];
        $missing = [];
        $invalid = [];

        foreach (glob(TEST_PROJECT_ROOT . '/plugin/*/composer.json') ?: [] as $file) {
            $manifest = json_decode((string)file_get_contents($file), true);
            if (!is_array($manifest)) {
                continue;
            }

            if (isset($manifest['extra']['config'])) {
                $legacy[] = str_replace(TEST_PROJECT_ROOT . '/', '', $file) . ' uses extra.config';
            }
            if (isset($manifest['extra']['xadmin']['service'])) {
                $legacy[] = str_replace(TEST_PROJECT_ROOT . '/', '', $file) . ' uses extra.xadmin.service';
            }
            if (isset($manifest['extra']['xadmin']['config'])) {
                $legacy[] = str_replace(TEST_PROJECT_ROOT . '/', '', $file) . ' uses extra.xadmin.config';
            }

            $services = (array)($manifest['extra']['think']['services'] ?? []);
            $service = strval($services[0] ?? '');
            if ($service === '' || !class_exists($service) || !is_subclass_of($service, Plugin::class)) {
                continue;
            }
            $app = $manifest['extra']['xadmin']['app'] ?? null;
            if (!is_array($app) || $app === []) {
                $missing[] = str_replace(TEST_PROJECT_ROOT . '/', '', $file);
                continue;
            }
            if (trim(strval($app['code'] ?? '')) === '' || trim(strval($app['name'] ?? '')) === '') {
                $missing[] = str_replace(TEST_PROJECT_ROOT . '/', '', $file) . ' missing code/name';
            }
            foreach (array_keys($app) as $key) {
                if (!in_array($key, $allowed, true)) {
                    $invalid[] = str_replace(TEST_PROJECT_ROOT . '/', '', $file) . " contains unsupported xadmin.app.{$key}";
                }
            }
        }

        $this->assertSame([], $legacy, 'Legacy plugin metadata blocks are not allowed: ' . implode(', ', $legacy));
        $this->assertSame([], $missing, 'Runtime plugin metadata must be declared in extra.xadmin.app: ' . implode(', ', $missing));
        $this->assertSame([], $invalid, 'Unsupported xadmin.app fields found: ' . implode(', ', $invalid));
    }
}
