<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | Payment Plugin for ThinkAdmin
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
use think\admin\extend\PhinxExtend;
use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', -1);

class InstallWuma20241010 extends Migrator
{
    /**
     * 获取脚本名称.
     */
    public function getName(): string
    {
        return 'WumaPlugin';
    }

    /**
     * 创建数据库.
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
     */
    private function _create_plugin_wuma_code_rule()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wuma_code_rule', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-生码-规则',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['type', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '批次类型(1前关联，2后关联)']],
            ['batch', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '批次编号']],
            ['mid_min', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '中码与小码比值']],
            ['max_mid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '大码与中码比值']],
            ['sns_start', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '序号起始值']],
            ['sns_after', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '序号结束值']],
            ['sns_length', 'biginteger', ['limit' => 20, 'default' => 20, 'null' => true, 'comment' => '序号长度']],
            ['max_length', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '大码长度']],
            ['mid_length', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '中码长度']],
            ['min_length', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '小码长度']],
            ['hex_length', 'biginteger', ['limit' => 20, 'default' => 10, 'null' => true, 'comment' => '加密长度']],
            ['ver_length', 'biginteger', ['limit' => 20, 'default' => 4, 'null' => true, 'comment' => '验证长度']],
            ['max_number', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '大码数量']],
            ['mid_number', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '中码数量']],
            ['number', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '物码总数']],
            ['template', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '导出模板']],
            ['remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '物码描述']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'type', 'batch', 'status', 'deleted',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWumaCodeRuleRange
     * @table plugin_wuma_code_rule_range
     */
    private function _create_plugin_wuma_code_rule_range()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wuma_code_rule_range', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-生码-范围',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['type', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '物码类型']],
            ['batch', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '物码批次号']],
            ['code_type', 'string', ['limit' => 3, 'default' => '', 'null' => true, 'comment' => '数码类型(min小码,mid中码,max大码)']],
            ['code_length', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '数码长度']],
            ['range_start', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '起始数码']],
            ['range_after', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '结束数码']],
            ['range_number', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '数码数量']],
        ], [
            'type', 'code_type', 'code_length', 'range_start', 'range_after',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSalesOrder
     * @table plugin_wuma_sales_order
     */
    private function _create_plugin_wuma_sales_order()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wuma_sales_order', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-代理-订单',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['auid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '经销商编号']],
            ['xuid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '来源经销商']],
            ['code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '操作单单号']],
            ['mode', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '操作方式(1扫码,2虚拟)']],
            ['ghash', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '商品哈唏']],
            ['vir_need', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '虚拟库统计']],
            ['vir_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '虚拟库使用']],
            ['num_need', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '累计出库数量']],
            ['num_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '累计已经出库']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效,2完成)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'auid', 'xuid', 'code', 'mode', 'ghash', 'status', 'deleted', 'create_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSalesOrderData
     * @table plugin_wuma_sales_order_data
     */
    private function _create_plugin_wuma_sales_order_data()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wuma_sales_order_data', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-代理-数据',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['auid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '经销商编号']],
            ['xuid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '来源经销商']],
            ['code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '操作单号']],
            ['mode', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '操作方式(1扫码,2虚拟)']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效)']],
            ['number', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '物码总数']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'auid', 'xuid', 'mode', 'code', 'status',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSalesOrderDataMins
     * @table plugin_wuma_sales_order_data_mins
     */
    private function _create_plugin_wuma_sales_order_data_mins()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wuma_sales_order_data_mins', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-代理-小码',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['ddid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '数据编号']],
            ['auid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '代理编号']],
            ['code', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '物码数据']],
            ['mode', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '操作类型(1扫码,2虚拟)']],
            ['stock', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '库存有效']],
            ['ghash', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '商品哈唏']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '数据状态(0无效,1有效)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0有效,1已删)']],
            ['status_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '状态时间']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
        ], [
            'ddid', 'auid', 'code', 'mode', 'stock', 'ghash', 'status', 'deleted',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSalesOrderDataNums
     * @table plugin_wuma_sales_order_data_nums
     */
    private function _create_plugin_wuma_sales_order_data_nums()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wuma_sales_order_data_nums', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-代理-箱码',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['uuid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '所属品牌']],
            ['ddid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '数据编号']],
            ['count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '物码数量']],
            ['type', 'string', ['limit' => 40, 'default' => '', 'null' => true, 'comment' => '物码类型']],
            ['code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '物码数据']],
        ], [
            'type', 'uuid', 'ddid', 'code',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSalesUser
     * @table plugin_wuma_sales_user
     */
    private function _create_plugin_wuma_sales_user()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wuma_sales_user', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-代理-用户',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['auid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上级代理']],
            ['code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '授权编号']],
            ['level', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '代理等级']],
            ['master', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '总部账号']],
            ['phone', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '用户手机']],
            ['userid', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '身份证号']],
            ['mobile', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '联系电话']],
            ['headimg', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '用户头像']],
            ['username', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '用户姓名']],
            ['password', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '登录密码']],
            ['date_start', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '开始时间']],
            ['date_after', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '结束时间']],
            ['super_auid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '邀请上级用户']],
            ['super_phone', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '邀请上级手机']],
            ['business', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '营业执照']],
            ['region_prov', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '所属省份']],
            ['region_city', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '所属城市']],
            ['region_area', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '所属区域']],
            ['region_address', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '详细地址']],
            ['remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '用户备注描述']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '用户状态(1正常,0已黑)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '注册时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'auid', 'code', 'level', 'status', 'deleted', 'super_auid',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSalesUserLevel
     * @table plugin_wuma_sales_user_level
     */
    private function _create_plugin_wuma_sales_user_level()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wuma_sales_user_level', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-代理-等级',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['name', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '代理级别名称']],
            ['number', 'integer', ['limit' => 2, 'default' => 0, 'null' => true, 'comment' => '代理级别序号']],
            ['remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '代理级别描述']],
            ['utime', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '等级更新时间']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '代理等级状态(1使用,0禁用)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '等级创建时间']],
        ], [
            'status', 'number',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSalesUserStock
     * @table plugin_wuma_sales_user_stock
     */
    private function _create_plugin_wuma_sales_user_stock()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wuma_sales_user_stock', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-代理-库存',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['auid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '经销编号']],
            ['ghash', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '商品哈唏']],
            ['vir_total', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '虚拟库存']],
            ['vir_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '虚拟出货']],
            ['num_total', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '累计库存']],
            ['num_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '累计出货']],
        ], [
            'auid', 'ghash',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSourceAssign
     * @table plugin_wuma_source_assign
     */
    private function _create_plugin_wuma_source_assign()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wuma_source_assign', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '溯源-赋码批次',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '赋码类型(0区间,1关联)']],
            ['batch', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '赋码批次号']],
            ['cbatch', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '物码批次号']],
            ['outer_items', 'text', ['default' => null, 'null' => true, 'comment' => 'JSON出库']],
            ['coder_items2', 'text', ['default' => null, 'null' => true, 'comment' => 'JSON赋码']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'type', 'batch', 'cbatch', 'status', 'deleted',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSourceAssignItem
     * @table plugin_wuma_source_assign_item
     */
    private function _create_plugin_wuma_source_assign_item()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wuma_source_assign_item', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '溯源-赋码规则',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['real', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '是否真锁定']],
            ['lock', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '是否已锁定']],
            ['batch', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '赋码批次号']],
            ['cbatch', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '物码批次号']],
            ['pbatch', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '生产批次号']],
            ['range_start', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '开始物码区间']],
            ['range_after', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '结束物码区间']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'lock', 'real', 'batch', 'cbatch', 'pbatch', 'range_start', 'range_after',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSourceBlockchain
     * @table plugin_wuma_source_blockchain
     */
    private function _create_plugin_wuma_source_blockchain()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wuma_source_blockchain', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '溯源-区块链',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['scid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '确权证书']],
            ['code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '流程编号']],
            ['hash', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '流程HASH']],
            ['name', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '流程名称']],
            ['data', 'text', ['default' => null, 'null' => true, 'comment' => '流程环节']],
            ['remark', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '流程备注']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效1有效)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删1已删)']],
            ['hash_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '上链时间']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'code', 'scid', 'sort', 'status', 'deleted',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSourceCertificate
     * @table plugin_wuma_source_certificate
     */
    private function _create_plugin_wuma_source_certificate()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wuma_source_certificate', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '溯源-确权证书',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '模板编号']],
            ['name', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '模板名称']],
            ['times', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '访问次数']],
            ['image', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '证书底图']],
            ['content', 'text', ['default' => null, 'null' => true, 'comment' => '定制规则']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效1有效)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删1已删)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'code', 'status', 'deleted',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSourceProduce
     * @table plugin_wuma_source_produce
     */
    private function _create_plugin_wuma_source_produce()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wuma_source_produce', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '溯源-生产批次',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['batch', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '生产批次']],
            ['ghash', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '产品编号']],
            ['tcode', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '关联溯源模板']],
            ['addr_prov', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '所在省份']],
            ['addr_city', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '所在城市']],
            ['addr_area', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '所在区域']],
            ['remark', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '批次备注']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效1有效)']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删1已删)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'batch', 'status', 'deleted',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSourceQuery
     * @table plugin_wuma_source_query
     */
    private function _create_plugin_wuma_source_query()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wuma_source_query', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '溯源-查询记录',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['auid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '代理用户']],
            ['code', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '小码数码']],
            ['ghash', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '商品哈希']],
            ['times', 'biginteger', ['limit' => 20, 'default' => 1, 'null' => true, 'comment' => '查询次数']],
            ['encode', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '物码编号']],
            ['prov', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '所在省份']],
            ['city', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '所在城市']],
            ['area', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '所在区域']],
            ['addr', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '详细地址']],
            ['geoip', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '访问IP']],
            ['gtype', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '定位类型']],
            ['latlng', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '经纬度']],
            ['notify', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '窜货状态']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'code', 'auid', 'prov', 'city', 'area', 'notify', 'encode',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSourceQueryNotify
     * @table plugin_wuma_source_query_notify
     */
    private function _create_plugin_wuma_source_query_notify()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wuma_source_query_notify', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '溯源-窜货异常',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['auid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '代理用户']],
            ['code', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '小码数码']],
            ['type', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '记录类型']],
            ['times', 'biginteger', ['limit' => 20, 'default' => 1, 'null' => true, 'comment' => '查询次数']],
            ['encode', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '物码编号']],
            ['prov', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '所在省份']],
            ['city', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '所在城市']],
            ['area', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '所在区域']],
            ['addr', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '详细地址']],
            ['gtype', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '定位类型']],
            ['geoip', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '访问IP']],
            ['latlng', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '经纬度']],
            ['pcode', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '商品编号']],
            ['pspec', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '商品规格']],
            ['agent_prov', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '代理省份']],
            ['agent_city', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '代理城市']],
            ['agent_area', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '代理区域']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'auid', 'prov', 'city', 'area', 'code', 'encode', 'agent_prov', 'agent_city', 'agent_area',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSourceQueryVerify
     * @table plugin_wuma_source_query_verify
     */
    private function _create_plugin_wuma_source_query_verify()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wuma_source_query_verify', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '溯源-查询记录',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['auid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '代理用户']],
            ['code', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '小码数码']],
            ['times', 'biginteger', ['limit' => 20, 'default' => 1, 'null' => true, 'comment' => '查询次数']],
            ['ghash', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '商品编号']],
            ['encode', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '物码编号']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'code', 'auid', 'encode',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWumaSourceTemplate
     * @table plugin_wuma_source_template
     */
    private function _create_plugin_wuma_source_template()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wuma_source_template', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '溯源-页面模板',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '模板编号']],
            ['name', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '模板名称']],
            ['times', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '访问次数']],
            ['styles', 'text', ['default' => null, 'null' => true, 'comment' => '主题样式']],
            ['content', 'text', ['default' => null, 'null' => true, 'comment' => '模板内容']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'code', 'status', 'deleted',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWumaWarehouse
     * @table plugin_wuma_warehouse
     */
    private function _create_plugin_wuma_warehouse()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wuma_warehouse', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-仓库',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '仓库编号']],
            ['name', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '仓库名称']],
            ['person', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '负责人']],
            ['remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '物码描述']],
            ['addr_prov', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '所属省份']],
            ['addr_city', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '所属城市']],
            ['addr_area', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '所属区域']],
            ['addr_text', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '详细地址']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效1有效)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删1已删)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'code', 'name', 'sort', 'status', 'deleted', 'create_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWumaWarehouseOrder
     * @table plugin_wuma_warehouse_order
     */
    private function _create_plugin_wuma_warehouse_order()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wuma_warehouse_order', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-仓库-订单',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '操作类型(1订单入库,2直接入库,3调货入库,4订单出库,5直接出库,6调货出库,7关联出库,8直接退货)']],
            ['mode', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '操作方式(1扫码操作,2虚拟操作)']],
            ['auid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '出库代理']],
            ['code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '操作单号']],
            ['wcode', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '仓库编号']],
            ['ghash', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '绑定产品']],
            ['vir_need', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '虚拟总数']],
            ['vir_used', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '虚拟完成']],
            ['num_need', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '扫码总数']],
            ['num_used', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '扫码完成']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效,2完成)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
            ['deleted_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '删除时间']],
        ], [
            'mode', 'auid', 'type', 'code', 'ghash', 'wcode', 'status', 'deleted', 'create_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWumaWarehouseOrderData
     * @table plugin_wuma_warehouse_order_data
     */
    private function _create_plugin_wuma_warehouse_order_data()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wuma_warehouse_order_data', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-仓库-数据',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '操作类型(1订单入库,2直接入库,3调货入库,4订单出库,5直接出库,6调货出库,7关联出库,8直接退货)']],
            ['mode', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '操作方式(1扫码操作,2虚拟操作)']],
            ['code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '操作单号']],
            ['number', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '标签总数']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
        ], [
            'mode', 'type', 'code', 'status',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWumaWarehouseOrderDataMins
     * @table plugin_wuma_warehouse_order_data_mins
     */
    private function _create_plugin_wuma_warehouse_order_data_mins()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wuma_warehouse_order_data_mins', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-仓库-小码',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '操作类型(1订单入库,2直接入库,3调货入库,4订单出库,5直接出库,6调货出库,7关联出库,8直接退货)']],
            ['mode', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '操作方式(1扫码操作,2虚拟操作)']],
            ['ddid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '数据编号']],
            ['code', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '物码数据']],
            ['stock', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '调货:库存有效(0已出,1暂存)']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '退货:记录状态(0无效,1有效)']],
            ['status_time', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '状态时间']],
        ], [
            'type', 'mode', 'ddid', 'code', 'stock', 'status',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWumaWarehouseOrderDataNums
     * @table plugin_wuma_warehouse_order_data_nums
     */
    private function _create_plugin_wuma_warehouse_order_data_nums()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wuma_warehouse_order_data_nums', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-仓库-箱码',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['ddid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '数据编号']],
            ['type', 'string', ['limit' => 40, 'default' => '', 'null' => true, 'comment' => '物码类型']],
            ['code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '物码数据']],
            ['count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '物码数量']],
        ], [
            'ddid', 'code', 'type',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWumaWarehouseRelation
     * @table plugin_wuma_warehouse_relation
     */
    private function _create_plugin_wuma_warehouse_relation()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wuma_warehouse_relation', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-仓库-关联',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['max', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '大码数值']],
            ['mid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '中码数值']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)']],
            ['create_by', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上传用户']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
        ], [
            'max', 'mid', 'deleted',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWumaWarehouseRelationData
     * @table plugin_wuma_warehouse_relation_data
     */
    private function _create_plugin_wuma_warehouse_relation_data()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wuma_warehouse_relation_data', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-仓库-关联',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['rid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '批次数据']],
            ['max', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '大码数值']],
            ['mid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '中码数值']],
            ['min', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '小码数值']],
            ['number', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '防窜编码']],
            ['encode', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '防伪编码']],
            ['lock', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '锁定状态']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)']],
            ['create_by', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上传用户']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
        ], [
            'rid', 'max', 'mid', 'min', 'lock', 'status', 'encode', 'number', 'deleted',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWumaWarehouseReplace
     * @table plugin_wuma_warehouse_replace
     */
    private function _create_plugin_wuma_warehouse_replace()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wuma_warehouse_replace', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-仓库-替换',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['type', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '物码类型']],
            ['smin', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '原值小码']],
            ['tmin', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '目标小码']],
            ['source', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '原物码值']],
            ['target', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '目标物码']],
            ['lock', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '锁定状态']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)']],
            ['create_by', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上传用户']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
        ], [
            'smin', 'tmin', 'lock', 'status', 'target', 'source', 'deleted',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWumaWarehouseStock
     * @table plugin_wuma_warehouse_stock
     */
    private function _create_plugin_wuma_warehouse_stock()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wuma_warehouse_stock', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-仓库-库存',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['wcode', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '仓库编号']],
            ['ghash', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '商品规格']],
            ['vir_total', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '虚拟总数']],
            ['vir_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '虚拟完成']],
            ['num_total', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '扫码总数']],
            ['num_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '扫码完成']],
        ], [
            'wcode', 'ghash',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWumaWarehouseUser
     * @table plugin_wuma_warehouse_user
     */
    private function _create_plugin_wuma_warehouse_user()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wuma_warehouse_user', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物码-仓库-用户',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['token', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '接口令牌']],
            ['username', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '用户账号']],
            ['nickname', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '用户昵称']],
            ['password', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '登录密码']],
            ['login_ip', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '登录地址']],
            ['login_time', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '登录时间']],
            ['login_num', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '登录测试']],
            ['login_vars', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '登录参数']],
            ['remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '物码描述']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'sort', 'token', 'status', 'deleted', 'username', 'password',
        ], true);
    }
}
