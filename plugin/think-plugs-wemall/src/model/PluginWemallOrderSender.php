<?php

declare(strict_types=1);

namespace plugin\wemall\model;

use think\model\relation\HasOne;

class PluginWemallOrderSender extends AbsUser
{
    public function main(): HasOne
    {
        return $this->hasOne(PluginWemallOrder::class, 'order_no', 'order_no')->with(['items']);
    }
}
