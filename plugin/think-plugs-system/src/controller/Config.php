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

namespace plugin\system\controller;

use plugin\system\service\PluginCenterService;
use plugin\system\service\SystemAuthService;
use plugin\system\service\SystemService;
use plugin\system\service\UserService;
use plugin\system\storage\StorageConfig;
use plugin\worker\service\ProcessService;
use think\admin\Controller;
use think\admin\service\AppService;
use think\admin\Storage;

/**
 * 系统参数配置.
 * @class Config
 */
class Config extends Controller
{
    public const themes = [
        'default' => '默认青 0',
        'white' => '极简白 0',
        'red-1' => '绛霞红 1',
        'blue-1' => '深空蓝 1',
        'green-1' => '翡翠绿 1',
        'black-1' => '石墨黑 1',
        'navy-1' => '夜幕蓝 1',
        'amber-1' => '琥珀金 1',
        'violet-1' => '流光紫 1',
        'rose-1' => '玫影粉 1',
        'lime-1' => '青柠绿 1',
        'indigo-1' => '靛夜蓝 1',
        'glacier-1' => '冰川蓝 1',
        'red-2' => '绛霞红 2',
        'blue-2' => '深空蓝 2',
        'green-2' => '翡翠绿 2',
        'black-2' => '石墨黑 2',
        'slate-2' => '雾岩灰 2',
        'ocean-2' => '深海青 2',
        'sunset-2' => '晚霞橙 2',
        'rose-2' => '玫影粉 2',
        'lime-2' => '青柠绿 2',
        'indigo-2' => '靛夜蓝 2',
        'glacier-2' => '冰川蓝 2',
    ];

