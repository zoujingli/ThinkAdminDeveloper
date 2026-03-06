<?php

declare(strict_types=1);

namespace app\wechat\model;

use think\admin\Model;
use think\model\concern\SoftDelete;

class WechatNews extends Model
{
    use SoftDelete;
}
