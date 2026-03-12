<?php

declare(strict_types=1);

namespace plugin\wechat\service\model;

use think\admin\Model;
use think\model\concern\SoftDelete;

class WechatAuth extends Model
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';

    protected $defaultSoftDelete = null;
}
