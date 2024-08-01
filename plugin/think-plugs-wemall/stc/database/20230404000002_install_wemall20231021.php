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
 * 数据库升级
 */
class InstallWemall20231021 extends Migrator
{
    public function change()
    {
        // 检查并更新会员等级
        $table = $this->table('plugin_wemall_config_level');
        $table->hasColumn('cardbg') || $table->addColumn('cardbg', 'string', [
            'limit' => 500, 'default' => '', 'null' => true, 'after' => 'cover', 'comment' => '会员等级卡片'
        ])->update();

        // 检查并更新商品规格表
        $table = $this->table('plugin_wemall_goods_item');
        $table->hasColumn('price_cost') || $table->addColumn('price_cost', 'decimal', [
            'precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'after' => 'stock_total', 'comment' => '成本价格'
        ])->update();

        // 检查并更新订单主表
        $table = $this->table('plugin_wemall_order');
        $table->hasColumn('amount_cost') || $table->addColumn('amount_cost', 'decimal', [
            'precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'after' => 'order_ps', 'comment' => '商品成本'
        ])->addColumn('amount_profit', 'decimal', [
            'precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'after' => 'amount_reduct', 'comment' => '销售利润'
        ])->update();

        // 检查并更新订单商品
        $table = $this->table('plugin_wemall_order_item');
        $table->hasColumn('amount_cost') || $table->addColumn('amount_cost', 'decimal', [
            'precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'after' => 'stock_sales', 'comment' => '商品成本单价'
        ])->addColumn('total_price_cost', 'decimal', [
            'precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'after' => 'price_selling', 'comment' => '商品成本总价'
        ])->update();

        //  创建新数据库
        $this->_create_plugin_wemall_config_notify();
        $this->_create_plugin_wemall_config_poster();
        $this->_create_plugin_wemall_user_action_collect();
        $this->_create_plugin_wemall_user_action_history();
        $this->_create_plugin_wemall_user_action_search();
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
            ->addColumn('levels', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '会员等级'])
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
            ->addColumn('unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户编号'])
            ->addColumn('gcode', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '商品编号'])
            ->addColumn('times', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '记录次数'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('unid', ['name' => 'i9bce34c4f_unid'])
            ->addIndex('sort', ['name' => 'i9bce34c4f_sort'])
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
            ->addColumn('levels', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '会员等级'])
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
}
