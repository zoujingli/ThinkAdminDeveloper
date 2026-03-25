<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

use think\admin\builder\base\render\BuilderNodeRenderContext;

/**
 * 页面节点渲染上下文.
 * @class PageNodeRenderContext
 */
class PageNodeRenderContext extends BuilderNodeRenderContext
{
    /**
     * @param callable(array<int, array<string, mixed>>): string $contentRenderer
     * @param callable(array<string, mixed>): string $attrsRenderer
     * @param callable(): string $searchRenderer
     * @param callable(): string $tableRenderer
     */
    public function __construct(
        callable $contentRenderer,
        callable $attrsRenderer,
        private $searchRenderer,
        private $tableRenderer,
    ) {
        parent::__construct($contentRenderer, $attrsRenderer);
    }

    public function renderSearch(): string
    {
        return ($this->searchRenderer)();
    }

    public function renderTable(): string
    {
        return ($this->tableRenderer)();
    }
}
