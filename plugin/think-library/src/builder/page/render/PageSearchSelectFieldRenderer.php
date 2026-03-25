<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

/**
 * 页面搜索下拉字段渲染器.
 * @class PageSearchSelectFieldRenderer
 */
class PageSearchSelectFieldRenderer extends AbstractPageSearchFieldRenderer
{
    public function render(array $field, PageSearchRenderContext $context): string
    {
        $attrs = is_array($field['attrs'] ?? null) ? $field['attrs'] : [];
        $attrs['name'] = strval($field['name'] ?? '');
        $attrs['class'] = $context->mergeClass(strval($attrs['class'] ?? ''), trim('layui-select ' . strval($field['class'] ?? '')));

        $control = '<div class="layui-input-inline"><select ' . $context->attrs($attrs) . '>';
        $control .= '<option value="">-- 全部 --</option>';
        $control .= $this->renderOptions($field, $context);
        $control .= '</select></div>';

        return $this->renderItem($field, $control, $context);
    }

    /**
     * @param array<string, mixed> $field
     */
    private function renderOptions(array $field, PageSearchRenderContext $context): string
    {
        $html = '';
        $current = $context->searchValue(strval($field['name'] ?? ''));
        foreach ($context->resolveOptions($field) as $value => $label) {
            $value = strval($value);
            $selected = $current !== '' && $current === $value ? ' selected' : '';
            $html .= sprintf(
                '<option%s value="%s">%s</option>',
                $selected,
                $context->escape($value),
                $context->escape(strval($label))
            );
        }
        return $html;
    }
}
