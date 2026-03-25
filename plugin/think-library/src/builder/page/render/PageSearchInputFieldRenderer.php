<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

/**
 * 页面搜索输入字段渲染器.
 * @class PageSearchInputFieldRenderer
 */
class PageSearchInputFieldRenderer extends AbstractPageSearchFieldRenderer
{
    public function render(array $field, PageSearchRenderContext $context): string
    {
        $name = strval($field['name'] ?? '');
        $label = strval($field['label'] ?? '');
        $attrs = is_array($field['attrs'] ?? null) ? $field['attrs'] : [];
        $attrs['name'] = $name;
        $attrs['value'] = $attrs['value'] ?? $context->searchValue($name);
        if (!array_key_exists('placeholder', $attrs)) {
            $attrs['placeholder'] = $field['placeholder'] ?: ($label === '' ? '' : "请输入{$label}");
        }
        $attrs['class'] = $context->mergeClass(strval($attrs['class'] ?? ''), trim('layui-input ' . strval($field['class'] ?? '')));

        return $this->renderItem(
            $field,
            '<label class="layui-input-inline"><input ' . $context->attrs($attrs) . '></label>',
            $context
        );
    }
}
