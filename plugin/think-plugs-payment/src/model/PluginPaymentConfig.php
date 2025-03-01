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

/**
 * 用户支付参数模型
 *
 * @property array $content 支付参数
 * @property int $deleted 删除状态
 * @property int $id
 * @property int $sort 排序权重
 * @property int $status 支付状态(1使用,0禁用)
 * @property string $code 通道编号
 * @property string $cover 支付图标
 * @property string $create_time 创建时间
 * @property string $name 支付名称
 * @property string $remark 支付说明
 * @property string $type 支付类型
 * @property string $update_time 更新时间
 * @class PluginPaymentConfig
 * @package plugin\payment\model
 */
class PluginPaymentConfig extends Abs
{
    protected $oplogName = '商城支付配置';
    protected $oplogType = '商城支付配置';

    /**
     * 格式化数据格式
     * @param mixed $value
     * @return string
     */
    public function setContentAttr($value): string
    {
        return $this->setExtraAttr($value);
    }

    /**
     * 格式化数据格式
     * @param mixed $value
     * @return array
     */
    public function getContentAttr($value): array
    {
        return $this->getExtraAttr($value);
    }
}