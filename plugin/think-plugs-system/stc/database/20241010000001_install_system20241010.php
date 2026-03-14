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
use Phinx\Db\Table;
use plugin\helper\service\PhinxExtend;
use plugin\system\Service;
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
        $this->_create_system_config();
        $this->_create_system_data();
        $this->_create_system_oplog();
        $this->_create_system_auth();
        $this->_create_system_auth_node();
        $this->_create_system_menu();
        $this->_create_system_user();
        $this->insertBaseConfig();
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
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '数据状态(0禁用,1启动)']],
            ['delete_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '删除时间']],
            ['deleted_by', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '删除用户']],
            ['create_time', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true, 'comment' => '创建时间']],
        ], [
            'type', 'code', 'sort', 'status', 'delete_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class SystemConfig
     * @table system_config
     */
    private function _create_system_config()
    {
        $table = $this->table('system_config', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '系统-配置',
        ]);
        $this->upgrade($table, [
            ['type', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '配置分类']],
            ['name', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '配置名称']],
            ['value', 'string', ['limit' => 2048, 'default' => '', 'null' => true, 'comment' => '配置内容']],
        ], [
            'type', 'name',
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
            ['utype', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '身份权限']],
            ['desc', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '备注说明']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '权限状态(1使用,0禁用)']],
            ['create_time', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true, 'comment' => '创建时间']],
        ], [
            'sort', 'title', 'status',
        ], true);
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
            ['usertype', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '用户类型']],
            ['username', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '用户账号']],
            ['password', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '用户密码']],
            ['nickname', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '用户昵称']],
            ['headimg', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '头像地址']],
            ['authorize', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '权限授权']],
            ['contact_qq', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '联系QQ']],
            ['contact_mail', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '联系邮箱']],
            ['contact_phone', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '联系手机']],
            ['login_ip', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '登录地址']],
            ['login_at', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '登录时间']],
            ['login_num', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '登录次数']],
            ['describe', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '备注说明']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '状态(0禁用,1启用)']],
            ['delete_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '删除时间']],
            ['create_time', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true, 'comment' => '创建时间']],
        ], [
            'sort', 'status', 'username', 'delete_time',
        ], true);
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
            'password' => '21232f297a57a5a743894a0e4a801fc3',
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
    private function insertBaseConfig(): void
    {
        if ($this->tableHasRows('system_config')) {
            return;
        }

        $this->table('system_config')->insert([
            ['type' => 'base', 'name' => 'app_name', 'value' => 'ThinkAdmin'],
            ['type' => 'base', 'name' => 'app_version', 'value' => 'v8'],
            ['type' => 'base', 'name' => 'editor', 'value' => 'ckeditor5'],
            ['type' => 'base', 'name' => 'login_name', 'value' => '系统管理'],
            ['type' => 'base', 'name' => 'site_copy', 'value' => '©版权所有 2014-' . date('Y') . ' ThinkAdmin'],
            ['type' => 'base', 'name' => 'site_icon', 'value' => 'https://thinkadmin.top/static/img/logo.png'],
            ['type' => 'base', 'name' => 'site_name', 'value' => 'ThinkAdmin'],
            ['type' => 'base', 'name' => 'site_theme', 'value' => 'default'],
        ])->saveData();
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

        return $stmt->fetch(\PDO::FETCH_ASSOC) !== false;
    }

    /**
     * 升级更新数据表.
     * @param array<int, array<int|string, mixed>> $fields
     * @param array<int, string|array<int, string>> $indexs
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
            $columns = is_array($field) ? $field : [$field];
            if (empty($columns) || (!empty($isExists) && $table->hasIndex($columns))) {
                continue;
            }
            $table->addIndex($columns);
        }
        $isExists ? $table->update() : $table->create();
        if ($table->hasColumn('id')) {
            $table->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
        }
        return $table;
    }
}
