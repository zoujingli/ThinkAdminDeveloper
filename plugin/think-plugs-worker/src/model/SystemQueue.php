<?php

declare(strict_types=1);

namespace plugin\worker\model;

use think\admin\Model;

class SystemQueue extends Model
{
    protected $updateTime = false;

    public function getExecTimeAttr($value): string
    {
        return format_datetime($value);
    }

    public function getEnterTimeAttr($value): string
    {
        return bccomp(strval($value), '0.00', 2) > 0 ? format_datetime((int) $value) : '';
    }

    public function getOuterTimeAttr($value, array $data): string
    {
        if ($value > 0 && $value > $data['enter_time']) {
            return lang('耗时 %.4f 秒', [$data['outer_time'] - $data['enter_time']]);
        }

        return ' - ';
    }

    public function getCreateTimeAttr($value): string
    {
        return format_datetime($value);
    }
}
