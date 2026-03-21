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

    public function testRuntimePluginServicesDoNotDeclareMetadataProperties(): void
    {
        $violations = [];
        $pattern = '/protected\s+(?:string|array|bool)\s+\$(appCode|appName|appPrefix|appPrefixes|package|appAlias|appDocument|appDescription|appPlatforms|appLicense|appVersion|appHomepage)\b/';

        foreach (glob(TEST_PROJECT_ROOT . '/plugin/*/composer.json') ?: [] as $file) {
            $manifest = json_decode((string)file_get_contents($file), true);
            if (!is_array($manifest)) {
                continue;
            }

            $services = (array)($manifest['extra']['think']['services'] ?? []);
            $service = strval($services[0] ?? '');
            if ($service === '' || !class_exists($service) || !is_subclass_of($service, Plugin::class)) {
                continue;
            }

            $serviceFile = dirname($file) . '/src/Service.php';
            if (!is_file($serviceFile)) {
                continue;
            }

            $source = (string)file_get_contents($serviceFile);
            if (preg_match_all($pattern, $source, $matches) < 1) {
                continue;
            }

            $names = array_values(array_unique($matches[1]));
            $label = str_replace(TEST_PROJECT_ROOT . '/', '', $serviceFile);
            $violations[] = "{$label} declares service metadata properties: " . implode(', ', $names);
        }

        $this->assertSame([], $violations, 'Runtime plugin metadata must only come from composer.json: ' . implode(', ', $violations));
    }

    public function testPluginComposerMetadataUsesXadminAppOnly(): void
    {
        $required = ['code', 'name'];
        $stringFields = ['code', 'name', 'prefix', 'alias', 'space', 'document', 'description', 'icon', 'cover'];
        $arrayFields = ['prefixes', 'platforms', 'license'];
        $allowed = array_merge($stringFields, $arrayFields, ['super']);
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
            foreach ($required as $key) {
                if (!is_string($app[$key] ?? null) || trim($app[$key]) === '') {
                    $missing[] = str_replace(TEST_PROJECT_ROOT . '/', '', $file) . " missing {$key}";
                }
            }
            foreach (array_keys($app) as $key) {
                if (!in_array($key, $allowed, true)) {
                    $invalid[] = str_replace(TEST_PROJECT_ROOT . '/', '', $file) . " contains unsupported xadmin.app.{$key}";
                }
            }
            foreach ($stringFields as $key) {
                if (!array_key_exists($key, $app)) {
                    continue;
                }
                if (!is_string($app[$key])) {
                    $invalid[] = str_replace(TEST_PROJECT_ROOT . '/', '', $file) . " requires xadmin.app.{$key} to be a string";
                    continue;
                }
                if (!in_array($key, $required, true) && trim($app[$key]) === '') {
                    $invalid[] = str_replace(TEST_PROJECT_ROOT . '/', '', $file) . " requires xadmin.app.{$key} to be non-empty when declared";
                }
            }
            foreach ($arrayFields as $key) {
                if (!array_key_exists($key, $app)) {
                    continue;
                }
                if (!is_array($app[$key])) {
                    $invalid[] = str_replace(TEST_PROJECT_ROOT . '/', '', $file) . " requires xadmin.app.{$key} to be an array";
                    continue;
                }
                foreach ($app[$key] as $index => $value) {
                    if (!is_string($value) || trim($value) === '') {
                        $invalid[] = str_replace(TEST_PROJECT_ROOT . '/', '', $file) . " requires xadmin.app.{$key}[{$index}] to be a non-empty string";
                    }
                }
            }
            if (array_key_exists('super', $app) && !is_bool($app['super'])) {
                $invalid[] = str_replace(TEST_PROJECT_ROOT . '/', '', $file) . ' requires xadmin.app.super to be a boolean';
            }
            if (isset($app['prefix'], $app['prefixes']) && is_string($app['prefix']) && is_array($app['prefixes'])) {
                if (!in_array($app['prefix'], $app['prefixes'], true)) {
                    $invalid[] = str_replace(TEST_PROJECT_ROOT . '/', '', $file) . ' requires xadmin.app.prefix to be included in xadmin.app.prefixes';
                }
            }
        }

        $this->assertSame([], $legacy, 'Legacy plugin metadata blocks are not allowed: ' . implode(', ', $legacy));
        $this->assertSame([], $missing, 'Runtime plugin metadata must be declared in extra.xadmin.app: ' . implode(', ', $missing));
        $this->assertSame([], $invalid, 'Unsupported xadmin.app fields found: ' . implode(', ', $invalid));
    }

    public function testRuntimePluginComposerManifestProvidesMinimalSkeleton(): void
    {
        $invalid = [];

        foreach (glob(TEST_PROJECT_ROOT . '/plugin/*/composer.json') ?: [] as $file) {
            $manifest = json_decode((string)file_get_contents($file), true);
            if (!is_array($manifest)) {
                continue;
            }

            $services = (array)($manifest['extra']['think']['services'] ?? []);
            $service = strval($services[0] ?? '');
            if ($service === '' || !class_exists($service) || !is_subclass_of($service, Plugin::class)) {
                continue;
            }

            $label = str_replace(TEST_PROJECT_ROOT . '/', '', $file);
            if (strval($manifest['type'] ?? '') !== 'think-admin-plugin') {
                $invalid[] = "{$label} requires type=think-admin-plugin";
            }
            if (!is_string($manifest['name'] ?? null) || trim($manifest['name']) === '') {
                $invalid[] = "{$label} requires composer.name";
            }
            if (!is_string($manifest['description'] ?? null) || trim($manifest['description']) === '') {
                $invalid[] = "{$label} requires composer.description";
            }
            if (!is_array($manifest['autoload']['psr-4'] ?? null) || ($manifest['autoload']['psr-4'] ?? []) === []) {
                $invalid[] = "{$label} requires autoload.psr-4";
            }
            if (count($services) !== 1) {
                $invalid[] = "{$label} requires exactly one extra.think.services entry";
            }
            if (!is_subclass_of($service, Plugin::class)) {
                $invalid[] = "{$label} service must extend think\\admin\\Plugin";
            }

            $autoload = (array)($manifest['autoload']['psr-4'] ?? []);
            $matched = false;
            foreach ($autoload as $namespace => $directory) {
                $namespace = trim(strval($namespace), '\\');
                $directory = trim(strval($directory), '\/');
                if ($namespace === '' || $directory === '') {
                    continue;
                }
                if (str_starts_with(trim($service, '\\'), $namespace . '\\') && $directory === 'src') {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                $invalid[] = "{$label} requires service namespace to be mapped to src in autoload.psr-4";
            }
        }

        $this->assertSame([], $invalid, 'Runtime plugin composer skeleton violations found: ' . implode(', ', $invalid));
    }
}
