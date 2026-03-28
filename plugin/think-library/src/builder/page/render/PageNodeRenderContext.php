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
     * @param callable(array<string, mixed>): string $searchRenderer
     * @param callable(array<string, mixed>): string $tableRenderer
     */
    public function __construct(
        callable $contentRenderer,
        callable $attrsRenderer,
        private $searchRenderer,
        private $tableRenderer,
    ) {
        parent::__construct($contentRenderer, $attrsRenderer);
    }

    public function renderSearch(array $node): string
    {
        return ($this->searchRenderer)($node);
    }

    public function renderTable(array $node): string
    {
        return ($this->tableRenderer)($node);
    }
}
