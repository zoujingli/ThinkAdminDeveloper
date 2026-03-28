<?php

declare(strict_types=1);

namespace think\admin\builder\page;

/**
 * 页面工具条列对象.
 * @class PageToolbarColumn
 */
class PageToolbarColumn extends PagePresetColumn
{
    public function __construct(PageBuilder $builder, string $title = '操作面板', array $options = [])
    {
        parent::__construct($builder, array_merge(['title' => $title], $options));
    }

    protected function sync(): self
    {
        if (is_callable($this->syncHandler)) {
            $this->syncResult(($this->syncHandler)($this));
        } elseif ($this->canSync()) {
            $this->syncResult($this->builder->replaceToolbarColumn($this->index(), $this->export(), $this->meta()));
        }
        return $this;
    }
}
