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

class InstallWemall20241010 extends Migrator
{
    /**
     * 获取脚本名称.
     */
    public function getName(): string
    {
        return 'WemallPlugin';
    }

    /**
     * 创建数据库.
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
     */
    private function _create_plugin_wemall_config_agent()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_config_agent', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-配置-等级',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '级别名称']],
            ['cover', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '等级图标']],
            ['cardbg', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '等级卡片']],
            ['number', 'integer', ['limit' => 2, 'default' => 0, 'null' => true, 'comment' => '级别序号']],
            ['upgrade_type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '升级规则(0单个,1同时)']],
            ['extra', 'text', ['default' => null, 'null' => true, 'comment' => '升级规则']],
            ['remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '级别描述']],
            ['utime', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '更新时间']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '等级状态(1使用,0禁用)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'utime', 'status', 'number', 'create_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallConfigCoupon
     * @table plugin_wemall_config_coupon
     */
    private function _create_plugin_wemall_config_coupon()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_config_coupon', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-配置-卡券',
        ], true);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['type', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '类型(0通用券,1商品券)']],
            ['name', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '优惠名称']],
            ['cover', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '封面图标']],
            ['extra', 'text', ['default' => null, 'null' => true, 'comment' => '扩展数据']],
            ['content', 'text', ['default' => null, 'null' => true, 'comment' => '内容描述']],
            ['remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '系统备注']],
            ['amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '抵扣金额']],
            ['limit_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '金额门槛(0不限制)']],
            ['limit_levels', 'string', ['limit' => 180, 'default' => '-', 'null' => true, 'comment' => '授权等级']],
            ['limit_times', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '限领数量(0不限制)']],
            ['expire_days', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '有效天数']],
            ['total_stock', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '库存数量']],
            ['total_sales', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '发放数量']],
            ['total_used', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '使用数量']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '卡券状态(0禁用,1使用)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(1已删,0未删)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'sort', 'type', 'status', 'deleted', 'create_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallConfigDiscount
     * @table plugin_wemall_config_discount
     */
    private function _create_plugin_wemall_config_discount()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_config_discount', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-配置-折扣',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['name', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '方案名称']],
            ['remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '方案描述']],
            ['items', 'text', ['default' => null, 'null' => true, 'comment' => '方案规则']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '方案状态(0禁用,1使用)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(1已删,0未删)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'sort', 'status', 'deleted', 'create_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallConfigLevel
     * @table plugin_wemall_config_level
     */
    private function _create_plugin_wemall_config_level()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_config_level', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-配置-等级',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '级别名称']],
            ['cover', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '等级图标']],
            ['cardbg', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '等级卡片']],
            ['number', 'integer', ['limit' => 2, 'default' => 0, 'null' => true, 'comment' => '级别序号']],
            ['upgrade_type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '升级规则(0单个,1同时)']],
            ['upgrade_team', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '团队人数统计(0不计,1累计)']],
            ['remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '用户级别描述']],
            ['extra', 'text', ['default' => null, 'null' => true, 'comment' => '配置规则']],
            ['utime', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '更新时间']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '等级状态(1使用,0禁用)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'utime', 'status', 'number', 'create_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallConfigNotify
     * @table plugin_wemall_config_notify
     */
    private function _create_plugin_wemall_config_notify()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_config_notify', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-配置-通知',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['code', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '通知编号']],
            ['name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '通知标题']],
            ['cover', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '通知图片']],
            ['levels', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '用户等级']],
            ['content', 'text', ['default' => null, 'null' => true, 'comment' => '通知内容']],
            ['remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '通知描述']],
            ['num_read', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '阅读次数']],
            ['tips', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => 'TIPS显示']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '激活状态(0无效,1有效)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(1已删,0未删)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'code', 'sort', 'name', 'tips', 'status', 'deleted', 'create_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallConfigPoster
     * @table plugin_wemall_config_poster
     */
    private function _create_plugin_wemall_config_poster()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_config_poster', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-配置-海报',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['code', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '推广编号']],
            ['name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '推广标题']],
            ['levels', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '用户等级']],
            ['devices', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '接口通道']],
            ['image', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '推广图片']],
            ['content', 'text', ['default' => null, 'null' => true, 'comment' => '二维位置']],
            ['remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '推广描述']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '激活状态(0无效,1有效)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(1已删,0未删)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'code', 'sort', 'name', 'status', 'deleted', 'create_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallConfigRebate
     * @table plugin_wemall_config_rebate
     */
    private function _create_plugin_wemall_config_rebate()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_config_rebate', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-配置-返利',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['type', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '奖励类型']],
            ['code', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '配置编号']],
            ['name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '配置名称']],
            ['path', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '等级关系']],
            ['stype', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '结算类型(0支付结算,1收货结算)']],
            ['p0_level', 'biginteger', ['limit' => 20, 'default' => -1, 'null' => true, 'comment' => '会员等级']],
            ['p1_level', 'biginteger', ['limit' => 20, 'default' => -1, 'null' => true, 'comment' => '上1级等级']],
            ['p2_level', 'biginteger', ['limit' => 20, 'default' => -1, 'null' => true, 'comment' => '上2级等级']],
            ['p3_level', 'biginteger', ['limit' => 20, 'default' => -1, 'null' => true, 'comment' => '上3级等级']],
            ['p0_reward_type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '会员计算类型(0固定金额,1交易比例,2利润比例)']],
            ['p0_reward_number', 'decimal', ['precision' => 20, 'scale' => 6, 'default' => '0.000000', 'null' => true, 'comment' => '会员计算系数']],
            ['p1_reward_type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '上1级计算类型(0固定金额,1交易比例,2利润比例)']],
            ['p1_reward_number', 'decimal', ['precision' => 20, 'scale' => 6, 'default' => '0.000000', 'null' => true, 'comment' => '上1级计算系数']],
            ['p2_reward_type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '上2级计算类型(0固定金额,1交易比例,2利润比例)']],
            ['p2_reward_number', 'decimal', ['precision' => 20, 'scale' => 6, 'default' => '0.000000', 'null' => true, 'comment' => '上2级计算系数']],
            ['p3_reward_type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '上3级计算类型(0固定金额,1交易比例,2利润比例)']],
            ['p3_reward_number', 'decimal', ['precision' => 20, 'scale' => 6, 'default' => '0.000000', 'null' => true, 'comment' => '上3级计算系数']],
            ['remark', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '配置描述']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '激活状态(0无效,1有效)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(1已删,0未删)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'code', 'sort', 'name', 'type', 'stype', 'status', 'deleted', 'p1_level', 'p2_level', 'p3_level', 'p0_level', 'create_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallExpressCompany
     * @table plugin_wemall_express_company
     */
    private function _create_plugin_wemall_express_company()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_express_company', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '数据-快递-公司',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['code', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '公司代码']],
            ['name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '公司名称']],
            ['remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '公司描述']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '激活状态(0无效,1有效)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(1已删,0未删)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'code', 'sort', 'name', 'status', 'deleted', 'create_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallExpressTemplate
     * @table plugin_wemall_express_template
     */
    private function _create_plugin_wemall_express_template()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_express_template', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '数据-快递-模板',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '模板编号']],
            ['name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '模板名称']],
            ['normal', 'text', ['default' => null, 'null' => true, 'comment' => '默认规则']],
            ['content', 'text', ['default' => null, 'null' => true, 'comment' => '模板规则']],
            ['company', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '快递公司']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '激活状态(0无效,1有效)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(1已删,0未删)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'code', 'sort', 'name', 'status', 'deleted', 'create_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallGoods
     * @table plugin_wemall_goods
     */
    private function _create_plugin_wemall_goods()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_goods', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-商品-内容',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['ssid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '所属商家']],
            ['code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '商品编号']],
            ['name', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '商品名称']],
            ['marks', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '商品标签']],
            ['cates', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '分类编号']],
            ['cover', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '商品封面']],
            ['slider', 'text', ['default' => null, 'null' => true, 'comment' => '轮播图片']],
            ['specs', 'text', ['default' => null, 'null' => true, 'comment' => '商品规格(JSON)']],
            ['content', 'text', ['default' => null, 'null' => true, 'comment' => '商品详情']],
            ['remark', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '商品描述']],
            ['stock_total', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '商品库存统计']],
            ['stock_sales', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '商品销售统计']],
            ['stock_virtual', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '商品虚拟销量']],
            ['price_selling', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '最低销售价格']],
            ['price_market', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '最低市场价格']],
            ['allow_integral', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '最大积分兑换']],
            ['allow_balance', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '最大余额支付']],
            ['rebate_type', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '参与返利(0无需返利,1需要返利)']],
            ['delivery_code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '物流运费模板']],
            ['limit_lowvip', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '限制购买等级(0不限制,其他限制)']],
            ['limit_maxnum', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '最大购买数量(0不限制,其他限制)']],
            ['level_agent', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '推广权益(0无,1有)']],
            ['level_upgrade', 'biginteger', ['limit' => 20, 'default' => -1, 'null' => true, 'comment' => '购买升级等级(-1非入会,0不升级,其他升级)']],
            ['discount_id', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '折扣方案编号(0无折扣,其他折扣)']],
            ['num_read', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '访问阅读统计']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '列表排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '商品状态(1使用,0禁用)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'code', 'sort', 'ssid', 'status', 'deleted', 'rebate_type', 'discount_id', 'create_time', 'level_agent', 'level_upgrade',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallGoodsCate
     * @table plugin_wemall_goods_cate
     */
    private function _create_plugin_wemall_goods_cate()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_goods_cate', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-商品-分类',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['pid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上级分类']],
            ['name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '分类名称']],
            ['cover', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '分类图标']],
            ['remark', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '分类描述']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '使用状态']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'pid', 'sort', 'status', 'deleted',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallGoodsItem
     * @table plugin_wemall_goods_item
     */
    private function _create_plugin_wemall_goods_item()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_goods_item', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-商品-规格',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['gsku', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '商品SKU']],
            ['ghash', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '商品哈希']],
            ['gcode', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '商品编号']],
            ['gspec', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '商品规格']],
            ['gunit', 'string', ['limit' => 10, 'default' => '件', 'null' => true, 'comment' => '商品单位']],
            ['gimage', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '商品图片']],
            ['stock_sales', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '销售数量']],
            ['stock_total', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '商品库存']],
            ['price_cost', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '进货成本']],
            ['price_selling', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '销售价格']],
            ['price_market', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '市场价格']],
            ['allow_integral', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '兑换积分']],
            ['allow_balance', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '余额支付']],
            ['reward_balance', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '奖励余额']],
            ['reward_integral', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '奖励积分']],
            ['number_virtual', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '虚拟销量']],
            ['number_express', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '计件系数']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '商品状态']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'gcode', 'gspec', 'ghash', 'status',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallGoodsMark
     * @table plugin_wemall_goods_mark
     */
    private function _create_plugin_wemall_goods_mark()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_goods_mark', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-商品-标签',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '标签名称']],
            ['remark', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '标签描述']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '标签状态(1使用,0禁用)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'sort', 'status',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallGoodsStock
     * @table plugin_wemall_goods_stock
     */
    private function _create_plugin_wemall_goods_stock()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_goods_stock', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-商品-库存',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['batch_no', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '操作批量']],
            ['ghash', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '商品哈希']],
            ['gcode', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '商品编号']],
            ['gspec', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '商品规格']],
            ['gstock', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '入库数量']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '数据状态(1使用,0禁用)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'ghash', 'gcode', 'status', 'deleted',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallHelpFeedback
     * @table plugin_wemall_help_feedback
     */
    private function _create_plugin_wemall_help_feedback()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_help_feedback', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '数据-意见-反馈',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '反馈用户']],
            ['phone', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '联系电话']],
            ['images', 'text', ['default' => null, 'null' => true, 'comment' => '反馈图片']],
            ['content', 'text', ['default' => null, 'null' => true, 'comment' => '反馈内容']],
            ['reply', 'text', ['default' => null, 'null' => true, 'comment' => '回复内容']],
            ['reply_st', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '回复状态']],
            ['reply_by', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '回复用户']],
            ['reply_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '回复时间']],
            ['sync', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '同步至常见问题状态(1已同步,0未同步)']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '展示状态(1使用,0禁用)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'sort', 'unid', 'status', 'deleted', 'reply_st',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallHelpProblem
     * @table plugin_wemall_help_problem
     */
    private function _create_plugin_wemall_help_problem()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_help_problem', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '数据-常见-问题',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['fid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => false, 'comment' => '来自反馈']],
            ['name', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '问题标题']],
            ['content', 'text', ['default' => null, 'null' => true, 'comment' => '问题内容']],
            ['num_er', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '未解决数']],
            ['num_ok', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '已解决数']],
            ['num_read', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '阅读次数']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '展示状态(1使用,0禁用)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'sort', 'status', 'deleted',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallHelpQuestion
     * @table plugin_wemall_help_question
     */
    private function _create_plugin_wemall_help_question()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_help_question', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '数据-问答-内容',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '提问用户']],
            ['name', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '工单标题']],
            ['phone', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '联系电话']],
            ['order_no', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '关联订单']],
            ['images', 'text', ['default' => null, 'null' => true, 'comment' => '工单图片']],
            ['content', 'text', ['default' => null, 'null' => true, 'comment' => '工单描述']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '工单状态(0取消,1新工单,2后台回复,3前台回复,4已完结)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'sort', 'name', 'unid', 'phone', 'status', 'deleted',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallHelpQuestionX
     * @table plugin_wemall_help_question_x
     */
    private function _create_plugin_wemall_help_question_x()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_help_question_x', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '数据-问答-评论',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['ccid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '目标编号']],
            ['unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户编号']],
            ['content', 'text', ['default' => null, 'null' => true, 'comment' => '文本内容']],
            ['images', 'text', ['default' => null, 'null' => true, 'comment' => '图片内容']],
            ['reply_by', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '后台用户']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1待审核,2已审核)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'ccid', 'unid', 'status',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallOrder
     * @table plugin_wemall_order
     */
    private function _create_plugin_wemall_order()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_order', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-订单-内容',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['ssid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '所属商家']],
            ['unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户编号']],
            ['puid1', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上1级代理']],
            ['puid2', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上2级代理']],
            ['puid3', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上3级代理']],
            ['order_no', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '订单单号']],
            ['order_ps', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '订单备注']],
            ['amount_cost', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '商品成本']],
            ['amount_real', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '实际金额']],
            ['amount_total', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '订单金额']],
            ['amount_goods', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '商品金额']],
            ['amount_profit', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '销售利润']],
            ['amount_reduct', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '随机减免']],
            ['amount_balance', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '余额支付']],
            ['amount_integral', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '积分抵扣']],
            ['amount_payment', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '金额支付']],
            ['amount_express', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '快递费用']],
            ['amount_discount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '折扣后金额']],
            ['coupon_code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '优惠券编号']],
            ['coupon_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '优惠券金额']],
            ['allow_balance', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '最大余额支付']],
            ['allow_integral', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '最大积分抵扣']],
            ['ratio_integral', 'decimal', ['precision' => 20, 'scale' => 6, 'default' => '0.000000', 'null' => true, 'comment' => '积分兑换比例']],
            ['number_goods', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '商品数量']],
            ['number_express', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '快递计数']],
            ['level_agent', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '升级代理等级']],
            ['level_member', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '升级会员等级']],
            ['rebate_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '返利金额']],
            ['reward_balance', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '奖励余额']],
            ['reward_integral', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '奖励积分']],
            ['payment_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '支付时间']],
            ['payment_status', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '支付状态(0未支付,1有支付)']],
            ['payment_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '实际支付']],
            ['delivery_type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '物流类型(0无配送,1需配送)']],
            ['cancel_time', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '取消时间']],
            ['cancel_status', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '取消状态']],
            ['cancel_remark', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '取消描述']],
            ['deleted_time', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '删除时间']],
            ['deleted_status', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)']],
            ['deleted_remark', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '删除描述']],
            ['confirm_time', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '签收时间']],
            ['confirm_remark', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '签收描述']],
            ['refund_code', 'string', ['limit' => 20, 'default' => null, 'null' => true, 'comment' => '售后单号']],
            ['refund_status', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '售后状态(0未售后,1预订单,2待审核,3待退货,4已退货,5待退款,6已退款,7已完成)']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '流程状态(0已取消,1预订单,2待支付,3待审核,4待发货,5已发货,6已收货,7已评论)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'unid', 'ssid', 'puid1', 'puid2', 'puid3', 'status', 'order_no', 'create_time', 'refund_code', 'coupon_code', 'delivery_type', 'cancel_status', 'refund_status', 'deleted_status',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallOrderCart
     * @table plugin_wemall_order_cart
     */
    private function _create_plugin_wemall_order_cart()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_order_cart', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-订单-购物车',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['ssid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '所属商家']],
            ['unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户编号']],
            ['ghash', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '规格哈希']],
            ['gcode', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '商品编号']],
            ['gspec', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '商品规格']],
            ['number', 'biginteger', ['limit' => 20, 'default' => 1, 'null' => true, 'comment' => '商品数量']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'unid', 'ssid', 'gcode', 'gspec', 'ghash',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallOrderItem
     * @table plugin_wemall_order_item
     */
    private function _create_plugin_wemall_order_item()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_order_item', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-订单-商品',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['ssid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '所属商家']],
            ['unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户编号']],
            ['gsku', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '商品SKU']],
            ['ghash', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '商品哈希']],
            ['gcode', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '商品编号']],
            ['gspec', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '商品规格']],
            ['gunit', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '商品单凭']],
            ['gname', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '商品名称']],
            ['gcover', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '商品封面']],
            ['order_no', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '订单单号']],
            ['stock_sales', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '包含商品数量']],
            ['amount_cost', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '商品成本单价']],
            ['price_market', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '商品市场单价']],
            ['price_selling', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '商品销售单价']],
            ['total_price_cost', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '商品成本总价']],
            ['total_price_market', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '商品市场总价']],
            ['total_price_selling', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '商品销售总价']],
            ['total_allow_balance', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '最大余额支付']],
            ['total_allow_integral', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '最大兑换总分']],
            ['total_reward_balance', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '商品奖励余额']],
            ['total_reward_integral', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '商品奖励积分']],
            ['level_code', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户等级序号']],
            ['level_name', 'string', ['limit' => 30, 'default' => '', 'null' => true, 'comment' => '用户等级名称']],
            ['level_agent', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '推广权益(0无,1有)']],
            ['level_upgrade', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '购买升级等级(-1非入会,0不升级,其他升级)']],
            ['rebate_type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '参与返利状态(0不返,1返利)']],
            ['rebate_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '参与返利金额']],
            ['delivery_code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '快递邮费模板']],
            ['delivery_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '快递计费基数']],
            ['discount_id', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '优惠方案编号']],
            ['discount_rate', 'decimal', ['precision' => 20, 'scale' => 6, 'default' => '100.000000', 'null' => true, 'comment' => '销售价格折扣']],
            ['discount_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '商品优惠金额']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '商品状态(1使用,0禁用)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'unid', 'gsku', 'ssid', 'gcode', 'gspec', 'ghash', 'status', 'deleted', 'order_no', 'rebate_type', 'discount_id', 'level_agent', 'delivery_code',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallOrderRefund
     * @table plugin_wemall_order_refund
     */
    private function _create_plugin_wemall_order_refund()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_order_refund', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-订单-售后',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['ssid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '所属商家']],
            ['unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户编号']],
            ['type', 'biginteger', ['limit' => 20, 'default' => 1, 'null' => true, 'comment' => '申请类型(1退货退款,2仅退款)']],
            ['code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '售后单号']],
            ['order_no', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '订单单号']],
            ['reason', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '退款原因']],
            ['number', 'biginteger', ['limit' => 20, 'default' => 1, 'null' => true, 'comment' => '退货数量']],
            ['amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '申请金额']],
            ['payment_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '退款支付']],
            ['balance_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '退款余额']],
            ['integral_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '退款积分']],
            ['payment_code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '退款单号']],
            ['balance_code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '退回单号']],
            ['integral_code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '退回单号']],
            ['phone', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '联系电话']],
            ['images', 'text', ['default' => null, 'null' => true, 'comment' => '申请图片']],
            ['content', 'text', ['default' => null, 'null' => true, 'comment' => '申请说明']],
            ['remark', 'string', ['limit' => 180, 'default' => null, 'null' => true, 'comment' => '操作描述']],
            ['express_no', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '快递单号']],
            ['express_code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '快递公司']],
            ['express_name', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '快递名称']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '流程状态(0已取消,1预订单,2待审核,3待退货,4已退货,5待退款,6已退款,7已完成)']],
            ['status_at', 'datetime', ['default' => null, 'null' => true, 'comment' => '状态变更时间']],
            ['status_ds', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '状态变更描述']],
            ['admin_by', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '后台用户']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'unid', 'type', 'code', 'ssid', 'status', 'order_no', 'create_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallOrderSend
     * @table plugin_wemall_order_send
     */
    private function _create_plugin_wemall_order_send()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_order_send', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-订单-配送',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '商城用户编号']],
            ['order_no', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '商城订单单号']],
            ['address_id', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '配送地址编号']],
            ['user_idcode', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '收货人证件号码']],
            ['user_idimg1', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '收货人证件正面']],
            ['user_idimg2', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '收货人证件反面']],
            ['user_name', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '收货人联系名称']],
            ['user_phone', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '收货人联系手机']],
            ['region_prov', 'string', ['limit' => 30, 'default' => '', 'null' => true, 'comment' => '配送地址的省份']],
            ['region_city', 'string', ['limit' => 30, 'default' => '', 'null' => true, 'comment' => '配送地址的城市']],
            ['region_area', 'string', ['limit' => 30, 'default' => '', 'null' => true, 'comment' => '配送地址的区域']],
            ['region_addr', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '配送的详细地址']],
            ['delivery_code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '配送模板编号']],
            ['delivery_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '快递计费基数']],
            ['delivery_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '配送计算金额']],
            ['delivery_remark', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '配送计算描述']],
            ['express_time', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '快递发送时间']],
            ['express_code', 'string', ['limit' => 80, 'default' => '', 'null' => true, 'comment' => '快递运送单号']],
            ['express_remark', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '快递发送备注']],
            ['company_code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '快递公司编码']],
            ['company_name', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '快递公司名称']],
            ['extra', 'text', ['default' => null, 'null' => true, 'comment' => '原始数据']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '发货状态(1待发货,2已发货,3已收货)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'unid', 'status', 'deleted', 'order_no', 'create_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallOrderSender
     * @table plugin_wemall_order_sender
     */
    private function _create_plugin_wemall_order_sender()
    {
        // 数据表重命名
        if ($this->hasTable('plugin_wemall_order_send') && !$this->hasTable('plugin_wemall_order_sender')) {
            $this->table('plugin_wemall_order_send')->rename('plugin_wemall_order_sender');
        }

        // 创建数据表对象
        $table = $this->table('plugin_wemall_order_sender', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-订单-配送',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['ssid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '所属商家']],
            ['unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '商城用户编号']],
            ['order_no', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '商城订单单号']],
            ['address_id', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '配送地址编号']],
            ['user_idcode', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '收货人证件号码']],
            ['user_idimg1', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '收货人证件正面']],
            ['user_idimg2', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '收货人证件反面']],
            ['user_name', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '收货人联系名称']],
            ['user_phone', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '收货人联系手机']],
            ['region_prov', 'string', ['limit' => 30, 'default' => '', 'null' => true, 'comment' => '配送地址的省份']],
            ['region_city', 'string', ['limit' => 30, 'default' => '', 'null' => true, 'comment' => '配送地址的城市']],
            ['region_area', 'string', ['limit' => 30, 'default' => '', 'null' => true, 'comment' => '配送地址的区域']],
            ['region_addr', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '配送的详细地址']],
            ['delivery_code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '配送模板编号']],
            ['delivery_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '快递计费基数']],
            ['delivery_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '配送计算金额']],
            ['delivery_remark', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '配送计算描述']],
            ['express_time', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '快递发送时间']],
            ['express_code', 'string', ['limit' => 80, 'default' => '', 'null' => true, 'comment' => '快递运送单号']],
            ['express_remark', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '快递发送备注']],
            ['company_code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '快递公司编码']],
            ['company_name', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '快递公司名称']],
            ['extra', 'text', ['default' => null, 'null' => true, 'comment' => '原始数据']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '发货状态(1待发货,2已发货,3已收货)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'unid', 'ssid', 'status', 'deleted', 'order_no', 'create_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallUserActionCollect
     * @table plugin_wemall_user_action_collect
     */
    private function _create_plugin_wemall_user_action_collect()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_user_action_collect', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-用户-收藏',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户编号']],
            ['gcode', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '商品编号']],
            ['times', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '记录次数']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'unid', 'sort', 'gcode',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallUserActionComment
     * @table plugin_wemall_user_action_comment
     */
    private function _create_plugin_wemall_user_action_comment()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_user_action_comment', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-用户-评论',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['ssid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '所属商家']],
            ['unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户编号']],
            ['code', 'string', ['limit' => 32, 'default' => null, 'null' => true, 'comment' => '评论编号']],
            ['gcode', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '商品编号']],
            ['ghash', 'string', ['limit' => 32, 'default' => null, 'null' => true, 'comment' => '商品哈希']],
            ['order_no', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '订单单号']],
            ['rate', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '5.00', 'null' => true, 'comment' => '评论分数']],
            ['content', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '评论内容']],
            ['images', 'text', ['default' => null, 'null' => true, 'comment' => '评论图片']],
            ['status', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '评论状态(0隐藏,1显示)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'unid', 'code', 'ssid', 'ghash', 'gcode', 'status', 'deleted', 'order_no', 'create_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallUserActionHistory
     * @table plugin_wemall_user_action_history
     */
    private function _create_plugin_wemall_user_action_history()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_user_action_history', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-用户-足迹',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['ssid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '所属商家']],
            ['unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户编号']],
            ['gcode', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '商品编号']],
            ['times', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '记录次数']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'unid', 'sort', 'ssid', 'gcode',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallUserActionSearch
     * @table plugin_wemall_user_action_search
     */
    private function _create_plugin_wemall_user_action_search()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_user_action_search', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-用户-搜索',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户编号']],
            ['keys', 'string', ['limit' => 99, 'default' => '', 'null' => true, 'comment' => '关键词']],
            ['times', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '搜索次数']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'keys', 'unid', 'sort',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallUserCheckin
     * @table plugin_wemall_user_checkin
     */
    private function _create_plugin_wemall_user_checkin()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_user_checkin', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '业务-活动-签到',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户UNID']],
            ['times', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '连续天数']],
            ['timed', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '奖励天数']],
            ['date', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '签到日期']],
            ['balance', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '赠送余额']],
            ['integral', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '赠送积分']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '生效状态(0未生效,1已生效)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删除,1已删除)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'unid', 'date', 'status', 'deleted', 'create_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallUserCoupon
     * @table plugin_wemall_user_coupon
     */
    private function _create_plugin_wemall_user_coupon()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_user_coupon', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-用户-卡券',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '卡券类型']],
            ['unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户UNID']],
            ['coid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '配置编号']],
            ['code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '卡券编号']],
            ['used', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '使用状态']],
            ['used_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '使用时间']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '生效状态(0未生效,1待使用,2已使用,3已过期)']],
            ['status_desc', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '状态描述']],
            ['status_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '修改时间']],
            ['expire', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '有效时间']],
            ['expire_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '有效日期']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删除,1已删除)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
            ['confirm_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '到账时间']],
        ], [
            'code', 'unid', 'coid', 'used', 'status', 'expire', 'deleted', 'create_time', 'confirm_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallUserCreate
     * @table plugin_wemall_user_create
     */
    private function _create_plugin_wemall_user_create()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_user_create', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-用户-创建',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => false, 'comment' => '关联用户']],
            ['name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '用户姓名']],
            ['phone', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '手机号码']],
            ['headimg', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '用户头像']],
            ['password', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '初始密码']],
            ['rebate_total', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '累计返利']],
            ['rebate_total_code', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '记录编号']],
            ['rebate_total_desc', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '记录描述']],
            ['rebate_usable', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '可提返利']],
            ['rebate_usable_code', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '记录编号']],
            ['rebate_usable_desc', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '记录描述']],
            ['agent_entry', 'tinyinteger', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '代理权限']],
            ['agent_phone', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '上级手机']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'name', 'unid', 'phone', 'status', 'deleted', 'create_time', 'agent_entry', 'agent_phone', 'rebate_total',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallUserRebate
     * @table plugin_wemall_user_rebate
     */
    private function _create_plugin_wemall_user_rebate()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_user_rebate', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-用户-返利',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户UNID']],
            ['layer', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上级层级']],
            ['code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '奖励编号']],
            ['hash', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '维一编号']],
            ['date', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '奖励日期']],
            ['type', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '奖励类型']],
            ['name', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '奖励名称']],
            ['amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '奖励数量']],
            ['order_no', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '订单单号']],
            ['order_unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '订单用户']],
            ['order_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '订单金额']],
            ['remark', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '奖励描述']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '生效状态(0未生效,1已生效)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删除,1已删除)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
            ['confirm_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '到账时间']],
        ], [
            'type', 'date', 'code', 'name', 'unid', 'hash', 'status', 'deleted', 'order_no', 'order_unid', 'create_time', 'confirm_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallUserRecharge
     * @table plugin_wemall_user_recharge
     */
    private function _create_plugin_wemall_user_recharge()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_user_recharge', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-用户-充值',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '账号编号']],
            ['code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '操作编号']],
            ['name', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '操作名称']],
            ['remark', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '操作备注']],
            ['amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '操作金额']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删除,1已删除)']],
            ['create_by', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '系统用户']],
            ['deleted_by', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '系统用户']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['deleted_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '删除时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'unid', 'code', 'deleted', 'create_time', 'deleted_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallUserRelation
     * @table plugin_wemall_user_relation
     */
    private function _create_plugin_wemall_user_relation()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_user_relation', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-用户-关系',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '当前用户']],
            ['puids', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '绑定状态']],
            ['puid1', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上1级代理']],
            ['puid2', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上2级代理']],
            ['puid3', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上3级代理']],
            ['layer', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '所属层级']],
            ['path', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '关系路径']],
            ['extra', 'text', ['default' => null, 'null' => true, 'comment' => '扩展数据']],
            ['entry_agent', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '推广权益(0无,1有)']],
            ['entry_member', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '入会礼包(0无,1有)']],
            ['level_code', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '会员等级']],
            ['level_name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '会员名称']],
            ['agent_uuid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '绑定用户']],
            ['agent_state', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '绑定状态']],
            ['agent_level_code', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '代理等级']],
            ['agent_level_name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '代理名称']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'unid', 'path', 'puid1', 'puid2', 'puid3', 'level_code', 'agent_uuid', 'create_time', 'entry_agent', 'entry_member', 'agent_level_code',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginWemallUserTransfer
     * @table plugin_wemall_user_transfer
     */
    private function _create_plugin_wemall_user_transfer()
    {
        // 创建数据表对象
        $table = $this->table('plugin_wemall_user_transfer', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-用户-提现',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户UNID']],
            ['type', 'string', ['limit' => 30, 'default' => '', 'null' => true, 'comment' => '提现方式']],
            ['date', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '提现日期']],
            ['code', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '提现单号']],
            ['appid', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '公众号APPID']],
            ['openid', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '公众号OPENID']],
            ['username', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '公众号真实姓名']],
            ['charge_rate', 'decimal', ['precision' => 20, 'scale' => 4, 'default' => '0.0000', 'null' => true, 'comment' => '提现手续费比例']],
            ['charge_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '提现手续费金额']],
            ['amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '提现转账金额']],
            ['qrcode', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '收款码图片地址']],
            ['bank_wseq', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '微信银行编号']],
            ['bank_name', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '开户银行名称']],
            ['bank_bran', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '开户分行名称']],
            ['bank_user', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '开户账号姓名']],
            ['bank_code', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '开户银行卡号']],
            ['alipay_user', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '支付宝姓名']],
            ['alipay_code', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '支付宝账号']],
            ['remark', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '提现描述']],
            ['trade_no', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '交易单号']],
            ['trade_time', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '打款时间']],
            ['change_time', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '处理时间']],
            ['change_desc', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '处理描述']],
            ['audit_time', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '审核时间']],
            ['audit_status', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '审核状态']],
            ['audit_remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '审核描述']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '提现状态(0失败,1待审核,2已审核,3打款中,4已打款,5已收款)']],
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'code', 'unid', 'date', 'type', 'appid', 'openid', 'status', 'create_time', 'audit_status',
        ], true);
    }
}
