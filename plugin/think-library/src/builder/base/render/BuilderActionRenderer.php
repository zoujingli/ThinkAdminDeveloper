<?php

declare(strict_types=1);

namespace think\admin\builder\base\render;

/**
 * Builder 动作节点渲染器.
 * @class BuilderActionRenderer
 */
class BuilderActionRenderer
{
    private BuilderAttributesRenderer $attributesRenderer;

    public function __construct(?BuilderAttributesRenderer $attributesRenderer = null)
    {
        $this->attributesRenderer = $attributesRenderer ?? new BuilderAttributesRenderer();
    }

    /**
     * @param array<string, mixed> $attrs
     */
    public function render(string $label, array $attrs = [], string $tag = 'a'): string
    {
        $tag = trim($tag) ?: 'a';
        $attrsHtml = $this->attributesRenderer->render($attrs);
        return sprintf('<%s%s>%s</%s>', $tag, $attrsHtml === '' ? '' : ' ' . $attrsHtml, $label, $tag);
    }
}
