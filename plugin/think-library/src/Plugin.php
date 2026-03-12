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

namespace think\admin;

use think\admin\module\ModuleService;
use think\admin\node\NodeService;
use think\admin\runtime\AppService;
use think\App;
use think\Service;

/**
 * 插件注册服务
 *
 * @class Plugin
 */
abstract class Plugin extends Service
{
    /**
     * 插件类型.
     */
    protected string $appType = '';

    /**
     * 必填，插件包名.
     */
    protected string $package = '';

    /**
     * 必填，插件编码
     */
    protected string $appCode = '';

    /**
     * 必填，插件名称.
     */
    protected string $appName = '';

    /**
     * 可选，插件目录.
     */
    protected string $appPath = '';

    /**
     * 可选，插件别名.
     */
    protected string $appAlias = '';

    /**
     * 可选，主访问前缀.
     */
    protected string $appPrefix = '';

    /**
     * 可选，访问前缀集合.
     */
    protected array $appPrefixes = [];

    /**
     * 可选，命名空间.
     */
    protected string $appSpace = '';

    /**
     * 可选，注册服务
     */
    protected string $appService = '';

    /**
     * 可选，文档地址.
     */
    protected string $appDocument = '';

    /**
     * 可选，插件说明.
     */
    protected string $appDescription = '';

    /**
     * 可选，支持平台.
     */
    protected array $appPlatforms = [];

    /**
     * 可选，协议列表.
     */
    protected array $appLicense = [];

    /**
     * 可选，版本号.
     */
    protected string $appVersion = '';

    /**
     * 可选，主页地址.
     */
    protected string $appHomepage = '';

    /**
     * 可选，菜单根节点配置.
     */
    protected array $appMenuRoot = [];

    /**
     * 可选，菜单存在检测条件.
     */
    protected array $appMenuExists = [];

    /**
     * Composer 配置.
     */
    protected array $composer = [];

    /**
     * 插件配置.
     */
    private static array $addons = [];

    /**
     * 自动注册插件.
     */
    public function __construct(App $app)
    {
        parent::__construct($app);

        // 获取基础服务类
        $ref = new \ReflectionClass(static::class);
        $this->composer = $this->resolveComposerManifest($ref);
        $this->hydrateComposerManifest($this->composer);

        // 应用服务注册类
        if (empty($this->appService)) {
            $this->appService = static::class;
        }

        // 应用命名空间名
        if (empty($this->appSpace)) {
            $this->appSpace = $ref->getNamespaceName();
        }

        // 应用插件路径计算
        if (empty($this->appPath) || !is_dir($this->appPath)) {
            $this->appPath = dirname($ref->getFileName());
        }

        // 应用插件包名计算
        if (empty($this->package) && ($path = $ref->getFileName())) {
            for ($level = 1; $level <= 3; ++$level) {
                if (is_file($file = dirname($path, $level) . '/composer.json')) {
                    $this->package = json_decode(file_get_contents($file), true)['name'] ?? '';
                    break;
                }
            }
        }

        // 应用插件计算名称及别名
        $attr = explode('\\', $ref->getNamespaceName());
        if ($attr[0] === NodeService::space()) {
            array_shift($attr);
        }

        $this->appCode = $this->appCode ?: join('-', $attr);
        if ($this->appCode === $this->appAlias) {
            $this->appAlias = '';
        }

        if (is_dir($this->appPath)) {
            $prefixes = $this->normalizePrefixes();
            // 写入插件参数信息
            self::$addons[$this->appCode] = [
                'name' => $this->appName,
                'type' => $this->appType ?: 'plugin',
                'path' => realpath($this->appPath) . DIRECTORY_SEPARATOR,
                'alias' => $this->appAlias,
                'prefix' => $prefixes[0] ?? '',
                'prefixes' => $prefixes,
                'space' => $this->appSpace ?: NodeService::space($this->appCode),
                'package' => $this->package,
                'service' => $this->appService,
                'document' => $this->appDocument,
                'description' => $this->appDescription,
                'platforms' => $this->normalizeArray($this->appPlatforms),
                'license' => $this->normalizeArray($this->appLicense),
                'version' => $this->appVersion,
                'homepage' => $this->appHomepage,
            ];
            AppService::clear();
        }
    }

    /**
     * 注册应用启动.
     */
    public function boot(): void {}

    /**
     * 获取插件编号。
     */
    public static function getAppCode(): string
    {
        return static::plugin()->appCode;
    }

    /**
     * 获取插件名称。
     */
    public static function getAppName(): string
    {
        return static::plugin()->appName;
    }

    /**
     * 获取插件路径。
     */
    public static function getAppPath(): string
    {
        return static::plugin()->appPath;
    }

    /**
     * 获取插件命名空间。
     */
    public static function getAppSpace(): string
    {
        return static::plugin()->appSpace;
    }

    /**
     * 获取插件安装包名。
     */
    public static function getAppPackage(): string
    {
        return static::plugin()->package;
    }

    /**
     * 获取插件主访问前缀。
     */
    public static function getAppPrefix(): string
    {
        return static::plugin()->appPrefix;
    }

    /**
     * 获取插件全部访问前缀。
     * @return string[]
     */
    public static function getAppPrefixes(): array
    {
        return static::plugin()->appPrefixes;
    }

    /**
     * 获取插件菜单根节点配置。
     */
    public static function getMenuRoot(): array
    {
        return static::plugin()->appMenuRoot;
    }

