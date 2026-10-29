<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
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
use Phinx\Migration\IrreversibleMigrationException;
use think\admin\extend\PhinxExtend;
use think\migration\Migrator;

/**
 * 扩展操作日志 IP 字段以支持 IPv6.
 * @class FixAdminOplogGeoip
 */
class FixAdminOplogGeoip extends Migrator
{
    public function getName(): string
    {
        return 'FixAdminOplogGeoip';
    }

    public function up(): void
    {
        $table = $this->table('system_oplog');
        if ($table->exists()) {
            PhinxExtend::upgrade($table, [
                ['geoip', 'string', ['limit' => 45, 'default' => '', 'null' => false, 'comment' => '操作者IP地址']],
            ], [], true);
        }
    }

    public function down(): void
    {
        throw new IrreversibleMigrationException('操作日志 IP 字段扩容不支持自动回滚，避免截断已保存的 IPv6 地址');
    }
}
