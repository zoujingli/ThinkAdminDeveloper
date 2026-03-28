<?php

declare(strict_types=1);

namespace plugin\system\service;

use think\App;

/**
 * System 模块语言包服务.
 * @class LangService
 */
class LangService
{
    public static function loadCurrent(App $app): void
    {
        self::load($app, $app->lang->getLangSet());
    }

    public static function load(App $app, string $langSet): void
    {
        foreach (self::files($langSet) as $file) {
            $app->lang->load($file, $langSet);
        }
    }

    /**
     * @return array<int, string>
     */
    private static function files(string $langSet): array
    {
        $files = [
            dirname(__DIR__) . "/lang/{$langSet}.php",
            dirname(__DIR__, 2) . "/lang/{$langSet}.php",
            syspath("lang/{$langSet}.php"),
        ];

        return array_values(array_filter(array_unique($files), 'is_file'));
    }
}
