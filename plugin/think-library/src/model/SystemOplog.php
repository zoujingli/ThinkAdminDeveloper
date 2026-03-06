<?php

declare(strict_types=1);

namespace think\admin\model;

use think\admin\Model;

class SystemOplog extends Model
{
    protected $updateTime = false;

    public function getCreateTimeAttr($value): string
    {
        return format_datetime($value);
    }
}
