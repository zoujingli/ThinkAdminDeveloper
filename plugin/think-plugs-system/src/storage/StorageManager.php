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

namespace plugin\system\storage;

use think\admin\Exception;
use think\App;
use think\Container;

/**
 * 存储门面：从 `config('storage')` 解析驱动，并生成上传授权、MIME 等运行时数据。
 * @class StorageManager
 */
class StorageManager
{
    public function __construct(protected App $app) {}

    /**
     * 全部已注册存储驱动定义（含 class、label、authorize 等）。
     * @return array<string, array<string, mixed>>
     */
    public function drivers(): array
    {
        return (array)$this->app->config->get('storage.drivers', []);
    }

    /**
     * 指定名称的驱动定义，含归一化后的 `name` 键。
     * @throws Exception 驱动不存在时
     */
    public function driver(?string $name = null): array
    {
        $name = $this->driverName($name);
        $drivers = $this->drivers();
        if (isset($drivers[$name])) {
            return $drivers[$name] + ['name' => $name];
        }
        throw new Exception("Storage driver [{$name}] does not exist.");
    }

    /**
     * 解析当前或默认驱动名（结合 sysdata 与 config 默认）。
     */
    public function driverName(?string $name = null): string
    {
        $name = strtolower((string)($name ?: StorageConfig::global('driver', $this->app->config->get('storage.default', 'local'))));
        return $name ?: 'local';
    }

    /**
     * 驱动实现类 FQCN，用于容器实例化。
     * @throws Exception 类不可用时
     */
    public function driverClass(?string $name = null): string
    {
        $driver = $this->driver($name);
        $class = $driver['class'] ?? '';
        if (!is_string($class) || $class === '' || !class_exists($class)) {
            $name = $driver['name'] ?? $name ?? 'unknown';
            throw new Exception("Storage driver [{$name}] class is not available.");
        }
        return $class;
    }

    /**
     * 驱动编码 => 展示标题（经 lang 包装）。
     * @return array<string, string>
     */
    public function types(): array
    {
        $types = [];
        foreach ($this->drivers() as $name => $driver) {
            $label = (string)($driver['label'] ?? $name);
            $types[$name] = function_exists('lang') ? lang($label) : $label;
        }
        return $types;
    }

    /**
     * 对象存储地域/节点列表（驱动配置或回调返回）。
     */
    public function regions(string $name): array
    {
        $regions = $this->driver($name)['regions'] ?? null;
        if (is_callable($regions)) {
            return (array)call_user_func($regions);
        }
        return is_array($regions) ? $regions : [];
    }

    /**
     * 后台存储配置页对应视图模板标识。
     */
    public function template(string $name): string
    {
        return $this->driver($name)['template'] ?? "storage-{$name}";
    }

    /**
     * 生成前端直传所需字段（url、token、policy 等，依驱动而定）。
     */
    public function authorize(string $name, string $key, bool $safe = false, ?string $attname = null, string $hash = ''): array
    {
        $authorize = $this->driver($name)['authorize'] ?? null;
        if (!is_callable($authorize)) {
            throw new Exception("Storage driver [{$name}] does not support upload authorization.");
        }
        $storage = Container::getInstance()->make($this->driverClass($name));
        return (array)call_user_func($authorize, $storage, $key, $safe, $attname, $hash);
    }

    /**
     * 上传允许的 MIME 类型表（来自 extra/mimes.php，静态缓存）。
     */
    public function mimes(): array
    {
        static $mimes = [];
        if (count($mimes) > 0) {
            return $mimes;
        }
        return $mimes = include __DIR__ . '/extra/mimes.php';
    }
}
