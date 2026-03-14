<?php

declare(strict_types=1);

namespace plugin\account\model;

use think\admin\Model;
use think\model\concern\SoftDelete;

abstract class Abs extends Model
{
    use SoftDelete;

    public function setExtraAttr($value): string
    {
        return is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function getExtraAttr($value): array
    {
        return empty($value) ? [] : (is_string($value) ? json_decode($value, true) : $value);
    }
}
