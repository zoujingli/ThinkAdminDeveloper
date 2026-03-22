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

namespace plugin\system\service;

use plugin\system\model\SystemData;
use plugin\system\model\SystemOplog;
use think\admin\Exception;
use think\admin\Library;
use think\admin\Model;
use think\admin\model\QueryFactory;
use think\admin\runtime\SystemContext;
use think\admin\Service;
use think\admin\service\FaviconBuilder;
use think\admin\service\NodeService;
use think\admin\Storage;
use think\db\PDOConnection;
use think\db\Query;

class SystemService extends Service
{
    private const GROUPED_DATA_KEYS = [
        'system.site',
        'system.security',
        'system.runtime',
        'system.plugin_center',
        'system.storage',
        'system.openapi',
        'wechat.client',
        'wechat.service',
    ];

    public static function uri(string $path = '', ?string $type = '__ROOT__', $default = '')
    {
        $plugin = Library::$sapp->http->getName();
        if ($path !== '') {
            $path = '/' . ltrim($path, '/');
        }
        $prefix = rtrim(dirname(Library::$sapp->request->basefile()), '\/');
        $data = [
            '__APP__' => rtrim(url('@')->build(), '\/') . $path,
            '__ROOT__' => $prefix . $path,
            '__PLUG__' => "{$prefix}/static/extra/{$plugin}{$path}",
            '__FULL__' => Library::$sapp->request->domain() . $prefix . $path,
        ];
        return is_null($type) ? $data : ($data[$type] ?? $default);
    }

    public static function uris(string $path = ''): array
    {
        return static::uri($path, null);
    }

