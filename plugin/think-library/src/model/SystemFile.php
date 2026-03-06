<?php

declare(strict_types=1);

namespace think\admin\model;

use think\admin\Model;
use think\model\relation\HasOne;

class SystemFile extends Model
{
    public function user(): HasOne
    {
        return $this->hasOne(SystemUser::class, 'id', 'uuid')->field('id,username,nickname');
    }

    public function getCreateTimeAttr($value): string
    {
        return format_datetime($value);
    }

    public function getUpdateTimeAttr($value): string
    {
        return format_datetime($value);
    }
}
