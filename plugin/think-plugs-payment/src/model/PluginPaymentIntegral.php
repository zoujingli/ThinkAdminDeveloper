<?php

declare(strict_types=1);

namespace plugin\payment\model;

use plugin\account\model\Abs;
use plugin\account\model\PluginAccountUser;
use think\model\relation\HasOne;

class PluginPaymentIntegral extends Abs
{
    protected $deleteTime = 'deleted_time';

    public function user(): HasOne
    {
        return $this->hasOne(PluginAccountUser::class, 'id', 'unid');
    }
}
