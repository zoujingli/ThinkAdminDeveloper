<?php

declare(strict_types=1);

namespace think\admin\extend;

/**
 * 标准树形数组工具。
 */
class ArrayTree
{
    /**
     * 二维数组转多维数据树。
     */
    public static function arr2tree(array $list, string $ckey = 'id', string $pkey = 'pid', string $chil = 'sub'): array
    {
        [$tree, $list] = [[], array_column($list, null, $ckey)];
        foreach ($list as $it) {
            isset($list[$it[$pkey]]) ? $list[$it[$pkey]][$chil][] = &$list[$it[$ckey]] : $tree[] = &$list[$it[$ckey]];
        }
        return $tree;
    }

    /**
     * 二维数组转带层级标识的数据表。
     * 这里继续输出历史字段 `spc/spt/spl/sps/spp`，避免影响后台树表展示。
     */
    public static function arr2table(array $list, string $ckey = 'id', string $pkey = 'pid', string $path = 'path'): array
    {
        $build = static function (array $nodes, callable $build, array &$data = [], string $parent = '') use ($ckey, $path) {
            foreach ($nodes as $node) {
                $subs = $node['sub'] ?? [];
                unset($node['sub']);
                $node[$path] = "{$parent}-{$node[$ckey]}";
                $node['spc'] = count($subs);
                $node['spt'] = substr_count($parent, '-');
                $node['spl'] = str_repeat('ㅤ├ㅤ', $node['spt']);
                $node['sps'] = ",{$node[$ckey]},";
                array_walk_recursive($subs, static function ($val, $key) use ($ckey, &$node) {
                    if ($key === $ckey) {
                        $node['sps'] .= "{$val},";
                    }
                });
                $node['spp'] = arr2str(str2arr(strtr($parent . $node['sps'], '-', ',')));
                $data[] = $node;
                if (!empty($subs)) {
                    $build($subs, $build, $data, $node[$path]);
                }
            }
            return $data;
        };
        return $build(static::arr2tree($list, $ckey, $pkey), $build);
    }

    /**
     * 获取数据树子 ID 集合。
     */
    public static function getArrSubIds(array $list, $value = 0, string $ckey = 'id', string $pkey = 'pid'): array
    {
        $ids = [(int)$value];
        foreach ($list as $vo) {
            if ((int)$vo[$pkey] > 0 && (int)$vo[$pkey] === (int)$value) {
                $ids = array_merge($ids, static::getArrSubIds($list, (int)$vo[$ckey], $ckey, $pkey));
            }
        }
        return $ids;
    }
}
