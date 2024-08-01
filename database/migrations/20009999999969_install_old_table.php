<?php

use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', -1);

class InstallOldTable extends Migrator {

	/**
	 * 创建数据库
	 */
	 public function change() {
		$this->_create_plugin_old_user();
		$this->_create_plugin_old_user2();

	}

    /**
     * 创建数据对象
     * @class PluginOldUser
     * @table plugin_old_user
     * @return void
     */
    private function _create_plugin_old_user() {

        // 当前数据表
        $table = 'plugin_old_user';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '',
        ])
		->addColumn('nickname','string',['limit' => 255, 'default' => NULL, 'null' => true, 'comment' => '昵称'])
		->addColumn('phone','string',['limit' => 255, 'default' => NULL, 'null' => true, 'comment' => '绑定手机号'])
		->addColumn('concat','string',['limit' => 255, 'default' => NULL, 'null' => true, 'comment' => '联系方式'])
		->addColumn('remark','string',['limit' => 255, 'default' => NULL, 'null' => true, 'comment' => '备注'])
		->addColumn('remark2','string',['limit' => 255, 'default' => NULL, 'null' => true, 'comment' => '备注名'])
		->addColumn('create_time','string',['limit' => 255, 'default' => NULL, 'null' => true, 'comment' => '加入时间'])
		->addColumn('role','string',['limit' => 255, 'default' => NULL, 'null' => true, 'comment' => '会员身份'])
		->addColumn('订单数','string',['limit' => 255, 'default' => NULL, 'null' => true, 'comment' => ''])
		->addColumn('优惠券总数','string',['limit' => 255, 'default' => NULL, 'null' => true, 'comment' => ''])
		->addColumn('卡券总数','string',['limit' => 255, 'default' => NULL, 'null' => true, 'comment' => ''])
		->addColumn('integral','string',['limit' => 255, 'default' => NULL, 'null' => true, 'comment' => '积分'])
		->addColumn('balance','string',['limit' => 255, 'default' => NULL, 'null' => true, 'comment' => '余额'])
		->addColumn('总消费','string',['limit' => 255, 'default' => NULL, 'null' => true, 'comment' => ''])
		->addColumn('spread','string',['limit' => 255, 'default' => NULL, 'null' => true, 'comment' => '用户推荐人'])
		->addColumn('用户标签','string',['limit' => 255, 'default' => NULL, 'null' => true, 'comment' => ''])
		->addColumn('所属平台(平台标识ID)','string',['limit' => 255, 'default' => NULL, 'null' => true, 'comment' => ''])
		->create();

		// 修改主键长度
		$this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
	}

    /**
     * 创建数据对象
     * @class PluginOldUser2
     * @table plugin_old_user2
     * @return void
     */
    private function _create_plugin_old_user2() {

        // 当前数据表
        $table = 'plugin_old_user2';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '',
        ])
		->addColumn('所属平台','string',['limit' => 255, 'default' => NULL, 'null' => true, 'comment' => ''])
		->addColumn('openid','string',['limit' => 255, 'default' => NULL, 'null' => true, 'comment' => ''])
		->addColumn('nickname','string',['limit' => 255, 'default' => NULL, 'null' => true, 'comment' => '昵称'])
		->addColumn('username','string',['limit' => 255, 'default' => NULL, 'null' => true, 'comment' => '姓名'])
		->addColumn('phone','string',['limit' => 255, 'default' => NULL, 'null' => true, 'comment' => '手机号'])
		->addColumn('create_time','string',['limit' => 255, 'default' => NULL, 'null' => true, 'comment' => '申请时间'])
		->addColumn('审核状态','string',['limit' => 255, 'default' => NULL, 'null' => true, 'comment' => ''])
		->addColumn('rebate','string',['limit' => 255, 'default' => NULL, 'null' => true, 'comment' => '累计佣金'])
		->addColumn('usable','string',['limit' => 255, 'default' => NULL, 'null' => true, 'comment' => '可提现佣金'])
		->addColumn('订单数','string',['limit' => 255, 'default' => NULL, 'null' => true, 'comment' => ''])
		->addColumn('下级用户','string',['limit' => 255, 'default' => NULL, 'null' => true, 'comment' => ''])
		->addColumn('spread','string',['limit' => 255, 'default' => NULL, 'null' => true, 'comment' => '推荐人'])
		->addColumn('备注信息','string',['limit' => 255, 'default' => NULL, 'null' => true, 'comment' => ''])
		->create();

		// 修改主键长度
		$this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
	}

}
