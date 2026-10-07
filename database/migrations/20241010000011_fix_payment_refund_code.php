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
use think\migration\Migrator;

/**
 * 支付退款编号约束.
 * @class FixPaymentRefundCode
 */
class FixPaymentRefundCode extends Migrator
{
    public function getName(): string
    {
        return 'FixPaymentRefundCode';
    }

    public function change(): void
    {
        $table = $this->table('plugin_payment_refund');
        if ($table->exists() && !$table->hasIndexByName('uq_plugin_payment_refund_code')) {
            // 已有重复退款编号时让迁移明确失败，交由管理员清理后重试。
            $table->addIndex(['code'], ['unique' => true, 'name' => 'uq_plugin_payment_refund_code'])->update();
        }
    }
}
