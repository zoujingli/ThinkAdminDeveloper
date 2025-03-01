<?php

// +----------------------------------------------------------------------
// | WeMall Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wemall
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wemall
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wemall\model;

use plugin\account\model\Abs;
use plugin\wemall\service\UserRebate;

/**
 * 商城返佣配置
 *
 * @property float $p0_reward_number 会员计算系数
 * @property float $p1_reward_number 上1级计算系数
 * @property float $p2_reward_number 上2级计算系数
 * @property float $p3_reward_number 上3级计算系数
 * @property int $deleted 删除状态(1已删,0未删)
 * @property int $id
 * @property int $p0_level 会员等级
 * @property int $p0_reward_type 会员计算类型(0固定金额,1交易比例,2利润比例)
 * @property int $p1_level 上1级等级
 * @property int $p1_reward_type 上1级计算类型(0固定金额,1交易比例,2利润比例)
 * @property int $p2_level 上2级等级
 * @property int $p2_reward_type 上2级计算类型(0固定金额,1交易比例,2利润比例)
 * @property int $p3_level 上3级等级
 * @property int $p3_reward_type 上3级计算类型(0固定金额,1交易比例,2利润比例)
 * @property int $sort 排序权重
 * @property int $status 激活状态(0无效,1有效)
 * @property int $stype 结算类型(0支付结算,1收货结算)
 * @property string $code 配置编号
 * @property string $create_time 创建时间
 * @property string $name 配置名称
 * @property string $path 等级关系
 * @property string $remark 配置描述
 * @property string $type 奖励类型
 * @property string $update_time 更新时间
 * @class PluginWemallConfigRebate
 * @package plugin\wemall\model
 */
class PluginWemallConfigRebate extends Abs
{
    /**
     * 数据输出处理
     * @return array
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        if (isset($data['type'])) {
            $data['type_name'] = UserRebate::prizes[$data['type']] ?? $data['type'];
        }
        if (isset($data['p0_level']) && isset($data['p1_level']) && isset($data['p2_level']) && isset($data['p3_level'])) {
            $levels = sysvar('plugin.wemall.levels') ?: sysvar('plugin.wemall.levels', PluginWemallConfigLevel::items());
            $data['levels'] = join(' - ', array_map(function ($v) use ($levels) {
                if ($v == -2) return '无';
                if ($v == -1) return '任意';
                return $levels[$v]['name'] ?? $v;
            }, [$data['p3_level'], $data['p2_level'], $data['p1_level'], $data['p0_level']]));
        }
        return $data;
    }
}