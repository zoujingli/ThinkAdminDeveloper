<?php

declare(strict_types=1);

namespace plugin\wechat\client\model;

use think\admin\Model;

class WechatKeys extends Model
{
    public function getCreateTimeAttr($value): string
    {
        return format_datetime($value);
    }
}
