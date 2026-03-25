<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

/**
 * 页面搜索提交字段渲染器.
 * @class PageSearchSubmitFieldRenderer
 */
class PageSearchSubmitFieldRenderer implements PageSearchFieldRendererInterface
{
    public function render(array $field, PageSearchRenderContext $context): string
    {
        $attrs = is_array($field['attrs'] ?? null) ? $field['attrs'] : [];
        $attrs['class'] = $context->mergeClass(
            strval($attrs['class'] ?? ''),
            trim('layui-btn layui-btn-primary ' . strval($field['class'] ?? ''))
        );
        $label = strval($field['label'] ?? '') ?: '搜 索';

        return '<div class="layui-form-item layui-inline"><button ' . $context->attrs($attrs) . '><i class="layui-icon">&#xe615;</i> ' . $context->escape($label) . '</button></div>';
    }
}