    public const themeCatalog = [
        'default' => ['label' => '默认青 0', 'layout' => 'classic', 'layout_label' => '标准', 'primary' => '#009688', 'header' => '#FFFFFF', 'side' => '#20222A', 'surface' => '#FFFFFF', 'body' => '#F4F7FB'],
        'white' => ['label' => '极简白 0', 'layout' => 'classic', 'layout_label' => '标准', 'primary' => '#16A34A', 'header' => '#FFFFFF', 'side' => '#FFFFFF', 'surface' => '#FFFFFF', 'body' => '#F8FAFC'],
        'red-1' => ['label' => '绛霞红 1', 'layout' => 'brand', 'layout_label' => '品牌侧栏', 'primary' => '#AA3130', 'header' => '#AA3130', 'side' => '#AA3130', 'surface' => '#FFFDFD', 'body' => '#FFF5F5'],
        'blue-1' => ['label' => '深空蓝 1', 'layout' => 'brand', 'layout_label' => '品牌侧栏', 'primary' => '#3963BC', 'header' => '#3963BC', 'side' => '#3963BC', 'surface' => '#FFFFFF', 'body' => '#F4F7FF'],
        'green-1' => ['label' => '翡翠绿 1', 'layout' => 'brand', 'layout_label' => '品牌侧栏', 'primary' => '#009688', 'header' => '#009688', 'side' => '#009688', 'surface' => '#FFFFFF', 'body' => '#F1FBFA'],
        'black-1' => ['label' => '石墨黑 1', 'layout' => 'brand', 'layout_label' => '品牌侧栏', 'primary' => '#393D49', 'header' => '#393D49', 'side' => '#393D49', 'surface' => '#FFFFFF', 'body' => '#F5F7FA'],
        'navy-1' => ['label' => '夜幕蓝 1', 'layout' => 'brand', 'layout_label' => '品牌侧栏', 'primary' => '#1D4ED8', 'header' => '#1D4ED8', 'side' => '#1D4ED8', 'surface' => '#FFFFFF', 'body' => '#F3F6FF'],
        'amber-1' => ['label' => '琥珀金 1', 'layout' => 'brand', 'layout_label' => '品牌侧栏', 'primary' => '#B45309', 'header' => '#B45309', 'side' => '#B45309', 'surface' => '#FFFFFF', 'body' => '#FFF8F1'],
        'violet-1' => ['label' => '流光紫 1', 'layout' => 'brand', 'layout_label' => '品牌侧栏', 'primary' => '#7C3AED', 'header' => '#7C3AED', 'side' => '#7C3AED', 'surface' => '#FFFFFF', 'body' => '#F7F3FF'],
        'rose-1' => ['label' => '玫影粉 1', 'layout' => 'brand', 'layout_label' => '品牌侧栏', 'primary' => '#DB2777', 'header' => '#DB2777', 'side' => '#DB2777', 'surface' => '#FFFDFE', 'body' => '#FFF2F8'],
        'lime-1' => ['label' => '青柠绿 1', 'layout' => 'brand', 'layout_label' => '品牌侧栏', 'primary' => '#65A30D', 'header' => '#65A30D', 'side' => '#65A30D', 'surface' => '#FFFFFE', 'body' => '#F7FCEB'],
        'indigo-1' => ['label' => '靛夜蓝 1', 'layout' => 'brand', 'layout_label' => '品牌侧栏', 'primary' => '#4338CA', 'header' => '#4338CA', 'side' => '#4338CA', 'surface' => '#FFFFFF', 'body' => '#F4F4FF'],
        'glacier-1' => ['label' => '冰川蓝 1', 'layout' => 'brand', 'layout_label' => '品牌侧栏', 'primary' => '#0284C7', 'header' => '#0284C7', 'side' => '#0284C7', 'surface' => '#FFFFFF', 'body' => '#F0F9FF'],
        'red-2' => ['label' => '绛霞红 2', 'layout' => 'split', 'layout_label' => '双栏导航', 'primary' => '#AA3130', 'header' => '#AA3130', 'side' => '#AA3130', 'surface' => '#FFFFFF', 'body' => '#FFF5F5'],
        'blue-2' => ['label' => '深空蓝 2', 'layout' => 'split', 'layout_label' => '双栏导航', 'primary' => '#3963BC', 'header' => '#3963BC', 'side' => '#3963BC', 'surface' => '#FFFFFF', 'body' => '#F4F7FF'],
        'green-2' => ['label' => '翡翠绿 2', 'layout' => 'split', 'layout_label' => '双栏导航', 'primary' => '#009688', 'header' => '#009688', 'side' => '#009688', 'surface' => '#FFFFFF', 'body' => '#F1FBFA'],
        'black-2' => ['label' => '石墨黑 2', 'layout' => 'split', 'layout_label' => '双栏导航', 'primary' => '#393D49', 'header' => '#393D49', 'side' => '#393D49', 'surface' => '#FFFFFF', 'body' => '#F5F7FA'],
        'slate-2' => ['label' => '雾岩灰 2', 'layout' => 'split', 'layout_label' => '双栏导航', 'primary' => '#475569', 'header' => '#475569', 'side' => '#475569', 'surface' => '#FFFFFF', 'body' => '#F7F8FB'],
        'ocean-2' => ['label' => '深海青 2', 'layout' => 'split', 'layout_label' => '双栏导航', 'primary' => '#0F766E', 'header' => '#0F766E', 'side' => '#0F766E', 'surface' => '#FFFFFF', 'body' => '#F0FBF9'],
        'sunset-2' => ['label' => '晚霞橙 2', 'layout' => 'split', 'layout_label' => '双栏导航', 'primary' => '#C2410C', 'header' => '#C2410C', 'side' => '#C2410C', 'surface' => '#FFFFFF', 'body' => '#FFF7ED'],
        'rose-2' => ['label' => '玫影粉 2', 'layout' => 'split', 'layout_label' => '双栏导航', 'primary' => '#DB2777', 'header' => '#DB2777', 'side' => '#DB2777', 'surface' => '#FFFDFE', 'body' => '#FFF2F8'],
        'lime-2' => ['label' => '青柠绿 2', 'layout' => 'split', 'layout_label' => '双栏导航', 'primary' => '#65A30D', 'header' => '#65A30D', 'side' => '#65A30D', 'surface' => '#FFFFFE', 'body' => '#F7FCEB'],
        'indigo-2' => ['label' => '靛夜蓝 2', 'layout' => 'split', 'layout_label' => '双栏导航', 'primary' => '#4338CA', 'header' => '#4338CA', 'side' => '#4338CA', 'surface' => '#FFFFFF', 'body' => '#F4F4FF'],
        'glacier-2' => ['label' => '冰川蓝 2', 'layout' => 'split', 'layout_label' => '双栏导航', 'primary' => '#0284C7', 'header' => '#0284C7', 'side' => '#0284C7', 'surface' => '#FFFFFF', 'body' => '#F0F9FF'],
    ];

