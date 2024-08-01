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

use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', -1);

/**
 * 系统模块数据
 */
class InstallAdmin20230325 extends Migrator
{
    public function change()
    {
        // 当前数据表
        $table = 'system_file';
        // 检查与更新数据表
        $this->table($table)->hasColumn('unid') || $this->table($table)
            ->addColumn('tags', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'after' => 'hash', 'comment' => '文件标签'])
            ->addColumn('unid', 'integer', ['limit' => 11, 'default' => 0, 'null' => true, 'after' => 'uuid', 'comment' => '会员编号'])
            ->addIndex('uuid', ['name' => 'i738a363ca_uuid'])
            ->addIndex('tags', ['name' => 'i738a363ca_tags'])
            ->update();
    }
}
