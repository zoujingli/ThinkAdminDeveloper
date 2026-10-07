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
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class WemallSqliteMigrationTest extends TestCase
{
    public function testSqliteMigrationPreservesDataAndEnforcesConstraints(): void
    {
        require_once dirname(__DIR__) . '/stc/database/20241010000010_fix_wemall_constraints.php';

        $database = new \PDO('sqlite::memory:');
        $database->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $adapter = AdapterFactory::instance()->getAdapter('sqlite', [
            'adapter' => 'sqlite',
            'connection' => $database,
            'name' => ':memory:',
            'table_prefix' => '',
        ]);
        $adapter = AdapterFactory::instance()->getWrapper('prefix', $adapter);
        foreach ([new \InstallPayment20241010('test', 20241010000006), new \InstallWemall20241010('test', 20241010000007)] as $schema) {
            $schema->setAdapter($adapter);
            $schema->change();
        }
        $database->exec("INSERT INTO plugin_wemall_order (id, order_no, amount_real) VALUES (1, 'SQLITE-CHECK', 100)");
        $database->exec('INSERT INTO plugin_payment_balance (id, amount) VALUES (1, -10)');
        $database->exec('INSERT INTO plugin_wemall_user_rebate (id, amount) VALUES (1, 10)');

        $migration = new \FixWemallConstraints('test', 20241010000010);
        $migration->setAdapter($adapter);
        $migration->change();
        $migration->change();

        $this->assertSame(100.0, (float)$database->query('SELECT amount_real FROM plugin_wemall_order WHERE id = 1')->fetchColumn());
        $this->assertSame(-10.0, (float)$database->query('SELECT amount FROM plugin_payment_balance WHERE id = 1')->fetchColumn());
        $this->assertTrue($migration->table('plugin_payment_balance')->hasColumn('source_type'));
        $this->assertTrue($migration->table('plugin_payment_integral')->hasColumn('source_id'));
        $this->assertTrue($migration->table('plugin_wemall_user_rebate')->hasColumn('order_item_id'));
        foreach (['path', 'puid1', 'puid2', 'puid3'] as $column) {
            $this->assertTrue($migration->table('plugin_wemall_user_relation')->hasIndex([$column]));
        }

        foreach ([
            'UPDATE plugin_wemall_order SET amount_real = -1 WHERE id = 1',
            'UPDATE plugin_wemall_order SET status = 8 WHERE id = 1',
            'UPDATE plugin_wemall_order SET payment_status = 3 WHERE id = 1',
            'UPDATE plugin_wemall_order SET delivery_type = 2 WHERE id = 1',
            'UPDATE plugin_wemall_user_rebate SET amount = -1 WHERE id = 1',
            "INSERT INTO plugin_wemall_order (order_no, amount_real) VALUES ('SQLITE-INVALID', -1)",
        ] as $statement) {
            try {
                $database->exec($statement);
                $this->fail('SQLite accepted a value outside the migration constraints.');
            } catch (\PDOException $exception) {
                $this->assertStringContainsString('CHECK constraint failed', $exception->getMessage());
            }
        }
        $database->exec('UPDATE plugin_wemall_order SET status = 7, payment_status = 2, delivery_type = 1 WHERE id = 1');
        $this->assertSame(7, (int)$database->query('SELECT status FROM plugin_wemall_order WHERE id = 1')->fetchColumn());
    }
}
