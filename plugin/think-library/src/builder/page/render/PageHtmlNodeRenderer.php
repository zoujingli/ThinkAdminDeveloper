<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

use think\admin\builder\base\render\BuilderHtmlNodeRenderer;

/**
 * 页面 HTML 节点渲染器.
 * @class PageHtmlNodeRenderer
 */
class PageHtmlNodeRenderer extends BuilderHtmlNodeRenderer implements PageNodeRendererInterface
{
    public function render(array $node, PageNodeRenderContext $context): string
    {
        return $this->renderHtml($node);
    }
}
