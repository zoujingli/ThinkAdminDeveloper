<?php

declare(strict_types=1);

namespace plugin\system\service;

use plugin\system\storage\StorageConfig;
use plugin\worker\service\ProcessService;
use think\admin\Service;
use think\admin\service\AppService;
use think\admin\Storage;

/**
 * 系统配置服务。
 * @class ConfigService
 */
class ConfigService extends Service
{
    private const STORAGE_SECRET_KEYS = ['password', 'access_key', 'secret_key'];

    /**
     * @var string[]
     */
    private const EDITOR_DRIVERS = ['ckeditor4', 'ckeditor5', 'wangEditor', 'auto'];

    /**
     * @param array<string, array<string, string>> $themes
     * @return array<string, mixed>
     */
    public static function buildIndexContext(array $themes = []): array
    {
        StorageConfig::initialize();

        $site = self::getSiteConfig($themes);
        $security = self::getSecurityConfig();
        $runtime = self::getRuntimeConfig();
        $storage = self::maskStorageViewData(StorageConfig::viewData());
        $files = Storage::types();
        $storageDriver = strtolower((string)StorageConfig::global('default_driver', 'local'));
        $storageName = strval($files[$storageDriver] ?? $storageDriver);
        $framework = AppService::getPluginLibrarys('topthink/framework');
        $thinkadmin = AppService::getPluginLibrarys('zoujingli/think-library');
        $showErrorMessage = '';

        if (AuthService::isSuper() && UserService::verifyPassword('admin', strval(AuthService::getUser('password', '')))) {
            $url = url('system/index/pass', ['id' => AuthService::getUserId()]);
            $showErrorMessage = lang("默认超管密码仍未修改，<a data-modal='%s'>立即修改</a>。", [$url]);
        }

        $pluginCenter = PluginService::getConfig();
        $systemInfo = [
            '核心框架' => ['value' => 'ThinkPHP Version ' . strval($framework['version'] ?? 'None'), 'url' => 'https://www.thinkphp.cn'],
            '平台框架' => ['value' => 'ThinkAdmin Version ' . strval($thinkadmin['version'] ?? '6.0.0'), 'url' => 'https://thinkadmin.top'],
            '操作系统' => ['value' => php_uname()],
            '运行环境' => ['value' => ucfirst(request()->server('SERVER_SOFTWARE', php_sapi_name())) . ' / PHP ' . PHP_VERSION . ' / ' . ucfirst(app()->db->connect()->getConfig('type'))],
        ];
        if (($systemid = ProcessService::getRunVar('uni')) !== null && $systemid !== '') {
            $systemInfo['系统序号'] = ['value' => strval($systemid)];
        }

        return [
            'site' => $site,
            'security' => $security,
            'runtime' => $runtime,
            'storage' => $storage,
            'files' => $files,
            'storageDriver' => $storageDriver,
            'storageName' => $storageName,
            'storageEditable' => self::canManageStorage(),
            'pluginCenter' => $pluginCenter,
            'issuper' => AuthService::isSuper(),
            'appDebug' => app()->isDebug(),
            'canEditSystem' => auth('system'),
            'showErrorMessage' => $showErrorMessage,
            'systemInfo' => $systemInfo,
        ];
    }

    /**
     * @param array<string, array<string, string>> $themes
     * @return array<string, mixed>
     */
    public static function buildSystemContext(array $themes = []): array
    {
        $site = self::getSiteConfig($themes);
        $security = self::getSecurityConfig();
        $runtime = self::getRuntimeConfig();
        $theme = strval($site['theme'] ?? 'default');
        if (!isset($themes[$theme])) {
            $theme = 'default';
        }

        return [
            'title' => '修改系统参数',
            'site' => $site,
            'security' => self::maskSecurityConfig($security),
            'runtime' => $runtime,
            'themes' => $themes,
            'siteThemeKey' => $theme,
            'siteThemeLabel' => strval($themes[$theme]['label'] ?? $theme),
            'themePickerUrl' => sysuri('system/index/theme'),
            'pluginCenter' => PluginService::getConfig(),
        ];
    }

    /**
     * @param array<string, array<string, string>> $themes
     * @return array<string, mixed>
     */
    public static function buildSystemFormData(array $themes = []): array
    {
        $context = self::buildSystemContext($themes);
        return [
            'site' => $context['site'],
            'security' => $context['security'],
            'runtime' => $context['runtime'],
            'plugin_center' => $context['pluginCenter'],
        ];
    }

