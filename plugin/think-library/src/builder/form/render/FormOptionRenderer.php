<?php

declare(strict_types=1);

namespace think\admin\builder\form\render;

/**
 * 表单选项模板渲染器.
 * @class FormOptionRenderer
 */
class FormOptionRenderer
{
    /**
     * @param array<string, mixed> $field
     */
    public function renderSelectOptions(array $field, FormFieldRenderContext $context): string
    {
        $variable = $context->variable();
        $valuePath = $context->valuePath();
        if (strval($field['vname'] ?? '') !== '') {
            $html = sprintf('{foreach $%s as $k=>$v}', $field['vname']);
            $html .= sprintf(
                '{if isset(%s.%s) and strval(%s.%s) eq strval($k)}<option selected value="{$k|default=\'\'}">{$v|default=\'\'}</option>{else}<option value="{$k|default=\'\'}">{$v|default=\'\'}</option>{/if}',
                $variable,
                $valuePath,
                $variable,
                $valuePath
            );
            $html .= '{/foreach}';
            return $html;
        }

        $html = '';
        foreach ((array)($field['options'] ?? []) as $value => $label) {
            $value = $context->escape((string)$value);
            $label = $context->escape((string)$label);
            $html .= sprintf(
                '{if isset(%s.%s) and strval(%s.%s) eq \'%s\'}<option selected value="%s">%s</option>{else}<option value="%s">%s</option>{/if}',
                $variable,
                $valuePath,
                $variable,
                $valuePath,
                addslashes($value),
                $value,
                $label,
                $value,
                $label
            );
        }
        return $html;
    }

    /**
     * @param array<string, mixed> $field
     * @param array<string, mixed> $attrs
     */
    public function renderChoiceOptions(array $field, FormFieldRenderContext $context, string $type, array $attrs): string
    {
        $variable = $context->variable();
        $valuePath = $context->valuePath();
        if (strval($field['vname'] ?? '') !== '') {
            return $this->renderDynamicChoiceOptions($field, $context, $type, $attrs);
        }

        $html = '';
        foreach ((array)$field['options'] as $value => $label) {
            $html .= "\n\t\t\t\t" . sprintf('<label class="think-%s label-required-null">', $type);
            $html .= "\n\t\t\t\t\t" . $this->renderStaticChoiceCondition($valuePath, $variable, (string)$value, $type);
            $html .= "\n\t\t\t\t\t" . sprintf(
                '<input value="%s" %s checked> %s',
                $context->escape((string)$value),
                $context->attrs($attrs),
                $context->escape((string)$label)
            );
            $html .= "\n\t\t\t\t\t" . '<!--{else}else-->';
            $html .= "\n\t\t\t\t\t" . sprintf(
                '<input value="%s" %s> %s',
                $context->escape((string)$value),
                $context->attrs($attrs),
                $context->escape((string)$label)
            );
            $html .= "\n\t\t\t\t\t" . '<!--{/if}if-->';
            $html .= "\n\t\t\t\t" . '</label>';
        }
        return $html;
    }

    /**
     * @param array<string, mixed> $field
     * @param array<string, mixed> $attrs
     */
    private function renderDynamicChoiceOptions(array $field, FormFieldRenderContext $context, string $type, array $attrs): string
    {
        $variable = $context->variable();
        $valuePath = $context->valuePath();
        $html = "\n\t\t\t\t" . sprintf('<!--{foreach $%s as $k=>$v}item-->', $field['vname']);
        $html .= "\n\t\t\t\t" . sprintf('<label class="think-%s label-required-null">', $type);
        $html .= "\n\t\t\t\t\t" . $this->renderChoiceCondition($valuePath, $variable, $type);
        $html .= "\n\t\t\t\t\t" . sprintf('<input value="{$k|default=\'\'}" %s checked> {$v|default=\'\'}', $context->attrs($attrs));
        $html .= "\n\t\t\t\t\t" . '<!--{else}else-->';
        $html .= "\n\t\t\t\t\t" . sprintf('<input value="{$k|default=\'\'}" %s> {$v|default=\'\'}', $context->attrs($attrs));
        $html .= "\n\t\t\t\t\t" . '<!--{/if}if-->';
        $html .= "\n\t\t\t\t" . '</label>';
        $html .= "\n\t\t\t\t" . '<!--{/foreach}end-->';
        return $html;
    }

    /**
     */
    private function renderChoiceCondition(string $valuePath, string $variable, string $type): string
    {
        if ($type === 'checkbox') {
            return sprintf(
                '<!--if{if isset(%s.%s) and is_array(%s.%s) and in_array($k,%s.%s)}-->',
                $variable,
                $valuePath,
                $variable,
                $valuePath,
                $variable,
                $valuePath
            );
        }
        return sprintf(
            '<!--if{if isset(%s.%s) and strval($k)==strval(%s.%s)}-->',
            $variable,
            $valuePath,
            $variable,
            $valuePath
        );
    }

    private function renderStaticChoiceCondition(string $valuePath, string $variable, string $value, string $type): string
    {
        $value = addslashes($value);
        if ($type === 'checkbox') {
            return sprintf(
                '<!--if{if isset(%s.%s) and is_array(%s.%s) and in_array(\'%s\',%s.%s)}-->',
                $variable,
                $valuePath,
                $variable,
                $valuePath,
                $value,
                $variable,
                $valuePath
            );
        }
        return sprintf(
            '<!--if{if isset(%s.%s) and strval(\'%s\')==strval(%s.%s)}-->',
            $variable,
            $valuePath,
            $value,
            $variable,
            $valuePath
        );
    }

}
