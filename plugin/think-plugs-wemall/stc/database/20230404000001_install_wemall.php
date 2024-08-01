<?php

// +----------------------------------------------------------------------
// | WeMall Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wemall
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wemall
// +----------------------------------------------------------------------

use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', -1);

/**
 * 用户数据
 */
class InstallWemall extends Migrator
{

    /**
     * 创建数据库
     */
    public function change()
    {
        $this->_create_plugin_wemall_config_agent();
        $this->_create_plugin_wemall_config_coupon();
        $this->_create_plugin_wemall_config_discount();
        $this->_create_plugin_wemall_config_level();
        $this->_create_plugin_wemall_config_notify();
        $this->_create_plugin_wemall_config_poster();
        $this->_create_plugin_wemall_config_rebate();
        $this->_create_plugin_wemall_express_company();
        $this->_create_plugin_wemall_express_template();
        $this->_create_plugin_wemall_goods();
        $this->_create_plugin_wemall_goods_cate();
        $this->_create_plugin_wemall_goods_item();
        $this->_create_plugin_wemall_goods_mark();
        $this->_create_plugin_wemall_goods_stock();
        $this->_create_plugin_wemall_help_feedback();
        $this->_create_plugin_wemall_help_problem();
        $this->_create_plugin_wemall_help_question();
        $this->_create_plugin_wemall_help_question_x();
        $this->_create_plugin_wemall_order();
        $this->_create_plugin_wemall_order_cart();
        $this->_create_plugin_wemall_order_item();
        $this->_create_plugin_wemall_order_refund();
        $this->_create_plugin_wemall_order_sender();
        $this->_create_plugin_wemall_user_action_collect();
        $this->_create_plugin_wemall_user_action_comment();
        $this->_create_plugin_wemall_user_action_history();
        $this->_create_plugin_wemall_user_action_search();
        $this->_create_plugin_wemall_user_checkin();
        $this->_create_plugin_wemall_user_coupon();
        $this->_create_plugin_wemall_user_create();
        $this->_create_plugin_wemall_user_rebate();
        $this->_create_plugin_wemall_user_recharge();
        $this->_create_plugin_wemall_user_relation();
        $this->_create_plugin_wemall_user_transfer();
    }

