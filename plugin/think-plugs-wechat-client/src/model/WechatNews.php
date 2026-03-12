<?php

declare(strict_types=1);

namespace plugin\wechat\client\model;

use think\admin\Model;
use think\model\concern\SoftDelete;

class WechatNews extends Model
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';

    protected $defaultSoftDelete = null;

    protected $updateTime = false;
}