    /**
     * 获取插件菜单存在检测条件。
     */
    public static function getMenuExists(): array
    {
        return static::plugin()->appMenuExists;
    }

    /**
     * 获取当前插件服务实例。
     */
    private static function plugin(): static
    {
        return app(static::class);
    }

    /**
     * 获取标准化前缀列表。
     * @return string[]
     */
    private function normalizePrefixes(): array
    {
        $items = [];
        foreach ([$this->appPrefix, $this->appPrefixes, $this->appAlias, $this->appCode] as $value) {
            foreach ((array)$value as $prefix) {
                $prefix = trim((string)$prefix, " \t\n\r\0\x0B\\/");
                if ($prefix === '') {
                    continue;
                }
                if (strpos($prefix, '/')) {
                    $prefix = strstr($prefix, '/', true) ?: $prefix;
                }
                if (strpos($prefix, '.')) {
                    $prefix = strstr($prefix, '.', true) ?: $prefix;
                }
                if ($prefix !== '' && !in_array($prefix, $items, true)) {
                    $items[] = $prefix;
                }
            }
        }

        $this->appPrefix = $items[0] ?? '';
        $this->appPrefixes = $items;
        return $items;
    }

    /**
     * 解析 Composer 配置.
     */
    private function resolveComposerManifest(\ReflectionClass $ref): array
    {
        if (!($path = $ref->getFileName())) {
            return [];
        }

        for ($level = 1; $level <= 3; ++$level) {
            $file = dirname($path, $level) . DIRECTORY_SEPARATOR . 'composer.json';
            if (is_file($file)) {
                return json_decode(file_get_contents($file), true) ?: [];
            }
        }

        return [];
    }

    /**
     * 同步 Composer 元数据.
     */
    private function hydrateComposerManifest(array $manifest): void
    {
        $config = (array)($manifest['extra']['config'] ?? []);
        $service = (array)($manifest['extra']['xadmin']['service'] ?? []);
        $menu = (array)($manifest['extra']['xadmin']['menu'] ?? []);

        $this->package = strval($manifest['name'] ?? $this->package);
        $this->appCode = array_key_exists('code', $service) ? strval($service['code']) : $this->appCode;
        $this->appName = array_key_exists('name', $service) ? strval($service['name']) : ($this->appName ?: strval($config['name'] ?? ''));
        $this->appAlias = array_key_exists('alias', $service) ? strval($service['alias']) : $this->appAlias;
        $this->appPrefix = array_key_exists('prefix', $service) ? strval($service['prefix']) : $this->appPrefix;
        if (array_key_exists('prefixes', $service)) {
            $this->appPrefixes = (array)$service['prefixes'];
        }
        $this->appSpace = array_key_exists('space', $service) ? strval($service['space']) : $this->appSpace;
        $this->appType = array_key_exists('type', $service) ? strval($service['type']) : ($this->appType ?: strval($config['type'] ?? 'plugin'));
        $this->appDocument = array_key_exists('document', $service) ? strval($service['document']) : ($this->appDocument ?: strval($config['document'] ?? ''));
        $this->appDescription = array_key_exists('description', $service) ? strval($service['description']) : ($this->appDescription ?: strval($config['description'] ?? ($manifest['description'] ?? '')));
        if (array_key_exists('platforms', $service)) {
            $this->appPlatforms = (array)$service['platforms'];
        } elseif (empty($this->appPlatforms)) {
            $this->appPlatforms = (array)($config['platforms'] ?? []);
        }
        if (array_key_exists('license', $service)) {
            $this->appLicense = (array)$service['license'];
        } elseif (empty($this->appLicense)) {
            $this->appLicense = (array)($config['license'] ?? ($manifest['license'] ?? []));
        }
        $this->appVersion = array_key_exists('version', $service) ? strval($service['version']) : ($this->appVersion ?: strval($config['version'] ?? ($manifest['version'] ?? '')));
        $this->appHomepage = array_key_exists('homepage', $service) ? strval($service['homepage']) : ($this->appHomepage ?: strval($config['homepage'] ?? ($manifest['homepage'] ?? '')));
        if (array_key_exists('root', $menu)) {
            $this->appMenuRoot = (array)$menu['root'];
        }
        if (array_key_exists('exists', $menu)) {
            $this->appMenuExists = (array)$menu['exists'];
        }
    }

    /**
     * 标准化字符串数组.
     * @return string[]
     */
    private function normalizeArray(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            $value = trim(strval($item));
            if ($value !== '' && !in_array($value, $result, true)) {
                $result[] = $value;
            }
        }

        return $result;
    }

    /**
     * 获取插件及安装信息.
     * @param ?string $code 指定插件编号
     * @param bool $append 关联安装数据
     */
    public static function get(?string $code = null, bool $append = false): ?array
    {
        // 读取插件原始信息
        $data = empty($code) ? self::$addons : (self::$addons[$code] ?? null);
        if (empty($data) || empty($append)) {
            return $data;
        }
        // 关联插件安装信息
        $versions = ModuleService::getLibrarys();
        return empty($code) ? array_map(static function ($item) use ($versions) {
            $item['install'] = $versions[$item['package']] ?? [];
            if (empty($item['name'])) {
                $item['name'] = $item['install']['name'] ?? '';
            }
            return $item;
        }, $data) : $data + ['install' => $versions[$data['package']] ?? []];
    }

    /**
     * 定义插件菜单.
     * @return array 一级或二级菜单
     */
    abstract public static function menu(): array;
}