    /**
     * 创建数据对象
     * @class PluginWemallConfigAgent
     * @table plugin_wemall_config_agent
     * @return void
     */
    private function _create_plugin_wemall_config_agent()
    {

        // 当前数据表
        $table = 'plugin_wemall_config_agent';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-配置-等级',
        ])
            ->addColumn('name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '级别名称'])
            ->addColumn('cover', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '等级图标'])
            ->addColumn('cardbg', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '等级卡片'])
            ->addColumn('number', 'integer', ['limit' => 2, 'default' => 0, 'null' => true, 'comment' => '级别序号'])
            ->addColumn('upgrade_type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '升级规则(0单个,1同时)'])
            ->addColumn('extra', 'text', ['default' => NULL, 'null' => true, 'comment' => '升级规则'])
            ->addColumn('remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '级别描述'])
            ->addColumn('utime', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '更新时间'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '等级状态(1使用,0禁用)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('utime', ['name' => 'i6a80c4b9e_utime'])
            ->addIndex('status', ['name' => 'i6a80c4b9e_status'])
            ->addIndex('number', ['name' => 'i6a80c4b9e_number'])
            ->addIndex('create_time', ['name' => 'i6a80c4b9e_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallConfigCoupon
     * @table plugin_wemall_config_coupon
     * @return void
     */
    private function _create_plugin_wemall_config_coupon()
    {

        // 当前数据表
        $table = 'plugin_wemall_config_coupon';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-配置-卡券',
        ])
            ->addColumn('type', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '类型(0通用券,1商品券)'])
            ->addColumn('name', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '优惠名称'])
            ->addColumn('cover', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '封面图标'])
            ->addColumn('extra', 'text', ['default' => NULL, 'null' => true, 'comment' => '扩展数据'])
            ->addColumn('content', 'text', ['default' => NULL, 'null' => true, 'comment' => '内容描述'])
            ->addColumn('remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '系统备注'])
            ->addColumn('amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '抵扣金额'])
            ->addColumn('limit_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '金额门槛(0不限制)'])
            ->addColumn('limit_levels', 'string', ['limit' => 180, 'default' => '-', 'null' => true, 'comment' => '授权等级'])
            ->addColumn('limit_times', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '限领数量(0不限制)'])
            ->addColumn('expire_days', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '有效天数'])
            ->addColumn('total_stock', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '库存数量'])
            ->addColumn('total_sales', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '发放数量'])
            ->addColumn('total_used', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '使用数量'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '卡券状态(0禁用,1使用)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(1已删,0未删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('sort', ['name' => 'ibfe2b6128_sort'])
            ->addIndex('type', ['name' => 'ibfe2b6128_type'])
            ->addIndex('status', ['name' => 'ibfe2b6128_status'])
            ->addIndex('deleted', ['name' => 'ibfe2b6128_deleted'])
            ->addIndex('create_time', ['name' => 'ibfe2b6128_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallConfigDiscount
     * @table plugin_wemall_config_discount
     * @return void
     */
    private function _create_plugin_wemall_config_discount()
    {

        // 当前数据表
        $table = 'plugin_wemall_config_discount';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-配置-折扣',
        ])
            ->addColumn('name', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '方案名称'])
            ->addColumn('remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '方案描述'])
            ->addColumn('items', 'text', ['default' => NULL, 'null' => true, 'comment' => '方案规则'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '方案状态(0禁用,1使用)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(1已删,0未删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('sort', ['name' => 'i8d0e0158e_sort'])
            ->addIndex('status', ['name' => 'i8d0e0158e_status'])
            ->addIndex('deleted', ['name' => 'i8d0e0158e_deleted'])
            ->addIndex('create_time', ['name' => 'i8d0e0158e_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallConfigLevel
     * @table plugin_wemall_config_level
     * @return void
     */
    private function _create_plugin_wemall_config_level()
    {

        // 当前数据表
        $table = 'plugin_wemall_config_level';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-配置-等级',
        ])
            ->addColumn('name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '级别名称'])
            ->addColumn('cover', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '等级图标'])
            ->addColumn('cardbg', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '等级卡片'])
            ->addColumn('number', 'integer', ['limit' => 2, 'default' => 0, 'null' => true, 'comment' => '级别序号'])
            ->addColumn('upgrade_type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '升级规则(0单个,1同时)'])
            ->addColumn('upgrade_team', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '团队人数统计(0不计,1累计)'])
            ->addColumn('remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '用户级别描述'])
            ->addColumn('extra', 'text', ['default' => NULL, 'null' => true, 'comment' => '配置规则'])
            ->addColumn('utime', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '更新时间'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '等级状态(1使用,0禁用)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('utime', ['name' => 'if851bb0b1_utime'])
            ->addIndex('status', ['name' => 'if851bb0b1_status'])
            ->addIndex('number', ['name' => 'if851bb0b1_number'])
            ->addIndex('create_time', ['name' => 'if851bb0b1_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallConfigNotify
     * @table plugin_wemall_config_notify
     * @return void
     */
    private function _create_plugin_wemall_config_notify()
    {

        // 当前数据表
        $table = 'plugin_wemall_config_notify';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-配置-通知',
        ])
            ->addColumn('code', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '通知编号'])
            ->addColumn('name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '通知标题'])
            ->addColumn('cover', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '通知图片'])
            ->addColumn('levels', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '用户等级'])
            ->addColumn('content', 'text', ['default' => NULL, 'null' => true, 'comment' => '通知内容'])
            ->addColumn('remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '通知描述'])
            ->addColumn('num_read', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '阅读次数'])
            ->addColumn('tips', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => 'TIPS显示'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '激活状态(0无效,1有效)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(1已删,0未删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('code', ['name' => 'i0614c3468_code'])
            ->addIndex('sort', ['name' => 'i0614c3468_sort'])
            ->addIndex('name', ['name' => 'i0614c3468_name'])
            ->addIndex('tips', ['name' => 'i0614c3468_tips'])
            ->addIndex('status', ['name' => 'i0614c3468_status'])
            ->addIndex('deleted', ['name' => 'i0614c3468_deleted'])
            ->addIndex('create_time', ['name' => 'i0614c3468_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallConfigPoster
     * @table plugin_wemall_config_poster
     * @return void
     */
    private function _create_plugin_wemall_config_poster()
    {

        // 当前数据表
        $table = 'plugin_wemall_config_poster';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-配置-海报',
        ])
            ->addColumn('code', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '推广编号'])
            ->addColumn('name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '推广标题'])
            ->addColumn('levels', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '用户等级'])
            ->addColumn('devices', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '接口通道'])
            ->addColumn('image', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '推广图片'])
            ->addColumn('content', 'text', ['default' => NULL, 'null' => true, 'comment' => '二维位置'])
            ->addColumn('remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '推广描述'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '激活状态(0无效,1有效)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(1已删,0未删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('code', ['name' => 'ib84148924_code'])
            ->addIndex('sort', ['name' => 'ib84148924_sort'])
            ->addIndex('name', ['name' => 'ib84148924_name'])
            ->addIndex('status', ['name' => 'ib84148924_status'])
            ->addIndex('deleted', ['name' => 'ib84148924_deleted'])
            ->addIndex('create_time', ['name' => 'ib84148924_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallConfigRebate
     * @table plugin_wemall_config_rebate
     * @return void
     */
    private function _create_plugin_wemall_config_rebate()
    {

        // 当前数据表
        $table = 'plugin_wemall_config_rebate';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-配置-返利',
        ])
            ->addColumn('type', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '奖励类型'])
            ->addColumn('code', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '配置编号'])
            ->addColumn('name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '配置名称'])
            ->addColumn('path', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '等级关系'])
            ->addColumn('stype', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '结算类型(0支付结算,1收货结算)'])
            ->addColumn('p0_level', 'biginteger', ['limit' => 20, 'default' => -1, 'null' => true, 'comment' => '会员等级'])
            ->addColumn('p1_level', 'biginteger', ['limit' => 20, 'default' => -1, 'null' => true, 'comment' => '上1级等级'])
            ->addColumn('p2_level', 'biginteger', ['limit' => 20, 'default' => -1, 'null' => true, 'comment' => '上2级等级'])
            ->addColumn('p3_level', 'biginteger', ['limit' => 20, 'default' => -1, 'null' => true, 'comment' => '上3级等级'])
            ->addColumn('p0_reward_type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '会员计算类型(0固定金额,1交易比例,2利润比例)'])
            ->addColumn('p0_reward_number', 'decimal', ['precision' => 20, 'scale' => 6, 'default' => '0.000000', 'null' => true, 'comment' => '会员计算系数'])
            ->addColumn('p1_reward_type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '上1级计算类型(0固定金额,1交易比例,2利润比例)'])
            ->addColumn('p1_reward_number', 'decimal', ['precision' => 20, 'scale' => 6, 'default' => '0.000000', 'null' => true, 'comment' => '上1级计算系数'])
            ->addColumn('p2_reward_type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '上2级计算类型(0固定金额,1交易比例,2利润比例)'])
            ->addColumn('p2_reward_number', 'decimal', ['precision' => 20, 'scale' => 6, 'default' => '0.000000', 'null' => true, 'comment' => '上2级计算系数'])
            ->addColumn('p3_reward_type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '上3级计算类型(0固定金额,1交易比例,2利润比例)'])
            ->addColumn('p3_reward_number', 'decimal', ['precision' => 20, 'scale' => 6, 'default' => '0.000000', 'null' => true, 'comment' => '上3级计算系数'])
            ->addColumn('remark', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '配置描述'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '激活状态(0无效,1有效)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(1已删,0未删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('code', ['name' => 'i3a0d023e7_code'])
            ->addIndex('sort', ['name' => 'i3a0d023e7_sort'])
            ->addIndex('name', ['name' => 'i3a0d023e7_name'])
            ->addIndex('type', ['name' => 'i3a0d023e7_type'])
            ->addIndex('stype', ['name' => 'i3a0d023e7_stype'])
            ->addIndex('status', ['name' => 'i3a0d023e7_status'])
            ->addIndex('deleted', ['name' => 'i3a0d023e7_deleted'])
            ->addIndex('p1_level', ['name' => 'i3a0d023e7_p1_level'])
            ->addIndex('p2_level', ['name' => 'i3a0d023e7_p2_level'])
            ->addIndex('p3_level', ['name' => 'i3a0d023e7_p3_level'])
            ->addIndex('p0_level', ['name' => 'i3a0d023e7_p0_level'])
            ->addIndex('create_time', ['name' => 'i3a0d023e7_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallExpressCompany
     * @table plugin_wemall_express_company
     * @return void
     */
    private function _create_plugin_wemall_express_company()
    {

        // 当前数据表
        $table = 'plugin_wemall_express_company';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '数据-快递-公司',
        ])
            ->addColumn('code', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '公司代码'])
            ->addColumn('name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '公司名称'])
            ->addColumn('remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '公司描述'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '激活状态(0无效,1有效)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(1已删,0未删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('code', ['name' => 'ia20ef923b_code'])
            ->addIndex('sort', ['name' => 'ia20ef923b_sort'])
            ->addIndex('name', ['name' => 'ia20ef923b_name'])
            ->addIndex('status', ['name' => 'ia20ef923b_status'])
            ->addIndex('deleted', ['name' => 'ia20ef923b_deleted'])
            ->addIndex('create_time', ['name' => 'ia20ef923b_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallExpressTemplate
     * @table plugin_wemall_express_template
     * @return void
     */
    private function _create_plugin_wemall_express_template()
    {

        // 当前数据表
        $table = 'plugin_wemall_express_template';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '数据-快递-模板',
        ])
            ->addColumn('code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '模板编号'])
            ->addColumn('name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '模板名称'])
            ->addColumn('normal', 'text', ['default' => NULL, 'null' => true, 'comment' => '默认规则'])
            ->addColumn('content', 'text', ['default' => NULL, 'null' => true, 'comment' => '模板规则'])
            ->addColumn('company', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '快递公司'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '激活状态(0无效,1有效)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(1已删,0未删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('code', ['name' => 'i415af662e_code'])
            ->addIndex('sort', ['name' => 'i415af662e_sort'])
            ->addIndex('name', ['name' => 'i415af662e_name'])
            ->addIndex('status', ['name' => 'i415af662e_status'])
            ->addIndex('deleted', ['name' => 'i415af662e_deleted'])
            ->addIndex('create_time', ['name' => 'i415af662e_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallGoods
     * @table plugin_wemall_goods
     * @return void
     */
    private function _create_plugin_wemall_goods()
    {

        // 当前数据表
        $table = 'plugin_wemall_goods';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-商品-内容',
        ])
            ->addColumn('ssid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '所属商家'])
            ->addColumn('code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '商品编号'])
            ->addColumn('name', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '商品名称'])
            ->addColumn('marks', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '商品标签'])
            ->addColumn('cates', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '分类编号'])
            ->addColumn('cover', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '商品封面'])
            ->addColumn('slider', 'text', ['default' => NULL, 'null' => true, 'comment' => '轮播图片'])
            ->addColumn('specs', 'text', ['default' => NULL, 'null' => true, 'comment' => '商品规格(JSON)'])
            ->addColumn('content', 'text', ['default' => NULL, 'null' => true, 'comment' => '商品详情'])
            ->addColumn('remark', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '商品描述'])
            ->addColumn('stock_total', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '商品库存统计'])
            ->addColumn('stock_sales', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '商品销售统计'])
            ->addColumn('stock_virtual', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '商品虚拟销量'])
            ->addColumn('price_selling', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '最低销售价格'])
            ->addColumn('price_market', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '最低市场价格'])
            ->addColumn('allow_integral', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '最大积分兑换'])
            ->addColumn('allow_balance', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '最大余额支付'])
            ->addColumn('rebate_type', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '参与返利(0无需返利,1需要返利)'])
            ->addColumn('delivery_code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '物流运费模板'])
            ->addColumn('limit_lowvip', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '限制购买等级(0不限制,其他限制)'])
            ->addColumn('limit_maxnum', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '最大购买数量(0不限制,其他限制)'])
            ->addColumn('level_agent', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '推广权益(0无,1有)'])
            ->addColumn('level_upgrade', 'biginteger', ['limit' => 20, 'default' => -1, 'null' => true, 'comment' => '购买升级等级(-1非入会,0不升级,其他升级)'])
            ->addColumn('discount_id', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '折扣方案编号(0无折扣,其他折扣)'])
            ->addColumn('num_read', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '访问阅读统计'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '列表排序权重'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '商品状态(1使用,0禁用)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('code', ['name' => 'i175165940_code'])
            ->addIndex('sort', ['name' => 'i175165940_sort'])
            ->addIndex('ssid', ['name' => 'i175165940_ssid'])
            ->addIndex('status', ['name' => 'i175165940_status'])
            ->addIndex('deleted', ['name' => 'i175165940_deleted'])
            ->addIndex('rebate_type', ['name' => 'i175165940_rebate_type'])
            ->addIndex('discount_id', ['name' => 'i175165940_discount_id'])
            ->addIndex('create_time', ['name' => 'i175165940_create_time'])
            ->addIndex('level_agent', ['name' => 'i175165940_level_agent'])
            ->addIndex('level_upgrade', ['name' => 'i175165940_level_upgrade'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallGoodsCate
     * @table plugin_wemall_goods_cate
     * @return void
     */
    private function _create_plugin_wemall_goods_cate()
    {

        // 当前数据表
        $table = 'plugin_wemall_goods_cate';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-商品-分类',
        ])
            ->addColumn('pid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上级分类'])
            ->addColumn('name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '分类名称'])
            ->addColumn('cover', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '分类图标'])
            ->addColumn('remark', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '分类描述'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '使用状态'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('pid', ['name' => 'i4e6868d55_pid'])
            ->addIndex('sort', ['name' => 'i4e6868d55_sort'])
            ->addIndex('status', ['name' => 'i4e6868d55_status'])
            ->addIndex('deleted', ['name' => 'i4e6868d55_deleted'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallGoodsItem
     * @table plugin_wemall_goods_item
     * @return void
     */
    private function _create_plugin_wemall_goods_item()
    {

        // 当前数据表
        $table = 'plugin_wemall_goods_item';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-商品-规格',
        ])
            ->addColumn('gsku', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '商品SKU'])
            ->addColumn('ghash', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '商品哈希'])
            ->addColumn('gcode', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '商品编号'])
            ->addColumn('gspec', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '商品规格'])
            ->addColumn('gunit', 'string', ['limit' => 10, 'default' => '件', 'null' => true, 'comment' => '商品单位'])
            ->addColumn('gimage', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '商品图片'])
            ->addColumn('stock_sales', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '销售数量'])
            ->addColumn('stock_total', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '商品库存'])
            ->addColumn('price_cost', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '进货成本'])
            ->addColumn('price_selling', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '销售价格'])
            ->addColumn('price_market', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '市场价格'])
            ->addColumn('allow_integral', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '兑换积分'])
            ->addColumn('allow_balance', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '余额支付'])
            ->addColumn('reward_balance', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '奖励余额'])
            ->addColumn('reward_integral', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '奖励积分'])
            ->addColumn('number_virtual', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '虚拟销量'])
            ->addColumn('number_express', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '计件系数'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '商品状态'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('gcode', ['name' => 'i65942f6de_gcode'])
            ->addIndex('gspec', ['name' => 'i65942f6de_gspec'])
            ->addIndex('ghash', ['name' => 'i65942f6de_ghash'])
            ->addIndex('status', ['name' => 'i65942f6de_status'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallGoodsMark
     * @table plugin_wemall_goods_mark
     * @return void
     */
    private function _create_plugin_wemall_goods_mark()
    {

        // 当前数据表
        $table = 'plugin_wemall_goods_mark';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-商品-标签',
        ])
            ->addColumn('name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '标签名称'])
            ->addColumn('remark', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '标签描述'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '标签状态(1使用,0禁用)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('sort', ['name' => 'i4dc56e1a2_sort'])
            ->addIndex('status', ['name' => 'i4dc56e1a2_status'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallGoodsStock
     * @table plugin_wemall_goods_stock
     * @return void
     */
    private function _create_plugin_wemall_goods_stock()
    {

        // 当前数据表
        $table = 'plugin_wemall_goods_stock';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-商品-库存',
        ])
            ->addColumn('batch_no', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '操作批量'])
            ->addColumn('ghash', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '商品哈希'])
            ->addColumn('gcode', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '商品编号'])
            ->addColumn('gspec', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '商品规格'])
            ->addColumn('gstock', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '入库数量'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '数据状态(1使用,0禁用)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('ghash', ['name' => 'ie71188985_ghash'])
            ->addIndex('gcode', ['name' => 'ie71188985_gcode'])
            ->addIndex('status', ['name' => 'ie71188985_status'])
            ->addIndex('deleted', ['name' => 'ie71188985_deleted'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallHelpFeedback
     * @table plugin_wemall_help_feedback
     * @return void
     */
    private function _create_plugin_wemall_help_feedback()
    {

        // 当前数据表
        $table = 'plugin_wemall_help_feedback';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '数据-意见-反馈',
        ])
            ->addColumn('unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '反馈用户'])
            ->addColumn('phone', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '联系电话'])
            ->addColumn('images', 'text', ['default' => NULL, 'null' => true, 'comment' => '反馈图片'])
            ->addColumn('content', 'text', ['default' => NULL, 'null' => true, 'comment' => '反馈内容'])
            ->addColumn('reply', 'text', ['default' => NULL, 'null' => true, 'comment' => '回复内容'])
            ->addColumn('reply_st', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '回复状态'])
            ->addColumn('reply_by', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '回复用户'])
            ->addColumn('reply_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '回复时间'])
            ->addColumn('sync', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '同步至常见问题状态(1已同步,0未同步)'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '展示状态(1使用,0禁用)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('sort', ['name' => 'i7fa1b82bf_sort'])
            ->addIndex('unid', ['name' => 'i7fa1b82bf_unid'])
            ->addIndex('status', ['name' => 'i7fa1b82bf_status'])
            ->addIndex('deleted', ['name' => 'i7fa1b82bf_deleted'])
            ->addIndex('reply_st', ['name' => 'i7fa1b82bf_reply_st'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallHelpProblem
     * @table plugin_wemall_help_problem
     * @return void
     */
    private function _create_plugin_wemall_help_problem()
    {

        // 当前数据表
        $table = 'plugin_wemall_help_problem';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '数据-常见-问题',
        ])
            ->addColumn('fid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => false, 'comment' => '来自反馈'])
            ->addColumn('name', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '问题标题'])
            ->addColumn('content', 'text', ['default' => NULL, 'null' => true, 'comment' => '问题内容'])
            ->addColumn('num_er', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '未解决数'])
            ->addColumn('num_ok', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '已解决数'])
            ->addColumn('num_read', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '阅读次数'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '展示状态(1使用,0禁用)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('sort', ['name' => 'i2a4212540_sort'])
            ->addIndex('status', ['name' => 'i2a4212540_status'])
            ->addIndex('deleted', ['name' => 'i2a4212540_deleted'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallHelpQuestion
     * @table plugin_wemall_help_question
     * @return void
     */
    private function _create_plugin_wemall_help_question()
    {

        // 当前数据表
        $table = 'plugin_wemall_help_question';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '数据-问答-内容',
        ])
            ->addColumn('unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '提问用户'])
            ->addColumn('name', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '工单标题'])
            ->addColumn('phone', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '联系电话'])
            ->addColumn('order_no', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '关联订单'])
            ->addColumn('images', 'text', ['default' => NULL, 'null' => true, 'comment' => '工单图片'])
            ->addColumn('content', 'text', ['default' => NULL, 'null' => true, 'comment' => '工单描述'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '工单状态(0取消,1新工单,2后台回复,3前台回复,4已完结)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('sort', ['name' => 'i9383b2cca_sort'])
            ->addIndex('name', ['name' => 'i9383b2cca_name'])
            ->addIndex('unid', ['name' => 'i9383b2cca_unid'])
            ->addIndex('phone', ['name' => 'i9383b2cca_phone'])
            ->addIndex('status', ['name' => 'i9383b2cca_status'])
            ->addIndex('deleted', ['name' => 'i9383b2cca_deleted'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallHelpQuestionX
     * @table plugin_wemall_help_question_x
     * @return void
     */
    private function _create_plugin_wemall_help_question_x()
    {

        // 当前数据表
        $table = 'plugin_wemall_help_question_x';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '数据-问答-评论',
        ])
            ->addColumn('ccid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '目标编号'])
            ->addColumn('unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户编号'])
            ->addColumn('content', 'text', ['default' => NULL, 'null' => true, 'comment' => '文本内容'])
            ->addColumn('images', 'text', ['default' => NULL, 'null' => true, 'comment' => '图片内容'])
            ->addColumn('reply_by', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '后台用户'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1待审核,2已审核)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('ccid', ['name' => 'i9180fa26f_ccid'])
            ->addIndex('unid', ['name' => 'i9180fa26f_unid'])
            ->addIndex('status', ['name' => 'i9180fa26f_status'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallOrder
     * @table plugin_wemall_order
     * @return void
     */
    private function _create_plugin_wemall_order()
    {

        // 当前数据表
        $table = 'plugin_wemall_order';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-订单-内容',
        ])
            ->addColumn('ssid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '所属商家'])
            ->addColumn('unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户编号'])
            ->addColumn('puid1', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上1级代理'])
            ->addColumn('puid2', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上2级代理'])
            ->addColumn('puid3', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上3级代理'])
            ->addColumn('order_no', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '订单单号'])
            ->addColumn('order_ps', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '订单备注'])
            ->addColumn('amount_cost', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '商品成本'])
            ->addColumn('amount_real', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '实际金额'])
            ->addColumn('amount_total', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '订单金额'])
            ->addColumn('amount_goods', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '商品金额'])
            ->addColumn('amount_profit', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '销售利润'])
            ->addColumn('amount_reduct', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '随机减免'])
            ->addColumn('amount_balance', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '余额支付'])
            ->addColumn('amount_integral', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '积分抵扣'])
            ->addColumn('amount_payment', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '金额支付'])
            ->addColumn('amount_express', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '快递费用'])
            ->addColumn('amount_discount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '折扣后金额'])
            ->addColumn('coupon_code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '优惠券编号'])
            ->addColumn('coupon_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '优惠券金额'])
            ->addColumn('allow_balance', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '最大余额支付'])
            ->addColumn('allow_integral', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '最大积分抵扣'])
            ->addColumn('ratio_integral', 'decimal', ['precision' => 20, 'scale' => 6, 'default' => '0.000000', 'null' => true, 'comment' => '积分兑换比例'])
            ->addColumn('number_goods', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '商品数量'])
            ->addColumn('number_express', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '快递计数'])
            ->addColumn('level_agent', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '升级代理等级'])
            ->addColumn('level_member', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '升级会员等级'])
            ->addColumn('rebate_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '返利金额'])
            ->addColumn('reward_balance', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '奖励余额'])
            ->addColumn('reward_integral', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '奖励积分'])
            ->addColumn('payment_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '支付时间'])
            ->addColumn('payment_status', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '支付状态(0未支付,1有支付)'])
            ->addColumn('payment_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '实际支付'])
            ->addColumn('delivery_type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '物流类型(0无配送,1需配送)'])
            ->addColumn('cancel_time', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '取消时间'])
            ->addColumn('cancel_status', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '取消状态'])
            ->addColumn('cancel_remark', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '取消描述'])
            ->addColumn('deleted_time', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '删除时间'])
            ->addColumn('deleted_status', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
            ->addColumn('deleted_remark', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '删除描述'])
            ->addColumn('confirm_time', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '签收时间'])
            ->addColumn('confirm_remark', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '签收描述'])
            ->addColumn('refund_code', 'string', ['limit' => 20, 'default' => NULL, 'null' => true, 'comment' => '售后单号'])
            ->addColumn('refund_status', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '售后状态(0未售后,1预订单,2待审核,3待退货,4已退货,5待退款,6已退款,7已完成)'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '流程状态(0已取消,1预订单,2待支付,3待审核,4待发货,5已发货,6已收货,7已评论)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('unid', ['name' => 'i4914b9e88_unid'])
            ->addIndex('ssid', ['name' => 'i4914b9e88_ssid'])
            ->addIndex('puid1', ['name' => 'i4914b9e88_puid1'])
            ->addIndex('puid2', ['name' => 'i4914b9e88_puid2'])
            ->addIndex('puid3', ['name' => 'i4914b9e88_puid3'])
            ->addIndex('status', ['name' => 'i4914b9e88_status'])
            ->addIndex('order_no', ['name' => 'i4914b9e88_order_no'])
            ->addIndex('create_time', ['name' => 'i4914b9e88_create_time'])
            ->addIndex('refund_code', ['name' => 'i4914b9e88_refund_code'])
            ->addIndex('coupon_code', ['name' => 'i4914b9e88_coupon_code'])
            ->addIndex('delivery_type', ['name' => 'i4914b9e88_delivery_type'])
            ->addIndex('cancel_status', ['name' => 'i4914b9e88_cancel_status'])
            ->addIndex('refund_status', ['name' => 'i4914b9e88_refund_status'])
            ->addIndex('deleted_status', ['name' => 'i4914b9e88_deleted_status'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallOrderCart
     * @table plugin_wemall_order_cart
     * @return void
     */
    private function _create_plugin_wemall_order_cart()
    {

        // 当前数据表
        $table = 'plugin_wemall_order_cart';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-订单-购物车',
        ])
            ->addColumn('ssid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '所属商家'])
            ->addColumn('unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户编号'])
            ->addColumn('ghash', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '规格哈希'])
            ->addColumn('gcode', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '商品编号'])
            ->addColumn('gspec', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '商品规格'])
            ->addColumn('number', 'biginteger', ['limit' => 20, 'default' => 1, 'null' => true, 'comment' => '商品数量'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('unid', ['name' => 'i75d9c8693_unid'])
            ->addIndex('ssid', ['name' => 'i75d9c8693_ssid'])
            ->addIndex('gcode', ['name' => 'i75d9c8693_gcode'])
            ->addIndex('gspec', ['name' => 'i75d9c8693_gspec'])
            ->addIndex('ghash', ['name' => 'i75d9c8693_ghash'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallOrderItem
     * @table plugin_wemall_order_item
     * @return void
     */
    private function _create_plugin_wemall_order_item()
    {

        // 当前数据表
        $table = 'plugin_wemall_order_item';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-订单-商品',
        ])
            ->addColumn('ssid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '所属商家'])
            ->addColumn('unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户编号'])
            ->addColumn('gsku', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '商品SKU'])
            ->addColumn('ghash', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '商品哈希'])
            ->addColumn('gcode', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '商品编号'])
            ->addColumn('gspec', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '商品规格'])
            ->addColumn('gunit', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '商品单凭'])
            ->addColumn('gname', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '商品名称'])
            ->addColumn('gcover', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '商品封面'])
            ->addColumn('order_no', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '订单单号'])
            ->addColumn('stock_sales', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '包含商品数量'])
            ->addColumn('amount_cost', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '商品成本单价'])
            ->addColumn('price_market', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '商品市场单价'])
            ->addColumn('price_selling', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '商品销售单价'])
            ->addColumn('total_price_cost', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '商品成本总价'])
            ->addColumn('total_price_market', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '商品市场总价'])
            ->addColumn('total_price_selling', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '商品销售总价'])
            ->addColumn('total_allow_balance', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '最大余额支付'])
            ->addColumn('total_allow_integral', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '最大兑换总分'])
            ->addColumn('total_reward_balance', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '商品奖励余额'])
            ->addColumn('total_reward_integral', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '商品奖励积分'])
            ->addColumn('level_code', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户等级序号'])
            ->addColumn('level_name', 'string', ['limit' => 30, 'default' => '', 'null' => true, 'comment' => '用户等级名称'])
            ->addColumn('level_agent', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '推广权益(0无,1有)'])
            ->addColumn('level_upgrade', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '购买升级等级(-1非入会,0不升级,其他升级)'])
            ->addColumn('rebate_type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '参与返利状态(0不返,1返利)'])
            ->addColumn('rebate_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '参与返利金额'])
            ->addColumn('delivery_code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '快递邮费模板'])
            ->addColumn('delivery_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '快递计费基数'])
            ->addColumn('discount_id', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '优惠方案编号'])
            ->addColumn('discount_rate', 'decimal', ['precision' => 20, 'scale' => 6, 'default' => '100.000000', 'null' => true, 'comment' => '销售价格折扣'])
            ->addColumn('discount_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '商品优惠金额'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '商品状态(1使用,0禁用)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('unid', ['name' => 'i16a8905b9_unid'])
            ->addIndex('gsku', ['name' => 'i16a8905b9_gsku'])
            ->addIndex('ssid', ['name' => 'i16a8905b9_ssid'])
            ->addIndex('gcode', ['name' => 'i16a8905b9_gcode'])
            ->addIndex('gspec', ['name' => 'i16a8905b9_gspec'])
            ->addIndex('ghash', ['name' => 'i16a8905b9_ghash'])
            ->addIndex('status', ['name' => 'i16a8905b9_status'])
            ->addIndex('deleted', ['name' => 'i16a8905b9_deleted'])
            ->addIndex('order_no', ['name' => 'i16a8905b9_order_no'])
            ->addIndex('rebate_type', ['name' => 'i16a8905b9_rebate_type'])
            ->addIndex('discount_id', ['name' => 'i16a8905b9_discount_id'])
            ->addIndex('level_agent', ['name' => 'i16a8905b9_level_agent'])
            ->addIndex('delivery_code', ['name' => 'i16a8905b9_delivery_code'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallOrderRefund
     * @table plugin_wemall_order_refund
     * @return void
     */
    private function _create_plugin_wemall_order_refund()
    {

        // 当前数据表
        $table = 'plugin_wemall_order_refund';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-订单-售后',
        ])
            ->addColumn('ssid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '所属商家'])
            ->addColumn('unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户编号'])
            ->addColumn('type', 'biginteger', ['limit' => 20, 'default' => 1, 'null' => true, 'comment' => '申请类型(1退货退款,2仅退款)'])
            ->addColumn('code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '售后单号'])
            ->addColumn('order_no', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '订单单号'])
            ->addColumn('reason', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '退款原因'])
            ->addColumn('number', 'biginteger', ['limit' => 20, 'default' => 1, 'null' => true, 'comment' => '退货数量'])
            ->addColumn('amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '申请金额'])
            ->addColumn('payment_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '退款支付'])
            ->addColumn('balance_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '退款余额'])
            ->addColumn('integral_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '退款积分'])
            ->addColumn('payment_code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '退款单号'])
            ->addColumn('balance_code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '退回单号'])
            ->addColumn('integral_code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '退回单号'])
            ->addColumn('phone', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '联系电话'])
            ->addColumn('images', 'text', ['default' => NULL, 'null' => true, 'comment' => '申请图片'])
            ->addColumn('content', 'text', ['default' => NULL, 'null' => true, 'comment' => '申请说明'])
            ->addColumn('remark', 'string', ['limit' => 180, 'default' => NULL, 'null' => true, 'comment' => '操作描述'])
            ->addColumn('express_no', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '快递单号'])
            ->addColumn('express_code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '快递公司'])
            ->addColumn('express_name', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '快递名称'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '流程状态(0已取消,1预订单,2待审核,3待退货,4已退货,5待退款,6已退款,7已完成)'])
            ->addColumn('status_at', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '状态变更时间'])
            ->addColumn('status_ds', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '状态变更描述'])
            ->addColumn('admin_by', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '后台用户'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('unid', ['name' => 'i3c826a8cd_unid'])
            ->addIndex('type', ['name' => 'i3c826a8cd_type'])
            ->addIndex('code', ['name' => 'i3c826a8cd_code'])
            ->addIndex('ssid', ['name' => 'i3c826a8cd_ssid'])
            ->addIndex('status', ['name' => 'i3c826a8cd_status'])
            ->addIndex('order_no', ['name' => 'i3c826a8cd_order_no'])
            ->addIndex('create_time', ['name' => 'i3c826a8cd_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallOrderSender
     * @table plugin_wemall_order_sender
     * @return void
     */
    private function _create_plugin_wemall_order_sender()
    {

        // 当前数据表
        $table = 'plugin_wemall_order_sender';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-订单-配送',
        ])
            ->addColumn('ssid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '所属商家'])
            ->addColumn('unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '商城用户编号'])
            ->addColumn('order_no', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '商城订单单号'])
            ->addColumn('address_id', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '配送地址编号'])
            ->addColumn('user_idcode', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '收货人证件号码'])
            ->addColumn('user_idimg1', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '收货人证件正面'])
            ->addColumn('user_idimg2', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '收货人证件反面'])
            ->addColumn('user_name', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '收货人联系名称'])
            ->addColumn('user_phone', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '收货人联系手机'])
            ->addColumn('region_prov', 'string', ['limit' => 30, 'default' => '', 'null' => true, 'comment' => '配送地址的省份'])
            ->addColumn('region_city', 'string', ['limit' => 30, 'default' => '', 'null' => true, 'comment' => '配送地址的城市'])
            ->addColumn('region_area', 'string', ['limit' => 30, 'default' => '', 'null' => true, 'comment' => '配送地址的区域'])
            ->addColumn('region_addr', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '配送的详细地址'])
            ->addColumn('delivery_code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '配送模板编号'])
            ->addColumn('delivery_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '快递计费基数'])
            ->addColumn('delivery_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '配送计算金额'])
            ->addColumn('delivery_remark', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '配送计算描述'])
            ->addColumn('express_time', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '快递发送时间'])
            ->addColumn('express_code', 'string', ['limit' => 80, 'default' => '', 'null' => true, 'comment' => '快递运送单号'])
            ->addColumn('express_remark', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '快递发送备注'])
            ->addColumn('company_code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '快递公司编码'])
            ->addColumn('company_name', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '快递公司名称'])
            ->addColumn('extra', 'text', ['default' => NULL, 'null' => true, 'comment' => '原始数据'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '发货状态(1待发货,2已发货,3已收货)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('unid', ['name' => 'if6f961fc1_unid'])
            ->addIndex('ssid', ['name' => 'if6f961fc1_ssid'])
            ->addIndex('status', ['name' => 'if6f961fc1_status'])
            ->addIndex('deleted', ['name' => 'if6f961fc1_deleted'])
            ->addIndex('order_no', ['name' => 'if6f961fc1_order_no'])
            ->addIndex('create_time', ['name' => 'if6f961fc1_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallUserActionCollect
     * @table plugin_wemall_user_action_collect
     * @return void
     */
    private function _create_plugin_wemall_user_action_collect()
    {

        // 当前数据表
        $table = 'plugin_wemall_user_action_collect';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-用户-收藏',
        ])
            ->addColumn('unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户编号'])
            ->addColumn('gcode', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '商品编号'])
            ->addColumn('times', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '记录次数'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('unid', ['name' => 'i79fcacf4f_unid'])
            ->addIndex('sort', ['name' => 'i79fcacf4f_sort'])
            ->addIndex('gcode', ['name' => 'i79fcacf4f_gcode'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallUserActionComment
     * @table plugin_wemall_user_action_comment
     * @return void
     */
    private function _create_plugin_wemall_user_action_comment()
    {

        // 当前数据表
        $table = 'plugin_wemall_user_action_comment';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-用户-评论',
        ])
            ->addColumn('ssid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '所属商家'])
            ->addColumn('unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户编号'])
            ->addColumn('code', 'string', ['limit' => 32, 'default' => NULL, 'null' => true, 'comment' => '评论编号'])
            ->addColumn('gcode', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '商品编号'])
            ->addColumn('ghash', 'string', ['limit' => 32, 'default' => NULL, 'null' => true, 'comment' => '商品哈希'])
            ->addColumn('order_no', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '订单单号'])
            ->addColumn('rate', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '5.00', 'null' => true, 'comment' => '评论分数'])
            ->addColumn('content', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '评论内容'])
            ->addColumn('images', 'text', ['default' => NULL, 'null' => true, 'comment' => '评论图片'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '评论状态(0隐藏,1显示)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('unid', ['name' => 'ie6e792cc7_unid'])
            ->addIndex('code', ['name' => 'ie6e792cc7_code'])
            ->addIndex('ssid', ['name' => 'ie6e792cc7_ssid'])
            ->addIndex('ghash', ['name' => 'ie6e792cc7_ghash'])
            ->addIndex('gcode', ['name' => 'ie6e792cc7_gcode'])
            ->addIndex('status', ['name' => 'ie6e792cc7_status'])
            ->addIndex('deleted', ['name' => 'ie6e792cc7_deleted'])
            ->addIndex('order_no', ['name' => 'ie6e792cc7_order_no'])
            ->addIndex('create_time', ['name' => 'ie6e792cc7_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallUserActionHistory
     * @table plugin_wemall_user_action_history
     * @return void
     */
    private function _create_plugin_wemall_user_action_history()
    {

        // 当前数据表
        $table = 'plugin_wemall_user_action_history';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-用户-足迹',
        ])
            ->addColumn('ssid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '所属商家'])
            ->addColumn('unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户编号'])
            ->addColumn('gcode', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '商品编号'])
            ->addColumn('times', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '记录次数'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('unid', ['name' => 'i9bce34c4f_unid'])
            ->addIndex('sort', ['name' => 'i9bce34c4f_sort'])
            ->addIndex('ssid', ['name' => 'i9bce34c4f_ssid'])
            ->addIndex('gcode', ['name' => 'i9bce34c4f_gcode'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallUserActionSearch
     * @table plugin_wemall_user_action_search
     * @return void
     */
    private function _create_plugin_wemall_user_action_search()
    {

        // 当前数据表
        $table = 'plugin_wemall_user_action_search';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-用户-搜索',
        ])
            ->addColumn('unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户编号'])
            ->addColumn('keys', 'string', ['limit' => 99, 'default' => '', 'null' => true, 'comment' => '关键词'])
            ->addColumn('times', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '搜索次数'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('keys', ['name' => 'i03c8b2b46_keys'])
            ->addIndex('unid', ['name' => 'i03c8b2b46_unid'])
            ->addIndex('sort', ['name' => 'i03c8b2b46_sort'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallUserCheckin
     * @table plugin_wemall_user_checkin
     * @return void
     */
    private function _create_plugin_wemall_user_checkin()
    {

        // 当前数据表
        $table = 'plugin_wemall_user_checkin';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '业务-活动-签到',
        ])
            ->addColumn('unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户UNID'])
            ->addColumn('times', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '连续天数'])
            ->addColumn('timed', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '奖励天数'])
            ->addColumn('date', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '签到日期'])
            ->addColumn('balance', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '赠送余额'])
            ->addColumn('integral', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '赠送积分'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '生效状态(0未生效,1已生效)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删除,1已删除)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('unid', ['name' => 'i0faf876c0_unid'])
            ->addIndex('date', ['name' => 'i0faf876c0_date'])
            ->addIndex('status', ['name' => 'i0faf876c0_status'])
            ->addIndex('deleted', ['name' => 'i0faf876c0_deleted'])
            ->addIndex('create_time', ['name' => 'i0faf876c0_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallUserCoupon
     * @table plugin_wemall_user_coupon
     * @return void
     */
    private function _create_plugin_wemall_user_coupon()
    {

        // 当前数据表
        $table = 'plugin_wemall_user_coupon';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-用户-卡券',
        ])
            ->addColumn('type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '卡券类型'])
            ->addColumn('unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户UNID'])
            ->addColumn('coid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '配置编号'])
            ->addColumn('code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '卡券编号'])
            ->addColumn('used', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '使用状态'])
            ->addColumn('used_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '使用时间'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '生效状态(0未生效,1待使用,2已使用,3已过期)'])
            ->addColumn('status_desc', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '状态描述'])
            ->addColumn('status_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '修改时间'])
            ->addColumn('expire', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '有效时间'])
            ->addColumn('expire_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '有效日期'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删除,1已删除)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addColumn('confirm_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '到账时间'])
            ->addIndex('code', ['name' => 'ic31512dc2_code'])
            ->addIndex('unid', ['name' => 'ic31512dc2_unid'])
            ->addIndex('coid', ['name' => 'ic31512dc2_coid'])
            ->addIndex('used', ['name' => 'ic31512dc2_used'])
            ->addIndex('status', ['name' => 'ic31512dc2_status'])
            ->addIndex('expire', ['name' => 'ic31512dc2_expire'])
            ->addIndex('deleted', ['name' => 'ic31512dc2_deleted'])
            ->addIndex('create_time', ['name' => 'ic31512dc2_create_time'])
            ->addIndex('confirm_time', ['name' => 'ic31512dc2_confirm_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallUserCreate
     * @table plugin_wemall_user_create
     * @return void
     */
    private function _create_plugin_wemall_user_create()
    {

        // 当前数据表
        $table = 'plugin_wemall_user_create';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-用户-创建',
        ])
            ->addColumn('unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => false, 'comment' => '关联用户'])
            ->addColumn('name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '用户姓名'])
            ->addColumn('phone', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '手机号码'])
            ->addColumn('headimg', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '用户头像'])
            ->addColumn('password', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '初始密码'])
            ->addColumn('rebate_total', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '累计返利'])
            ->addColumn('rebate_total_code', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '记录编号'])
            ->addColumn('rebate_total_desc', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '记录描述'])
            ->addColumn('rebate_usable', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '可提返利'])
            ->addColumn('rebate_usable_code', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '记录编号'])
            ->addColumn('rebate_usable_desc', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '记录描述'])
            ->addColumn('agent_entry', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '代理权限'])
            ->addColumn('agent_phone', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '上级手机'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('name', ['name' => 'i55481d44e_name'])
            ->addIndex('unid', ['name' => 'i55481d44e_unid'])
            ->addIndex('phone', ['name' => 'i55481d44e_phone'])
            ->addIndex('status', ['name' => 'i55481d44e_status'])
            ->addIndex('deleted', ['name' => 'i55481d44e_deleted'])
            ->addIndex('create_time', ['name' => 'i55481d44e_create_time'])
            ->addIndex('agent_entry', ['name' => 'i55481d44e_agent_entry'])
            ->addIndex('agent_phone', ['name' => 'i55481d44e_agent_phone'])
            ->addIndex('rebate_total', ['name' => 'i55481d44e_rebate_total'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallUserRebate
     * @table plugin_wemall_user_rebate
     * @return void
     */
    private function _create_plugin_wemall_user_rebate()
    {

        // 当前数据表
        $table = 'plugin_wemall_user_rebate';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-用户-返利',
        ])
            ->addColumn('unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户UNID'])
            ->addColumn('layer', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上级层级'])
            ->addColumn('code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '奖励编号'])
            ->addColumn('hash', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '维一编号'])
            ->addColumn('date', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '奖励日期'])
            ->addColumn('type', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '奖励类型'])
            ->addColumn('name', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '奖励名称'])
            ->addColumn('amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '奖励数量'])
            ->addColumn('order_no', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '订单单号'])
            ->addColumn('order_unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '订单用户'])
            ->addColumn('order_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '订单金额'])
            ->addColumn('remark', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '奖励描述'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '生效状态(0未生效,1已生效)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删除,1已删除)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addColumn('confirm_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '到账时间'])
            ->addIndex('type', ['name' => 'i5f9c1d4b3_type'])
            ->addIndex('date', ['name' => 'i5f9c1d4b3_date'])
            ->addIndex('code', ['name' => 'i5f9c1d4b3_code'])
            ->addIndex('name', ['name' => 'i5f9c1d4b3_name'])
            ->addIndex('unid', ['name' => 'i5f9c1d4b3_unid'])
            ->addIndex('hash', ['name' => 'i5f9c1d4b3_hash'])
            ->addIndex('status', ['name' => 'i5f9c1d4b3_status'])
            ->addIndex('deleted', ['name' => 'i5f9c1d4b3_deleted'])
            ->addIndex('order_no', ['name' => 'i5f9c1d4b3_order_no'])
            ->addIndex('order_unid', ['name' => 'i5f9c1d4b3_order_unid'])
            ->addIndex('create_time', ['name' => 'i5f9c1d4b3_create_time'])
            ->addIndex('confirm_time', ['name' => 'i5f9c1d4b3_confirm_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallUserRecharge
     * @table plugin_wemall_user_recharge
     * @return void
     */
    private function _create_plugin_wemall_user_recharge()
    {

        // 当前数据表
        $table = 'plugin_wemall_user_recharge';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-用户-充值',
        ])
            ->addColumn('unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '账号编号'])
            ->addColumn('code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '操作编号'])
            ->addColumn('name', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '操作名称'])
            ->addColumn('remark', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '操作备注'])
            ->addColumn('amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '操作金额'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删除,1已删除)'])
            ->addColumn('create_by', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '系统用户'])
            ->addColumn('deleted_by', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '系统用户'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('deleted_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '删除时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('unid', ['name' => 'id6918c8c5_unid'])
            ->addIndex('code', ['name' => 'id6918c8c5_code'])
            ->addIndex('deleted', ['name' => 'id6918c8c5_deleted'])
            ->addIndex('create_time', ['name' => 'id6918c8c5_create_time'])
            ->addIndex('deleted_time', ['name' => 'id6918c8c5_deleted_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallUserRelation
     * @table plugin_wemall_user_relation
     * @return void
     */
    private function _create_plugin_wemall_user_relation()
    {

        // 当前数据表
        $table = 'plugin_wemall_user_relation';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-用户-关系',
        ])
            ->addColumn('unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '当前用户'])
            ->addColumn('puids', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '绑定状态'])
            ->addColumn('puid1', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上1级代理'])
            ->addColumn('puid2', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上2级代理'])
            ->addColumn('puid3', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上3级代理'])
            ->addColumn('layer', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '所属层级'])
            ->addColumn('path', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '关系路径'])
            ->addColumn('extra', 'text', ['default' => NULL, 'null' => true, 'comment' => '扩展数据'])
            ->addColumn('entry_agent', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '推广权益(0无,1有)'])
            ->addColumn('entry_member', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '入会礼包(0无,1有)'])
            ->addColumn('level_code', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '会员等级'])
            ->addColumn('level_name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '会员名称'])
            ->addColumn('agent_uuid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '绑定用户'])
            ->addColumn('agent_state', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '绑定状态'])
            ->addColumn('agent_level_code', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '代理等级'])
            ->addColumn('agent_level_name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '代理名称'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('unid', ['name' => 'i863175e04_unid'])
            ->addIndex('path', ['name' => 'i863175e04_path'])
            ->addIndex('puid1', ['name' => 'i863175e04_puid1'])
            ->addIndex('puid2', ['name' => 'i863175e04_puid2'])
            ->addIndex('puid3', ['name' => 'i863175e04_puid3'])
            ->addIndex('level_code', ['name' => 'i863175e04_level_code'])
            ->addIndex('agent_uuid', ['name' => 'i863175e04_agent_uuid'])
            ->addIndex('create_time', ['name' => 'i863175e04_create_time'])
            ->addIndex('entry_agent', ['name' => 'i863175e04_entry_agent'])
            ->addIndex('entry_member', ['name' => 'i863175e04_entry_member'])
            ->addIndex('agent_level_code', ['name' => 'i863175e04_agent_level_code'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallUserTransfer
     * @table plugin_wemall_user_transfer
     * @return void
     */
    private function _create_plugin_wemall_user_transfer()
    {

        // 当前数据表
        $table = 'plugin_wemall_user_transfer';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-用户-提现',
        ])
            ->addColumn('unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户UNID'])
            ->addColumn('type', 'string', ['limit' => 30, 'default' => '', 'null' => true, 'comment' => '提现方式'])
            ->addColumn('date', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '提现日期'])
            ->addColumn('code', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '提现单号'])
            ->addColumn('appid', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '公众号APPID'])
            ->addColumn('openid', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '公众号OPENID'])
            ->addColumn('username', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '公众号真实姓名'])
            ->addColumn('charge_rate', 'decimal', ['precision' => 20, 'scale' => 4, 'default' => '0.0000', 'null' => true, 'comment' => '提现手续费比例'])
            ->addColumn('charge_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '提现手续费金额'])
            ->addColumn('amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '提现转账金额'])
            ->addColumn('qrcode', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '收款码图片地址'])
            ->addColumn('bank_wseq', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '微信银行编号'])
            ->addColumn('bank_name', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '开户银行名称'])
            ->addColumn('bank_bran', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '开户分行名称'])
            ->addColumn('bank_user', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '开户账号姓名'])
            ->addColumn('bank_code', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '开户银行卡号'])
            ->addColumn('alipay_user', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '支付宝姓名'])
            ->addColumn('alipay_code', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '支付宝账号'])
            ->addColumn('remark', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '提现描述'])
            ->addColumn('trade_no', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '交易单号'])
            ->addColumn('trade_time', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '打款时间'])
            ->addColumn('change_time', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '处理时间'])
            ->addColumn('change_desc', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '处理描述'])
            ->addColumn('audit_time', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '审核时间'])
            ->addColumn('audit_status', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '审核状态'])
            ->addColumn('audit_remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '审核描述'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '提现状态(0失败,1待审核,2已审核,3打款中,4已打款,5已收款)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('code', ['name' => 'i574337aba_code'])
            ->addIndex('unid', ['name' => 'i574337aba_unid'])
            ->addIndex('date', ['name' => 'i574337aba_date'])
            ->addIndex('type', ['name' => 'i574337aba_type'])
            ->addIndex('appid', ['name' => 'i574337aba_appid'])
            ->addIndex('openid', ['name' => 'i574337aba_openid'])
            ->addIndex('status', ['name' => 'i574337aba_status'])
            ->addIndex('create_time', ['name' => 'i574337aba_create_time'])
            ->addIndex('audit_status', ['name' => 'i574337aba_audit_status'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }
}
