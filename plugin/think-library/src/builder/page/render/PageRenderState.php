<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

use think\admin\builder\base\render\BuilderRenderState;

/**
 * 页面渲染状态对象.
 * @class PageRenderState
 */
class PageRenderState extends BuilderRenderState
{
    /**
     * @param array<string, mixed> $schema
     */
    public function __construct(
        array $schema,
        PageNodeRendererFactory $nodeRendererFactory,
        PageNodeRenderContext $nodeRenderContext,
        private PageSearchRenderContext $searchRenderContext,
        private PageTableRenderContext $tableRenderContext,
        private PageScriptRenderContext $scriptRenderContext,
    ) {
        parent::__construct($schema, $nodeRendererFactory, $nodeRenderContext);
    }

    public function nodeRendererFactory(): PageNodeRendererFactory
    {
        return parent::nodeRendererFactory();
    }

    public function nodeRenderContext(): PageNodeRenderContext
    {
        return parent::nodeRenderContext();
    }

    public function searchRenderContext(): PageSearchRenderContext
    {
        return $this->searchRenderContext;
    }

    public function tableRenderContext(): PageTableRenderContext
    {
        return $this->tableRenderContext;
    }

    public function scriptRenderContext(): PageScriptRenderContext
    {
        return $this->scriptRenderContext;
    }
}
