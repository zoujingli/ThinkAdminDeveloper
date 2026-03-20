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

namespace plugin\wechat\client\command;

use plugin\wechat\client\model\WechatPaymentRecord;
use think\admin\Command;
use think\admin\Exception;
use think\console\Input;
use think\console\Output;
use think\db\exception\DbException;
use think\db\Query;
use think\Model;

/**
 * 微信支付单清理任务
 * @class Clear
 */
class Clear extends Command
{
    protected function configure()
    {
        $this->setName('xadmin:fanspay');
        $this->setDescription('Wechat Users Payment auto clear for ThinkAdmin');
    }

    /**
     * 执行支付单清理任务
     * @throws Exception
     * @throws DbException
     */
    protected function execute(Input $input, Output $output): int
    {
        $query = WechatPaymentRecord::mq();
        $query->where(['payment_status' => 0]);
        $query->whereTime('create_time', '<', strtotime('-24 hours'));
        [$total, $count] = [(clone $query)->count(), 0];
        if (empty($total)) {
            $this->setQueueSuccess('无需清理24小时未支付！');
        }
        if (!$query instanceof Query) {
            return 0;
        }
        /** @var Model $item */
        foreach ($query->cursor() as $item) {
            $this->setQueueMessage($total, ++$count, sprintf('开始清理 %s 支付单...', $item->getAttr('code')));
            $item->delete();
            $this->setQueueMessage($total, $count, sprintf('完成清理 %s 支付单！', $item->getAttr('code')), 1);
        }
        return 0;
    }
}
