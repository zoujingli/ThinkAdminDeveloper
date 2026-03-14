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

namespace plugin\storage\service;

use think\admin\Exception;
use think\App;
use think\Container;

class StorageManager
{
    public function __construct(protected App $app) {}

    public function drivers(): array
    {
        $drivers = $this->app->config->get('think_plugs_storage.drivers', []);
        if (count($drivers) > 0) {
            return $drivers;
        }
        $file = dirname(__DIR__, 2) . '/stc/config/storage.php';
        if (is_file($file)) {
            $config = include $file;
            $this->app->config->set($config, 'think_plugs_storage');
            return $config['drivers'] ?? [];
        }
        return [];
    }

    public function driver(?string $name = null): array
    {
        $name = $this->driverName($name);
        $drivers = $this->drivers();
        if (isset($drivers[$name])) {
            return $drivers[$name] + ['name' => $name];
        }
        throw new Exception("Storage driver [{$name}] does not exist.");
    }

    public function driverName(?string $name = null): string
    {
        $name = strtolower((string)($name ?: StorageConfig::global('driver', $this->app->config->get('think_plugs_storage.default', 'local'))));
        return $name ?: 'local';
    }

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

    public function types(): array
    {
        $types = [];
        foreach ($this->drivers() as $name => $driver) {
            $label = (string)($driver['label'] ?? $name);
            $types[$name] = function_exists('lang') ? lang($label) : $label;
        }
        return $types;
    }

    public function regions(string $name): array
    {
        $regions = $this->driver($name)['regions'] ?? null;
        if (is_callable($regions)) {
            return (array)call_user_func($regions);
        }
        return is_array($regions) ? $regions : [];
    }

    public function template(string $name): string
    {
        return $this->driver($name)['template'] ?? "storage-{$name}";
    }

    public function authorize(string $name, string $key, bool $safe = false, ?string $attname = null, string $hash = ''): array
    {
        $authorize = $this->driver($name)['authorize'] ?? null;
        if (!is_callable($authorize)) {
            throw new Exception("Storage driver [{$name}] does not support upload authorization.");
        }
        $storage = Container::getInstance()->make($this->driverClass($name));
        return (array)call_user_func($authorize, $storage, $key, $safe, $attname, $hash);
    }

    public function mimes(): array
    {
        static $mimes = [];
        if (count($mimes) > 0) {
            return $mimes;
        }
        return $mimes = include dirname(__DIR__, 2) . '/stc/bin/mimes.php';
    }
}
