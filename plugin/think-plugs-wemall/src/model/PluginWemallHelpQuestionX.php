<?php

// +----------------------------------------------------------------------
// | WeMall Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
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
 * 工单交互数据模型
 *
 * @property array $images 图片内容
 * @property int $ccid 目标编号
 * @property int $deleted 删除状态(0未删,1已删)
 * @property int $id
 * @property int $reply_by 后台用户
 * @property int $status 记录状态(0无效,1待审核,2已审核)
 * @property int $unid 用户编号
 * @property string $content 文本内容
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 * @property-read \think\admin\model\SystemUser $bind_admin
 * @class PluginWemallHelpQuestionX
 * @package plugin\wemall\model
 */
class PluginWemallHelpQuestionX extends AbsUser
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
}