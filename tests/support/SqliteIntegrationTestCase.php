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

namespace think\admin\tests\Support;

use PHPUnit\Framework\TestCase;
use plugin\account\model\PluginAccountUser;
use plugin\account\service\Account;
use plugin\account\service\contract\AccountInterface;
use plugin\payment\model\PluginPaymentAddress;
use plugin\payment\model\PluginPaymentRecord;
use plugin\payment\service\Payment;
use plugin\storage\model\SystemFile;
use plugin\system\model\SystemAuth;
use plugin\system\model\SystemBase;
use plugin\system\model\SystemConfig;
use plugin\system\model\SystemData;
use plugin\system\model\SystemMenu;
use plugin\system\model\SystemNode;
use plugin\system\model\SystemOplog;
use plugin\system\model\SystemUser;
use plugin\system\service\UserService;
use plugin\wemall\model\PluginWemallOrder;
use plugin\wemall\model\PluginWemallOrderItem;
use plugin\wemall\model\PluginWemallUserRelation;
use plugin\worker\model\SystemQueue;
use think\admin\contract\SystemContextInterface;
use think\admin\runtime\NullSystemContext;
use think\admin\runtime\RequestContext;
use think\admin\service\RuntimeService;
use think\App;
use think\Container;
use think\db\ConnectionInterface;

abstract class SqliteIntegrationTestCase extends TestCase
{
    protected App $app;

    protected TestSystemContext $context;

    protected string $databaseFile;

    protected string $sandboxPath;

    protected string $connectionName;

    private ?array $accountTypesSnapshot = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sandboxPath = sys_get_temp_dir() . '/thinkadmin-sqlite-' . md5(static::class . microtime(true) . uniqid('', true));
        $this->databaseFile = $this->sandboxPath . '/database.sqlite';
        $this->connectionName = 'sqlite_' . md5($this->sandboxPath);

        if (!is_dir($this->sandboxPath)) {
            mkdir($this->sandboxPath, 0777, true);
        }

