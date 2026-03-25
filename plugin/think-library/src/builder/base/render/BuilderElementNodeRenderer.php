<?php

declare(strict_types=1);

namespace think\admin\builder\base\render;

/**
 * Builder 普通元素节点渲染基类.
 * @class BuilderElementNodeRenderer
 */
abstract class BuilderElementNodeRenderer
{
    /**
     * @param array<string, mixed> $node
     */
    protected function renderElement(array $node, BuilderNodeRenderContext $context): string
    {
        $tag = trim(strval($node['tag'] ?? 'div')) ?: 'div';
        $attrs = is_array($node['attrs'] ?? null) ? $node['attrs'] : [];
        $children = is_array($node['children'] ?? null) ? $node['children'] : [];
        return $this->wrapElement($tag, $attrs, $context->renderChildren($children), $context);
    }

    /**
     * @param array<string, mixed> $attrs
     */
    protected function wrapElement(string $tag, array $attrs, string $content, BuilderNodeRenderContext $context): string
    {
        $attrsHtml = count($attrs) > 0 ? ' ' . ltrim($context->attrs($attrs)) : '';
        return sprintf('<%s%s>%s</%s>', $tag, $attrsHtml, $content, $tag);
    }
}