    /**
     * @param array<string, array<string, string>> $themes
     * @return array<string, mixed>
     */
    public static function getSiteConfig(array $themes = []): array
    {
        return self::siteConfig($themes);
    }

    /**
     * @return array<string, mixed>
     */
    public static function getSecurityConfig(): array
    {
        return self::securityConfig();
    }

    /**
     * @return array<string, mixed>
     */
    public static function getRuntimeConfig(): array
    {
        return self::runtimeConfig();
    }

    /**
     * @param array<string, array<string, string>> $themes
     */
    public static function getSiteTheme(array $themes = []): string
    {
        return strval(self::siteConfig($themes)['theme'] ?? 'default') ?: 'default';
    }

    /**
     * 获取站点域名。
     */
    public static function getSiteHost(string $default = ''): string
    {
        $host = self::normalizeSiteHost(strval(self::siteConfig()['host'] ?? ''));
        return $host !== '' ? $host : $default;
    }

    /**
     * 同步站点域名。
     */
    public static function syncSiteHost(string $domain): void
    {
        $domain = self::normalizeSiteHost($domain);
        if ($domain !== '' && $domain !== self::getSiteHost()) {
            sysdata('system.site.host', $domain);
        }
    }

    /**
     * 获取编辑器驱动，保持历史存储值透传。
     */
    public static function getEditorDriver(): string
    {
        $runtime = (array)sysget('system.runtime', []);
        $editor = trim(strval($runtime['editor_driver'] ?? ''));
        return $editor !== '' ? $editor : 'ckeditor5';
    }

    /**
     * 设置编辑器驱动。
     */
    public static function setEditorDriver(string $editor): string
    {
        $editor = trim($editor);
        if ($editor === '') {
            $editor = 'auto';
        }
        sysdata('system.runtime.editor_driver', $editor);
        return $editor;
    }

    /**
     * @param array<string, mixed> $post
     * @param array<string, array<string, string>> $themes
     */
    public static function saveSystemConfig(array $post, array $themes = []): void
    {
        $payload = self::normalizeSystemPayload($post, $themes);
        $site = $payload['site'];
        $security = $payload['security'];
        $runtime = $payload['runtime'];
        $pluginCenter = $payload['plugin_center'];

        if (preg_match('#^https?://#', strval($site['browser_icon'] ?? ''))) {
            try {
                SystemService::setFavicon(strval($site['browser_icon'] ?? ''));
            } catch (\Throwable $exception) {
                trace_file($exception);
            }
        }

        sysdata('system.site', $site);
        sysdata('system.security', $security);
        sysdata('system.runtime', $runtime);
        PluginService::setConfig($pluginCenter);
        sysoplog('系统参数配置', '更新系统参数');
    }

