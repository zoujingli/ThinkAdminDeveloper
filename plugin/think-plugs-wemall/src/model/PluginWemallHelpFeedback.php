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

use think\admin\model\SystemUser;
use think\model\relation\HasOne;

/**
 * 意见反馈数据模型
 * @class PluginWemallHelpFeedback
 * @package plugin\wemall\model
 */
class PluginWemallHelpFeedback extends AbsUser
{
    /**
     * 绑定回复用户数据
     * @return HasOne
     */
    public function bindAdmin(): HasOne
    {
        return $this->hasOne(SystemUser::class, 'id', 'reply_by')->bind([
            'reply_headimg'  => 'headimg',
            'reply_username' => 'username',
            'reply_nickname' => 'nickname',
        ]);
    }

    /**
     * 格式化图片格式
     * @param mixed $value
     * @return array
     */
    public function getImagesAttr($value): array
    {
        return str2arr($value ?? '', '|');
    }

    /**
     * 获取回复时间
     * @param mixed $value
     * @return string
     */
    public function getReplyTimeAttr($value): string
    {
        return parent::getCreateTimeAttr($value);
    }

    /**
     * 设置回复时间
     * @param mixed $value
     * @return string
     */
    public function setReplyTimeAttr($value): string
    {
        return parent::setCreateTimeAttr($value);
    }
}