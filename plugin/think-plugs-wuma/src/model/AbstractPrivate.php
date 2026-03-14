<?php

declare(strict_types=1);

namespace plugin\wuma\model;

use think\admin\Model;
use think\model\concern\SoftDelete;

abstract class AbstractPrivate extends Model
{
    use SoftDelete;
}
