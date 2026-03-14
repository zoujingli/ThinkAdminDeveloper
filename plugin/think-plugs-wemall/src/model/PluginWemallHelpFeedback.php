<?php

declare(strict_types=1);

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
