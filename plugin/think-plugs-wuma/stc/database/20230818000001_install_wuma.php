<?php

use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', -1);

class InstallWuma extends Migrator
{

    /**
     * 创建数据库
     */
    public function change()
    {
        $this->_create_plugin_wuma_code_rule();
        $this->_create_plugin_wuma_code_rule_range();
        $this->_create_plugin_wuma_sales_order();
        $this->_create_plugin_wuma_sales_order_data();
        $this->_create_plugin_wuma_sales_order_data_mins();
        $this->_create_plugin_wuma_sales_order_data_nums();
        $this->_create_plugin_wuma_sales_user();
        $this->_create_plugin_wuma_sales_user_level();
        $this->_create_plugin_wuma_sales_user_stock();
        $this->_create_plugin_wuma_source_assign();
        $this->_create_plugin_wuma_source_assign_item();
        $this->_create_plugin_wuma_source_blockchain();
        $this->_create_plugin_wuma_source_certificate();
        $this->_create_plugin_wuma_source_produce();
        $this->_create_plugin_wuma_source_query();
        $this->_create_plugin_wuma_source_query_notify();
        $this->_create_plugin_wuma_source_query_verify();
        $this->_create_plugin_wuma_source_template();
        $this->_create_plugin_wuma_warehouse();
        $this->_create_plugin_wuma_warehouse_order();
        $this->_create_plugin_wuma_warehouse_order_data();
        $this->_create_plugin_wuma_warehouse_order_data_mins();
        $this->_create_plugin_wuma_warehouse_order_data_nums();
        $this->_create_plugin_wuma_warehouse_relation();
        $this->_create_plugin_wuma_warehouse_relation_data();
        $this->_create_plugin_wuma_warehouse_replace();
        $this->_create_plugin_wuma_warehouse_stock();
        $this->_create_plugin_wuma_warehouse_user();
    }

