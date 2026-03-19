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

namespace plugin\wemall\model;

use plugin\system\model\SystemUser;
use think\model\relation\HasOne;

/**
 * 工单交互数据模型.
 *
 * @property int $ccid 目标编号
 * @property string $delete_time 删除时间
 * @property int $id
 * @property int $reply_by 后台用户
 * @property int $status 记录状态(0无效,1待审核,2已审核)
 * @property int $unid 用户编号
 * @property string $content 文本内容
 * @property string $create_time 创建时间
 * @property string $images 图片内容
 * @property string $update_time 更新时间
 * @property SystemUser $bind_admin
 * @class PluginWemallHelpQuestionX
 */
class PluginWemallHelpQuestionX extends AbsUser
{
    /**
     * 绑定回复用户数据.
     */
    public function bindAdmin(): HasOne
    {
        return $this->hasOne(SystemUser::class, 'id', 'reply_by')->bind([
            'reply_headimg' => 'headimg',
            'reply_username' => 'username',
            'reply_nickname' => 'nickname',
        ]);
    }

    /**
     * 格式化图片格式.
     * @param mixed $value
     */
    public function getImagesAttr($value): array
    {
        return str2arr($value ?? '', '|');
    }
}