    /**
     * 系统参数配置.
     * @auth true
     * @menu true
     */
    public function index()
    {
        StorageConfig::initialize();
        $this->title = '系统参数配置';
        $this->site = $this->siteConfig();
        $this->runtime = $this->runtimeConfig();
        $this->storage = StorageConfig::viewData();
        $this->files = Storage::types();
        $this->storageDriver = strtolower((string)StorageConfig::global('default_driver', 'local'));
        $this->storageName = $this->files[$this->storageDriver] ?? $this->storageDriver;
        $this->storageEditable = $this->canManageStorage();
        $this->plugins = $this->configPlugins();
        $this->issuper = SystemAuthService::isSuper();
        $this->systemid = ProcessService::getRunVar('uni');
        $this->framework = AppService::getPluginLibrarys('topthink/framework');
        $this->thinkadmin = AppService::getPluginLibrarys('zoujingli/think-library');
        if (SystemAuthService::isSuper() && UserService::verifyPassword('admin', strval(SystemAuthService::getUser('password', '')))) {
            $url = url('system/index/pass', ['id' => SystemAuthService::getUserId()]);
            $this->showErrorMessage = lang("默认超管密码仍未修改，<a data-modal='%s'>立即修改</a>。", [$url]);
        }
        [$this->pluginLeft, $this->pluginRight] = $this->splitPluginColumns($this->plugins);
        $this->fetch();
    }

    public function system()
    {
        if ($this->request->isGet()) {
            $this->title = '修改系统参数';
            $this->site = $this->siteConfig();
            $this->security = $this->securityConfig();
            $this->runtime = $this->runtimeConfig();
            $this->siteLoginImagesText = join('|', $this->site['login_background_images']);
            $theme = strval($this->site['theme'] ?? 'default');
            if (!isset(static::themeCatalog[$theme])) {
                $theme = 'default';
            }
            $this->themes = static::themeCatalog;
            $this->siteThemeKey = $theme;
            $this->siteThemeLabel = static::themeCatalog[$theme]['label'];
            $this->pluginCenter = PluginCenterService::getConfig();
            $this->fetch();
            return;
        }

        $post = $this->request->post();
        $site = $this->normalizeSiteConfig((array)($post['site'] ?? []));
        $security = $this->normalizeSecurityConfig((array)($post['security'] ?? []));
        $runtime = $this->normalizeRuntimeConfig((array)($post['runtime'] ?? []));
        if (preg_match('#^https?://#', $site['browser_icon'] ?? '')) {
            try {
                SystemService::setFavicon($site['browser_icon'] ?? '');
            } catch (\Exception $exception) {
                trace_file($exception);
            }
        }
        sysdata('system.site', $site);
        sysdata('system.security', $security);
        sysdata('system.runtime', $runtime);
        PluginCenterService::setConfig((array)($post['plugin_center'] ?? []));
        sysoplog('系统参数配置', '更新系统参数');
        $this->success('系统参数保存成功。', system_uri('system/config/index'));
    }

    /**
     * 存储中心配置.
     * @auth true
     * @menu true
     */
    public function storage()
    {
        StorageConfig::initialize();

        if ($this->request->isGet()) {
            $type = strtolower(trim(strval($this->request->get('type', ''))));
            if ($type == '') {
                $this->authorizeStorageView();
                $this->title = '存储配置中心';
                $this->storage = StorageConfig::viewData();
                $this->files = Storage::types();
                $this->driver = strtolower((string)StorageConfig::global('default_driver', 'local'));
                $this->driverName = $this->files[$this->driver] ?? $this->driver;
                $this->canEdit = $this->canManageStorage();
                $this->fetch('config/storage-index');
                return;
            }

            $this->authorizeStorageManage();
            $this->files = Storage::types();
            if (!isset($this->files[$type])) {
                $type = array_key_first($this->files) ?: 'local';
            }
            $this->title = '修改存储驱动';
            $this->type = $type;
            $this->points = Storage::regions($type);
            $this->storage = StorageConfig::viewData();
            $this->fetch('config/' . Storage::template($type));
            return;
        }

        $this->authorizeStorageManage();
        $storage = $this->normalizeStorageConfig((array)$this->request->post('storage', []));
        if (!empty($storage['allowed_extensions'])) {
            $deny = ['sh', 'asp', 'bat', 'cmd', 'exe', 'php'];
            if (count(array_intersect($deny, $storage['allowed_extensions'])) > 0) {
                $this->error('禁止上传可执行文件类型。');
            }
        }
        StorageConfig::save($storage);
        sysoplog('存储参数配置', '更新存储配置');
        $this->success('存储配置保存成功。', system_uri('system/config/storage'));
    }

