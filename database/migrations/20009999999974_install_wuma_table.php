<?php

use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', -1);

class InstallWumaTable extends Migrator {

	/**
	 * 创建数据库
	 */
	 public function change() {
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
    private function _create_plugin_wuma_code_rule() {

        // 当前数据表
        $table = 'plugin_wuma_code_rule';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-生码-规则',
        ])
		->addColumn('type','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '批次类型(1前关联，2后关联)'])
		->addColumn('batch','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '批次编号'])
		->addColumn('mid_min','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '中码与小码比值'])
		->addColumn('max_mid','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '大码与中码比值'])
		->addColumn('sns_start','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '序号起始值'])
		->addColumn('sns_after','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '序号结束值'])
		->addColumn('sns_length','biginteger',['limit' => 20, 'default' => 20, 'null' => true, 'comment' => '序号长度'])
		->addColumn('max_length','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '大码长度'])
		->addColumn('mid_length','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '中码长度'])
		->addColumn('min_length','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '小码长度'])
		->addColumn('hex_length','biginteger',['limit' => 20, 'default' => 10, 'null' => true, 'comment' => '加密长度'])
		->addColumn('ver_length','biginteger',['limit' => 20, 'default' => 4, 'null' => true, 'comment' => '验证长度'])
		->addColumn('max_number','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '大码数量'])
		->addColumn('mid_number','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '中码数量'])
		->addColumn('number','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '物码总数'])
		->addColumn('template','string',['limit' => 500, 'default' => '', 'null' => true, 'comment' => '导出模板'])
		->addColumn('remark','string',['limit' => 500, 'default' => '', 'null' => true, 'comment' => '物码描述'])
		->addColumn('status','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效)'])
		->addColumn('deleted','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
		->addColumn('create_time','datetime',['default' => NULL, 'null' => true, 'comment' => '创建时间'])
		->addColumn('update_time','datetime',['default' => NULL, 'null' => true, 'comment' => '更新时间'])
		->addIndex('type', ['name' => 'ia578a2d18_type'])
		->addIndex('batch', ['name' => 'ia578a2d18_batch'])
		->addIndex('status', ['name' => 'ia578a2d18_status'])
		->addIndex('deleted', ['name' => 'ia578a2d18_deleted'])
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
    private function _create_plugin_wuma_code_rule_range() {

        // 当前数据表
        $table = 'plugin_wuma_code_rule_range';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-生码-范围',
        ])
		->addColumn('type','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '物码类型'])
		->addColumn('batch','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '物码批次号'])
		->addColumn('code_type','string',['limit' => 3, 'default' => '', 'null' => true, 'comment' => '数码类型(min小码,mid中码,max大码)'])
		->addColumn('code_length','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '数码长度'])
		->addColumn('range_start','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '起始数码'])
		->addColumn('range_after','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '结束数码'])
		->addColumn('range_number','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '数码数量'])
		->addIndex('type', ['name' => 'i39dabc79d_type'])
		->addIndex('code_type', ['name' => 'i39dabc79d_code_type'])
		->addIndex('code_length', ['name' => 'i39dabc79d_code_length'])
		->addIndex('range_start', ['name' => 'i39dabc79d_range_start'])
		->addIndex('range_after', ['name' => 'i39dabc79d_range_after'])
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
    private function _create_plugin_wuma_sales_order() {

        // 当前数据表
        $table = 'plugin_wuma_sales_order';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-代理-订单',
        ])
		->addColumn('auid','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '经销商编号'])
		->addColumn('xuid','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '来源经销商'])
		->addColumn('code','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '操作单单号'])
		->addColumn('mode','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '操作方式(1扫码,2虚拟)'])
		->addColumn('ghash','string',['limit' => 32, 'default' => '', 'null' => true, 'comment' => '商品哈唏'])
		->addColumn('vir_need','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '虚拟库统计'])
		->addColumn('vir_count','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '虚拟库使用'])
		->addColumn('num_need','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '累计出库数量'])
		->addColumn('num_count','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '累计已经出库'])
		->addColumn('status','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效,2完成)'])
		->addColumn('deleted','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
		->addColumn('create_time','datetime',['default' => NULL, 'null' => true, 'comment' => '创建时间'])
		->addColumn('update_time','datetime',['default' => NULL, 'null' => true, 'comment' => '更新时间'])
		->addIndex('auid', ['name' => 'i1f94143fc_auid'])
		->addIndex('xuid', ['name' => 'i1f94143fc_xuid'])
		->addIndex('code', ['name' => 'i1f94143fc_code'])
		->addIndex('mode', ['name' => 'i1f94143fc_mode'])
		->addIndex('ghash', ['name' => 'i1f94143fc_ghash'])
		->addIndex('status', ['name' => 'i1f94143fc_status'])
		->addIndex('deleted', ['name' => 'i1f94143fc_deleted'])
		->addIndex('create_time', ['name' => 'i1f94143fc_create_time'])
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
    private function _create_plugin_wuma_sales_order_data() {

        // 当前数据表
        $table = 'plugin_wuma_sales_order_data';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-代理-数据',
        ])
		->addColumn('auid','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '经销商编号'])
		->addColumn('xuid','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '来源经销商'])
		->addColumn('code','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '操作单号'])
		->addColumn('mode','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '操作方式(1扫码,2虚拟)'])
		->addColumn('status','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效)'])
		->addColumn('number','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '物码总数'])
		->addColumn('create_time','datetime',['default' => NULL, 'null' => true, 'comment' => '创建时间'])
		->addColumn('update_time','datetime',['default' => NULL, 'null' => true, 'comment' => '更新时间'])
		->addIndex('auid', ['name' => 'ibf51d799e_auid'])
		->addIndex('xuid', ['name' => 'ibf51d799e_xuid'])
		->addIndex('mode', ['name' => 'ibf51d799e_mode'])
		->addIndex('code', ['name' => 'ibf51d799e_code'])
		->addIndex('status', ['name' => 'ibf51d799e_status'])
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
    private function _create_plugin_wuma_sales_order_data_mins() {

        // 当前数据表
        $table = 'plugin_wuma_sales_order_data_mins';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-代理-小码',
        ])
		->addColumn('ddid','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '数据编号'])
		->addColumn('auid','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '代理编号'])
		->addColumn('code','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '物码数据'])
		->addColumn('mode','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '操作类型(1扫码,2虚拟)'])
		->addColumn('stock','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '库存有效'])
		->addColumn('ghash','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '商品哈唏'])
		->addColumn('status','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '数据状态(0无效,1有效)'])
		->addColumn('deleted','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0有效,1已删)'])
		->addColumn('status_time','datetime',['default' => NULL, 'null' => true, 'comment' => '状态时间'])
		->addColumn('create_time','datetime',['default' => NULL, 'null' => true, 'comment' => '创建时间'])
		->addIndex('ddid', ['name' => 'i4b3ba8dcc_ddid'])
		->addIndex('auid', ['name' => 'i4b3ba8dcc_auid'])
		->addIndex('code', ['name' => 'i4b3ba8dcc_code'])
		->addIndex('mode', ['name' => 'i4b3ba8dcc_mode'])
		->addIndex('stock', ['name' => 'i4b3ba8dcc_stock'])
		->addIndex('ghash', ['name' => 'i4b3ba8dcc_ghash'])
		->addIndex('status', ['name' => 'i4b3ba8dcc_status'])
		->addIndex('deleted', ['name' => 'i4b3ba8dcc_deleted'])
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
    private function _create_plugin_wuma_sales_order_data_nums() {

        // 当前数据表
        $table = 'plugin_wuma_sales_order_data_nums';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-代理-箱码',
        ])
		->addColumn('uuid','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '所属品牌'])
		->addColumn('ddid','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '数据编号'])
		->addColumn('count','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '物码数量'])
		->addColumn('type','string',['limit' => 40, 'default' => '', 'null' => true, 'comment' => '物码类型'])
		->addColumn('code','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '物码数据'])
		->addIndex('type', ['name' => 'i889b5a793_type'])
		->addIndex('uuid', ['name' => 'i889b5a793_uuid'])
		->addIndex('ddid', ['name' => 'i889b5a793_ddid'])
		->addIndex('code', ['name' => 'i889b5a793_code'])
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
    private function _create_plugin_wuma_sales_user() {

        // 当前数据表
        $table = 'plugin_wuma_sales_user';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-代理-用户',
        ])
		->addColumn('auid','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上级代理'])
		->addColumn('code','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '授权编号'])
		->addColumn('level','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '代理等级'])
		->addColumn('master','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '总部账号'])
		->addColumn('phone','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '用户手机'])
		->addColumn('userid','string',['limit' => 50, 'default' => '', 'null' => true, 'comment' => '身份证号'])
		->addColumn('mobile','string',['limit' => 50, 'default' => '', 'null' => true, 'comment' => '联系电话'])
		->addColumn('headimg','string',['limit' => 500, 'default' => '', 'null' => true, 'comment' => '用户头像'])
		->addColumn('username','string',['limit' => 50, 'default' => '', 'null' => true, 'comment' => '用户姓名'])
		->addColumn('password','string',['limit' => 32, 'default' => '', 'null' => true, 'comment' => '登录密码'])
		->addColumn('date_start','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '开始时间'])
		->addColumn('date_after','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '结束时间'])
		->addColumn('super_auid','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '邀请上级用户'])
		->addColumn('super_phone','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '邀请上级手机'])
		->addColumn('business','string',['limit' => 500, 'default' => '', 'null' => true, 'comment' => '营业执照'])
		->addColumn('region_prov','string',['limit' => 100, 'default' => '', 'null' => true, 'comment' => '所属省份'])
		->addColumn('region_city','string',['limit' => 100, 'default' => '', 'null' => true, 'comment' => '所属城市'])
		->addColumn('region_area','string',['limit' => 100, 'default' => '', 'null' => true, 'comment' => '所属区域'])
		->addColumn('region_address','string',['limit' => 255, 'default' => '', 'null' => true, 'comment' => '详细地址'])
		->addColumn('remark','string',['limit' => 500, 'default' => '', 'null' => true, 'comment' => '用户备注描述'])
		->addColumn('status','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '用户状态(1正常,0已黑)'])
		->addColumn('deleted','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
		->addColumn('create_time','datetime',['default' => NULL, 'null' => true, 'comment' => '注册时间'])
		->addColumn('update_time','datetime',['default' => NULL, 'null' => true, 'comment' => '更新时间'])
		->addIndex('auid', ['name' => 'i377b9bafa_auid'])
		->addIndex('code', ['name' => 'i377b9bafa_code'])
		->addIndex('level', ['name' => 'i377b9bafa_level'])
		->addIndex('status', ['name' => 'i377b9bafa_status'])
		->addIndex('deleted', ['name' => 'i377b9bafa_deleted'])
		->addIndex('super_auid', ['name' => 'i377b9bafa_super_auid'])
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
    private function _create_plugin_wuma_sales_user_level() {

        // 当前数据表
        $table = 'plugin_wuma_sales_user_level';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-代理-等级',
        ])
		->addColumn('name','string',['limit' => 200, 'default' => '', 'null' => true, 'comment' => '代理级别名称'])
		->addColumn('number','integer',['limit' => 2, 'default' => 0, 'null' => true, 'comment' => '代理级别序号'])
		->addColumn('remark','string',['limit' => 500, 'default' => '', 'null' => true, 'comment' => '代理级别描述'])
		->addColumn('utime','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '等级更新时间'])
		->addColumn('status','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '代理等级状态(1使用,0禁用)'])
		->addColumn('create_time','datetime',['default' => NULL, 'null' => true, 'comment' => '等级创建时间'])
		->addIndex('status', ['name' => 'i229ec78e0_status'])
		->addIndex('number', ['name' => 'i229ec78e0_number'])
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
    private function _create_plugin_wuma_sales_user_stock() {

        // 当前数据表
        $table = 'plugin_wuma_sales_user_stock';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-代理-库存',
        ])
		->addColumn('auid','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '经销编号'])
		->addColumn('ghash','string',['limit' => 32, 'default' => '', 'null' => true, 'comment' => '商品哈唏'])
		->addColumn('vir_total','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '虚拟库存'])
		->addColumn('vir_count','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '虚拟出货'])
		->addColumn('num_total','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '累计库存'])
		->addColumn('num_count','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '累计出货'])
		->addIndex('auid', ['name' => 'i04cc86743_auid'])
		->addIndex('ghash', ['name' => 'i04cc86743_ghash'])
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
    private function _create_plugin_wuma_source_assign() {

        // 当前数据表
        $table = 'plugin_wuma_source_assign';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '溯源-赋码批次',
        ])
		->addColumn('type','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '赋码类型(0区间,1关联)'])
		->addColumn('batch','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '赋码批次号'])
		->addColumn('cbatch','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '物码批次号'])
		->addColumn('outer_items','text',['default' => NULL, 'null' => true, 'comment' => 'JSON出库'])
		->addColumn('coder_items2','text',['default' => NULL, 'null' => true, 'comment' => 'JSON赋码'])
		->addColumn('status','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效)'])
		->addColumn('deleted','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
		->addColumn('create_time','datetime',['default' => NULL, 'null' => true, 'comment' => '创建时间'])
		->addColumn('update_time','datetime',['default' => NULL, 'null' => true, 'comment' => '更新时间'])
		->addIndex('type', ['name' => 'ia625c5e0a_type'])
		->addIndex('batch', ['name' => 'ia625c5e0a_batch'])
		->addIndex('cbatch', ['name' => 'ia625c5e0a_cbatch'])
		->addIndex('status', ['name' => 'ia625c5e0a_status'])
		->addIndex('deleted', ['name' => 'ia625c5e0a_deleted'])
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
    private function _create_plugin_wuma_source_assign_item() {

        // 当前数据表
        $table = 'plugin_wuma_source_assign_item';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '溯源-赋码规则',
        ])
		->addColumn('real','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '是否真锁定'])
		->addColumn('lock','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '是否已锁定'])
		->addColumn('batch','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '赋码批次号'])
		->addColumn('cbatch','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '物码批次号'])
		->addColumn('pbatch','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '生产批次号'])
		->addColumn('range_start','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '开始物码区间'])
		->addColumn('range_after','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '结束物码区间'])
		->addColumn('create_time','datetime',['default' => NULL, 'null' => true, 'comment' => '创建时间'])
		->addColumn('update_time','datetime',['default' => NULL, 'null' => true, 'comment' => '更新时间'])
		->addIndex('lock', ['name' => 'i055bd72d1_lock'])
		->addIndex('real', ['name' => 'i055bd72d1_real'])
		->addIndex('batch', ['name' => 'i055bd72d1_batch'])
		->addIndex('cbatch', ['name' => 'i055bd72d1_cbatch'])
		->addIndex('pbatch', ['name' => 'i055bd72d1_pbatch'])
		->addIndex('range_start', ['name' => 'i055bd72d1_range_start'])
		->addIndex('range_after', ['name' => 'i055bd72d1_range_after'])
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
    private function _create_plugin_wuma_source_blockchain() {

        // 当前数据表
        $table = 'plugin_wuma_source_blockchain';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '溯源-区块链',
        ])
		->addColumn('scid','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '确权证书'])
		->addColumn('code','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '流程编号'])
		->addColumn('hash','string',['limit' => 255, 'default' => '', 'null' => true, 'comment' => '流程HASH'])
		->addColumn('name','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '流程名称'])
		->addColumn('data','text',['default' => NULL, 'null' => true, 'comment' => '流程环节'])
		->addColumn('remark','string',['limit' => 999, 'default' => '', 'null' => true, 'comment' => '流程备注'])
		->addColumn('sort','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
		->addColumn('status','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效1有效)'])
		->addColumn('deleted','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删1已删)'])
		->addColumn('hash_time','datetime',['default' => NULL, 'null' => true, 'comment' => '上链时间'])
		->addColumn('create_time','datetime',['default' => NULL, 'null' => true, 'comment' => '创建时间'])
		->addColumn('update_time','datetime',['default' => NULL, 'null' => true, 'comment' => '更新时间'])
		->addIndex('code', ['name' => 'i035596f4d_code'])
		->addIndex('scid', ['name' => 'i035596f4d_scid'])
		->addIndex('sort', ['name' => 'i035596f4d_sort'])
		->addIndex('status', ['name' => 'i035596f4d_status'])
		->addIndex('deleted', ['name' => 'i035596f4d_deleted'])
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
    private function _create_plugin_wuma_source_certificate() {

        // 当前数据表
        $table = 'plugin_wuma_source_certificate';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '溯源-确权证书',
        ])
		->addColumn('code','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '模板编号'])
		->addColumn('name','string',['limit' => 200, 'default' => '', 'null' => true, 'comment' => '模板名称'])
		->addColumn('times','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '访问次数'])
		->addColumn('image','string',['limit' => 500, 'default' => '', 'null' => true, 'comment' => '证书底图'])
		->addColumn('content','text',['default' => NULL, 'null' => true, 'comment' => '定制规则'])
		->addColumn('sort','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
		->addColumn('status','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效1有效)'])
		->addColumn('deleted','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删1已删)'])
		->addColumn('create_time','datetime',['default' => NULL, 'null' => true, 'comment' => '创建时间'])
		->addColumn('update_time','datetime',['default' => NULL, 'null' => true, 'comment' => '更新时间'])
		->addIndex('code', ['name' => 'i550f3ad85_code'])
		->addIndex('status', ['name' => 'i550f3ad85_status'])
		->addIndex('deleted', ['name' => 'i550f3ad85_deleted'])
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
    private function _create_plugin_wuma_source_produce() {

        // 当前数据表
        $table = 'plugin_wuma_source_produce';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '溯源-生产批次',
        ])
		->addColumn('batch','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '生产批次'])
		->addColumn('ghash','string',['limit' => 32, 'default' => '', 'null' => true, 'comment' => '产品编号'])
		->addColumn('tcode','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '关联溯源模板'])
		->addColumn('addr_prov','string',['limit' => 255, 'default' => '', 'null' => true, 'comment' => '所在省份'])
		->addColumn('addr_city','string',['limit' => 255, 'default' => '', 'null' => true, 'comment' => '所在城市'])
		->addColumn('addr_area','string',['limit' => 255, 'default' => '', 'null' => true, 'comment' => '所在区域'])
		->addColumn('remark','string',['limit' => 999, 'default' => '', 'null' => true, 'comment' => '批次备注'])
		->addColumn('status','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效1有效)'])
		->addColumn('sort','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
		->addColumn('deleted','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删1已删)'])
		->addColumn('create_time','datetime',['default' => NULL, 'null' => true, 'comment' => '创建时间'])
		->addColumn('update_time','datetime',['default' => NULL, 'null' => true, 'comment' => '更新时间'])
		->addIndex('batch', ['name' => 'i95bd96a03_batch'])
		->addIndex('status', ['name' => 'i95bd96a03_status'])
		->addIndex('deleted', ['name' => 'i95bd96a03_deleted'])
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
    private function _create_plugin_wuma_source_query() {

        // 当前数据表
        $table = 'plugin_wuma_source_query';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '溯源-查询记录',
        ])
		->addColumn('auid','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '代理用户'])
		->addColumn('code','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '小码数码'])
		->addColumn('ghash','string',['limit' => 32, 'default' => '', 'null' => true, 'comment' => '商品哈希'])
		->addColumn('times','biginteger',['limit' => 20, 'default' => 1, 'null' => true, 'comment' => '查询次数'])
		->addColumn('encode','string',['limit' => 50, 'default' => '', 'null' => true, 'comment' => '物码编号'])
		->addColumn('prov','string',['limit' => 50, 'default' => '', 'null' => true, 'comment' => '所在省份'])
		->addColumn('city','string',['limit' => 50, 'default' => '', 'null' => true, 'comment' => '所在城市'])
		->addColumn('area','string',['limit' => 50, 'default' => '', 'null' => true, 'comment' => '所在区域'])
		->addColumn('addr','string',['limit' => 200, 'default' => '', 'null' => true, 'comment' => '详细地址'])
		->addColumn('geoip','string',['limit' => 50, 'default' => '', 'null' => true, 'comment' => '访问IP'])
		->addColumn('gtype','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '定位类型'])
		->addColumn('latlng','string',['limit' => 50, 'default' => '', 'null' => true, 'comment' => '经纬度'])
		->addColumn('notify','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '窜货状态'])
		->addColumn('create_time','datetime',['default' => NULL, 'null' => true, 'comment' => '创建时间'])
		->addColumn('update_time','datetime',['default' => NULL, 'null' => true, 'comment' => '更新时间'])
		->addIndex('code', ['name' => 'ibdbf0aa26_code'])
		->addIndex('auid', ['name' => 'ibdbf0aa26_auid'])
		->addIndex('prov', ['name' => 'ibdbf0aa26_prov'])
		->addIndex('city', ['name' => 'ibdbf0aa26_city'])
		->addIndex('area', ['name' => 'ibdbf0aa26_area'])
		->addIndex('notify', ['name' => 'ibdbf0aa26_notify'])
		->addIndex('encode', ['name' => 'ibdbf0aa26_encode'])
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
    private function _create_plugin_wuma_source_query_notify() {

        // 当前数据表
        $table = 'plugin_wuma_source_query_notify';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '溯源-窜货异常',
        ])
		->addColumn('auid','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '代理用户'])
		->addColumn('code','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '小码数码'])
		->addColumn('type','string',['limit' => 50, 'default' => '', 'null' => true, 'comment' => '记录类型'])
		->addColumn('times','biginteger',['limit' => 20, 'default' => 1, 'null' => true, 'comment' => '查询次数'])
		->addColumn('encode','string',['limit' => 50, 'default' => '', 'null' => true, 'comment' => '物码编号'])
		->addColumn('prov','string',['limit' => 100, 'default' => '', 'null' => true, 'comment' => '所在省份'])
		->addColumn('city','string',['limit' => 100, 'default' => '', 'null' => true, 'comment' => '所在城市'])
		->addColumn('area','string',['limit' => 100, 'default' => '', 'null' => true, 'comment' => '所在区域'])
		->addColumn('addr','string',['limit' => 200, 'default' => '', 'null' => true, 'comment' => '详细地址'])
		->addColumn('gtype','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '定位类型'])
		->addColumn('geoip','string',['limit' => 50, 'default' => '', 'null' => true, 'comment' => '访问IP'])
		->addColumn('latlng','string',['limit' => 50, 'default' => '', 'null' => true, 'comment' => '经纬度'])
		->addColumn('pcode','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '商品编号'])
		->addColumn('pspec','string',['limit' => 180, 'default' => '', 'null' => true, 'comment' => '商品规格'])
		->addColumn('agent_prov','string',['limit' => 100, 'default' => '', 'null' => true, 'comment' => '代理省份'])
		->addColumn('agent_city','string',['limit' => 100, 'default' => '', 'null' => true, 'comment' => '代理城市'])
		->addColumn('agent_area','string',['limit' => 100, 'default' => '', 'null' => true, 'comment' => '代理区域'])
		->addColumn('create_time','datetime',['default' => NULL, 'null' => true, 'comment' => '创建时间'])
		->addColumn('update_time','datetime',['default' => NULL, 'null' => true, 'comment' => '更新时间'])
		->addIndex('auid', ['name' => 'ibc28d4409_auid'])
		->addIndex('prov', ['name' => 'ibc28d4409_prov'])
		->addIndex('city', ['name' => 'ibc28d4409_city'])
		->addIndex('area', ['name' => 'ibc28d4409_area'])
		->addIndex('code', ['name' => 'ibc28d4409_code'])
		->addIndex('encode', ['name' => 'ibc28d4409_encode'])
		->addIndex('agent_prov', ['name' => 'ibc28d4409_agent_prov'])
		->addIndex('agent_city', ['name' => 'ibc28d4409_agent_city'])
		->addIndex('agent_area', ['name' => 'ibc28d4409_agent_area'])
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
    private function _create_plugin_wuma_source_query_verify() {

        // 当前数据表
        $table = 'plugin_wuma_source_query_verify';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '溯源-查询记录',
        ])
		->addColumn('auid','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '代理用户'])
		->addColumn('code','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '小码数码'])
		->addColumn('times','biginteger',['limit' => 20, 'default' => 1, 'null' => true, 'comment' => '查询次数'])
		->addColumn('ghash','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '商品编号'])
		->addColumn('encode','string',['limit' => 50, 'default' => '', 'null' => true, 'comment' => '物码编号'])
		->addColumn('create_time','datetime',['default' => NULL, 'null' => true, 'comment' => '创建时间'])
		->addColumn('update_time','datetime',['default' => NULL, 'null' => true, 'comment' => '更新时间'])
		->addIndex('code', ['name' => 'i7875f3381_code'])
		->addIndex('auid', ['name' => 'i7875f3381_auid'])
		->addIndex('encode', ['name' => 'i7875f3381_encode'])
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
    private function _create_plugin_wuma_source_template() {

        // 当前数据表
        $table = 'plugin_wuma_source_template';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '溯源-页面模板',
        ])
		->addColumn('code','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '模板编号'])
		->addColumn('name','string',['limit' => 200, 'default' => '', 'null' => true, 'comment' => '模板名称'])
		->addColumn('times','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '访问次数'])
		->addColumn('styles','text',['default' => NULL, 'null' => true, 'comment' => '主题样式'])
		->addColumn('content','text',['default' => NULL, 'null' => true, 'comment' => '模板内容'])
		->addColumn('sort','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
		->addColumn('status','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效)'])
		->addColumn('deleted','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
		->addColumn('create_time','datetime',['default' => NULL, 'null' => true, 'comment' => '创建时间'])
		->addColumn('update_time','datetime',['default' => NULL, 'null' => true, 'comment' => '更新时间'])
		->addIndex('code', ['name' => 'i79c0c749e_code'])
		->addIndex('status', ['name' => 'i79c0c749e_status'])
		->addIndex('deleted', ['name' => 'i79c0c749e_deleted'])
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
    private function _create_plugin_wuma_warehouse() {

        // 当前数据表
        $table = 'plugin_wuma_warehouse';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-仓库',
        ])
		->addColumn('code','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '仓库编号'])
		->addColumn('name','string',['limit' => 200, 'default' => '', 'null' => true, 'comment' => '仓库名称'])
		->addColumn('person','string',['limit' => 100, 'default' => '', 'null' => true, 'comment' => '负责人'])
		->addColumn('remark','string',['limit' => 500, 'default' => '', 'null' => true, 'comment' => '物码描述'])
		->addColumn('addr_prov','string',['limit' => 100, 'default' => '', 'null' => true, 'comment' => '所属省份'])
		->addColumn('addr_city','string',['limit' => 100, 'default' => '', 'null' => true, 'comment' => '所属城市'])
		->addColumn('addr_area','string',['limit' => 100, 'default' => '', 'null' => true, 'comment' => '所属区域'])
		->addColumn('addr_text','string',['limit' => 255, 'default' => '', 'null' => true, 'comment' => '详细地址'])
		->addColumn('sort','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
		->addColumn('status','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效1有效)'])
		->addColumn('deleted','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删1已删)'])
		->addColumn('create_time','datetime',['default' => NULL, 'null' => true, 'comment' => '创建时间'])
		->addColumn('update_time','datetime',['default' => NULL, 'null' => true, 'comment' => '更新时间'])
		->addIndex('code', ['name' => 'ie4be941b1_code'])
		->addIndex('name', ['name' => 'ie4be941b1_name'])
		->addIndex('sort', ['name' => 'ie4be941b1_sort'])
		->addIndex('status', ['name' => 'ie4be941b1_status'])
		->addIndex('deleted', ['name' => 'ie4be941b1_deleted'])
		->addIndex('create_time', ['name' => 'ie4be941b1_create_time'])
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
    private function _create_plugin_wuma_warehouse_order() {

        // 当前数据表
        $table = 'plugin_wuma_warehouse_order';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-仓库-订单',
        ])
		->addColumn('type','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '操作类型(1订单入库,2直接入库,3调货入库,4订单出库,5直接出库,6调货出库,7关联出库,8直接退货)'])
		->addColumn('mode','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '操作方式(1扫码操作,2虚拟操作)'])
		->addColumn('auid','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '出库代理'])
		->addColumn('code','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '操作单号'])
		->addColumn('wcode','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '仓库编号'])
		->addColumn('ghash','string',['limit' => 32, 'default' => '', 'null' => true, 'comment' => '绑定产品'])
		->addColumn('vir_need','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '虚拟总数'])
		->addColumn('vir_used','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '虚拟完成'])
		->addColumn('num_need','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '扫码总数'])
		->addColumn('num_used','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '扫码完成'])
		->addColumn('status','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效,2完成)'])
		->addColumn('deleted','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
		->addColumn('create_time','datetime',['default' => NULL, 'null' => true, 'comment' => '创建时间'])
		->addColumn('update_time','datetime',['default' => NULL, 'null' => true, 'comment' => '更新时间'])
		->addColumn('deleted_time','datetime',['default' => NULL, 'null' => true, 'comment' => '删除时间'])
		->addIndex('mode', ['name' => 'ic05f6dea4_mode'])
		->addIndex('auid', ['name' => 'ic05f6dea4_auid'])
		->addIndex('type', ['name' => 'ic05f6dea4_type'])
		->addIndex('code', ['name' => 'ic05f6dea4_code'])
		->addIndex('ghash', ['name' => 'ic05f6dea4_ghash'])
		->addIndex('wcode', ['name' => 'ic05f6dea4_wcode'])
		->addIndex('status', ['name' => 'ic05f6dea4_status'])
		->addIndex('deleted', ['name' => 'ic05f6dea4_deleted'])
		->addIndex('create_time', ['name' => 'ic05f6dea4_create_time'])
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
    private function _create_plugin_wuma_warehouse_order_data() {

        // 当前数据表
        $table = 'plugin_wuma_warehouse_order_data';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-仓库-数据',
        ])
		->addColumn('type','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '操作类型(1订单入库,2直接入库,3调货入库,4订单出库,5直接出库,6调货出库,7关联出库,8直接退货)'])
		->addColumn('mode','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '操作方式(1扫码操作,2虚拟操作)'])
		->addColumn('code','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '操作单号'])
		->addColumn('number','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '标签总数'])
		->addColumn('status','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效)'])
		->addColumn('create_time','datetime',['default' => NULL, 'null' => true, 'comment' => '创建时间'])
		->addIndex('mode', ['name' => 'i4970a1f8f_mode'])
		->addIndex('type', ['name' => 'i4970a1f8f_type'])
		->addIndex('code', ['name' => 'i4970a1f8f_code'])
		->addIndex('status', ['name' => 'i4970a1f8f_status'])
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
    private function _create_plugin_wuma_warehouse_order_data_mins() {

        // 当前数据表
        $table = 'plugin_wuma_warehouse_order_data_mins';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-仓库-小码',
        ])
		->addColumn('type','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '操作类型(1订单入库,2直接入库,3调货入库,4订单出库,5直接出库,6调货出库,7关联出库,8直接退货)'])
		->addColumn('mode','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '操作方式(1扫码操作,2虚拟操作)'])
		->addColumn('ddid','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '数据编号'])
		->addColumn('code','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '物码数据'])
		->addColumn('stock','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '调货:库存有效(0已出,1暂存)'])
		->addColumn('status','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '退货:记录状态(0无效,1有效)'])
		->addColumn('status_time','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '状态时间'])
		->addIndex('type', ['name' => 'ieae539ea0_type'])
		->addIndex('mode', ['name' => 'ieae539ea0_mode'])
		->addIndex('ddid', ['name' => 'ieae539ea0_ddid'])
		->addIndex('code', ['name' => 'ieae539ea0_code'])
		->addIndex('stock', ['name' => 'ieae539ea0_stock'])
		->addIndex('status', ['name' => 'ieae539ea0_status'])
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
    private function _create_plugin_wuma_warehouse_order_data_nums() {

        // 当前数据表
        $table = 'plugin_wuma_warehouse_order_data_nums';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-仓库-箱码',
        ])
		->addColumn('ddid','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '数据编号'])
		->addColumn('type','string',['limit' => 40, 'default' => '', 'null' => true, 'comment' => '物码类型'])
		->addColumn('code','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '物码数据'])
		->addColumn('count','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '物码数量'])
		->addIndex('ddid', ['name' => 'ie9a274f6f_ddid'])
		->addIndex('code', ['name' => 'ie9a274f6f_code'])
		->addIndex('type', ['name' => 'ie9a274f6f_type'])
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
    private function _create_plugin_wuma_warehouse_relation() {

        // 当前数据表
        $table = 'plugin_wuma_warehouse_relation';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-仓库-关联',
        ])
		->addColumn('max','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '大码数值'])
		->addColumn('mid','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '中码数值'])
		->addColumn('deleted','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
		->addColumn('create_by','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上传用户'])
		->addColumn('create_time','datetime',['default' => NULL, 'null' => true, 'comment' => '创建时间'])
		->addIndex('max', ['name' => 'i10d5c759c_max'])
		->addIndex('mid', ['name' => 'i10d5c759c_mid'])
		->addIndex('deleted', ['name' => 'i10d5c759c_deleted'])
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
    private function _create_plugin_wuma_warehouse_relation_data() {

        // 当前数据表
        $table = 'plugin_wuma_warehouse_relation_data';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-仓库-关联',
        ])
		->addColumn('rid','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '批次数据'])
		->addColumn('max','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '大码数值'])
		->addColumn('mid','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '中码数值'])
		->addColumn('min','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '小码数值'])
		->addColumn('number','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '防窜编码'])
		->addColumn('encode','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '防伪编码'])
		->addColumn('lock','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '锁定状态'])
		->addColumn('status','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效)'])
		->addColumn('deleted','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
		->addColumn('create_by','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上传用户'])
		->addColumn('create_time','datetime',['default' => NULL, 'null' => true, 'comment' => '创建时间'])
		->addIndex('rid', ['name' => 'i1ef793abd_rid'])
		->addIndex('max', ['name' => 'i1ef793abd_max'])
		->addIndex('mid', ['name' => 'i1ef793abd_mid'])
		->addIndex('min', ['name' => 'i1ef793abd_min'])
		->addIndex('lock', ['name' => 'i1ef793abd_lock'])
		->addIndex('status', ['name' => 'i1ef793abd_status'])
		->addIndex('encode', ['name' => 'i1ef793abd_encode'])
		->addIndex('number', ['name' => 'i1ef793abd_number'])
		->addIndex('deleted', ['name' => 'i1ef793abd_deleted'])
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
    private function _create_plugin_wuma_warehouse_replace() {

        // 当前数据表
        $table = 'plugin_wuma_warehouse_replace';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-仓库-替换',
        ])
		->addColumn('type','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '物码类型'])
		->addColumn('smin','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '原值小码'])
		->addColumn('tmin','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '目标小码'])
		->addColumn('source','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '原物码值'])
		->addColumn('target','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '目标物码'])
		->addColumn('lock','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '锁定状态'])
		->addColumn('status','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效)'])
		->addColumn('deleted','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
		->addColumn('create_by','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上传用户'])
		->addColumn('create_time','datetime',['default' => NULL, 'null' => true, 'comment' => '创建时间'])
		->addIndex('smin', ['name' => 'idb2cdf832_smin'])
		->addIndex('tmin', ['name' => 'idb2cdf832_tmin'])
		->addIndex('lock', ['name' => 'idb2cdf832_lock'])
		->addIndex('status', ['name' => 'idb2cdf832_status'])
		->addIndex('target', ['name' => 'idb2cdf832_target'])
		->addIndex('source', ['name' => 'idb2cdf832_source'])
		->addIndex('deleted', ['name' => 'idb2cdf832_deleted'])
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
    private function _create_plugin_wuma_warehouse_stock() {

        // 当前数据表
        $table = 'plugin_wuma_warehouse_stock';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-仓库-库存',
        ])
		->addColumn('wcode','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '仓库编号'])
		->addColumn('ghash','string',['limit' => 32, 'default' => '', 'null' => true, 'comment' => '商品规格'])
		->addColumn('vir_total','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '虚拟总数'])
		->addColumn('vir_count','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '虚拟完成'])
		->addColumn('num_total','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '扫码总数'])
		->addColumn('num_count','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '扫码完成'])
		->addIndex('wcode', ['name' => 'i0344f448b_wcode'])
		->addIndex('ghash', ['name' => 'i0344f448b_ghash'])
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
    private function _create_plugin_wuma_warehouse_user() {

        // 当前数据表
        $table = 'plugin_wuma_warehouse_user';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-仓库-用户',
        ])
		->addColumn('token','string',['limit' => 32, 'default' => '', 'null' => true, 'comment' => '接口令牌'])
		->addColumn('username','string',['limit' => 180, 'default' => '', 'null' => true, 'comment' => '用户账号'])
		->addColumn('nickname','string',['limit' => 180, 'default' => '', 'null' => true, 'comment' => '用户昵称'])
		->addColumn('password','string',['limit' => 32, 'default' => '', 'null' => true, 'comment' => '登录密码'])
		->addColumn('login_ip','string',['limit' => 180, 'default' => '', 'null' => true, 'comment' => '登录地址'])
		->addColumn('login_time','string',['limit' => 180, 'default' => '', 'null' => true, 'comment' => '登录时间'])
		->addColumn('login_num','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '登录测试'])
		->addColumn('login_vars','string',['limit' => 999, 'default' => '', 'null' => true, 'comment' => '登录参数'])
		->addColumn('remark','string',['limit' => 500, 'default' => '', 'null' => true, 'comment' => '物码描述'])
		->addColumn('sort','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
		->addColumn('status','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效)'])
		->addColumn('deleted','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
		->addColumn('create_time','datetime',['default' => NULL, 'null' => true, 'comment' => '创建时间'])
		->addColumn('update_time','datetime',['default' => NULL, 'null' => true, 'comment' => '更新时间'])
		->addIndex('sort', ['name' => 'i12b7dd060_sort'])
		->addIndex('token', ['name' => 'i12b7dd060_token'])
		->addIndex('status', ['name' => 'i12b7dd060_status'])
		->addIndex('deleted', ['name' => 'i12b7dd060_deleted'])
		->addIndex('username', ['name' => 'i12b7dd060_username'])
		->addIndex('password', ['name' => 'i12b7dd060_password'])
		->create();

		// 修改主键长度
		$this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
	}

}
