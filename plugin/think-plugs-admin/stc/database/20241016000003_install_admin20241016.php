<?php

// +----------------------------------------------------------------------
// | Admin Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-admin
// | github 代码仓库：https://github.com/zoujingli/think-plugs-admin
// +----------------------------------------------------------------------

use think\admin\extend\PhinxExtend;
use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', -1);

/**
 * 系统模块数据
 */
class InstallAdmin20241016 extends Migrator
{

    /**
     * 获取脚本名称
     * @return string
     */
    public function getName(): string
    {
        return 'AdminPlugin';
    }

    /**
     * 创建数据库
     */
    public function change()
    {
        $this->_create_system_auth();
        $this->_create_system_auth_node();
        $this->_create_system_base();
        $this->_create_system_config();
        $this->_create_system_data();
        $this->_create_system_file();
        $this->_create_system_menu();
        $this->_create_system_oplog();
        $this->_create_system_queue();
        $this->_create_system_user();
    }

    /**
     * 创建数据对象
     * @class SystemAuth
     * @table system_auth
     * @return void
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
            ['create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true, 'comment' => '创建时间']],
        ], [
            'sort', 'title', 'status',
        ], true);
    }

    /**
     * 创建数据对象
     * @class SystemAuthNode
     * @table system_auth_node
     * @return void
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
     * @class SystemBase
     * @table system_base
     * @return void
     */
    private function _create_system_base()
    {
        // 创建数据表对象
        $table = $this->table('system_base', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '系统-字典',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['type', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '数据类型']],
            ['code', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '数据代码']],
            ['name', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '数据名称']],
            ['content', 'text', ['default' => NULL, 'null' => true, 'comment' => '数据内容']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '数据状态(0禁用,1启动)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0正常,1已删)']],
            ['deleted_at', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '删除时间']],
            ['deleted_by', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '删除用户']],
            ['create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true, 'comment' => '创建时间']],
        ], [
            'type', 'code', 'name', 'sort', 'status', 'deleted',
        ], true);
    }

    /**
     * 创建数据对象
     * @class SystemConfig
     * @table system_config
     * @return void
     */
    private function _create_system_config()
    {
        // 创建数据表对象
        $table = $this->table('system_config', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '系统-配置',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
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
     * @return void
     */
    private function _create_system_data()
    {
        // 创建数据表对象
        $table = $this->table('system_data', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '系统-数据',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['name', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '配置名']],
            ['value', 'text', ['default' => NULL, 'null' => true, 'comment' => '配置值']],
            ['create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间']],
        ], [
            'name', 'create_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class SystemFile
     * @table system_file
     * @return void
     */
    private function _create_system_file()
    {
        // 创建数据表对象
        $table = $this->table('system_file', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '系统-文件',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['type', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '上传类型']],
            ['hash', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '文件哈希']],
            ['tags', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '文件标签']],
            ['name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '文件名称']],
            ['xext', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '文件后缀']],
            ['xurl', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '访问链接']],
            ['xkey', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '文件路径']],
            ['mime', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '文件类型']],
            ['size', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '文件大小']],
            ['uuid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户编号']],
            ['unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '会员编号']],
            ['isfast', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '是否秒传']],
            ['issafe', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '安全模式']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '上传状态(1悬空,2落地)']],
            ['create_at', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间']],
            ['update_at', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间']],
        ], [
            'type', 'hash', 'uuid', 'xext', 'unid', 'tags', 'name', 'status', 'issafe', 'isfast', 'create_at',
        ], true);
    }

    /**
     * 创建数据对象
     * @class SystemMenu
     * @table system_menu
     * @return void
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
            ['create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true, 'comment' => '创建时间']],
        ], [
            'pid', 'sort', 'status',
        ], true);
    }

    /**
     * 创建数据对象
     * @class SystemOplog
     * @table system_oplog
     * @return void
     */
    private function _create_system_oplog()
    {
        // 创建数据表对象
        $table = $this->table('system_oplog', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '系统-日志',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['node', 'string', ['limit' => 200, 'default' => '', 'null' => false, 'comment' => '当前操作节点']],
            ['geoip', 'string', ['limit' => 15, 'default' => '', 'null' => false, 'comment' => '操作者IP地址']],
            ['action', 'string', ['limit' => 200, 'default' => '', 'null' => false, 'comment' => '操作行为名称']],
            ['content', 'string', ['limit' => 1024, 'default' => '', 'null' => false, 'comment' => '操作内容描述']],
            ['username', 'string', ['limit' => 50, 'default' => '', 'null' => false, 'comment' => '操作人用户名']],
            ['create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false, 'comment' => '创建时间']],
        ], [
            'create_at',
        ], true);
    }

    /**
     * 创建数据对象
     * @class SystemQueue
     * @table system_queue
     * @return void
     */
    private function _create_system_queue()
    {
        // 创建数据表对象
        $table = $this->table('system_queue', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '系统-任务',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['code', 'string', ['limit' => 20, 'default' => '', 'null' => false, 'comment' => '任务编号']],
            ['title', 'string', ['limit' => 100, 'default' => '', 'null' => false, 'comment' => '任务名称']],
            ['command', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '执行指令']],
            ['exec_pid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '执行进程']],
            ['exec_data', 'text', ['default' => NULL, 'null' => true, 'comment' => '执行参数']],
            ['exec_time', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '执行时间']],
            ['exec_desc', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '执行描述']],
            ['enter_time', 'decimal', ['precision' => 20, 'scale' => 4, 'default' => '0.0000', 'null' => true, 'comment' => '开始时间']],
            ['outer_time', 'decimal', ['precision' => 20, 'scale' => 4, 'default' => '0.0000', 'null' => true, 'comment' => '结束时间']],
            ['loops_time', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '循环时间']],
            ['attempts', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '执行次数']],
            ['message', 'text', ['default' => NULL, 'null' => true, 'comment' => '最新消息']],
            ['rscript', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '任务类型(0单例,1多例)']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '任务状态(1新任务,2处理中,3成功,4失败)']],
            ['create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false, 'comment' => '创建时间']],
        ], [
            'code', 'title', 'status', 'rscript', 'create_at', 'exec_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class SystemUser
     * @table system_user
     * @return void
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
            ['is_deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除(1删除,0未删)']],
            ['create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true, 'comment' => '创建时间']],
        ], [
            'sort', 'status', 'username', 'is_deleted',
        ], true);
    }
}