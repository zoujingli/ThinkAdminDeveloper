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

namespace plugin\system\model;

use think\admin\Model;
use think\admin\service\AppService;
use think\model\concern\SoftDelete;

/**
 * 数据字典模型.
 *
 * @property int $deleted_by 删除用户
 * @property int $id
 * @property int $sort 排序权重
 * @property int $status 数据状态(0禁用,1启动)
 * @property string $code 数据代码
 * @property string $content 数据内容
 * @property string $create_time 创建时间
 * @property null|string $delete_time 删除时间
 * @property string $name 数据名称
 * @property string $type 数据类型
 * @class SystemBase
 */
class SystemBase extends Model
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';

    protected $defaultSoftDelete;

    protected $updateTime = false;

    /**
     * 日志名称.
     * @var string
     */
    protected $oplogName = '数据字典';

    /**
     * 日志类型.
     * @var string
     */
    protected $oplogType = '数据字典管理';

    /**
     * 获取指定数据列表.
     * @param string $type 数据类型
     * @param array $data 外围数据
     * @param string $field 外链字段
     * @param string $bind 绑定字段
     */
    public static function items(string $type, array &$data = [], string $field = 'base_code', string $bind = 'base_info'): array
    {
        $map = ['type' => $type, 'status' => 1];
        $bases = static::mk()->where($map)->order('sort desc,id asc')->column('code,name,content', 'code');
        if (count($data) > 0) {
            foreach ($data as &$vo) {
                $vo[$bind] = $bases[$vo[$field]] ?? [];
            }
        }
        return $bases;
    }

    /**
     * 获取带插件归属信息的数据字典.
     */
    public static function itemsWithPlugins(string $type, bool $active = true): array
    {
        $query = static::mk()->where(['type' => $type]);
        $active && $query->where(['status' => 1]);
        return static::appendPlugins($query->order('sort desc,id asc')->select()->toArray());
    }

    /**
     * 追加插件归属信息.
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    public static function appendPlugins(array $items): array
    {
        if (empty($items)) {
            return [];
        }

        $titles = [];
        foreach (AppService::all(true) as $code => $plugin) {
            $titles[$code] = strval($plugin['name'] ?? $code);
        }
        foreach (AppService::all() as $code => $app) {
            $titles[$code] = strval($app['name'] ?? $code);
        }

        foreach ($items as &$item) {
            $meta = static::parseContent(strval($item['content'] ?? ''));
            $codes = self::normalizePluginCodes($meta['plugin'] ?? ($meta['plugins'] ?? []));
            $names = [];
            foreach ($codes as $code) {
                $names[] = $titles[$code] ?? $code;
            }
            if (count($codes) > 1) {
                [$group, $title] = ['mixed', '跨插件'];
            } elseif (count($codes) === 1) {
                [$group, $title] = [$codes[0], $names[0] ?? $codes[0]];
            } else {
                [$group, $title] = ['common', '未绑定'];
            }

            $item['content_meta'] = $meta;
            $item['content_text'] = strval($meta['text'] ?? '');
            $item['plugin_codes'] = $codes;
            $item['plugin_names'] = $names;
            $item['plugin_count'] = count($codes);
            $item['plugin_text'] = join(' / ', $names);
            $item['plugin_group'] = $group;
            $item['plugin_title'] = $title;
        }
        unset($item);

        return $items;
    }

    /**
     * 获取插件分组选项.
     * @return array<int, array<string, string>>
     */
    public static function groups(?string $type = null, bool $active = false): array
    {
        $query = static::mk()->order('sort desc,id asc');
        $active && $query->where(['status' => 1]);
        if ($type !== null && $type !== '') {
            $query->where(['type' => $type]);
        }

        $groups = [];
        foreach (static::appendPlugins($query->select()->toArray()) as $item) {
            $code = strval($item['plugin_group'] ?? 'common');
            if (!isset($groups[$code])) {
                $groups[$code] = ['code' => $code, 'name' => strval($item['plugin_title'] ?? $code)];
            }
        }

        $specials = [];
        foreach (['common', 'mixed'] as $code) {
            if (isset($groups[$code])) {
                $specials[$code] = $groups[$code];
                unset($groups[$code]);
            }
        }

        uasort($groups, static function (array $a, array $b): int {
            return strcmp($a['name'], $b['name']);
        });

        return array_values(array_merge($groups, $specials));
    }

    /**
     * 按插件分组获取数据字典编号.
     * @return int[]
     */
    public static function idsByPluginGroup(string $group, ?string $type = null): array
    {
        $query = static::mk()->field('id,type,content')->order('sort desc,id asc');
        if ($type !== null && $type !== '') {
            $query->where(['type' => $type]);
        }

        $ids = [];
        foreach (static::appendPlugins($query->select()->toArray()) as $item) {
            if (strval($item['plugin_group'] ?? '') === $group) {
                $ids[] = intval($item['id']);
            }
        }
        return $ids;
    }

    /**
     * 获取可选插件列表.
     * @return array<int, array<string, string>>
     */
    public static function pluginOptions(): array
    {
        $items = [];
        foreach (AppService::all(true) as $code => $plugin) {
            $items[] = ['code' => $code, 'name' => strval($plugin['name'] ?? $code)];
        }
        foreach (AppService::local() as $code => $app) {
            $items[] = ['code' => $code, 'name' => strval($app['name'] ?? $code)];
        }
        usort($items, static function (array $a, array $b): int {
            return strcmp($a['name'], $b['name']);
        });
        return $items;
    }

    /**
     * 解析内容元数据.
     * @return array<string, mixed>
     */
    public static function parseContent(?string $content): array
    {
        $text = trim(strval($content));
        $meta = ['raw' => $text, 'text' => $text, 'plugin' => [], 'plugins' => []];
        if ($text === '' || !in_array(substr($text, 0, 1), ['{', '['], true)) {
            return $meta;
        }

        $data = json_decode($text, true);
        if (!is_array($data) || array_is_list($data)) {
            return $meta;
        }

        $meta = $data + $meta;
        $meta['text'] = strval($data['text'] ?? ($data['remark'] ?? ($data['content'] ?? '')));
        return $meta;
    }

    /**
     * 打包内容元数据.
     * @param mixed $plugins
     */
    public static function packContent(string $text = '', $plugins = []): string
    {
        $text = trim($text);
        $codes = self::normalizePluginCodes($plugins);
        if ($text === '' && empty($codes)) {
            return '';
        }
        if (empty($codes)) {
            return $text;
        }

        return json_encode([
            'text' => $text,
            'plugin' => count($codes) === 1 ? $codes[0] : $codes,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * 获取所有数据类型.
     * @param bool $simple 加载默认值
     */
    public static function types(bool $simple = false): array
    {
        $types = static::mk()->distinct()->column('type');
        if (empty($types) && empty($simple)) {
            $types = ['身份权限'];
        }
        return $types;
    }

    /**
     * 格式化创建时间.
     * @param mixed $plugins
     */

    /**
     * 标准化插件编码列表.
     * @param mixed $plugins
     * @return string[]
     */
    private static function normalizePluginCodes($plugins): array
    {
        $items = [];
        foreach ((array)$plugins as $plugin) {
            $plugin = trim(strval($plugin));
            if ($plugin === '') {
                continue;
            }
            if ($resolved = AppService::resolvePlugin($plugin, true)) {
                $plugin = strval($resolved['code'] ?? $plugin);
            } elseif ($app = AppService::get($plugin)) {
                $plugin = strval($app['code'] ?? $plugin);
            }
            if ($plugin !== '' && !in_array($plugin, $items, true)) {
                $items[] = $plugin;
            }
        }
        sort($items);
        return $items;
    }
}