    private function authorizeStorageView(): void
    {
        if ($this->canViewStorage()) {
            return;
        }
        $this->error('抱歉，没有访问该操作的权限！');
    }

    private function authorizeStorageManage(): void
    {
        if ($this->canManageStorage()) {
            return;
        }
        $this->error('抱歉，没有访问该操作的权限！');
    }

    private function canViewStorage(): bool
    {
        return SystemAuthService::isSuper()
            || SystemAuthService::check('system/config/storage')
            || SystemAuthService::check('system/file/index')
            || $this->canManageStorage();
    }

    private function canManageStorage(): bool
    {
        return SystemAuthService::isSuper()
            || SystemAuthService::check('system/config/storage')
            || SystemAuthService::check('system/file/edit')
            || SystemAuthService::check('system/file/remove')
            || SystemAuthService::check('system/file/distinct');
    }

    private function siteConfig(): array
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
        $site['host'] = strval($site['host']);
        $site['login_background_images'] = array_values(array_filter(array_map('strval', (array)$site['login_background_images'])));

        if (!isset(static::themeCatalog[$site['theme']])) {
            $site['theme'] = 'default';
        }

        return $site;
    }

    private function securityConfig(): array
    {
        $security = array_replace_recursive([
            'jwt_secret' => bin2hex(random_bytes(16)),
        ], (array)sysget('system.security', []));

        $security['jwt_secret'] = strval($security['jwt_secret']);
        if (strlen($security['jwt_secret']) != 32) {
            $security['jwt_secret'] = bin2hex(random_bytes(16));
        }

        return $security;
    }

    private function runtimeConfig(): array
    {
        $runtime = array_replace_recursive([
            'editor_driver' => 'ckeditor5',
            'queue_retain_days' => 7,
        ], (array)sysget('system.runtime', []));

        $runtime['editor_driver'] = strval($runtime['editor_driver']) ?: 'ckeditor5';
        if (!in_array($runtime['editor_driver'], ['ckeditor4', 'ckeditor5', 'wangEditor', 'auto'], true)) {
            $runtime['editor_driver'] = 'ckeditor5';
        }
        $runtime['queue_retain_days'] = max(1, intval($runtime['queue_retain_days'] ?? 7));

        return $runtime;
    }

    private function normalizeSiteConfig(array $data): array
    {
        $site = array_replace_recursive($this->siteConfig(), $data);
        $site['login_title'] = trim(strval($site['login_title'] ?? ''));
        $site['theme'] = trim(strval($site['theme'] ?? 'default'));
        $site['browser_icon'] = trim(strval($site['browser_icon'] ?? ''));
        $site['website_name'] = trim(strval($site['website_name'] ?? ''));
        $site['application_name'] = trim(strval($site['application_name'] ?? ''));
        $site['application_version'] = trim(strval($site['application_version'] ?? ''));
        $site['public_security_filing'] = trim(strval($site['public_security_filing'] ?? ''));
        $site['miit_filing'] = trim(strval($site['miit_filing'] ?? ''));
        $site['copyright'] = trim(strval($site['copyright'] ?? ''));
        $site['host'] = trim(strval($site['host'] ?? ''));

        $images = $site['login_background_images'] ?? [];
        if (is_string($images)) {
            $images = str2arr($images, '|');
        }
        $site['login_background_images'] = array_values(array_filter(array_map(static fn ($item) => trim(strval($item)), (array)$images)));

        if (!isset(static::themeCatalog[$site['theme']])) {
            $site['theme'] = 'default';
        }
        if ($site['login_title'] == '') {
            $site['login_title'] = '系统管理';
        }
        if ($site['website_name'] == '') {
            $site['website_name'] = 'ThinkAdmin';
        }
        if ($site['application_name'] == '') {
            $site['application_name'] = 'ThinkAdmin';
        }

        return $site;
    }

    private function normalizeSecurityConfig(array $data): array
    {
        $security = array_replace_recursive($this->securityConfig(), $data);
        $security['jwt_secret'] = trim(strval($security['jwt_secret'] ?? ''));
        if (strlen($security['jwt_secret']) != 32) {
            $security['jwt_secret'] = bin2hex(random_bytes(16));
        }
        return $security;
    }

    private function normalizeRuntimeConfig(array $data): array
    {
        $runtime = array_replace_recursive($this->runtimeConfig(), $data);
        $runtime['editor_driver'] = trim(strval($runtime['editor_driver'] ?? 'ckeditor5'));
        if (!in_array($runtime['editor_driver'], ['ckeditor4', 'ckeditor5', 'wangEditor', 'auto'], true)) {
            $runtime['editor_driver'] = 'ckeditor5';
        }
        $runtime['queue_retain_days'] = max(1, intval($runtime['queue_retain_days'] ?? 7));
        return $runtime;
    }

    private function normalizeStorageConfig(array $data): array
    {
        if (isset($data['allowed_extensions_text']) && !isset($data['allowed_extensions'])) {
            $data['allowed_extensions'] = $data['allowed_extensions_text'];
        }
        unset($data['allowed_extensions_text']);
        return array_replace_recursive(StorageConfig::payload(), $data);
    }

    /**
     * 获取系统配置页展示的插件应用。
     * 这里仅展示外部插件应用，不再混入 System 自身。
     *
     * @return array<string, array<string, mixed>>
     */
    private function configPlugins(): array
    {
        $plugins = $this->manifestPluginApps();
        foreach (AppService::plugins(true) as $plugin) {
            $code = trim(strval($plugin['code'] ?? ''));
            if ($code === '' || $code === 'system') {
                continue;
            }
            $plugins[$code] = array_replace($plugins[$code] ?? [], $plugin);
        }

        $plugins = array_filter($plugins, static function (array $plugin): bool {
            return !empty($plugin['show']) && trim(strval($plugin['code'] ?? '')) !== '';
        });

        uasort($plugins, static function (array $left, array $right): int {
            return strnatcasecmp(strval($left['code'] ?? ''), strval($right['code'] ?? ''));
        });

        foreach ($plugins as &$plugin) {
            $plugin['version_text'] = strval($plugin['version'] ?? '') ?: 'unknown';
            $plugin['license_text'] = empty($plugin['license']) ? '-' : implode(' / ', array_filter((array)$plugin['license']));
            $plugin['description_text'] = trim(strval($plugin['description'] ?? '')) ?: '暂未提供插件说明，可进入插件后查看具体能力。';
        }

        unset($plugin);
        return $plugins;
    }

    /**
     * 扫描本地插件 composer 清单，补足开发态下未注册到运行时的插件应用。
     *
     * @return array<string, array<string, mixed>>
     */
    private function manifestPluginApps(): array
    {
        $items = [];
        $pluginPath = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'plugin';
        if (!is_dir($pluginPath)) {
            return $items;
        }

        foreach (glob($pluginPath . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'composer.json') ?: [] as $file) {
            $content = file_get_contents($file);
            $config = is_string($content) ? json_decode($content, true) : null;
            $app = is_array($config) ? ($config['extra']['xadmin']['app'] ?? null) : null;
            if (!is_array($app)) {
                continue;
            }

            $code = trim(strval($app['code'] ?? ''));
            if ($code === '' || $code === 'system' || (array_key_exists('show', $app) && empty($app['show']))) {
                continue;
            }

            $prefixes = [];
            foreach ((array)($app['prefixes'] ?? ($app['prefix'] ?? [])) as $prefix) {
                $prefix = trim(strval($prefix), " \t\n\r\0\x0B\\/");
                if ($prefix !== '' && !in_array($prefix, $prefixes, true)) {
                    $prefixes[] = $prefix;
                }
            }
            if ($prefixes === []) {
                $prefixes = [$code];
            }

            $license = $config['license'] ?? [];
            $license = is_array($license) ? $license : [$license];

            $items[$code] = [
                'code' => $code,
                'name' => strval($app['name'] ?? $code),
                'package' => strval($config['name'] ?? ''),
                'document' => strval($app['document'] ?? ''),
                'description' => strval($app['description'] ?? ($config['description'] ?? '')),
                'license' => array_values(array_filter(array_map('strval', $license))),
                'version' => strval($config['version'] ?? ''),
                'prefixes' => $prefixes,
                'show' => !array_key_exists('show', $app) || !empty($app['show']),
            ];
        }

        return $items;
    }

    /**
     * 将插件列表拆分成左右两列，避免不同高度卡片产生大面积留白。
     *
     * @param array<string, array<string, mixed>> $plugins
     * @return array{0: array<string, array<string, mixed>>, 1: array<string, array<string, mixed>>}
     */
    private function splitPluginColumns(array $plugins): array
    {
        $columns = [[], []];
        $index = 0;
        foreach ($plugins as $code => $plugin) {
            $columns[$index % 2][$code] = $plugin;
            ++$index;
        }
        return $columns;
    }
}
