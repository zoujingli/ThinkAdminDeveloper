<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdminDeveloper
 * +----------------------------------------------------------------------
 * | Copyright (c) 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | Official Website: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | Licensed: https://mit-license.org
 * | Disclaimer: https://thinkadmin.top/disclaimer
 * | Vip Rights: https://thinkadmin.top/vip-introduce
 * +----------------------------------------------------------------------
 * | Gitee Repository: https://gitee.com/zoujingli/ThinkAdmin
 * | Github Repository: https://github.com/zoujingli/ThinkAdmin
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

    public function __construct(array|object|string $data = [], array|object|string $name = '', string $connection = '')
    {
        if (is_string($data)) {
            $this->runtimeName = $data;
            $this->runtimeConnection = is_string($name) && $connection === '' ? $name : $connection;
            $data = is_array($name) || is_object($name) ? $name : [];
        } else {
            $this->runtimeName = is_string($name) ? $name : '';
            $this->runtimeConnection = $connection;
        }

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
