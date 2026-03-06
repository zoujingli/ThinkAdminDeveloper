<?php

declare(strict_types=1);

namespace app\wechat\model;

use think\admin\Model;

class WechatKeys extends Model
{
    public function getCreateTimeAttr($value): string
    {
        return format_datetime($value);
    }
}
