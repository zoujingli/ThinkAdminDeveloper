<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

use think\admin\builder\base\render\BuilderElementNodeRenderer;

/**
 * 页面普通元素节点渲染器.
 * @class PageElementNodeRenderer
 */
class PageElementNodeRenderer extends BuilderElementNodeRenderer implements PageNodeRendererInterface
{
    public function render(array $node, PageNodeRenderContext $context): string
    {
        return $this->renderElement($node, $context);
    }
}
