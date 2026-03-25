<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

use think\admin\builder\base\render\BuilderCallbackNodeRenderer;

/**
 * 页面搜索节点渲染器.
 * @class PageSearchNodeRenderer
 */
class PageSearchNodeRenderer extends BuilderCallbackNodeRenderer implements PageNodeRendererInterface
{
    public function render(array $node, PageNodeRenderContext $context): string
    {
        return $this->invoke([$context, 'renderSearch']);
    }
}
