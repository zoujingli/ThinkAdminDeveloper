<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdminDeveloper
 * +----------------------------------------------------------------------
 * | Copyright (c) 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | Official Website: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | Licensed: https://mit-license.org
 * | Disclaimer: https://thinkadmin.top/disclaimer
 * | Vip Rights: https://thinkadmin.top/vip-introduce
 * +----------------------------------------------------------------------
 * | Gitee Repository: https://gitee.com/zoujingli/ThinkAdmin
 * | Github Repository: https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */
use plugin\helper\service\PhinxExtend;
use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', '-1');

class InstallStorage20241010 extends Migrator
{
    /**
     * 获取脚本名称.
     */
    public function getName(): string
    {
        return 'StoragePlugin';
    }

    /**
     * 创建数据库.
     */
    public function change()
    {
        $this->_create_system_file();
    }

    /**
     * 创建数据对象
     * @class SystemFile
     * @table system_file
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
            ['create_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '更新时间']],
        ], [
            'type', 'hash', 'uuid', 'xext', 'unid', 'tags', 'name', 'status', 'issafe', 'isfast', 'create_time',
        ], true);
    }
}
