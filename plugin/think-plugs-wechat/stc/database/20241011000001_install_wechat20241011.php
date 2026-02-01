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
use app\wechat\Service;
use think\admin\extend\PhinxExtend;
use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', -1);

/**
 * 系统模块数据.
 */
class InstallWechat20241011 extends Migrator
{
    /**
     * 获取脚本名称.
     */
    public function getName(): string
    {
        return 'WechatPlugin';
    }

    /**
     * 创建数据库.
     * @throws Exception
     */
    public function change()
    {
        $this->insertMenu();
    }

    /**
     * 初始化系统菜单.
     * @throws Exception
     */
    private function insertMenu()
    {
        // 初始化菜单数据
        PhinxExtend::write2menu([
            [
                'name' => '微信管理',
                'sort' => '200',
                'subs' => Service::menu(),
            ],
        ], [
            'url|node' => 'wechat/fans/index',
        ]);
    }
}
