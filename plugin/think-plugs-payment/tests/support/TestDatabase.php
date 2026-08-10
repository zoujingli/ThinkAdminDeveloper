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

namespace think\admin\tests\support;

use Phinx\Db\Adapter\AdapterFactory;
use think\facade\Db;
use think\migration\Migrator;

final class TestDatabase
{
    private const TABLES = [
        'system_data',
        'plugin_account_user',
        'plugin_account_bind',
        'plugin_account_auth',
        'plugin_account_msms',
        'plugin_payment_address',
        'plugin_payment_balance',
        'plugin_payment_config',
        'plugin_payment_integral',
        'plugin_payment_record',
        'plugin_payment_refund',
    ];

    public static function createSchema(array $migrations): void
    {
        Db::execute('CREATE TABLE system_data (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL DEFAULT "",
            value TEXT,
            create_time TEXT,
            update_time TEXT
        )');

        $adapter = AdapterFactory::instance()->getAdapter('sqlite', [
            'connection' => Db::connect()->connect(),
            'name' => ':memory:',
        ]);
        foreach ($migrations as [$file, $class, $version]) {
            if (!is_file($file)) {
                throw new \RuntimeException("Test migration not found: {$file}");
            }
            require_once $file;
            $migration = new $class('test', $version);
            if (!$migration instanceof Migrator) {
                throw new \RuntimeException("Invalid test migration: {$class}");
            }
            $migration->setAdapter($adapter);
            $migration->change();
        }
    }

    public static function reset(): void
    {
        foreach (array_reverse(self::TABLES) as $table) {
            Db::table($table)->delete(true);
        }
    }
}
