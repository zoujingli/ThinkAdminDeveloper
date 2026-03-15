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

namespace plugin\wemall\model;

use plugin\system\model\SystemUser;
use think\model\relation\HasOne;

class PluginWemallHelpFeedback extends AbsUser
{
    public function bindAdmin(): HasOne
    {
        return $this->hasOne(SystemUser::class, 'id', 'reply_by')->bind([
            'reply_headimg' => 'headimg',
            'reply_username' => 'username',
            'reply_nickname' => 'nickname',
        ]);
    }

    public function getImagesAttr($value): array
    {
        return str2arr($value ?? '', '|');
    }
}
