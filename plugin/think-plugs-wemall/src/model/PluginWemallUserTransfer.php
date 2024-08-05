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

use plugin\wemall\service\UserTransfer;

/**
 * 代理提现模型
 * @class PluginWemallUserTransfer
 * @package plugin\wemall\model
 */
class PluginWemallUserTransfer extends AbsUser
{

    /**
     * 格式化输出时间
     * @param mixed $value
     * @return string
     */
    public function getChangeTimeAttr($value): string
    {
        return format_datetime($value);
    }

    /**
     * 自动显示类型名称
     * @return array
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