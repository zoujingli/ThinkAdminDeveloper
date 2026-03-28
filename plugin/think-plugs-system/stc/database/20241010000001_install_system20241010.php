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
use Phinx\Db\Table;
use plugin\helper\migration\PhinxExtend;
use plugin\system\Service;
use think\admin\Library;
use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', '-1');

/**
 * 系统模块数据.
 */
class InstallSystem20241010 extends Migrator
{
    /**
     * 获取脚本名称.
     */
    public function getName(): string
    {
        return 'SystemPlugin';
    }

    /**
     * 创建数据库.
     */
    public function change()
    {
        $this->_create_system_base();
        $this->_create_system_data();
        $this->_create_system_file();
        $this->_create_system_oplog();
        $this->_create_system_auth();
        $this->_create_system_auth_node();
        $this->_create_system_menu();
        $this->_create_system_user();
        $this->insertSystemData();
        $this->insertUser();
        $this->insertMenu();
    }

    /**
     * 创建数据对象
     * @class SystemBase
     * @table system_base
     */
    private function _create_system_base()
    {
        $table = $this->table('system_base', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '系统-字典',
        ]);
        $this->upgrade($table, [
            ['type', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '数据类型']],
            ['code', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '数据代码']],
            ['name', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '数据名称']],
            ['content', 'text', ['default' => null, 'null' => true, 'comment' => '数据内容']],
            ['text_value', 'text', ['default' => null, 'null' => true, 'comment' => '文本值']],
            ['meta_json', 'text', ['default' => null, 'null' => true, 'comment' => '扩展元数据']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '数据状态(0禁用,1启动)']],
            ['delete_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '删除时间']],
            ['deleted_by', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '删除用户']],
            ['create_time', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'type', 'code', 'sort', 'status', 'delete_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class SystemData
     * @table system_data
     */
    private function _create_system_data()
    {
        $table = $this->table('system_data', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '系统-数据',
        ]);
        $this->upgrade($table, [
            ['name', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '配置名']],
            ['value', 'text', ['default' => null, 'null' => true, 'comment' => '配置值']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'name', 'create_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class SystemFile
     * @table system_file
     */
    private function _create_system_file()
    {
        $table = $this->table('system_file', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '系统-文件',
        ]);
        $this->upgrade($table, [
            ['type', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '存储类型']],
            ['hash', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '文件哈希']],
            ['tags', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '文件标签']],
            ['name', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '文件名称']],
            ['extension', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '文件后缀']],
            ['xext', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '文件后缀']],
            ['file_url', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '文件链接']],
            ['xurl', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '文件链接']],
            ['storage_key', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '存储键名']],
            ['xkey', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '存储键名']],
            ['mime', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '文件类型']],
            ['size', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '文件大小']],
            ['system_user_id', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '后台用户']],
            ['uuid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '后台用户']],
            ['biz_user_id', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '业务用户']],
            ['unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '业务用户']],
            ['is_fast_upload', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '是否秒传']],
            ['isfast', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '是否秒传']],
            ['is_safe', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '安全模式']],
            ['issafe', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '安全模式']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '状态(1上传中,2已完成)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'type', 'hash', 'system_user_id', 'biz_user_id', 'status', 'create_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class SystemOplog
     * @table system_oplog
     */
    private function _create_system_oplog()
    {
        $table = $this->table('system_oplog', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '系统-日志',
        ]);
        $this->upgrade($table, [
            ['node', 'string', ['limit' => 200, 'default' => '', 'null' => false, 'comment' => '当前操作节点']],
            ['request_ip', 'string', ['limit' => 64, 'default' => '', 'null' => false, 'comment' => '请求IP地址']],
            ['geoip', 'string', ['limit' => 15, 'default' => '', 'null' => false, 'comment' => '操作者IP地址']],
            ['action', 'string', ['limit' => 200, 'default' => '', 'null' => false, 'comment' => '操作行为名称']],
            ['content', 'string', ['limit' => 1024, 'default' => '', 'null' => false, 'comment' => '操作内容描述']],
            ['username', 'string', ['limit' => 50, 'default' => '', 'null' => false, 'comment' => '操作人用户名']],
            ['create_time', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false, 'comment' => '创建时间']],
        ], [
            'create_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class SystemAuth
     * @table system_auth
     */
    private function _create_system_auth()
    {
        // 创建数据表对象
        $table = $this->table('system_auth', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '系统-权限',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['title', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '权限名称']],
            ['code', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '角色编码']],
            ['remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '角色说明']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '权限状态(1使用,0禁用)']],
            ['create_time', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true, 'comment' => '创建时间']],
        ], [
            'sort', 'title', 'status',
        ], true);
        $this->syncSystemAuthSchema();
    }

    /**
     * 创建数据对象
     * @class SystemAuthNode
     * @table system_auth_node
     */
    private function _create_system_auth_node()
    {
        // 创建数据表对象
        $table = $this->table('system_auth_node', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '系统-授权',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['auth', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '角色']],
            ['node', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '节点']],
        ], [
            'auth', 'node',
        ], true);
    }

    /**
     * 创建数据对象
     * @class SystemMenu
     * @table system_menu
     */
    private function _create_system_menu()
    {
        // 创建数据表对象
        $table = $this->table('system_menu', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '系统-菜单',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['pid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上级ID']],
            ['title', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '菜单名称']],
            ['icon', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '菜单图标']],
            ['node', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '节点代码']],
            ['url', 'string', ['limit' => 400, 'default' => '', 'null' => true, 'comment' => '链接节点']],
            ['params', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '链接参数']],
            ['target', 'string', ['limit' => 20, 'default' => '_self', 'null' => true, 'comment' => '打开方式']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '状态(0:禁用,1:启用)']],
            ['create_time', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true, 'comment' => '创建时间']],
        ], [
            'pid', 'sort', 'status',
        ], true);
    }

    /**
     * 创建数据对象
     * @class SystemUser
     * @table system_user
     */
    private function _create_system_user()
    {
        // 创建数据表对象
        $table = $this->table('system_user', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '系统-用户',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['base_code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '角色身份编码']],
            ['username', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '用户账号']],
            ['password', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '用户密码']],
            ['nickname', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '用户昵称']],
            ['headimg', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '头像地址']],
            ['auth_ids', 'text', ['default' => null, 'null' => true, 'comment' => '权限角色列表']],
            ['contact_qq', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '联系QQ']],
            ['contact_mail', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '联系邮箱']],
            ['contact_phone', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '联系手机']],
            ['login_ip', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '登录地址']],
            ['login_at', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '登录时间']],
            ['login_num', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '登录次数']],
            ['remark', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '备注说明']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '状态(0禁用,1启用)']],
            ['delete_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '删除时间']],
            ['create_time', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true, 'comment' => '创建时间']],
        ], [
            'sort', 'status', 'username', 'delete_time',
        ], true);
        $this->syncSystemUserSchema();
    }

    /**
     * 初始化用户数据.
     */
    private function insertUser()
    {
        if (PhinxExtend::migrationRowExists($this, 'system_user')) {
            return;
        }

        $this->table('system_user')->insert([[
            'id' => 10000,
            'username' => 'admin',
            'nickname' => '超级管理员',
            'password' => password_hash('admin', PASSWORD_DEFAULT),
            'headimg' => 'https://thinkadmin.top/static/img/head.png',
        ]])->saveData();
    }

    /**
     * 初始化系统菜单.
     * @throws Exception
     */
    private function insertMenu()
    {
        PhinxExtend::writePluginMenu(Service::class, [], [], $this);
    }

    /**
     * 初始化基础配置.
     */
    private function insertSystemData(): void
    {
        $this->seedSystemData('system.site', [
            'login_title' => '系统管理',
            'theme' => 'default',
            'login_background_images' => [],
            'browser_icon' => 'https://thinkadmin.top/static/img/logo.png',
            'website_name' => 'ThinkAdmin',
            'application_name' => 'ThinkAdmin',
            'application_version' => 'v8',
            'public_security_filing' => '',
            'miit_filing' => '',
            'copyright' => '©版权所有 2014-' . date('Y') . ' ThinkAdmin',
            'host' => '',
        ]);
        $this->seedSystemData('system.security', [
            'jwt_secret' => bin2hex(random_bytes(16)),
        ]);
        $this->seedSystemData('system.runtime', [
            'editor_driver' => 'ckeditor5',
            'queue_retain_days' => 7,
        ]);
        $this->seedSystemData('system.plugin_center', [
            'enabled' => 1,
            'show_menu' => 1,
        ]);
    }

    /**
     * 检查数据表是否已有记录.
     */
    private function tableHasRows(string $table): bool
    {
        $quoted = $this->getAdapter()->quoteTableName($table);
        $stmt = $this->query("SELECT 1 FROM {$quoted} LIMIT 1");
        if ($stmt === false) {
            return false;
        }

        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    /**
     * 写入系统初始化数据。
     */
    private function seedSystemData(string $name, array $value): void
    {
        if ($this->systemDataExists($name)) {
            return;
        }

        $this->table('system_data')->insert([[
            'name' => $name,
            'value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ]])->saveData();
    }

    /**
     * 检查系统初始化数据是否存在。
     */
    private function systemDataExists(string $name): bool
    {
        $quoted = $this->getAdapter()->quoteTableName('system_data');
        $name = str_replace("'", "''", $name);
        $stmt = $this->query("SELECT 1 FROM {$quoted} WHERE `name` = '{$name}' LIMIT 1");
        if ($stmt === false) {
            return false;
        }

        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    /**
     * 兼容旧版权限字段并清理遗留列.
     */
    private function syncSystemAuthSchema(): void
    {
        $table = $this->table('system_auth');
        $hasLegacyCode = $table->hasColumn('utype');
        $hasLegacyRemark = $table->hasColumn('desc');

        if ($hasLegacyCode || $hasLegacyRemark) {
            $fields = ['id', 'title', 'code', 'remark'];
            $hasLegacyCode && $fields[] = 'utype';
            $hasLegacyRemark && $fields[] = 'desc';

            $used = [];
            foreach ($this->fetchTableRows('system_auth', $fields) as $row) {
                $id = intval($row['id'] ?? 0);
                if ($id < 1) {
                    continue;
                }

                $updates = [];
                $code = $this->resolveAuthCode($row, $used);
                if ($code !== trim(strval($row['code'] ?? ''))) {
                    $updates['code'] = $code;
                }
                if ($hasLegacyRemark && trim(strval($row['remark'] ?? '')) === '' && trim(strval($row['desc'] ?? '')) !== '') {
                    $updates['remark'] = trim(strval($row['desc'] ?? ''));
                }
                if (!empty($updates)) {
                    Library::$sapp->db->name('system_auth')->where(['id' => $id])->update($updates);
                }
            }
        }

        $table = $this->table('system_auth');
        if ($table->hasColumn('utype')) {
            $table->removeColumn('utype');
        }
        if ($table->hasColumn('desc')) {
            $table->removeColumn('desc');
        }
        if (!$table->hasIndex(['code'])) {
            $table->addIndex(['code'], ['unique' => true]);
        }
        $table->update();
    }

    /**
     * 兼容旧版用户字段并清理遗留列.
     */
    private function syncSystemUserSchema(): void
    {
        $table = $this->table('system_user');
        $hasLegacyBase = $table->hasColumn('usertype');
        $hasLegacyAuth = $table->hasColumn('authorize');
        $hasLegacyRemark = $table->hasColumn('describe');

        if ($hasLegacyBase || $hasLegacyAuth || $hasLegacyRemark) {
            $fields = ['id', 'base_code', 'auth_ids', 'remark'];
            $hasLegacyBase && $fields[] = 'usertype';
            $hasLegacyAuth && $fields[] = 'authorize';
            $hasLegacyRemark && $fields[] = 'describe';

            foreach ($this->fetchTableRows('system_user', $fields) as $row) {
                $id = intval($row['id'] ?? 0);
                if ($id < 1) {
                    continue;
                }

                $updates = [];
                if ($hasLegacyBase && trim(strval($row['base_code'] ?? '')) === '' && trim(strval($row['usertype'] ?? '')) !== '') {
                    $updates['base_code'] = trim(strval($row['usertype'] ?? ''));
                }
                if ($hasLegacyAuth && trim(strval($row['auth_ids'] ?? '')) === '' && trim(strval($row['authorize'] ?? '')) !== '') {
                    $updates['auth_ids'] = trim(strval($row['authorize'] ?? ''));
                }
                if ($hasLegacyRemark && trim(strval($row['remark'] ?? '')) === '' && trim(strval($row['describe'] ?? '')) !== '') {
                    $updates['remark'] = trim(strval($row['describe'] ?? ''));
                }
                if (!empty($updates)) {
                    Library::$sapp->db->name('system_user')->where(['id' => $id])->update($updates);
                }
            }
        }

        $table = $this->table('system_user');
        if ($table->hasColumn('usertype')) {
            $table->removeColumn('usertype');
        }
        if ($table->hasColumn('authorize')) {
            $table->removeColumn('authorize');
        }
        if ($table->hasColumn('describe')) {
            $table->removeColumn('describe');
        }
        $table->update();
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, bool> $used
     */
    private function resolveAuthCode(array $row, array &$used): string
    {
        $id = intval($row['id'] ?? 0);
        $candidates = [
            strval($row['code'] ?? ''),
            strval($row['utype'] ?? ''),
            strval($row['title'] ?? ''),
            'role-' . $id,
        ];

        foreach ($candidates as $candidate) {
            $code = $this->normalizeAuthCode($candidate);
            if ($code !== '' && !isset($used[$code])) {
                $used[$code] = true;
                return $code;
            }
        }

        $fallback = $this->normalizeAuthCode('role-' . ($id > 0 ? $id : count($used) + 1));
        while ($fallback === '' || isset($used[$fallback])) {
            $fallback = $this->normalizeAuthCode($fallback . '-x');
        }
        $used[$fallback] = true;

        return $fallback;
    }

    private function normalizeAuthCode(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9:_-]+/', '-', $value) ?: '';
        $value = trim($value, '-_:');
        if ($value === '' || !preg_match('/^[a-z]/', $value)) {
            return '';
        }

        return substr($value, 0, 50);
    }

    /**
     * @param array<int, string> $columns
     * @return array<int, array<string, mixed>>
     */
    private function fetchTableRows(string $table, array $columns): array
    {
        $quotedTable = $this->getAdapter()->quoteTableName($table);
        $quotedColumns = array_map(fn(string $column): string => $this->getAdapter()->quoteColumnName($column), $columns);
        $stmt = $this->query(sprintf('SELECT %s FROM %s', join(',', $quotedColumns), $quotedTable));
        if ($stmt === false) {
            return [];
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * 升级更新数据表.
     * @param array<int, array<int|string, mixed>> $fields
     * @param array<int, array<int, string>|string> $indexs
     */
    private function upgrade(Table $table, array $fields, array $indexs = [], bool $force = false): Table
    {
        [$_exists, $_fields] = [[], array_column($fields, 0)];
        if ($isExists = $table->exists()) {
            if (!$force) {
                return $table;
            }
            foreach ($table->getColumns() as $column) {
                $_exists[] = $column->getName();
                if (!in_array($column->getName(), $_fields, true)) {
                    continue;
                }
            }
        }
        foreach ($fields as $field) {
            if (in_array($field[0], $_exists, true)) {
                $table->changeColumn($field[0], ...array_slice($field, 1));
            } else {
                $table->addColumn($field[0], ...array_slice($field, 1));
            }
        }
        foreach ($indexs as $field) {
            if (is_string($field)) {
                $columns = [$field];
                $options = [];
            } elseif (is_array($field) && array_is_list($field)) {
                $columns = array_values(array_filter($field, 'is_string'));
                $options = [];
            } else {
                $columns = array_values(array_filter((array)($field['columns'] ?? []), 'is_string'));
                $options = array_diff_key((array)$field, ['columns' => true]);
            }
            if (empty($columns) || (!empty($isExists) && $table->hasIndex($columns))) {
                continue;
            }
            $table->addIndex($columns, $options);
        }
        $isExists ? $table->update() : $table->create();
        if ($table->hasColumn('id')) {
            $table->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
        }
        return $table;
    }
}
