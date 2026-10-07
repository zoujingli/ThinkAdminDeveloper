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

namespace plugin\wemall\tests;

use Phinx\Db\Adapter\AdapterFactory;
use Phinx\Db\Adapter\AdapterInterface;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class WemallMigrationTest extends TestCase
{
    public function testSqliteMigrationPreservesDataAndEnforcesConstraints(): void
    {
        $this->assertSqliteMigration('');
    }

    public function testSqliteMigrationWithTablePrefixPreservesDataAndEnforcesConstraints(): void
    {
        $this->assertSqliteMigration('tenant_');
    }

    public function testMysqlMigrationWithAndWithoutTablePrefixesPreservesDataAndEnforcesConstraints(): void
    {
        $port = getenv('PHINX_TEST_MYSQL_PORT');
        if (!$port) {
            self::markTestSkipped('Set PHINX_TEST_MYSQL_PORT to run against a disposable MySQL instance.');
        }
        $database = new \PDO('mysql:host=127.0.0.1;port=' . (int)$port . ';dbname=phinx_fixture;charset=utf8mb4', 'root', '');
        $database->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $name = 'wemall_fixture_' . bin2hex(random_bytes(6));
        $database->exec("CREATE DATABASE `{$name}` CHARACTER SET utf8mb4");
        try {
            foreach (['', 'tenant_'] as $prefix) {
                $adapter = AdapterFactory::instance()->getAdapter('mysql', [
                    'adapter' => 'mysql', 'host' => '127.0.0.1', 'port' => (int)$port,
                    'name' => $name, 'user' => 'root', 'pass' => '', 'charset' => 'utf8mb4', 'table_prefix' => $prefix,
                ]);
                $this->assertMigration(AdapterFactory::instance()->getWrapper('prefix', $adapter), $prefix);
            }
        } finally {
            $database->exec("DROP DATABASE `{$name}`");
        }
    }

    private function assertSqliteMigration(string $prefix): void
    {
        $database = new \PDO('sqlite::memory:');
        $database->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $adapter = AdapterFactory::instance()->getAdapter('sqlite', [
            'adapter' => 'sqlite',
            'connection' => $database,
            'name' => ':memory:',
            'table_prefix' => $prefix,
        ]);
        $adapter = AdapterFactory::instance()->getWrapper('prefix', $adapter);
        $this->assertMigration($adapter, $prefix);
    }

    private function assertMigration(AdapterInterface $adapter, string $prefix): void
    {
        require_once dirname(__DIR__) . '/stc/database/20241010000010_fix_wemall_constraints.php';
        $database = $adapter->getConnection();
        foreach ([new \InstallPayment20241010('test', 20241010000006), new \InstallWemall20241010('test', 20241010000007)] as $schema) {
            $schema->setAdapter($adapter);
            $schema->change();
        }
        $database->exec("INSERT INTO {$prefix}plugin_wemall_order (id, order_no, amount_real) VALUES (1, 'SQLITE-CHECK', 100)");
        $database->exec("INSERT INTO {$prefix}plugin_payment_balance (id, amount) VALUES (1, -10)");
        $database->exec("INSERT INTO {$prefix}plugin_wemall_user_rebate (id, amount) VALUES (1, 10)");

        $migration = new \FixWemallConstraints('test', 20241010000010);
        $migration->setAdapter($adapter);
        $migration->change();
        $migration->change();

        $this->assertSame(100.0, (float)$database->query("SELECT amount_real FROM {$prefix}plugin_wemall_order WHERE id = 1")->fetchColumn());
        $this->assertSame(-10.0, (float)$database->query("SELECT amount FROM {$prefix}plugin_payment_balance WHERE id = 1")->fetchColumn());
        $this->assertTrue($migration->table('plugin_payment_balance')->hasColumn('source_type'));
        $this->assertTrue($migration->table('plugin_payment_integral')->hasColumn('source_id'));
        $this->assertTrue($migration->table('plugin_wemall_user_rebate')->hasColumn('order_item_id'));
        foreach (['path', 'puid1', 'puid2', 'puid3'] as $column) {
            $this->assertTrue($migration->table('plugin_wemall_user_relation')->hasIndex([$column]));
        }

        foreach ([
            "UPDATE {$prefix}plugin_wemall_order SET amount_real = -1 WHERE id = 1",
            "UPDATE {$prefix}plugin_wemall_order SET status = 8 WHERE id = 1",
            "UPDATE {$prefix}plugin_wemall_order SET payment_status = 3 WHERE id = 1",
            "UPDATE {$prefix}plugin_wemall_order SET delivery_type = 2 WHERE id = 1",
            "UPDATE {$prefix}plugin_wemall_user_rebate SET amount = -1 WHERE id = 1",
            "INSERT INTO {$prefix}plugin_wemall_order (order_no, amount_real) VALUES ('SQLITE-INVALID', -1)",
        ] as $statement) {
            try {
                $database->exec($statement);
                $this->fail('The database accepted a value outside the migration constraints.');
            } catch (\PDOException $exception) {
                $this->assertStringContainsStringIgnoringCase('check constraint', $exception->getMessage());
            }
        }
        $database->exec("UPDATE {$prefix}plugin_wemall_order SET status = 7, payment_status = 2, delivery_type = 1 WHERE id = 1");
        $this->assertSame(7, (int)$database->query("SELECT status FROM {$prefix}plugin_wemall_order WHERE id = 1")->fetchColumn());
    }
}
