<?php

// +----------------------------------------------------------------------
// | Payment Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-payment
// | github 代码仓库：https://github.com/zoujingli/think-plugs-payment
// +----------------------------------------------------------------------

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

namespace plugin\payment\service\contract;

/**
 * 支付响应对象
 * @class PaymentResponse
 */
class PaymentResponse extends \stdClass
{
    public $record;

    public $status;

    public $params;

    public $message;

    public $channleCode = '';

    public $channelType = '';

    public function __construct(bool $status = true, string $message = '创建支付成功', array $record = [], array $params = [])
    {
        $this->record = $record;
        $this->status = $status;
        $this->params = $params;
        $this->message = $message;
    }

    /**
     * 更新返回内容.
     * @return $this
     */
    public function set(bool $status = true, string $message = '创建支付成功', array $record = [], array $params = []): PaymentResponse
    {
        $this->record = $record;
        $this->status = $status;
        $this->params = $params;
        $this->message = $message;
        return $this;
    }

    /**
     * 输出数组数据.
     */
    public function toArray(): array
    {
        return [
            'record' => $this->record,
            'params' => $this->params,
            'channel' => [
                'type' => $this->channelType,
                'code' => $this->channleCode,
            ],
        ];
    }

    /**
     * 创建支付响应对象
     */
    public static function mk(bool $status = true, string $message = '创建支付成功', array $record = [], array $params = []): PaymentResponse
    {
        return new static($status, $message, $record, $params);
    }
}
