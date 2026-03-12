<?php

declare(strict_types=1);

namespace plugin\wechat\client\model;

use think\admin\Model;

class WechatAuto extends Model
{
    public function getCreateTimeAttr($value): string
    {
        return format_datetime($value);
    }
}