        touch($this->databaseFile);
        $this->bootApplication();
        $this->defineSchema();
        $this->afterSchemaCreated();
    }

    protected function tearDown(): void
    {
        $this->restoreAccountTypes();
        RequestContext::clear();
        function_exists('sysvar') && sysvar('', '');
        Container::getInstance()->instance(SystemContextInterface::class, new NullSystemContext());

        if (isset($this->app)) {
            try {
                $this->app->db->connect()->close();
            } catch (\Throwable) {
            }
        }

        $this->removeDirectory($this->sandboxPath);
        parent::tearDown();
    }

    abstract protected function defineSchema(): void;

    protected function afterSchemaCreated(): void {}

    protected function connection(): ConnectionInterface
    {
        return $this->app->db->connect($this->connectionName, true);
    }

    protected function executeStatements(array $statements): void
    {
        $connection = $this->connection();
        foreach ($statements as $statement) {
            $connection->execute($statement);
        }
    }

    protected function createAccountTables(): void
    {
        $this->executeStatements([
            <<<'SQL'
CREATE TABLE plugin_account_user (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT DEFAULT '',
    phone TEXT DEFAULT '',
    email TEXT DEFAULT '',
    unionid TEXT DEFAULT '',
    username TEXT DEFAULT '',
    nickname TEXT DEFAULT '',
    password TEXT DEFAULT '',
    headimg TEXT DEFAULT '',
    region_prov TEXT DEFAULT '',
    region_city TEXT DEFAULT '',
    region_area TEXT DEFAULT '',
    remark TEXT DEFAULT '',
    extra TEXT DEFAULT '',
    sort INTEGER DEFAULT 0,
    status INTEGER DEFAULT 1,
    create_time TEXT DEFAULT NULL,
    update_time TEXT DEFAULT NULL,
    delete_time TEXT DEFAULT NULL
)
SQL,
            <<<'SQL'
CREATE TABLE plugin_account_bind (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    unid INTEGER DEFAULT 0,
    type TEXT DEFAULT '',
    phone TEXT DEFAULT '',
    appid TEXT DEFAULT '',
    openid TEXT DEFAULT '',
    unionid TEXT DEFAULT '',
    headimg TEXT DEFAULT '',
    nickname TEXT DEFAULT '',
    password TEXT DEFAULT '',
    extra TEXT DEFAULT '',
    sort INTEGER DEFAULT 0,
    status INTEGER DEFAULT 1,
    create_time TEXT DEFAULT NULL,
    update_time TEXT DEFAULT NULL,
    delete_time TEXT DEFAULT NULL
)
SQL,
            <<<'SQL'
CREATE TABLE plugin_account_auth (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usid INTEGER DEFAULT 0,
    time INTEGER DEFAULT 0,
    type TEXT DEFAULT '',
    token TEXT DEFAULT '',
    tokenv TEXT DEFAULT '',
    create_time TEXT DEFAULT NULL,
    update_time TEXT DEFAULT NULL
)
SQL,
        ]);
    }

    protected function createPaymentBalanceTable(): void
    {
        $this->executeStatements([
            <<<'SQL'
CREATE TABLE plugin_payment_balance (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    unid INTEGER DEFAULT 0,
    code TEXT DEFAULT '',
    source_type TEXT DEFAULT '',
    source_id TEXT DEFAULT '',
    name TEXT DEFAULT '',
    remark TEXT DEFAULT '',
    amount NUMERIC DEFAULT 0.00,
    amount_prev NUMERIC DEFAULT 0.00,
    amount_next NUMERIC DEFAULT 0.00,
    cancel INTEGER DEFAULT 0,
    unlock INTEGER DEFAULT 0,
    create_by INTEGER DEFAULT 0,
    cancel_time TEXT DEFAULT NULL,
    unlock_time TEXT DEFAULT NULL,
    create_time TEXT DEFAULT NULL,
    update_time TEXT DEFAULT NULL,
    delete_time TEXT DEFAULT NULL
)
SQL,
        ]);
    }

    protected function createPaymentIntegralTable(): void
    {
        $this->executeStatements([
            <<<'SQL'
CREATE TABLE plugin_payment_integral (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    unid INTEGER DEFAULT 0,
    code TEXT DEFAULT '',
    source_type TEXT DEFAULT '',
    source_id TEXT DEFAULT '',
    name TEXT DEFAULT '',
    remark TEXT DEFAULT '',
    amount NUMERIC DEFAULT 0.00,
    amount_prev NUMERIC DEFAULT 0.00,
    amount_next NUMERIC DEFAULT 0.00,
    cancel INTEGER DEFAULT 0,
    unlock INTEGER DEFAULT 0,
    create_by INTEGER DEFAULT 0,
    cancel_time TEXT DEFAULT NULL,
    unlock_time TEXT DEFAULT NULL,
    create_time TEXT DEFAULT NULL,
    update_time TEXT DEFAULT NULL,
    delete_time TEXT DEFAULT NULL
)
SQL,
        ]);
    }

    protected function createPaymentRecordTable(): void
    {
        $this->executeStatements([
            <<<'SQL'
CREATE TABLE plugin_payment_record (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    unid INTEGER DEFAULT 0,
    usid INTEGER DEFAULT 0,
    code TEXT DEFAULT '',
    order_no TEXT DEFAULT '',
    order_name TEXT DEFAULT '',
    order_amount NUMERIC DEFAULT 0.00,
    channel_type TEXT DEFAULT '',
    channel_code TEXT DEFAULT '',
    payment_time TEXT DEFAULT NULL,
    payment_trade TEXT DEFAULT '',
    payment_status INTEGER DEFAULT 0,
    payment_amount NUMERIC DEFAULT 0.00,
    payment_coupon NUMERIC DEFAULT 0.00,
    payment_images TEXT DEFAULT '',
    payment_remark TEXT DEFAULT '',
    payment_notify TEXT DEFAULT '',
    audit_user INTEGER DEFAULT 0,
    audit_time TEXT DEFAULT NULL,
    audit_status INTEGER DEFAULT 1,
    audit_remark TEXT DEFAULT '',
    refund_status INTEGER DEFAULT 0,
    refund_amount NUMERIC DEFAULT 0.00,
    refund_payment NUMERIC DEFAULT 0.00,
    refund_balance NUMERIC DEFAULT 0.00,
    refund_integral NUMERIC DEFAULT 0.00,
    used_payment NUMERIC DEFAULT 0.00,
    used_balance NUMERIC DEFAULT 0.00,
    used_integral NUMERIC DEFAULT 0.00,
    create_time TEXT DEFAULT NULL,
    update_time TEXT DEFAULT NULL
)
SQL,
        ]);
    }

    protected function createSystemFileTable(): void
    {
        $this->executeStatements([
            <<<'SQL'
CREATE TABLE system_file (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT DEFAULT '',
    hash TEXT DEFAULT '',
    tags TEXT DEFAULT '',
    name TEXT DEFAULT '',
    xext TEXT DEFAULT '',
    xurl TEXT DEFAULT '',
    xkey TEXT DEFAULT '',
    mime TEXT DEFAULT '',
    size INTEGER DEFAULT 0,
    uuid INTEGER DEFAULT 0,
    unid INTEGER DEFAULT 0,
    isfast INTEGER DEFAULT 0,
    issafe INTEGER DEFAULT 0,
    status INTEGER DEFAULT 1,
    create_time TEXT DEFAULT NULL,
    update_time TEXT DEFAULT NULL
)
SQL,
        ]);
    }

    protected function createSystemBaseTable(): void
    {
        $this->executeStatements([
            <<<'SQL'
CREATE TABLE system_base (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT DEFAULT '',
    code TEXT DEFAULT '',
    name TEXT DEFAULT '',
    content TEXT DEFAULT '',
    sort INTEGER DEFAULT 0,
    status INTEGER DEFAULT 1,
    delete_time TEXT DEFAULT NULL,
    deleted_by INTEGER DEFAULT 0,
    create_time TEXT DEFAULT NULL
)
SQL,
        ]);
    }

    protected function createSystemUserTable(): void
    {
        $this->executeStatements([
            <<<'SQL'
CREATE TABLE system_user (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usertype TEXT DEFAULT '',
    username TEXT DEFAULT '',
    password TEXT DEFAULT '',
    nickname TEXT DEFAULT '',
    headimg TEXT DEFAULT '',
    authorize TEXT DEFAULT '',
    contact_qq TEXT DEFAULT '',
    contact_mail TEXT DEFAULT '',
    contact_phone TEXT DEFAULT '',
    login_ip TEXT DEFAULT '',
    login_at TEXT DEFAULT '',
    login_num INTEGER DEFAULT 0,
    describe TEXT DEFAULT '',
    sort INTEGER DEFAULT 0,
    status INTEGER DEFAULT 1,
    delete_time TEXT DEFAULT NULL,
    create_time TEXT DEFAULT NULL,
    update_time TEXT DEFAULT NULL
)
SQL,
        ]);
    }

    protected function createSystemAuthTable(): void
    {
        $this->executeStatements([
            <<<'SQL'
CREATE TABLE system_auth (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT DEFAULT '',
    utype TEXT DEFAULT '',
    desc TEXT DEFAULT '',
    sort INTEGER DEFAULT 0,
    status INTEGER DEFAULT 1,
    create_time TEXT DEFAULT NULL
)
SQL,
        ]);
    }

    protected function createSystemAuthNodeTable(): void
    {
        $this->executeStatements([
            <<<'SQL'
CREATE TABLE system_auth_node (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    auth INTEGER DEFAULT 0,
    node TEXT DEFAULT ''
)
SQL,
        ]);
    }

    protected function createSystemMenuTable(): void
    {
        $this->executeStatements([
            <<<'SQL'
CREATE TABLE system_menu (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    pid INTEGER DEFAULT 0,
    title TEXT DEFAULT '',
    icon TEXT DEFAULT '',
    node TEXT DEFAULT '',
    url TEXT DEFAULT '',
    params TEXT DEFAULT '',
    target TEXT DEFAULT '_self',
    sort INTEGER DEFAULT 0,
    status INTEGER DEFAULT 1,
    create_time TEXT DEFAULT NULL
)
SQL,
        ]);
    }

    protected function createSystemConfigTable(): void
    {
        $this->executeStatements([
            <<<'SQL'
CREATE TABLE system_config (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT DEFAULT '',
    name TEXT DEFAULT '',
    value TEXT DEFAULT ''
)
SQL,
        ]);
    }

    protected function createSystemDataTable(): void
    {
        $this->executeStatements([
            <<<'SQL'
CREATE TABLE system_data (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT DEFAULT '',
    value TEXT DEFAULT '',
    create_time TEXT DEFAULT NULL,
    update_time TEXT DEFAULT NULL
)
SQL,
        ]);
    }

    protected function createSystemOplogTable(): void
    {
        $this->executeStatements([
            <<<'SQL'
CREATE TABLE system_oplog (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    node TEXT DEFAULT '',
    geoip TEXT DEFAULT '',
    action TEXT DEFAULT '',
    content TEXT DEFAULT '',
    username TEXT DEFAULT '',
    create_time TEXT DEFAULT NULL
)
SQL,
        ]);
    }

    protected function createSystemQueueTable(): void
    {
        $this->executeStatements([
            <<<'SQL'
CREATE TABLE system_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT DEFAULT '',
    exec_hash TEXT DEFAULT '',
    title TEXT DEFAULT '',
    command TEXT DEFAULT '',
    exec_pid INTEGER DEFAULT 0,
    exec_data TEXT DEFAULT '',
    exec_time INTEGER DEFAULT 0,
    exec_desc TEXT DEFAULT '',
    enter_time NUMERIC DEFAULT 0.0000,
    outer_time NUMERIC DEFAULT 0.0000,
    loops_time INTEGER DEFAULT 0,
    attempts INTEGER DEFAULT 0,
    message TEXT DEFAULT '',
    status INTEGER DEFAULT 1,
    create_time TEXT DEFAULT NULL
)
SQL,
        ]);
    }

    protected function createPaymentAddressTable(): void
    {
        $this->executeStatements([
            <<<'SQL'
CREATE TABLE plugin_payment_address (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    unid INTEGER DEFAULT 0,
    type INTEGER DEFAULT 0,
    user_name TEXT DEFAULT '',
    user_phone TEXT DEFAULT '',
    idcode TEXT DEFAULT '',
    idimg1 TEXT DEFAULT '',
    idimg2 TEXT DEFAULT '',
    region_prov TEXT DEFAULT '',
    region_city TEXT DEFAULT '',
    region_area TEXT DEFAULT '',
    region_addr TEXT DEFAULT '',
    create_time TEXT DEFAULT NULL,
    update_time TEXT DEFAULT NULL,
    delete_time TEXT DEFAULT NULL
)
SQL,
        ]);
    }

    protected function createPaymentRefundTable(): void
    {
        $this->executeStatements([
            <<<'SQL'
CREATE TABLE plugin_payment_refund (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    unid INTEGER DEFAULT 0,
    usid INTEGER DEFAULT 0,
    code TEXT DEFAULT '',
    record_code TEXT DEFAULT '',
    refund_time TEXT DEFAULT NULL,
    refund_trade TEXT DEFAULT '',
    refund_status INTEGER DEFAULT 0,
    refund_amount NUMERIC DEFAULT 0.00,
    refund_account TEXT DEFAULT '',
    refund_scode TEXT DEFAULT '',
    refund_remark TEXT DEFAULT '',
    refund_notify TEXT DEFAULT '',
    used_payment NUMERIC DEFAULT 0.00,
    used_balance NUMERIC DEFAULT 0.00,
    used_integral NUMERIC DEFAULT 0.00,
    create_time TEXT DEFAULT NULL,
    update_time TEXT DEFAULT NULL
)
SQL,
        ]);
    }

    protected function createWemallOrderTable(): void
    {
        $this->executeStatements([
            <<<'SQL'
CREATE TABLE plugin_wemall_order (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    unid INTEGER DEFAULT 0,
    puid1 INTEGER DEFAULT 0,
    puid2 INTEGER DEFAULT 0,
    puid3 INTEGER DEFAULT 0,
    order_no TEXT DEFAULT '',
    status INTEGER DEFAULT 2,
    delivery_type INTEGER DEFAULT 1,
    payment_status INTEGER DEFAULT 0,
    refund_status INTEGER DEFAULT 0,
    cancel_status INTEGER DEFAULT 0,
    deleted_status INTEGER DEFAULT 0,
    amount_goods NUMERIC DEFAULT 0.00,
    amount_discount NUMERIC DEFAULT 0.00,
    amount_reduct NUMERIC DEFAULT 0.00,
    amount_express NUMERIC DEFAULT 0.00,
    amount_real NUMERIC DEFAULT 0.00,
    amount_total NUMERIC DEFAULT 0.00,
    payment_amount NUMERIC DEFAULT 0.00,
    amount_payment NUMERIC DEFAULT 0.00,
    amount_balance NUMERIC DEFAULT 0.00,
    amount_integral NUMERIC DEFAULT 0.00,
    rebate_amount NUMERIC DEFAULT 0.00,
    reward_balance NUMERIC DEFAULT 0.00,
    reward_integral NUMERIC DEFAULT 0.00,
    level_agent INTEGER DEFAULT 0,
    level_member INTEGER DEFAULT 0,
    payment_time TEXT DEFAULT NULL,
    deleted_remark TEXT DEFAULT '',
    create_time TEXT DEFAULT NULL,
    update_time TEXT DEFAULT NULL,
    delete_time TEXT DEFAULT NULL
)
SQL,
        ]);
    }

    protected function createWemallOrderItemTable(): void
    {
        $this->executeStatements([
            <<<'SQL'
CREATE TABLE plugin_wemall_order_item (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    unid INTEGER DEFAULT 0,
    ssid INTEGER DEFAULT 0,
    status INTEGER DEFAULT 1,
    level_code INTEGER DEFAULT 0,
    level_agent INTEGER DEFAULT 0,
    level_upgrade INTEGER DEFAULT 0,
    discount_id INTEGER DEFAULT 0,
    rebate_type INTEGER DEFAULT 0,
    stock_sales INTEGER DEFAULT 0,
    delivery_count INTEGER DEFAULT 0,
    order_no TEXT DEFAULT '',
    gcode TEXT DEFAULT '',
    ghash TEXT DEFAULT '',
    gname TEXT DEFAULT '',
    gcover TEXT DEFAULT '',
    gunit TEXT DEFAULT '',
    gspec TEXT DEFAULT '',
    gsku TEXT DEFAULT '',
    level_name TEXT DEFAULT '',
    delivery_code TEXT DEFAULT '',
    price_market NUMERIC DEFAULT 0.00,
    price_selling NUMERIC DEFAULT 0.00,
    amount_cost NUMERIC DEFAULT 0.00,
    total_price_market NUMERIC DEFAULT 0.00,
    total_price_selling NUMERIC DEFAULT 0.00,
    total_price_cost NUMERIC DEFAULT 0.00,
    discount_rate NUMERIC DEFAULT 0.00,
    discount_amount NUMERIC DEFAULT 0.00,
    total_allow_balance NUMERIC DEFAULT 0.00,
    total_allow_integral NUMERIC DEFAULT 0.00,
    rebate_amount NUMERIC DEFAULT 0.00,
    total_reward_balance NUMERIC DEFAULT 0.00,
    total_reward_integral NUMERIC DEFAULT 0.00,
    create_time TEXT DEFAULT NULL,
    update_time TEXT DEFAULT NULL,
    delete_time TEXT DEFAULT NULL
)
SQL,
        ]);
    }

    protected function createWemallOrderSenderTable(): void
    {
        $this->executeStatements([
            <<<'SQL'
CREATE TABLE plugin_wemall_order_sender (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    unid INTEGER DEFAULT 0,
    ssid INTEGER DEFAULT 0,
    status INTEGER DEFAULT 1,
    delivery_count INTEGER DEFAULT 0,
    address_id TEXT DEFAULT '',
    order_no TEXT DEFAULT '',
    delivery_code TEXT DEFAULT '',
    delivery_remark TEXT DEFAULT '',
    company_code TEXT DEFAULT '',
    company_name TEXT DEFAULT '',
    express_code TEXT DEFAULT '',
    express_remark TEXT DEFAULT '',
    express_time TEXT DEFAULT NULL,
    user_name TEXT DEFAULT '',
    user_phone TEXT DEFAULT '',
    user_idcode TEXT DEFAULT '',
    user_idimg1 TEXT DEFAULT '',
    user_idimg2 TEXT DEFAULT '',
    region_prov TEXT DEFAULT '',
    region_city TEXT DEFAULT '',
    region_area TEXT DEFAULT '',
    region_addr TEXT DEFAULT '',
    extra TEXT DEFAULT '',
    delivery_amount NUMERIC DEFAULT 0.00,
    create_time TEXT DEFAULT NULL,
    update_time TEXT DEFAULT NULL,
    delete_time TEXT DEFAULT NULL
)
SQL,
        ]);
    }

    protected function createWemallUserRebateTable(): void
    {
        $this->executeStatements([
            <<<'SQL'
CREATE TABLE plugin_wemall_user_rebate (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    unid INTEGER DEFAULT 0,
    order_unid INTEGER DEFAULT 0,
    layer INTEGER DEFAULT 1,
    status INTEGER DEFAULT 0,
    code TEXT DEFAULT '',
    hash TEXT DEFAULT '',
    name TEXT DEFAULT '',
    type TEXT DEFAULT '',
    date TEXT DEFAULT '',
    order_no TEXT DEFAULT '',
    remark TEXT DEFAULT '',
    amount NUMERIC DEFAULT 0.00,
    order_amount NUMERIC DEFAULT 0.00,
    confirm_time TEXT DEFAULT NULL,
    create_time TEXT DEFAULT NULL,
    update_time TEXT DEFAULT NULL,
    delete_time TEXT DEFAULT NULL
)
SQL,
        ]);
    }

    protected function createWemallUserTransferTable(): void
    {
        $this->executeStatements([
            <<<'SQL'
CREATE TABLE plugin_wemall_user_transfer (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    unid INTEGER DEFAULT 0,
    status INTEGER DEFAULT 0,
    code TEXT DEFAULT '',
    type TEXT DEFAULT '',
    amount NUMERIC DEFAULT 0.00,
    create_time TEXT DEFAULT NULL,
    update_time TEXT DEFAULT NULL,
    delete_time TEXT DEFAULT NULL
)
SQL,
        ]);
    }

    protected function createWemallConfigLevelTable(): void
    {
        $this->executeStatements([
            <<<'SQL'
CREATE TABLE plugin_wemall_config_level (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    number INTEGER DEFAULT 0,
    status INTEGER DEFAULT 1,
    upgrade_team INTEGER DEFAULT 0,
    upgrade_type INTEGER DEFAULT 0,
    utime INTEGER DEFAULT 0,
    name TEXT DEFAULT '',
    remark TEXT DEFAULT '',
    cardbg TEXT DEFAULT '',
    cover TEXT DEFAULT '',
    extra TEXT DEFAULT '',
    delete_time TEXT DEFAULT NULL,
    create_time TEXT DEFAULT NULL,
    update_time TEXT DEFAULT NULL
)
SQL,
        ]);
    }

    protected function createWemallConfigAgentTable(): void
    {
        $this->executeStatements([
            <<<'SQL'
CREATE TABLE plugin_wemall_config_agent (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    number INTEGER DEFAULT 0,
    status INTEGER DEFAULT 1,
    upgrade_type INTEGER DEFAULT 0,
    utime INTEGER DEFAULT 0,
    name TEXT DEFAULT '',
    remark TEXT DEFAULT '',
    cardbg TEXT DEFAULT '',
    cover TEXT DEFAULT '',
    extra TEXT DEFAULT '',
    delete_time TEXT DEFAULT NULL,
    create_time TEXT DEFAULT NULL,
    update_time TEXT DEFAULT NULL
)
SQL,
        ]);
    }

    protected function createWemallUserCreateTable(): void
    {
        $this->executeStatements([
            <<<'SQL'
CREATE TABLE plugin_wemall_user_create (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    unid INTEGER DEFAULT 0,
    status INTEGER DEFAULT 1,
    agent_entry INTEGER DEFAULT 0,
    phone TEXT DEFAULT '',
    name TEXT DEFAULT '',
    password TEXT DEFAULT '',
    headimg TEXT DEFAULT '',
    agent_phone TEXT DEFAULT '',
    rebate_total NUMERIC DEFAULT 0.00,
    rebate_usable NUMERIC DEFAULT 0.00,
    rebate_total_code TEXT DEFAULT '',
    rebate_total_desc TEXT DEFAULT '',
    rebate_usable_code TEXT DEFAULT '',
    rebate_usable_desc TEXT DEFAULT '',
    create_time TEXT DEFAULT NULL,
    update_time TEXT DEFAULT NULL,
    delete_time TEXT DEFAULT NULL
)
SQL,
        ]);
    }

    protected function createWemallUserRelationTable(): void
    {
        $this->executeStatements([
            <<<'SQL'
CREATE TABLE plugin_wemall_user_relation (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    unid INTEGER DEFAULT 0,
    layer INTEGER DEFAULT 0,
    puid1 INTEGER DEFAULT 0,
    puid2 INTEGER DEFAULT 0,
    puid3 INTEGER DEFAULT 0,
    puids INTEGER DEFAULT 0,
    level_code INTEGER DEFAULT 0,
    agent_level_code INTEGER DEFAULT 0,
    entry_agent INTEGER DEFAULT 0,
    entry_member INTEGER DEFAULT 0,
    agent_state INTEGER DEFAULT 0,
    agent_uuid INTEGER DEFAULT 0,
    sort INTEGER DEFAULT 0,
    path TEXT DEFAULT '',
    level_name TEXT DEFAULT '',
    agent_level_name TEXT DEFAULT '',
    extra TEXT DEFAULT '',
    create_time TEXT DEFAULT NULL,
    update_time TEXT DEFAULT NULL,
    delete_time TEXT DEFAULT NULL
)
SQL,
        ]);
    }

    protected function decimal($value): string
    {
        return number_format((float)$value, 2, '.', '');
    }

    protected function configureAccountAccess(array $overrides = []): void
    {
        $this->rememberAccountTypes();
        $this->restoreAccountTypes();
        $this->context->setData('plugin.account.access', array_merge([
            'expire' => 3600,
            'headimg' => 'https://example.com/default-account.png',
            'userPrefix' => '测试账号',
        ], $overrides));
    }

    protected function createAccountUser(array $overrides = []): PluginAccountUser
    {
        $user = PluginAccountUser::mk();
        $user->save(array_merge([
            'code' => 'U' . random_int(100000, 999999),
            'phone' => $this->randomPhone(),
            'username' => 'user-' . random_int(100, 999),
            'nickname' => '测试用户',
            'extra' => [],
        ], $overrides));

        return $user->refresh();
    }

    protected function createAccountFixture(string $type = Account::WAP, array $data = []): AccountInterface
    {
        $field = Account::field($type) ?: 'phone';
        $value = strval($data[$field] ?? $this->makeAccountIdentity($field));

        $account = Account::mk($type);
        $account->set(array_merge([$field => $value], $data), false);

        return $account;
    }

    protected function createBoundAccountFixture(string $type = Account::WAP, array $bindData = [], array $userData = []): AccountInterface
    {
        $field = Account::field($type) ?: 'phone';
        $account = $this->createAccountFixture($type, $bindData);
        $current = $account->get();
        $identity = strval($current[$field] ?? $bindData[$field] ?? '');

        $map = isset($userData['id']) ? ['id' => intval($userData['id'])] : [$field => strval($userData[$field] ?? $identity)];
        $payload = array_merge([
            'username' => 'user-' . random_int(100, 999),
        ], $userData);

        if (!isset($payload[$field]) && isset($map[$field])) {
            $payload[$field] = $map[$field];
        }

        $account->bind($map, $payload);
        return $account;
    }

    protected function createPaidEmptyOrderFixture(
        string $orderNo,
        ?AccountInterface $account = null,
        array $overrides = []
    ): PluginPaymentRecord {
        $account ??= $this->createBoundAccountFixture();
        $response = Payment::mk(Payment::EMPTY)->create(
            $account,
            $orderNo,
            strval($overrides['title'] ?? '退款测试订单'),
            strval($overrides['order_amount'] ?? '10.00'),
            strval($overrides['pay_amount'] ?? '10.00'),
            strval($overrides['remark'] ?? '退款边界测试')
        );

        return PluginPaymentRecord::mk()->where(['code' => $response->record['code']])->findOrEmpty();
    }

    protected function createWemallOrderFixture(AccountInterface $account, array $overrides = []): PluginWemallOrder
    {
        $order = PluginWemallOrder::mk();
        $order->save(array_merge([
            'unid' => $account->getUnid(),
            'order_no' => 'ORDER-' . strtoupper(substr(md5(uniqid('', true)), 0, 10)),
            'status' => 2,
            'delivery_type' => 1,
            'payment_status' => 0,
            'refund_status' => 0,
            'cancel_status' => 0,
            'deleted_status' => 0,
            'puid1' => 0,
            'puid2' => 0,
            'puid3' => 0,
            'amount_goods' => '10.00',
            'amount_discount' => '10.00',
            'amount_reduct' => '0.00',
            'amount_express' => '0.00',
            'amount_real' => '10.00',
            'amount_total' => '10.00',
            'payment_amount' => '0.00',
            'amount_payment' => '0.00',
            'amount_balance' => '0.00',
            'amount_integral' => '0.00',
            'rebate_amount' => '0.00',
            'reward_balance' => '0.00',
            'reward_integral' => '0.00',
            'level_agent' => 0,
            'level_member' => 0,
        ], $overrides));

        return $order->refresh();
    }

    protected function createPaymentAddressFixture(int $unid, array $overrides = []): PluginPaymentAddress
    {
        $address = PluginPaymentAddress::mk();
        $address->save(array_merge([
            'unid' => $unid,
            'type' => 1,
            'delete_time' => null,
            'user_name' => '测试收货人',
            'user_phone' => $this->randomPhone('1380013'),
            'idcode' => '110101199001010011',
            'idimg1' => '/upload/idcard-front.png',
            'idimg2' => '/upload/idcard-back.png',
            'region_prov' => '广东省',
            'region_city' => '深圳市',
            'region_area' => '南山区',
            'region_addr' => '科技园 1 号',
        ], $overrides));

        return $address->refresh();
    }

    protected function createSystemFileFixture(array $overrides = []): SystemFile
    {
        $file = SystemFile::mk();
        $file->save(array_merge([
            'type' => 'local',
            'hash' => md5(uniqid('file', true)),
            'tags' => '',
            'name' => 'test-file.png',
            'xext' => 'png',
            'xurl' => 'https://example.com/upload/test-file.png',
            'xkey' => 'upload/test-file.png',
            'mime' => 'image/png',
            'size' => 1024,
            'uuid' => 0,
            'unid' => 0,
            'isfast' => 0,
            'issafe' => 0,
            'status' => 2,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ], $overrides));

        return $file->refresh();
    }

    protected function createSystemBaseFixture(array $overrides = []): SystemBase
    {
        $base = SystemBase::mk();
        $base->save(array_merge([
            'type' => 'identity',
            'code' => 'base-' . random_int(1000, 9999),
            'name' => '测试字典',
            'content' => '',
            'sort' => 0,
            'status' => 1,
            'delete_time' => null,
            'deleted_by' => 0,
            'create_time' => date('Y-m-d H:i:s'),
        ], $overrides));

        return $base->refresh();
    }

    protected function createSystemUserFixture(array $overrides = []): SystemUser
    {
        $data = array_merge([
            'usertype' => '',
            'username' => 'admin-' . random_int(1000, 9999),
            'password' => $this->hashSystemPassword('123456'),
            'nickname' => '测试管理员',
            'headimg' => '',
            'authorize' => '',
            'contact_qq' => '',
            'contact_mail' => '',
            'contact_phone' => '',
            'login_ip' => '127.0.0.1',
            'login_at' => '',
            'login_num' => 0,
            'describe' => '',
            'sort' => 0,
            'status' => 1,
            'delete_time' => null,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ], $overrides);
        $user = SystemUser::mk();
        if (isset($data['id']) && is_numeric($data['id'])) {
            $user = SystemUser::mk()->withTrashed()->findOrEmpty(intval($data['id']));
        }
        $user->save($data);

        return $user->refresh();
    }

    protected function hashSystemPassword(string $password): string
    {
        return UserService::hashPassword($password);
    }

    protected function verifySystemPassword(string $password, ?string $hash): bool
    {
        return UserService::verifyPassword($password, $hash);
    }

    protected function createSystemAuthFixture(array $overrides = []): SystemAuth
    {
        $auth = SystemAuth::mk();
        $auth->save(array_merge([
            'title' => '测试权限',
            'utype' => '',
            'desc' => '测试说明',
            'sort' => 0,
            'status' => 1,
            'create_time' => date('Y-m-d H:i:s'),
        ], $overrides));

        return $auth->refresh();
    }

    protected function createSystemAuthNodeFixture(array $overrides = []): SystemNode
    {
        $node = SystemNode::mk();
        $node->save(array_merge([
            'auth' => 0,
            'node' => 'index/test/index',
        ], $overrides));

        return $node->refresh();
    }

    protected function createSystemMenuFixture(array $overrides = []): SystemMenu
    {
        $menu = SystemMenu::mk();
        $menu->save(array_merge([
            'pid' => 0,
            'title' => '测试菜单',
            'icon' => 'layui-icon layui-icon-set',
            'node' => '',
            'url' => '#',
            'params' => '',
            'target' => '_self',
            'sort' => 0,
            'status' => 1,
            'create_time' => date('Y-m-d H:i:s'),
        ], $overrides));

        return $menu->refresh();
    }

    protected function createSystemConfigFixture(array $overrides = []): SystemConfig
    {
        $config = SystemConfig::mk();
        $config->save(array_merge([
            'type' => 'base',
            'name' => 'site_name',
            'value' => 'ThinkAdmin',
        ], $overrides));

        return $config->refresh();
    }

    protected function createSystemDataFixture(array $overrides = []): SystemData
    {
        $data = SystemData::mk();
        $data->save(array_merge([
            'name' => 'TestDataKey',
            'value' => json_encode([['ok' => true]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ], $overrides));

        return $data->refresh();
    }

    protected function createSystemOplogFixture(array $overrides = []): SystemOplog
    {
        $oplog = SystemOplog::mk();
        $oplog->save(array_merge([
            'node' => 'system/test/index',
            'geoip' => '127.0.0.1',
            'action' => '测试行为',
            'content' => '测试内容',
            'username' => 'tester',
            'create_time' => date('Y-m-d H:i:s'),
        ], $overrides));

        return $oplog->refresh();
    }

    protected function createSystemQueueFixture(array $overrides = []): SystemQueue
    {
        $data = array_merge([
            'code' => 'Q' . strtoupper(substr(md5(uniqid('', true)), 0, 15)),
            'title' => '测试任务',
            'command' => 'xadmin:test queue',
            'exec_pid' => 0,
            'exec_data' => '{}',
            'exec_time' => time(),
            'exec_desc' => '',
            'enter_time' => '0.0000',
            'outer_time' => '0.0000',
            'loops_time' => 0,
            'attempts' => 0,
            'message' => '',
            'status' => 1,
            'create_time' => date('Y-m-d H:i:s'),
        ], $overrides);
        $queue = SystemQueue::mk()->where(['code' => $data['code']])->findOrEmpty();
        $queue->save($data);

        return $queue->refresh();
    }

    protected function createWemallOrderItemFixture(AccountInterface $account, string $orderNo, array $overrides = []): PluginWemallOrderItem
    {
        $item = PluginWemallOrderItem::mk();
        $item->save(array_merge([
            'unid' => $account->getUnid(),
            'status' => 1,
            'delete_time' => null,
            'order_no' => $orderNo,
            'delivery_count' => 1,
            'delivery_code' => 'NONE',
            'gcode' => 'GCODE-' . strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'ghash' => 'GHASH-' . strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'gname' => '测试商品',
            'gcover' => '/upload/goods.png',
            'gunit' => '件',
            'gspec' => '默认规格',
            'gsku' => 'SKU-001',
            'price_market' => '10.00',
            'price_selling' => '10.00',
            'amount_cost' => '5.00',
            'total_price_market' => '10.00',
            'total_price_selling' => '10.00',
            'total_price_cost' => '5.00',
            'discount_rate' => '100.00',
            'discount_amount' => '0.00',
            'total_allow_balance' => '0.00',
            'total_allow_integral' => '0.00',
            'rebate_amount' => '0.00',
            'total_reward_balance' => '0.00',
            'total_reward_integral' => '0.00',
        ], $overrides));

        return $item->refresh();
    }

    protected function createWemallRelationFixture(int $unid, array $overrides = []): PluginWemallUserRelation
    {
        $relation = PluginWemallUserRelation::mk();
        $relation->save(array_merge([
            'unid' => $unid,
            'layer' => 0,
            'puid1' => 0,
            'puid2' => 0,
            'puid3' => 0,
            'puids' => 0,
            'level_code' => 0,
            'agent_level_code' => 0,
            'entry_agent' => 0,
            'entry_member' => 0,
            'path' => ',',
            'level_name' => '普通用户',
            'agent_level_name' => '会员用户',
            'extra' => [],
        ], $overrides));

        return $relation->refresh();
    }

    protected function randomPhone(string $prefix = '1360013'): string
    {
        return $prefix . random_int(1000, 9999);
    }

    private function bootApplication(): void
    {
        function_exists('test_reset_model_makers') && test_reset_model_makers();
        $this->app = new App(TEST_PROJECT_ROOT);
        RuntimeService::init($this->app);

        $this->app->config->set([
            'default' => 'file',
            'stores' => [
                'file' => [
                    'type' => 'File',
                    'path' => $this->sandboxPath . '/cache',
                ],
            ],
        ], 'cache');
        $this->app->config->set([
            'default' => 'file',
            'channels' => [
                'file' => [
                    'type' => 'File',
                    'path' => $this->sandboxPath . '/log',
                    'single' => true,
                    'apart_level' => [],
                    'max_files' => 0,
                    'json' => false,
                    'format' => '[%s][%s] %s',
                    'realtime_write' => true,
                ],
            ],
        ], 'log');
        $this->app->config->set(['jwtkey' => 'integration-test-jwt'], 'app');
        $this->app->config->set([
            'default' => $this->connectionName,
            'auto_timestamp' => true,
            'datetime_format' => 'Y-m-d H:i:s',
            'connections' => [
                $this->connectionName => [
                    'type' => 'sqlite',
                    'database' => $this->databaseFile,
                    'charset' => 'utf8',
                    'trigger_sql' => false,
                    'deploy' => 0,
                    'suffix' => '',
                    'prefix' => '',
                    'hostname' => '',
                    'hostport' => '',
                    'username' => '',
                    'password' => '',
                ],
            ],
        ], 'database');
        $this->app->db->setConfig($this->app->config);
        $this->app->db->connect($this->connectionName, true);

        $this->context = new TestSystemContext();
        Container::getInstance()->instance(SystemContextInterface::class, $this->context);
        RequestContext::clear();
        function_exists('sysvar') && sysvar('', '');
    }

    private function rememberAccountTypes(): void
    {
        if ($this->accountTypesSnapshot !== null) {
            return;
        }

        $this->accountTypesSnapshot = (new \ReflectionClass(Account::class))->getDefaultProperties()['types'];
    }

    private function restoreAccountTypes(): void
    {
        if ($this->accountTypesSnapshot === null) {
            return;
        }

        $reflection = new \ReflectionClass(Account::class);

        $types = $reflection->getProperty('types');
        $types->setValue(null, $this->accountTypesSnapshot);

        $denys = $reflection->getProperty('denys');
        $denys->setValue(null, null);
    }

    private function makeAccountIdentity(string $field): string
    {
        if ($field === 'phone') {
            return $this->randomPhone();
        }

        return $field . '-' . uniqid();
    }

    private function removeDirectory(string $path): void
    {
        if ($path === '' || !is_dir($path)) {
            return;
        }

        foreach (scandir($path) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $target = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($target)) {
                $this->removeDirectory($target);
            } elseif (is_file($target)) {
                @unlink($target);
            }
        }

        @rmdir($path);
    }
}
