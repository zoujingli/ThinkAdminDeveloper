<?php

declare(strict_types=1);

use plugin\system\model\SystemBase;
use think\admin\Library;

/**
 * 构建 System 模块语言包。
 *
 * @param array<string, string> $static
 * @return array<string, string>
 */
return static function (string $langSet, string $dictType, string $menuType, array $static = []): array {
    $cacheKey = "system.lang.{$langSet}";
    $dynamic = Library::$sapp->cache->get($cacheKey, []);
    if (!is_array($dynamic) || $dynamic === []) {
        $dynamic = [];
        try {
            if ($dictType !== '') {
                $dynamic = array_column(SystemBase::items($dictType), 'name', 'code');
            }
            if ($menuType !== '') {
                foreach (array_column(SystemBase::items($menuType), 'name', 'code') as $code => $name) {
                    $dynamic["menus_{$code}"] = $name;
                }
            }
        } catch (\Throwable) {
            $dynamic = [];
        }
        Library::$sapp->cache->set($cacheKey, $dynamic, 360);
    }

    return array_merge($static, $dynamic);
};
