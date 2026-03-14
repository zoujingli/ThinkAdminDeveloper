<?php

declare(strict_types=1);

namespace think\admin\model;

/**
 * Runtime model for table-name based queries.
 */
class RuntimeModel extends \think\admin\Model
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
