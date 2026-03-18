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

namespace plugin\system\model;

use think\admin\Model;
use think\admin\service\AppService;
use think\admin\service\PluginService;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class SystemAuth extends Model
{
    protected $updateTime = false;

    protected $oplogName = '系统权限';

    protected $oplogType = '系统权限管理';

    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function items(): array
    {
        return static::mk()->where(['status' => 1])->order('sort desc,id desc')->select()->toArray();
    }

    /**
     * 获取带插件归属信息的权限列表.
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function itemsWithPlugins(): array
    {
        return static::appendPlugins(static::items());
    }

    /**
     * 追加插件归属信息.
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
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

        $binds = [];
        $query = SystemNode::mk()->whereIn('auth', array_column($items, 'id'))->field('auth,node')->select()->toArray();
        foreach ($query as $item) {
            if (!($code = static::resolveNodePlugin(strval($item['node'] ?? '')))) {
                continue;
            }
            if (!isset($binds[$item['auth']])) {
                $binds[$item['auth']] = [];
            }
            if (!in_array($code, $binds[$item['auth']], true)) {
                $binds[$item['auth']][] = $code;
            }
        }

        foreach ($items as &$item) {
            $codes = $binds[$item['id']] ?? [];
            sort($codes);
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
     * 获取角色插件分组.
     * @return array<int, array<string, mixed>>
     */
    public static function groups(bool $active = false): array
    {
        $query = static::mk()->order('sort desc,id desc');
        if ($active) {
            $query->where(['status' => 1]);
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
     * 按插件分组获取角色编号.
     * @return int[]
     */
    public static function idsByPluginGroup(string $group): array
    {
        $ids = [];
        foreach (static::appendPlugins(static::mk()->field('id')->order('sort desc,id desc')->select()->toArray()) as $item) {
            if (strval($item['plugin_group'] ?? '') === $group) {
                $ids[] = intval($item['id']);
            }
        }
        return $ids;
    }

    public function onAdminDelete(string $ids)
    {
        if (count($aids = str2arr($ids)) > 0) {
            SystemNode::mk()->whereIn('auth', $aids)->delete();
        }

        sysoplog($this->oplogType, lang('删除%s[%s]及授权配置', [lang($this->oplogName), $ids]));
    }

    /**
     * 解析节点所属插件或应用.
     */
    private static function resolveNodePlugin(string $node): string
    {
        $node = trim($node, '\/');
        if ($node === '') {
            return '';
        }

        $prefix = explode('/', $node, 2)[0];
        if ($plugin = AppService::resolvePluginPrefix($prefix, true) ?: AppService::resolve($prefix, true)) {
            return strval($plugin['code'] ?? '');
        }
        if ($app = AppService::get($prefix)) {
            return strval($app['code'] ?? $prefix);
        }

        return '';
    }
}
