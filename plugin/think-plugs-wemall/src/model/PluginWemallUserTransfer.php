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

namespace plugin\wemall\model;

use plugin\wemall\service\UserTransfer;

/**
 * 代理提现数据.
 *
 * @property string $amount 提现转账金额
 * @property string $charge_amount 提现手续费金额
 * @property string $charge_rate 提现手续费比例
 * @property int $audit_status 审核状态
 * @property int $id
 * @property int $status 提现状态(0失败,1待审核,2已审核,3打款中,4已打款,5已收款)
 * @property int $unid 用户UNID
 * @property string $alipay_code 支付宝账号
 * @property string $alipay_user 支付宝姓名
 * @property string $appid 公众号APPID
 * @property string $audit_remark 审核描述
 * @property string $audit_time 审核时间
 * @property string $bank_bran 开户分行名称
 * @property string $bank_code 开户银行卡号
 * @property string $bank_name 开户银行名称
 * @property string $bank_user 开户账号姓名
 * @property string $bank_wseq 微信银行编号
 * @property string $change_desc 处理描述
 * @property string $change_time 处理时间
 * @property string $code 提现单号
 * @property string $create_time 创建时间
 * @property string $date 提现日期
 * @property string $openid 公众号OPENID
 * @property string $qrcode 收款码图片地址
 * @property string $remark 提现描述
 * @property string $trade_no 交易单号
 * @property string $trade_time 打款时间
 * @property string $type 提现方式
 * @property string $update_time 更新时间
 * @property string $username 公众号真实姓名
 * @class PluginWemallUserTransfer
 */
class PluginWemallUserTransfer extends AbsUser
{
    /**
     * 格式化输出时间.
     * @param mixed $value
     */
    public function getChangeTimeAttr($value): string
    {
        return format_datetime($value);
    }

    /**
     * 自动显示类型名称.
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        if (isset($data['type'])) {
            $map = ['platform' => '平台发放'];
            $data['type_name'] = $map[$data['type']] ?? (UserTransfer::types[$data['type']] ?? $data['type']);
        }
        return $data;
    }
}
