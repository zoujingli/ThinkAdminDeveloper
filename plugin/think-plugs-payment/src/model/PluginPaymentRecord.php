<?php

// +----------------------------------------------------------------------
// | Payment Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-payment
// | github 代码仓库：https://github.com/zoujingli/think-plugs-payment
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\payment\model;

use plugin\account\model\Abs;
use plugin\account\model\PluginAccountBind;
use plugin\account\model\PluginAccountUser;
use plugin\payment\service\Payment;
use think\model\relation\HasOne;

/**
 * 用户支付行为模型
 * @class PluginPaymentRecord
 * @package plugin\payment\model
 */
class PluginPaymentRecord extends Abs
{
    /**
     * 关联用户数据
     * @return \think\model\relation\HasOne
     */
    public function user(): HasOne
    {
        return $this->hasOne(PluginAccountUser::class, 'id', 'unid');
    }

    /**
     * 关联客户端数据
     * @return \think\model\relation\HasOne
     */
    public function device(): HasOne
    {
        return $this->hasOne(PluginAccountBind::class, 'id', 'usid');
    }

    /**
     * @param $value
     * @return array
     */
    public function getUserAttr($value): array
    {
        return is_array($value) ? $value : [];
    }

    /**
     * 格式化时间
     * @param mixed $value
     * @return string
     */
    public function getAuditTimeAttr($value): string
    {
        return $this->getCreateTimeAttr($value);
    }

    /**
     * 格式化时间
     * @param mixed $value
     * @return string
     */
    public function setAuditTimeAttr($value): string
    {
        return $this->setCreateTimeAttr($value);
    }

    /**
     * 格式化输出时间
     * @param mixed $value
     * @return string
     */
    public function getPaymentTimeAttr($value): string
    {
        return $this->getCreateTimeAttr($value);
    }

    /**
     * 格式化时间
     * @param mixed $value
     * @return string
     */
    public function setPaymentTimeAttr($value): string
    {
        return $this->setCreateTimeAttr($value);
    }

    /**
     *  格式化通知
     * @param mixed $value
     * @return string
     */
    public function setPaymentNotifyAttr($value): string
    {
        return $this->setExtraAttr($value);
    }

    /**
     * 格式化通知
     * @param mixed $value
     * @return array
     */
    public function getPaymentNotifyAttr($value): array
    {
        return $this->getExtraAttr($value);
    }

    /**
     * 数据输出处理
     * @return array
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        if (isset($data['channel_type'])) {
            $data['channel_type_name'] = Payment::typeName($data['channel_type']);
        }
        return $data;
    }
}