    public static function save($query, array &$data, string $key = 'id', $map = [])
    {
        try {
            $query = QueryFactory::build($query)->master()->strict(false);
            if (empty($map[$key])) {
                $query->where([$key => $data[$key] ?? null]);
            }
            $model = $query->where($map)->findOrEmpty();
            $action = $model->isExists() ? 'onAdminUpdate' : 'onAdminInsert';
            if ($model->save($data) === false) {
                return false;
            }
            if ($model instanceof Model) {
                $model->{$action}(strval($model->getAttr($key)));
            }
            $data = $model->toArray();
            return $model[$key] ?? true;
        } catch (\Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }

    public static function update($query, array $data, string $key = 'id', $map = [])
    {
        try {
            $query = QueryFactory::build($query)->master()->where($map);
            if (empty($map[$key])) {
                $query->where([$key => $data[$key] ?? null]);
            }
            return (clone $query)->count() > 1 ? $query->strict(false)->update($data) : $query->findOrEmpty()->save($data);
        } catch (\Exception|\Throwable $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }

    public static function getTables(): array
    {
        $connection = Library::$sapp->db->connect();
        if (!$connection instanceof PDOConnection) {
            return [[], 0, 0];
        }
        $tables = $connection->getTables();
        return [$tables, count($tables), 0];
    }

    public static function copyTableStruct(string $from, string $create, array $tables = [], bool $copy = false, $where = []): void
    {
        try {
            if ($tables === []) {
                [$tables] = static::getTables();
            }
            if (!in_array($from, $tables, true)) {
                throw new Exception("table {$from} does not exist");
            }
            if (!in_array($create, $tables, true)) {
                $connection = Library::$sapp->db->connect();
                if (!$connection instanceof PDOConnection) {
                    throw new Exception('current database connection does not support table copy');
                }
                $connection->execute("CREATE TABLE IF NOT EXISTS {$create} (LIKE {$from})");
                if ($copy) {
                    $query = Library::$sapp->db->name($from)->where($where);
                    if (!$query instanceof Query) {
                        throw new Exception('failed to build copy query');
                    }
                    $connection->execute("INSERT INTO {$create} {$query->buildSql(false)}");
                }
            }
        } catch (\Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }

    public static function setData(string $name, $value): bool
    {
        try {
            [$group, $path] = self::resolveDataRule($name);
            $payload = $path === ''
                ? self::normalizeDataPayload($value)
                : self::arraySetValue((array)static::getData($group, []), $path, $value);
            return self::saveDataRow($group, $payload);
        } catch (\Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }

    public static function getData(string $name, $default = [])
    {
        try {
            [$group, $path] = self::resolveDataRule($name);
            $rows = self::loadDataRows();
            if (!array_key_exists($group, $rows)) {
                return $default;
            }
            $payload = is_array($rows[$group]) ? $rows[$group] : [];
            return $path === '' ? $payload : self::arrayGetValue($payload, $path, $default);
        } catch (\Exception $exception) {
            trace_file($exception);
            return $default;
        }
    }

    public static function setOplog(string $action, string $content): bool
    {
        return SystemOplog::mk()->save(static::getOplog($action, $content)) !== false;
    }

    public static function getOplog(string $action, string $content): array
    {
        return [
            'node' => NodeService::getCurrent(),
            'action' => lang($action),
            'content' => lang($content),
            'geoip' => Library::$sapp->request->ip() ?: '127.0.0.1',
            'username' => strval(SystemContext::instance()->getUser('username', '-')) ?: '-',
            'create_time' => date('Y-m-d H:i:s'),
        ];
    }

    public static function setFavicon(?string $icon = null): bool
    {
        try {
            $icon = $icon ?: strval(static::getData('system.site.browser_icon', ''));
            if (!preg_match('#^https?://#i', $icon)) {
                throw new Exception(lang('无效的图标地址'));
            }
            [$file, $temporary] = self::resolveFaviconFile($icon);
            if ($file === '' || !is_file($file)) {
                return false;
            }
            try {
                $favicon = new FaviconBuilder($file, [48, 48]);
                return $favicon->saveIco(runpath('public/favicon.ico'));
            } finally {
                if ($temporary && is_file($file)) {
                    @unlink($file);
                }
            }
        } catch (Exception $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            trace_file($exception);
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }

    private static function loadDataRows(): array
    {
        $cacheKey = 'think.admin.data';
        $rows = sysvar($cacheKey) ?: [];
        if ($rows !== []) {
            return $rows;
        }

        SystemData::mk()->cache('SystemData')->select()->each(function (SystemData $item) use (&$rows) {
            $rows[strval($item->getAttr('name'))] = (array)$item->getAttr('value');
        });

        sysvar($cacheKey, $rows);
        return $rows;
    }

    private static function saveDataRow(string $name, array $value): bool
    {
        $saved = SystemData::mk()->where(['name' => $name])->findOrEmpty()->save([
            'name' => $name,
            'value' => $value,
        ]) !== false;

        sysvar('think.admin.data', []);
        Library::$sapp->cache->delete('SystemData');

        return $saved;
    }

    private static function resolveDataRule(string $name): array
    {
        $name = trim($name);
        foreach (self::GROUPED_DATA_KEYS as $group) {
            if ($name === $group) {
                return [$group, ''];
            }
            if (str_starts_with($name, $group . '.')) {
                return [$group, substr($name, strlen($group) + 1)];
            }
        }
        return [$name, ''];
    }

    private static function normalizeDataPayload($value): array
    {
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        } elseif (is_object($value)) {
            $value = json_decode(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), true) ?: [];
        }

        return is_array($value) ? $value : [];
    }

    private static function normalizeDataLeaf($value)
    {
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        } elseif (is_object($value)) {
            return json_decode(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), true) ?: [];
        }

        return $value;
    }

    private static function arrayGetValue(array $data, string $path, $default = null)
    {
        $segments = array_values(array_filter(explode('.', $path), 'strlen'));
        foreach ($segments as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return $default;
            }
            $data = $data[$segment];
        }
        return $data;
    }

    private static function arraySetValue(array $data, string $path, $value): array
    {
        $segments = array_values(array_filter(explode('.', $path), 'strlen'));
        if ($segments === []) {
            return $data;
        }

        $cursor = &$data;
        $last = array_pop($segments);
        foreach ($segments as $segment) {
            if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) {
                $cursor[$segment] = [];
            }
            $cursor = &$cursor[$segment];
        }
        $cursor[$last] = self::normalizeDataLeaf($value);

        return $data;
    }

    private static function resolveFaviconFile(string $icon): array
    {
        if ($file = self::resolveUploadFile($icon)) {
            return [$file, false];
        }
        $body = Storage::curlGet($icon);
        if ($body === '') {
            return ['', false];
        }
        $file = runpath('runtime/' . Storage::name($icon, 'tmp', 'favicon'));
        is_dir($dir = dirname($file)) || mkdir($dir, 0777, true);
        return file_put_contents($file, $body) === false ? ['', false] : [$file, true];
    }

    private static function resolveUploadFile(string $icon): ?string
    {
        $path = parse_url($icon, PHP_URL_PATH);
        if (!is_string($path) || !preg_match('#/upload/(.+)$#i', rawurldecode($path), $matches)) {
            return null;
        }
        $name = ltrim(str_replace('\\', '/', $matches[1]), '/');
        if (preg_match('#(^|/)\.\.(/|$)#', $name)) {
            return null;
        }
        $file = runpath("public/upload/{$name}");
        return is_file($file) ? $file : null;
    }
}
