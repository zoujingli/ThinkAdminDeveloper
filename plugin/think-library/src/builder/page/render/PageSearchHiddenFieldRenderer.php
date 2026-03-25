<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

/**
 * 页面搜索隐藏字段渲染器.
 * @class PageSearchHiddenFieldRenderer
 */
class PageSearchHiddenFieldRenderer implements PageSearchFieldRendererInterface
{
    public function render(array $field, PageSearchRenderContext $context): string
    {
        $name = strval($field['name'] ?? '');
        $attrs = is_array($field['attrs'] ?? null) ? $field['attrs'] : [];
        $attrs['type'] = 'hidden';
        $attrs['name'] = $name;
        $attrs['value'] = $attrs['value'] ?? $context->searchValue($name);
        return sprintf('<input %s>', $context->attrs($attrs));
    }
}