    /**
     * 创建数据对象
     * @class PluginWumaCodeRule
     * @table plugin_wuma_code_rule
     * @return void
     */
    private function _create_plugin_wuma_code_rule()
    {

        // 当前数据表
        $table = 'plugin_wuma_code_rule';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-生码-规则',
        ])
            ->addColumn('type', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '批次类型(1前关联，2后关联)'])
            ->addColumn('batch', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '批次编号'])
            ->addColumn('mid_min', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '中码与小码比值'])
            ->addColumn('max_mid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '大码与中码比值'])
            ->addColumn('sns_start', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '序号起始值'])
            ->addColumn('sns_after', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '序号结束值'])
            ->addColumn('sns_length', 'biginteger', ['limit' => 20, 'default' => 20, 'null' => true, 'comment' => '序号长度'])
            ->addColumn('max_length', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '大码长度'])
            ->addColumn('mid_length', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '中码长度'])
            ->addColumn('min_length', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '小码长度'])
            ->addColumn('hex_length', 'biginteger', ['limit' => 20, 'default' => 10, 'null' => true, 'comment' => '加密长度'])
            ->addColumn('ver_length', 'biginteger', ['limit' => 20, 'default' => 4, 'null' => true, 'comment' => '验证长度'])
            ->addColumn('max_number', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '大码数量'])
            ->addColumn('mid_number', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '中码数量'])
            ->addColumn('number', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '物码总数'])
            ->addColumn('template', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '导出模板'])
            ->addColumn('remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '物码描述'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('type', ['name' => 'idx_plugin_wuma_code_rule_type'])
            ->addIndex('batch', ['name' => 'idx_plugin_wuma_code_rule_batch'])
            ->addIndex('status', ['name' => 'idx_plugin_wuma_code_rule_status'])
            ->addIndex('deleted', ['name' => 'idx_plugin_wuma_code_rule_deleted'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWumaCodeRuleRange
     * @table plugin_wuma_code_rule_range
     * @return void
     */
    private function _create_plugin_wuma_code_rule_range()
    {

        // 当前数据表
        $table = 'plugin_wuma_code_rule_range';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-生码-范围',
        ])
            ->addColumn('type', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '物码类型'])
            ->addColumn('batch', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '物码批次号'])
            ->addColumn('code_type', 'string', ['limit' => 3, 'default' => '', 'null' => true, 'comment' => '数码类型(min小码,mid中码,max大码)'])
            ->addColumn('code_length', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '数码长度'])
            ->addColumn('range_start', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '起始数码'])
            ->addColumn('range_after', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '结束数码'])
            ->addColumn('range_number', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '数码数量'])
            ->addIndex('type', ['name' => 'idx_plugin_wuma_code_rule_range_type'])
            ->addIndex('code_type', ['name' => 'idx_plugin_wuma_code_rule_range_code_type'])
            ->addIndex('code_length', ['name' => 'idx_plugin_wuma_code_rule_range_code_length'])
            ->addIndex('range_start', ['name' => 'idx_plugin_wuma_code_rule_range_range_start'])
            ->addIndex('range_after', ['name' => 'idx_plugin_wuma_code_rule_range_range_after'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSalesOrder
     * @table plugin_wuma_sales_order
     * @return void
     */
    private function _create_plugin_wuma_sales_order()
    {

        // 当前数据表
        $table = 'plugin_wuma_sales_order';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-代理-订单',
        ])
            ->addColumn('auid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '经销商编号'])
            ->addColumn('xuid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '来源经销商'])
            ->addColumn('code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '操作单单号'])
            ->addColumn('mode', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '操作方式(1扫码,2虚拟)'])
            ->addColumn('ghash', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '商品哈唏'])
            ->addColumn('vir_need', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '虚拟库统计'])
            ->addColumn('vir_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '虚拟库使用'])
            ->addColumn('num_need', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '累计出库数量'])
            ->addColumn('num_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '累计已经出库'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效,2完成)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('auid', ['name' => 'idx_plugin_wuma_sales_order_auid'])
            ->addIndex('xuid', ['name' => 'idx_plugin_wuma_sales_order_xuid'])
            ->addIndex('code', ['name' => 'idx_plugin_wuma_sales_order_code'])
            ->addIndex('mode', ['name' => 'idx_plugin_wuma_sales_order_mode'])
            ->addIndex('ghash', ['name' => 'idx_plugin_wuma_sales_order_ghash'])
            ->addIndex('status', ['name' => 'idx_plugin_wuma_sales_order_status'])
            ->addIndex('deleted', ['name' => 'idx_plugin_wuma_sales_order_deleted'])
            ->addIndex('create_time', ['name' => 'idx_plugin_wuma_sales_order_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSalesOrderData
     * @table plugin_wuma_sales_order_data
     * @return void
     */
    private function _create_plugin_wuma_sales_order_data()
    {

        // 当前数据表
        $table = 'plugin_wuma_sales_order_data';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-代理-数据',
        ])
            ->addColumn('auid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '经销商编号'])
            ->addColumn('xuid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '来源经销商'])
            ->addColumn('code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '操作单号'])
            ->addColumn('mode', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '操作方式(1扫码,2虚拟)'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效)'])
            ->addColumn('number', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '物码总数'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('auid', ['name' => 'idx_plugin_wuma_sales_order_data_auid'])
            ->addIndex('xuid', ['name' => 'idx_plugin_wuma_sales_order_data_xuid'])
            ->addIndex('mode', ['name' => 'idx_plugin_wuma_sales_order_data_mode'])
            ->addIndex('code', ['name' => 'idx_plugin_wuma_sales_order_data_code'])
            ->addIndex('status', ['name' => 'idx_plugin_wuma_sales_order_data_status'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSalesOrderDataMins
     * @table plugin_wuma_sales_order_data_mins
     * @return void
     */
    private function _create_plugin_wuma_sales_order_data_mins()
    {

        // 当前数据表
        $table = 'plugin_wuma_sales_order_data_mins';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-代理-小码',
        ])
            ->addColumn('ddid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '数据编号'])
            ->addColumn('auid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '代理编号'])
            ->addColumn('code', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '物码数据'])
            ->addColumn('mode', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '操作类型(1扫码,2虚拟)'])
            ->addColumn('stock', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '库存有效'])
            ->addColumn('ghash', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '商品哈唏'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '数据状态(0无效,1有效)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0有效,1已删)'])
            ->addColumn('status_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '状态时间'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addIndex('ddid', ['name' => 'idx_plugin_wuma_sales_order_data_mins_ddid'])
            ->addIndex('auid', ['name' => 'idx_plugin_wuma_sales_order_data_mins_auid'])
            ->addIndex('code', ['name' => 'idx_plugin_wuma_sales_order_data_mins_code'])
            ->addIndex('mode', ['name' => 'idx_plugin_wuma_sales_order_data_mins_mode'])
            ->addIndex('stock', ['name' => 'idx_plugin_wuma_sales_order_data_mins_stock'])
            ->addIndex('ghash', ['name' => 'idx_plugin_wuma_sales_order_data_mins_ghash'])
            ->addIndex('status', ['name' => 'idx_plugin_wuma_sales_order_data_mins_status'])
            ->addIndex('deleted', ['name' => 'idx_plugin_wuma_sales_order_data_mins_deleted'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSalesOrderDataNums
     * @table plugin_wuma_sales_order_data_nums
     * @return void
     */
    private function _create_plugin_wuma_sales_order_data_nums()
    {

        // 当前数据表
        $table = 'plugin_wuma_sales_order_data_nums';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-代理-箱码',
        ])
            ->addColumn('uuid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '所属品牌'])
            ->addColumn('ddid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '数据编号'])
            ->addColumn('count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '物码数量'])
            ->addColumn('type', 'string', ['limit' => 40, 'default' => '', 'null' => true, 'comment' => '物码类型'])
            ->addColumn('code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '物码数据'])
            ->addIndex('type', ['name' => 'idx_plugin_wuma_sales_order_data_nums_type'])
            ->addIndex('uuid', ['name' => 'idx_plugin_wuma_sales_order_data_nums_uuid'])
            ->addIndex('ddid', ['name' => 'idx_plugin_wuma_sales_order_data_nums_ddid'])
            ->addIndex('code', ['name' => 'idx_plugin_wuma_sales_order_data_nums_code'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSalesUser
     * @table plugin_wuma_sales_user
     * @return void
     */
    private function _create_plugin_wuma_sales_user()
    {

        // 当前数据表
        $table = 'plugin_wuma_sales_user';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-代理-用户',
        ])
            ->addColumn('auid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上级代理'])
            ->addColumn('code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '授权编号'])
            ->addColumn('level', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '代理等级'])
            ->addColumn('master', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '总部账号'])
            ->addColumn('phone', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '用户手机'])
            ->addColumn('userid', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '身份证号'])
            ->addColumn('mobile', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '联系电话'])
            ->addColumn('headimg', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '用户头像'])
            ->addColumn('username', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '用户姓名'])
            ->addColumn('password', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '登录密码'])
            ->addColumn('date_start', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '开始时间'])
            ->addColumn('date_after', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '结束时间'])
            ->addColumn('super_auid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '邀请上级用户'])
            ->addColumn('super_phone', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '邀请上级手机'])
            ->addColumn('business', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '营业执照'])
            ->addColumn('region_prov', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '所属省份'])
            ->addColumn('region_city', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '所属城市'])
            ->addColumn('region_area', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '所属区域'])
            ->addColumn('region_address', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '详细地址'])
            ->addColumn('remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '用户备注描述'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '用户状态(1正常,0已黑)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '注册时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('auid', ['name' => 'idx_plugin_wuma_sales_user_auid'])
            ->addIndex('code', ['name' => 'idx_plugin_wuma_sales_user_code'])
            ->addIndex('level', ['name' => 'idx_plugin_wuma_sales_user_level'])
            ->addIndex('status', ['name' => 'idx_plugin_wuma_sales_user_status'])
            ->addIndex('deleted', ['name' => 'idx_plugin_wuma_sales_user_deleted'])
            ->addIndex('super_auid', ['name' => 'idx_plugin_wuma_sales_user_super_auid'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSalesUserLevel
     * @table plugin_wuma_sales_user_level
     * @return void
     */
    private function _create_plugin_wuma_sales_user_level()
    {

        // 当前数据表
        $table = 'plugin_wuma_sales_user_level';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-代理-等级',
        ])
            ->addColumn('name', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '代理级别名称'])
            ->addColumn('number', 'integer', ['limit' => 2, 'default' => 0, 'null' => true, 'comment' => '代理级别序号'])
            ->addColumn('remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '代理级别描述'])
            ->addColumn('utime', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '等级更新时间'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '代理等级状态(1使用,0禁用)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '等级创建时间'])
            ->addIndex('status', ['name' => 'idx_plugin_wuma_sales_user_level_status'])
            ->addIndex('number', ['name' => 'idx_plugin_wuma_sales_user_level_number'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSalesUserStock
     * @table plugin_wuma_sales_user_stock
     * @return void
     */
    private function _create_plugin_wuma_sales_user_stock()
    {

        // 当前数据表
        $table = 'plugin_wuma_sales_user_stock';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-代理-库存',
        ])
            ->addColumn('auid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '经销编号'])
            ->addColumn('ghash', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '商品哈唏'])
            ->addColumn('vir_total', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '虚拟库存'])
            ->addColumn('vir_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '虚拟出货'])
            ->addColumn('num_total', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '累计库存'])
            ->addColumn('num_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '累计出货'])
            ->addIndex('auid', ['name' => 'idx_plugin_wuma_sales_user_stock_auid'])
            ->addIndex('ghash', ['name' => 'idx_plugin_wuma_sales_user_stock_ghash'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSourceAssign
     * @table plugin_wuma_source_assign
     * @return void
     */
    private function _create_plugin_wuma_source_assign()
    {

        // 当前数据表
        $table = 'plugin_wuma_source_assign';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '溯源-赋码批次',
        ])
            ->addColumn('batch', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '赋码批次号'])
            ->addColumn('cbatch', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '物码批次号'])
            ->addColumn('outer_items', 'text', ['default' => NULL, 'null' => true, 'comment' => 'JSON出库'])
            ->addColumn('items', 'text', ['default' => NULL, 'null' => true, 'comment' => 'JSON赋码'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('batch', ['name' => 'idx_plugin_wuma_source_assign_batch'])
            ->addIndex('cbatch', ['name' => 'idx_plugin_wuma_source_assign_cbatch'])
            ->addIndex('status', ['name' => 'idx_plugin_wuma_source_assign_status'])
            ->addIndex('deleted', ['name' => 'idx_plugin_wuma_source_assign_deleted'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSourceAssignItem
     * @table plugin_wuma_source_assign_item
     * @return void
     */
    private function _create_plugin_wuma_source_assign_item()
    {

        // 当前数据表
        $table = 'plugin_wuma_source_assign_item';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '溯源-赋码规则',
        ])
            ->addColumn('lock', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '是否已锁定'])
            ->addColumn('batch', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '赋码批次号'])
            ->addColumn('pbatch', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '生产批次号'])
            ->addColumn('cbatch', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '物码批次号'])
            ->addColumn('range_start', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '开始物码区间'])
            ->addColumn('range_after', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '结束物码区间'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('batch', ['name' => 'idx_plugin_wuma_source_assign_item_batch'])
            ->addIndex('cbatch', ['name' => 'idx_plugin_wuma_source_assign_item_cbatch'])
            ->addIndex('pbatch', ['name' => 'idx_plugin_wuma_source_assign_item_pbatch'])
            ->addIndex('range_start', ['name' => 'idx_plugin_wuma_source_assign_item_range_start'])
            ->addIndex('range_after', ['name' => 'idx_plugin_wuma_source_assign_item_range_after'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSourceBlockchain
     * @table plugin_wuma_source_blockchain
     * @return void
     */
    private function _create_plugin_wuma_source_blockchain()
    {

        // 当前数据表
        $table = 'plugin_wuma_source_blockchain';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '溯源-区块链',
        ])
            ->addColumn('scid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '确权证书'])
            ->addColumn('code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '流程编号'])
            ->addColumn('hash', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '流程HASH'])
            ->addColumn('name', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '流程名称'])
            ->addColumn('data', 'text', ['default' => NULL, 'null' => true, 'comment' => '流程环节'])
            ->addColumn('remark', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '流程备注'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效1有效)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删1已删)'])
            ->addColumn('hash_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '上链时间'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('code', ['name' => 'idx_plugin_wuma_source_blockchain_code'])
            ->addIndex('scid', ['name' => 'idx_plugin_wuma_source_blockchain_scid'])
            ->addIndex('sort', ['name' => 'idx_plugin_wuma_source_blockchain_sort'])
            ->addIndex('status', ['name' => 'idx_plugin_wuma_source_blockchain_status'])
            ->addIndex('deleted', ['name' => 'idx_plugin_wuma_source_blockchain_deleted'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSourceCertificate
     * @table plugin_wuma_source_certificate
     * @return void
     */
    private function _create_plugin_wuma_source_certificate()
    {

        // 当前数据表
        $table = 'plugin_wuma_source_certificate';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '溯源-确权证书',
        ])
            ->addColumn('code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '模板编号'])
            ->addColumn('name', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '模板名称'])
            ->addColumn('times', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '访问次数'])
            ->addColumn('image', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '证书底图'])
            ->addColumn('content', 'text', ['default' => NULL, 'null' => true, 'comment' => '定制规则'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效1有效)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删1已删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('code', ['name' => 'idx_plugin_wuma_source_certificate_code'])
            ->addIndex('status', ['name' => 'idx_plugin_wuma_source_certificate_status'])
            ->addIndex('deleted', ['name' => 'idx_plugin_wuma_source_certificate_deleted'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSourceProduce
     * @table plugin_wuma_source_produce
     * @return void
     */
    private function _create_plugin_wuma_source_produce()
    {

        // 当前数据表
        $table = 'plugin_wuma_source_produce';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '溯源-生产批次',
        ])
            ->addColumn('batch', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '生产批次'])
            ->addColumn('ghash', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '产品编号'])
            ->addColumn('tcode', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '关联溯源模板'])
            ->addColumn('addr_prov', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '所在省份'])
            ->addColumn('addr_city', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '所在城市'])
            ->addColumn('addr_area', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '所在区域'])
            ->addColumn('remark', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '批次备注'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效1有效)'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删1已删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('batch', ['name' => 'idx_plugin_wuma_source_produce_batch'])
            ->addIndex('status', ['name' => 'idx_plugin_wuma_source_produce_status'])
            ->addIndex('deleted', ['name' => 'idx_plugin_wuma_source_produce_deleted'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSourceQuery
     * @table plugin_wuma_source_query
     * @return void
     */
    private function _create_plugin_wuma_source_query()
    {

        // 当前数据表
        $table = 'plugin_wuma_source_query';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '溯源-查询记录',
        ])
            ->addColumn('auid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '代理用户'])
            ->addColumn('code', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '小码数码'])
            ->addColumn('ghash', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '商品哈希'])
            ->addColumn('times', 'biginteger', ['limit' => 20, 'default' => 1, 'null' => true, 'comment' => '查询次数'])
            ->addColumn('encode', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '物码编号'])
            ->addColumn('prov', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '所在省份'])
            ->addColumn('city', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '所在城市'])
            ->addColumn('area', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '所在区域'])
            ->addColumn('addr', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '详细地址'])
            ->addColumn('geoip', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '访问IP'])
            ->addColumn('gtype', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '定位类型'])
            ->addColumn('latlng', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '经纬度'])
            ->addColumn('notify', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '窜货状态'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('code', ['name' => 'idx_plugin_wuma_source_query_code'])
            ->addIndex('auid', ['name' => 'idx_plugin_wuma_source_query_auid'])
            ->addIndex('prov', ['name' => 'idx_plugin_wuma_source_query_prov'])
            ->addIndex('city', ['name' => 'idx_plugin_wuma_source_query_city'])
            ->addIndex('area', ['name' => 'idx_plugin_wuma_source_query_area'])
            ->addIndex('notify', ['name' => 'idx_plugin_wuma_source_query_notify'])
            ->addIndex('encode', ['name' => 'idx_plugin_wuma_source_query_encode'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSourceQueryNotify
     * @table plugin_wuma_source_query_notify
     * @return void
     */
    private function _create_plugin_wuma_source_query_notify()
    {

        // 当前数据表
        $table = 'plugin_wuma_source_query_notify';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '溯源-窜货异常',
        ])
            ->addColumn('auid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '代理用户'])
            ->addColumn('code', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '小码数码'])
            ->addColumn('type', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '记录类型'])
            ->addColumn('times', 'biginteger', ['limit' => 20, 'default' => 1, 'null' => true, 'comment' => '查询次数'])
            ->addColumn('encode', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '物码编号'])
            ->addColumn('prov', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '所在省份'])
            ->addColumn('city', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '所在城市'])
            ->addColumn('area', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '所在区域'])
            ->addColumn('addr', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '详细地址'])
            ->addColumn('gtype', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '定位类型'])
            ->addColumn('geoip', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '访问IP'])
            ->addColumn('latlng', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '经纬度'])
            ->addColumn('pcode', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '商品编号'])
            ->addColumn('pspec', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '商品规格'])
            ->addColumn('agent_prov', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '代理省份'])
            ->addColumn('agent_city', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '代理城市'])
            ->addColumn('agent_area', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '代理区域'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('auid', ['name' => 'idx_plugin_wuma_source_query_notify_auid'])
            ->addIndex('prov', ['name' => 'idx_plugin_wuma_source_query_notify_prov'])
            ->addIndex('city', ['name' => 'idx_plugin_wuma_source_query_notify_city'])
            ->addIndex('area', ['name' => 'idx_plugin_wuma_source_query_notify_area'])
            ->addIndex('code', ['name' => 'idx_plugin_wuma_source_query_notify_code'])
            ->addIndex('encode', ['name' => 'idx_plugin_wuma_source_query_notify_encode'])
            ->addIndex('agent_prov', ['name' => 'idx_plugin_wuma_source_query_notify_agent_prov'])
            ->addIndex('agent_city', ['name' => 'idx_plugin_wuma_source_query_notify_agent_city'])
            ->addIndex('agent_area', ['name' => 'idx_plugin_wuma_source_query_notify_agent_area'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSourceQueryVerify
     * @table plugin_wuma_source_query_verify
     * @return void
     */
    private function _create_plugin_wuma_source_query_verify()
    {

        // 当前数据表
        $table = 'plugin_wuma_source_query_verify';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '溯源-查询记录',
        ])
            ->addColumn('auid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '代理用户'])
            ->addColumn('code', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '小码数码'])
            ->addColumn('times', 'biginteger', ['limit' => 20, 'default' => 1, 'null' => true, 'comment' => '查询次数'])
            ->addColumn('ghash', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '商品编号'])
            ->addColumn('encode', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '物码编号'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('code', ['name' => 'idx_plugin_wuma_source_query_verify_code'])
            ->addIndex('auid', ['name' => 'idx_plugin_wuma_source_query_verify_auid'])
            ->addIndex('encode', ['name' => 'idx_plugin_wuma_source_query_verify_encode'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSourceTemplate
     * @table plugin_wuma_source_template
     * @return void
     */
    private function _create_plugin_wuma_source_template()
    {

        // 当前数据表
        $table = 'plugin_wuma_source_template';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '溯源-页面模板',
        ])
            ->addColumn('code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '模板编号'])
            ->addColumn('name', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '模板名称'])
            ->addColumn('times', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '访问次数'])
            ->addColumn('styles', 'text', ['default' => NULL, 'null' => true, 'comment' => '主题样式'])
            ->addColumn('content', 'text', ['default' => NULL, 'null' => true, 'comment' => '模板内容'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('code', ['name' => 'idx_plugin_wuma_source_template_code'])
            ->addIndex('status', ['name' => 'idx_plugin_wuma_source_template_status'])
            ->addIndex('deleted', ['name' => 'idx_plugin_wuma_source_template_deleted'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWumaWarehouse
     * @table plugin_wuma_warehouse
     * @return void
     */
    private function _create_plugin_wuma_warehouse()
    {

        // 当前数据表
        $table = 'plugin_wuma_warehouse';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-仓库',
        ])
            ->addColumn('code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '仓库编号'])
            ->addColumn('name', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '仓库名称'])
            ->addColumn('person', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '负责人'])
            ->addColumn('remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '物码描述'])
            ->addColumn('addr_prov', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '所属省份'])
            ->addColumn('addr_city', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '所属城市'])
            ->addColumn('addr_area', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '所属区域'])
            ->addColumn('addr_text', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '详细地址'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效1有效)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删1已删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('code', ['name' => 'idx_plugin_wuma_warehouse_code'])
            ->addIndex('name', ['name' => 'idx_plugin_wuma_warehouse_name'])
            ->addIndex('sort', ['name' => 'idx_plugin_wuma_warehouse_sort'])
            ->addIndex('status', ['name' => 'idx_plugin_wuma_warehouse_status'])
            ->addIndex('deleted', ['name' => 'idx_plugin_wuma_warehouse_deleted'])
            ->addIndex('create_time', ['name' => 'idx_plugin_wuma_warehouse_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWumaWarehouseOrder
     * @table plugin_wuma_warehouse_order
     * @return void
     */
    private function _create_plugin_wuma_warehouse_order()
    {

        // 当前数据表
        $table = 'plugin_wuma_warehouse_order';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-仓库-订单',
        ])
            ->addColumn('type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '操作类型(1订单入库,2直接入库,3调货入库,4订单出库,5直接出库,6调货出库,7关联出库,8直接退货)'])
            ->addColumn('mode', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '操作方式(1扫码操作,2虚拟操作)'])
            ->addColumn('auid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '出库代理'])
            ->addColumn('code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '操作单号'])
            ->addColumn('wcode', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '仓库编号'])
            ->addColumn('ghash', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '绑定产品'])
            ->addColumn('vir_need', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '虚拟总数'])
            ->addColumn('vir_used', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '虚拟完成'])
            ->addColumn('num_need', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '扫码总数'])
            ->addColumn('num_used', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '扫码完成'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效,2完成)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addColumn('deleted_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '删除时间'])
            ->addIndex('mode', ['name' => 'idx_plugin_wuma_warehouse_order_mode'])
            ->addIndex('auid', ['name' => 'idx_plugin_wuma_warehouse_order_auid'])
            ->addIndex('type', ['name' => 'idx_plugin_wuma_warehouse_order_type'])
            ->addIndex('code', ['name' => 'idx_plugin_wuma_warehouse_order_code'])
            ->addIndex('ghash', ['name' => 'idx_plugin_wuma_warehouse_order_ghash'])
            ->addIndex('wcode', ['name' => 'idx_plugin_wuma_warehouse_order_wcode'])
            ->addIndex('status', ['name' => 'idx_plugin_wuma_warehouse_order_status'])
            ->addIndex('deleted', ['name' => 'idx_plugin_wuma_warehouse_order_deleted'])
            ->addIndex('create_time', ['name' => 'idx_plugin_wuma_warehouse_order_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWumaWarehouseOrderData
     * @table plugin_wuma_warehouse_order_data
     * @return void
     */
    private function _create_plugin_wuma_warehouse_order_data()
    {

        // 当前数据表
        $table = 'plugin_wuma_warehouse_order_data';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-仓库-数据',
        ])
            ->addColumn('type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '操作类型(1订单入库,2直接入库,3调货入库,4订单出库,5直接出库,6调货出库,7关联出库,8直接退货)'])
            ->addColumn('mode', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '操作方式(1扫码操作,2虚拟操作)'])
            ->addColumn('code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '操作单号'])
            ->addColumn('number', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '标签总数'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addIndex('mode', ['name' => 'idx_plugin_wuma_warehouse_order_data_mode'])
            ->addIndex('type', ['name' => 'idx_plugin_wuma_warehouse_order_data_type'])
            ->addIndex('code', ['name' => 'idx_plugin_wuma_warehouse_order_data_code'])
            ->addIndex('status', ['name' => 'idx_plugin_wuma_warehouse_order_data_status'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWumaWarehouseOrderDataMins
     * @table plugin_wuma_warehouse_order_data_mins
     * @return void
     */
    private function _create_plugin_wuma_warehouse_order_data_mins()
    {

        // 当前数据表
        $table = 'plugin_wuma_warehouse_order_data_mins';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-仓库-小码',
        ])
            ->addColumn('type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '操作类型(1订单入库,2直接入库,3调货入库,4订单出库,5直接出库,6调货出库,7关联出库,8直接退货)'])
            ->addColumn('mode', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '操作方式(1扫码操作,2虚拟操作)'])
            ->addColumn('ddid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '数据编号'])
            ->addColumn('code', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '物码数据'])
            ->addColumn('stock', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '调货:库存有效(0已出,1暂存)'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '退货:记录状态(0无效,1有效)'])
            ->addColumn('status_time', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '状态时间'])
            ->addIndex('type', ['name' => 'idx_plugin_wuma_warehouse_order_data_mins_type'])
            ->addIndex('mode', ['name' => 'idx_plugin_wuma_warehouse_order_data_mins_mode'])
            ->addIndex('ddid', ['name' => 'idx_plugin_wuma_warehouse_order_data_mins_ddid'])
            ->addIndex('code', ['name' => 'idx_plugin_wuma_warehouse_order_data_mins_code'])
            ->addIndex('stock', ['name' => 'idx_plugin_wuma_warehouse_order_data_mins_stock'])
            ->addIndex('status', ['name' => 'idx_plugin_wuma_warehouse_order_data_mins_status'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWumaWarehouseOrderDataNums
     * @table plugin_wuma_warehouse_order_data_nums
     * @return void
     */
    private function _create_plugin_wuma_warehouse_order_data_nums()
    {

        // 当前数据表
        $table = 'plugin_wuma_warehouse_order_data_nums';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-仓库-箱码',
        ])
            ->addColumn('ddid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '数据编号'])
            ->addColumn('type', 'string', ['limit' => 40, 'default' => '', 'null' => true, 'comment' => '物码类型'])
            ->addColumn('code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '物码数据'])
            ->addColumn('count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '物码数量'])
            ->addIndex('ddid', ['name' => 'idx_plugin_wuma_warehouse_order_data_nums_ddid'])
            ->addIndex('code', ['name' => 'idx_plugin_wuma_warehouse_order_data_nums_code'])
            ->addIndex('type', ['name' => 'idx_plugin_wuma_warehouse_order_data_nums_type'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWumaWarehouseRelation
     * @table plugin_wuma_warehouse_relation
     * @return void
     */
    private function _create_plugin_wuma_warehouse_relation()
    {

        // 当前数据表
        $table = 'plugin_wuma_warehouse_relation';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-仓库-关联',
        ])
            ->addColumn('max', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '大码数值'])
            ->addColumn('mid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '中码数值'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
            ->addColumn('create_by', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上传用户'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addIndex('max', ['name' => 'idx_plugin_wuma_warehouse_relation_max'])
            ->addIndex('mid', ['name' => 'idx_plugin_wuma_warehouse_relation_mid'])
            ->addIndex('deleted', ['name' => 'idx_plugin_wuma_warehouse_relation_deleted'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWumaWarehouseRelationData
     * @table plugin_wuma_warehouse_relation_data
     * @return void
     */
    private function _create_plugin_wuma_warehouse_relation_data()
    {

        // 当前数据表
        $table = 'plugin_wuma_warehouse_relation_data';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-仓库-关联',
        ])
            ->addColumn('rid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '批次数据'])
            ->addColumn('max', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '大码数值'])
            ->addColumn('mid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '中码数值'])
            ->addColumn('min', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '小码数值'])
            ->addColumn('number', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '防窜编码'])
            ->addColumn('encode', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '防伪编码'])
            ->addColumn('lock', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '锁定状态'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
            ->addColumn('create_by', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上传用户'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addIndex('rid', ['name' => 'idx_plugin_wuma_warehouse_relation_data_rid'])
            ->addIndex('max', ['name' => 'idx_plugin_wuma_warehouse_relation_data_max'])
            ->addIndex('mid', ['name' => 'idx_plugin_wuma_warehouse_relation_data_mid'])
            ->addIndex('min', ['name' => 'idx_plugin_wuma_warehouse_relation_data_min'])
            ->addIndex('lock', ['name' => 'idx_plugin_wuma_warehouse_relation_data_lock'])
            ->addIndex('status', ['name' => 'idx_plugin_wuma_warehouse_relation_data_status'])
            ->addIndex('encode', ['name' => 'idx_plugin_wuma_warehouse_relation_data_encode'])
            ->addIndex('number', ['name' => 'idx_plugin_wuma_warehouse_relation_data_number'])
            ->addIndex('deleted', ['name' => 'idx_plugin_wuma_warehouse_relation_data_deleted'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWumaWarehouseReplace
     * @table plugin_wuma_warehouse_replace
     * @return void
     */
    private function _create_plugin_wuma_warehouse_replace()
    {

        // 当前数据表
        $table = 'plugin_wuma_warehouse_replace';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-仓库-替换',
        ])
            ->addColumn('type', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '物码类型'])
            ->addColumn('smin', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '原值小码'])
            ->addColumn('tmin', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '目标小码'])
            ->addColumn('source', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '原物码值'])
            ->addColumn('target', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '目标物码'])
            ->addColumn('lock', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '锁定状态'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
            ->addColumn('create_by', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上传用户'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addIndex('smin', ['name' => 'idx_plugin_wuma_warehouse_replace_smin'])
            ->addIndex('tmin', ['name' => 'idx_plugin_wuma_warehouse_replace_tmin'])
            ->addIndex('lock', ['name' => 'idx_plugin_wuma_warehouse_replace_lock'])
            ->addIndex('status', ['name' => 'idx_plugin_wuma_warehouse_replace_status'])
            ->addIndex('target', ['name' => 'idx_plugin_wuma_warehouse_replace_target'])
            ->addIndex('source', ['name' => 'idx_plugin_wuma_warehouse_replace_source'])
            ->addIndex('deleted', ['name' => 'idx_plugin_wuma_warehouse_replace_deleted'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWumaWarehouseStock
     * @table plugin_wuma_warehouse_stock
     * @return void
     */
    private function _create_plugin_wuma_warehouse_stock()
    {

        // 当前数据表
        $table = 'plugin_wuma_warehouse_stock';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-仓库-库存',
        ])
            ->addColumn('wcode', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '仓库编号'])
            ->addColumn('ghash', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '商品规格'])
            ->addColumn('vir_total', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '虚拟总数'])
            ->addColumn('vir_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '虚拟完成'])
            ->addColumn('num_total', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '扫码总数'])
            ->addColumn('num_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '扫码完成'])
            ->addIndex('wcode', ['name' => 'idx_plugin_wuma_warehouse_stock_wcode'])
            ->addIndex('ghash', ['name' => 'idx_plugin_wuma_warehouse_stock_ghash'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWumaWarehouseUser
     * @table plugin_wuma_warehouse_user
     * @return void
     */
    private function _create_plugin_wuma_warehouse_user()
    {

        // 当前数据表
        $table = 'plugin_wuma_warehouse_user';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-仓库-用户',
        ])
            ->addColumn('token', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '接口令牌'])
            ->addColumn('username', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '用户账号'])
            ->addColumn('nickname', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '用户昵称'])
            ->addColumn('password', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '登录密码'])
            ->addColumn('login_ip', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '登录地址'])
            ->addColumn('login_time', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '登录时间'])
            ->addColumn('login_num', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '登录测试'])
            ->addColumn('login_vars', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '登录参数'])
            ->addColumn('remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '物码描述'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('token', ['name' => 'idx_plugin_wuma_warehouse_user_token'])
            ->addIndex('username', ['name' => 'idx_plugin_wuma_warehouse_user_username'])
            ->addIndex('password', ['name' => 'idx_plugin_wuma_warehouse_user_password'])
            ->addIndex('sort', ['name' => 'idx_plugin_wuma_warehouse_user_sort'])
            ->addIndex('status', ['name' => 'idx_plugin_wuma_warehouse_user_status'])
            ->addIndex('deleted', ['name' => 'idx_plugin_wuma_warehouse_user_deleted'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }
}
