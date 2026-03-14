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
use plugin\helper\service\PhinxExtend;
use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', '-1');

class InstallWechatService20241010 extends Migrator
{
    /**
     * 获取脚本名称.
     */
    public function getName(): string
    {
        return 'WechatServicePlugin';
    }

    /**
     * 创建数据库.
     */
    public function change()
    {
        $this->_create_wechat_auth();
    }

    /**
     * 创建数据对象
     * @class WechatAuth
     * @table wechat_auth
     */
    private function _create_wechat_auth()
    {
        // 创建数据表对象
        $table = $this->table('wechat_auth', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '微信-授权',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['authorizer_appid', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '微信APPID']],
            ['authorizer_access_token', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '授权Token']],
            ['authorizer_refresh_token', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '刷新Token']],
            ['expires_in', 'integer', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => 'Token时限']],
            ['user_alias', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '公众号别名']],
            ['user_name', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '众众号原账号']],
            ['user_nickname', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '公众号昵称']],
            ['user_headimg', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '公众号头像']],
            ['user_signature', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '公众号描述']],
            ['user_company', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '公众号公司']],
            ['func_info', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '公众号集权']],
            ['service_type', 'string', ['limit' => 10, 'default' => '', 'null' => true, 'comment' => '公众号类型']],
            ['service_verify', 'string', ['limit' => 10, 'default' => '', 'null' => true, 'comment' => '公众号认证']],
            ['qrcode_url', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '公众号二维码']],
            ['businessinfo', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '业务序列内容']],
            ['miniprograminfo', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '小程序序列内容']],
            ['total', 'integer', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '统计调用次数']],
            ['appkey', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '应用接口KEY']],
            ['appuri', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '应用接口URI']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '授权状态(0已取消,1已授权)']],
            ['delete_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '删除时间']],
            ['auth_time', 'integer', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '授权时间']],
            ['create_time', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true, 'comment' => '创建时间']],
        ], [
            'status', 'delete_time', 'authorizer_appid',
        ], true);
    }
}
