<?php

// +----------------------------------------------------------------------
// | Account Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-account
// | github 代码仓库：https://github.com/zoujingli/think-plugs-account
// +----------------------------------------------------------------------

use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', -1);

class InstallAccount extends Migrator
{

    /**
     * 创建数据库
     */
    public function change()
    {
        $this->_create_plugin_account_auth();
        $this->_create_plugin_account_bind();
        $this->_create_plugin_account_msms();
        $this->_create_plugin_account_user();
    }

    /**
     * 创建数据对象
     * @class PluginAccountAuth
     * @table plugin_account_auth
     * @return void
     */
    private function _create_plugin_account_auth()
    {

        // 当前数据表
        $table = 'plugin_account_auth';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '插件-账号-授权',
        ])
            ->addColumn('usid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '终端账号'])
            ->addColumn('time', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '有效时间'])
            ->addColumn('type', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '授权类型'])
            ->addColumn('token', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '授权令牌'])
            ->addColumn('tokenv', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '授权验证'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('usid', ['name' => 'i8a91c286f_usid'])
            ->addIndex('type', ['name' => 'i8a91c286f_type'])
            ->addIndex('time', ['name' => 'i8a91c286f_time'])
            ->addIndex('token', ['name' => 'i8a91c286f_token'])
            ->addIndex('create_time', ['name' => 'i8a91c286f_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginAccountBind
     * @table plugin_account_bind
     * @return void
     */
    private function _create_plugin_account_bind()
    {

        // 当前数据表
        $table = 'plugin_account_bind';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '插件-账号-终端',
        ])
            ->addColumn('unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '会员编号'])
            ->addColumn('type', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '终端类型'])
            ->addColumn('phone', 'string', ['limit' => 30, 'default' => '', 'null' => true, 'comment' => '绑定手机'])
            ->addColumn('appid', 'string', ['limit' => 30, 'default' => '', 'null' => true, 'comment' => 'APPID'])
            ->addColumn('openid', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => 'OPENID'])
            ->addColumn('unionid', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => 'UnionID'])
            ->addColumn('headimg', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '用户头像'])
            ->addColumn('nickname', 'string', ['limit' => 99, 'default' => '', 'null' => true, 'comment' => '用户昵称'])
            ->addColumn('password', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '登录密码'])
            ->addColumn('extra', 'text', ['default' => NULL, 'null' => true, 'comment' => '扩展数据'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '账号状态'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '注册时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('type', ['name' => 'i4ec9ee5c7_type'])
            ->addIndex('unid', ['name' => 'i4ec9ee5c7_unid'])
            ->addIndex('sort', ['name' => 'i4ec9ee5c7_sort'])
            ->addIndex('phone', ['name' => 'i4ec9ee5c7_phone'])
            ->addIndex('appid', ['name' => 'i4ec9ee5c7_appid'])
            ->addIndex('status', ['name' => 'i4ec9ee5c7_status'])
            ->addIndex('openid', ['name' => 'i4ec9ee5c7_openid'])
            ->addIndex('unionid', ['name' => 'i4ec9ee5c7_unionid'])
            ->addIndex('deleted', ['name' => 'i4ec9ee5c7_deleted'])
            ->addIndex('create_time', ['name' => 'i4ec9ee5c7_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginAccountMsms
     * @table plugin_account_msms
     * @return void
     */
    private function _create_plugin_account_msms()
    {

        // 当前数据表
        $table = 'plugin_account_msms';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '插件-账号-短信',
        ])
            ->addColumn('unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => false, 'comment' => '账号编号'])
            ->addColumn('usid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => false, 'comment' => '终端编号'])
            ->addColumn('type', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '短信类型'])
            ->addColumn('scene', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '业务场景'])
            ->addColumn('smsid', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '消息编号'])
            ->addColumn('phone', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '目标手机'])
            ->addColumn('result', 'string', ['limit' => 512, 'default' => '', 'null' => true, 'comment' => '返回结果'])
            ->addColumn('params', 'string', ['limit' => 512, 'default' => '', 'null' => true, 'comment' => '短信内容'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '短信状态(0失败,1成功)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('type', ['name' => 'i66baec398_type'])
            ->addIndex('usid', ['name' => 'i66baec398_usid'])
            ->addIndex('unid', ['name' => 'i66baec398_unid'])
            ->addIndex('phone', ['name' => 'i66baec398_phone'])
            ->addIndex('smsid', ['name' => 'i66baec398_smsid'])
            ->addIndex('scene', ['name' => 'i66baec398_scene'])
            ->addIndex('status', ['name' => 'i66baec398_status'])
            ->addIndex('create_time', ['name' => 'i66baec398_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginAccountUser
     * @table plugin_account_user
     * @return void
     */
    private function _create_plugin_account_user()
    {

        // 当前数据表
        $table = 'plugin_account_user';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '插件-账号-资料',
        ])
            ->addColumn('code', 'string', ['limit' => 16, 'default' => '', 'null' => true, 'comment' => '用户编号'])
            ->addColumn('phone', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '用户手机'])
            ->addColumn('email', 'string', ['limit' => 99, 'default' => '', 'null' => true, 'comment' => '用户邮箱'])
            ->addColumn('unionid', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => 'UnionID'])
            ->addColumn('username', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '用户姓名'])
            ->addColumn('nickname', 'string', ['limit' => 99, 'default' => '', 'null' => true, 'comment' => '用户昵称'])
            ->addColumn('password', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '认证密码'])
            ->addColumn('headimg', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '用户头像'])
            ->addColumn('region_prov', 'string', ['limit' => 99, 'default' => '', 'null' => true, 'comment' => '所在省份'])
            ->addColumn('region_city', 'string', ['limit' => 99, 'default' => '', 'null' => true, 'comment' => '所在城市'])
            ->addColumn('region_area', 'string', ['limit' => 99, 'default' => '', 'null' => true, 'comment' => '所在区域'])
            ->addColumn('remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '备注(内部使用)'])
            ->addColumn('extra', 'text', ['default' => NULL, 'null' => true, 'comment' => '扩展数据'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '用户状态(0拉黑,1正常)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '注册时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('code', ['name' => 'iddb76b051_code'])
            ->addIndex('sort', ['name' => 'iddb76b051_sort'])
            ->addIndex('phone', ['name' => 'iddb76b051_phone'])
            ->addIndex('email', ['name' => 'iddb76b051_email'])
            ->addIndex('status', ['name' => 'iddb76b051_status'])
            ->addIndex('unionid', ['name' => 'iddb76b051_unionid'])
            ->addIndex('deleted', ['name' => 'iddb76b051_deleted'])
            ->addIndex('username', ['name' => 'iddb76b051_username'])
            ->addIndex('nickname', ['name' => 'iddb76b051_nickname'])
            ->addIndex('region_prov', ['name' => 'iddb76b051_region_prov'])
            ->addIndex('region_city', ['name' => 'iddb76b051_region_city'])
            ->addIndex('region_area', ['name' => 'iddb76b051_region_area'])
            ->addIndex('create_time', ['name' => 'iddb76b051_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }
}