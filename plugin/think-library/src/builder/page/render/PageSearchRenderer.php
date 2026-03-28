<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

use think\admin\builder\BuilderLang;

/**
 * 页面搜索渲染器.
 * @class PageSearchRenderer
 */
class PageSearchRenderer
{
    /**
     * @param array<int, array<string, mixed>> $fields
     * @param array<string, mixed> $attrs
     */
    public function render(
        array $fields,
        array $attrs,
        string $tableId,
        string $action,
        string $legend,
        bool $legendEnabled,
        PageSearchRenderContext $context
    ): string {
        if (count($fields) < 1) {
            return '';
        }

        $attrs = array_merge([
            'action' => $action,
            'data-table-id' => $tableId,
            'autocomplete' => 'off',
            'method' => 'get',
            'onsubmit' => 'return false',
        ], $attrs);
        $attrs['class'] = $context->mergeClass(strval($attrs['class'] ?? ''), 'layui-form layui-form-pane form-search');

        $items = [];
        $hasSubmit = false;
        $factory = new PageSearchFieldRendererFactory();
        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }
            $field = $this->normalizeField($field);
            $items[] = $factory->create($field)->render($field, $context);
            $hasSubmit = $hasSubmit || strval($field['type'] ?? '') === 'submit';
        }
        if (!$hasSubmit) {
            $field = $this->normalizeField(['type' => 'submit', 'label' => BuilderLang::text('搜 索'), 'attrs' => []]);
            $items[] = $factory->create($field)->render($field, $context);
        }

        $html = '';
        if ($legendEnabled) {
            $html .= '<fieldset><legend>' . $context->escape(BuilderLang::text($legend)) . '</legend>';
        }
        $html .= sprintf('<form %s>', $context->attrs($attrs));
        $html .= "\n\t" . join("\n\t", array_filter($items));
        $html .= "\n</form>";
        if ($legendEnabled) {
            $html .= '</fieldset>';
        }
        return $html;
    }

    /**
     * @param array<string, mixed> $field
     * @return array<string, mixed>
     */
    private function normalizeField(array $field): array
    {
        return array_merge([
            'type' => 'input',
            'name' => '',
            'label' => '',
            'placeholder' => '',
            'attrs' => [],
            'options' => [],
            'source' => '',
            'class' => '',
            'wrapClass' => '',
        ], $field);
    }
}
