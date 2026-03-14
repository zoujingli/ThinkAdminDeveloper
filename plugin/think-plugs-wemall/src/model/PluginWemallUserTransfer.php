<?php

declare(strict_types=1);

namespace plugin\wemall\model;

use plugin\wemall\service\UserTransfer;

class PluginWemallUserTransfer extends AbsUser
{
    protected $deleteTime = false;

    public function toArray(): array
    {
        $data = parent::toArray();
        if (isset($data['type'])) {
            $map = ['platform' => '平台打款'];
            $data['type_name'] = $map[$data['type']] ?? (UserTransfer::types[$data['type']] ?? $data['type']);
        }
        return $data;
    }
}