    /**
     * @return array<string, mixed>
     */
    public static function buildStorageIndexContext(): array
    {
        StorageConfig::initialize();
        $storage = self::maskStorageViewData(StorageConfig::viewData());
        $files = Storage::types();
        $driver = strtolower((string)StorageConfig::global('default_driver', 'local'));

        return [
            'storage' => $storage,
            'files' => $files,
            'driver' => $driver,
            'driverName' => strval($files[$driver] ?? $driver),
            'canEdit' => self::canManageStorage(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function buildStorageFormContext(string $type): array
    {
        StorageConfig::initialize();
        $files = Storage::types();
        $type = strtolower(trim($type));
        if (!isset($files[$type])) {
            $type = strval(array_key_first($files) ?: 'local');
        }

        return [
            'title' => '修改存储驱动',
            'type' => $type,
            'driverName' => strval($files[$type] ?? $type),
            'points' => Storage::regions($type),
            'storage' => self::maskStorageViewData(StorageConfig::viewData()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function buildStorageFormData(): array
    {
        StorageConfig::initialize();
        return ['storage' => self::maskStorageViewData(StorageConfig::viewData())];
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    public static function normalizeStorageConfig(array $post): array
    {
        $data = (array)($post['storage'] ?? $post);
        if (isset($data['allowed_extensions_text']) && !isset($data['allowed_extensions'])) {
            $data['allowed_extensions'] = $data['allowed_extensions_text'];
        }
        unset($data['allowed_extensions_text']);
        $current = StorageConfig::payload();
        return self::restoreStorageSecrets(array_replace_recursive($current, $data), $current);
    }

    /**
     * 是否允许查看存储配置.
     */
    public static function canViewStorage(): bool
    {
        return AuthService::isSuper()
            || AuthService::check('system/config/storage')
            || AuthService::check('system/file/index')
            || self::canManageStorage();
    }

    /**
     * 是否允许管理存储配置.
     */
    public static function canManageStorage(): bool
    {
        return AuthService::isSuper()
            || AuthService::check('system/config/storage')
            || AuthService::check('system/file/edit')
            || AuthService::check('system/file/remove')
            || AuthService::check('system/file/distinct');
    }

    /**
     * @param array<string, array<string, string>> $themes
     * @return array<string, mixed>
     */
    private static function siteConfig(array $themes = []): array
    {
        $site = array_replace_recursive([
            'login_title' => '系统管理',
            'theme' => 'default',
            'login_background_images' => [],
            'browser_icon' => 'https://thinkadmin.top/static/img/logo.png',
            'website_name' => 'ThinkAdmin',
            'application_name' => 'ThinkAdmin',
            'application_version' => 'v8',
            'public_security_filing' => '',
            'miit_filing' => '',
            'copyright' => 'Copyright 2014-' . date('Y') . ' ThinkAdmin',
            'host' => '',
        ], (array)sysget('system.site', []));

        $site['login_title'] = strval($site['login_title']);
        $site['theme'] = strval($site['theme']) ?: 'default';
        $site['browser_icon'] = strval($site['browser_icon']);
        $site['website_name'] = strval($site['website_name']);
        $site['application_name'] = strval($site['application_name']);
        $site['application_version'] = strval($site['application_version']);
        $site['public_security_filing'] = strval($site['public_security_filing']);
        $site['miit_filing'] = strval($site['miit_filing']);
        $site['copyright'] = strval($site['copyright']);
        $site['host'] = self::normalizeSiteHost(strval($site['host']));
        $site['login_background_images'] = array_values(array_filter(array_map('strval', (array)$site['login_background_images'])));

        if (!isset($themes[$site['theme']]) && $themes !== []) {
            $site['theme'] = 'default';
        }

        return $site;
    }

    /**
     * @return array<string, mixed>
     */
    private static function securityConfig(): array
    {
        $security = array_replace_recursive([
            'jwt_secret' => bin2hex(random_bytes(16)),
        ], (array)sysget('system.security', []));

        $security['jwt_secret'] = strval($security['jwt_secret']);
        if (strlen($security['jwt_secret']) !== 32) {
            $security['jwt_secret'] = bin2hex(random_bytes(16));
        }

        return $security;
    }

    /**
     * @return array<string, mixed>
     */
    private static function runtimeConfig(): array
    {
        $runtime = array_replace_recursive([
            'editor_driver' => 'ckeditor5',
            'queue_retain_days' => 7,
        ], (array)sysget('system.runtime', []));

        $runtime['editor_driver'] = strval($runtime['editor_driver']) ?: 'ckeditor5';
        if (!in_array($runtime['editor_driver'], self::EDITOR_DRIVERS, true)) {
            $runtime['editor_driver'] = 'ckeditor5';
        }
        $runtime['queue_retain_days'] = max(1, intval($runtime['queue_retain_days'] ?? 7));

        return $runtime;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, array<string, string>> $themes
     * @return array<string, mixed>
     */
    private static function normalizeSiteConfig(array $data, array $themes = []): array
    {
        $site = array_replace_recursive(self::siteConfig($themes), $data);
        $site['login_title'] = trim(strval($site['login_title'] ?? ''));
        $site['theme'] = trim(strval($site['theme'] ?? 'default'));
        $site['browser_icon'] = trim(strval($site['browser_icon'] ?? ''));
        $site['website_name'] = trim(strval($site['website_name'] ?? ''));
        $site['application_name'] = trim(strval($site['application_name'] ?? ''));
        $site['application_version'] = trim(strval($site['application_version'] ?? ''));
        $site['public_security_filing'] = trim(strval($site['public_security_filing'] ?? ''));
        $site['miit_filing'] = trim(strval($site['miit_filing'] ?? ''));
        $site['copyright'] = trim(strval($site['copyright'] ?? ''));
        $site['host'] = self::normalizeSiteHost(strval($site['host'] ?? ''));

        $images = $site['login_background_images'] ?? [];
        if (is_string($images)) {
            $images = str2arr($images, '|');
        }
        $site['login_background_images'] = array_values(array_filter(array_map(static fn(mixed $item): string => trim(strval($item)), (array)$images)));

        if (!isset($themes[$site['theme']]) && $themes !== []) {
            $site['theme'] = 'default';
        }
        if ($site['login_title'] === '') {
            $site['login_title'] = '系统管理';
        }
        if ($site['website_name'] === '') {
            $site['website_name'] = 'ThinkAdmin';
        }
        if ($site['application_name'] === '') {
            $site['application_name'] = 'ThinkAdmin';
        }

        return $site;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function normalizeSecurityConfig(array $data): array
    {
        $current = self::securityConfig();
        $security = array_replace_recursive($current, $data);
        $security['jwt_secret'] = trim(strval($security['jwt_secret'] ?? ''));
        if (password_is_unchanged($security['jwt_secret'])) {
            $security['jwt_secret'] = strval($current['jwt_secret'] ?? '');
            return $security;
        }
        if (strlen($security['jwt_secret']) !== 32) {
            $security['jwt_secret'] = bin2hex(random_bytes(16));
        }
        return $security;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function maskSecurityConfig(array $data): array
    {
        if (!empty($data['jwt_secret'])) {
            $data['jwt_secret'] = password_mask(strlen(strval($data['jwt_secret'])));
        }
        return $data;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private static function maskStorageViewData(array $payload): array
    {
        foreach ((array)($payload['drivers'] ?? []) as $driver => $config) {
            foreach (self::STORAGE_SECRET_KEYS as $key) {
                $value = trim(strval($config[$key] ?? ''));
                if ($value !== '') {
                    $payload['drivers'][$driver][$key] = password_mask();
                }
            }
        }
        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $current
     * @return array<string, mixed>
     */
    private static function restoreStorageSecrets(array $payload, array $current): array
    {
        foreach ((array)($payload['drivers'] ?? []) as $driver => $config) {
            foreach (self::STORAGE_SECRET_KEYS as $key) {
                $value = trim(strval($config[$key] ?? ''));
                $origin = trim(strval($current['drivers'][$driver][$key] ?? ''));
                if ($value === '' || password_is_mask($value)) {
                    $payload['drivers'][$driver][$key] = $origin;
                }
            }
        }
        return $payload;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function normalizeRuntimeConfig(array $data): array
    {
        $runtime = array_replace_recursive(self::runtimeConfig(), $data);
        $runtime['editor_driver'] = trim(strval($runtime['editor_driver'] ?? 'ckeditor5'));
        if (!in_array($runtime['editor_driver'], self::EDITOR_DRIVERS, true)) {
            $runtime['editor_driver'] = 'ckeditor5';
        }
        $runtime['queue_retain_days'] = max(1, intval($runtime['queue_retain_days'] ?? 7));
        return $runtime;
    }

    /**
     * @param array<string, mixed> $post
     * @param array<string, array<string, string>> $themes
     * @return array{site:array<string,mixed>,security:array<string,mixed>,runtime:array<string,mixed>,plugin_center:array<string,int>}
     */
    private static function normalizeSystemPayload(array $post, array $themes = []): array
    {
        return [
            'site' => self::normalizeSiteConfig((array)($post['site'] ?? []), $themes),
            'security' => self::normalizeSecurityConfig((array)($post['security'] ?? [])),
            'runtime' => self::normalizeRuntimeConfig((array)($post['runtime'] ?? [])),
            'plugin_center' => self::normalizePluginCenterConfig((array)($post['plugin_center'] ?? [])),
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{enabled:int,show_menu:int}
     */
    private static function normalizePluginCenterConfig(array $data): array
    {
        return [
            'enabled' => empty($data['enabled']) ? 0 : 1,
            'show_menu' => empty($data['show_menu']) ? 0 : 1,
        ];
    }

    private static function normalizeSiteHost(string $host): string
    {
        return rtrim(trim($host), "\\/");
    }
}
