<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
 * +----------------------------------------------------------------------
 * | 版权所有 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | 官方网站: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | 开源协议 ( https://mit-license.org )
 * | 免责声明 ( https://thinkadmin.top/disclaimer )
 * | 会员特权 ( https://thinkadmin.top/vip-introduce )
 * +----------------------------------------------------------------------
 * | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
 * | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace think\admin\model;

use think\admin\Model;

/**
 * Runtime model for table-name based queries.
 */
class RuntimeModel extends Model
{
    protected string $runtimeName = '';

    protected string $runtimeConnection = '';

    public function __construct(string $name, array|object $data = [], string $connection = '')
    {
        $this->runtimeName = $name;
        $this->runtimeConnection = $connection;
        parent::__construct($data);
    }

    protected function getBaseOptions(): array
    {
        $options = ['name' => $this->runtimeName];
        if ($this->runtimeConnection !== '') {
            $options['connection'] = $this->runtimeConnection;
        }

        return $options;
    }
}
