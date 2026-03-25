<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

/**
 * 页面搜索字段渲染基类.
 * @class AbstractPageSearchFieldRenderer
 */
abstract class AbstractPageSearchFieldRenderer implements PageSearchFieldRendererInterface
{
    /**
     * @param array<string, mixed> $field
     */
    protected function renderItem(array $field, string $control, PageSearchRenderContext $context): string
    {
        $wrapClass = trim(sprintf('layui-form-item layui-inline %s', strval($field['wrapClass'] ?? '')));
        $html = sprintf('<div class="%s">', $context->escape($wrapClass));
        if (($label = strval($field['label'] ?? '')) !== '') {
            $html .= '<label class="layui-form-label">' . $context->escape($label) . '</label>';
        }
        return $html . $control . '</div>';
    }
}
