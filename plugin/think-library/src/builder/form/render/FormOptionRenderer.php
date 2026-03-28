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
        $default = $context->scalarDefaultLiteral();
        if (strval($field['vname'] ?? '') !== '') {
            $html = sprintf('{foreach $%s as $k=>$v}', $field['vname']);
            $html .= sprintf(
                '{if (isset(%s.%s) and strval(%s.%s) eq strval($k)) or (!isset(%s.%s) and strval($k) eq strval(%s))}<option selected value="{$k|default=\'\'}">{$v|default=\'\'}</option>{else}<option value="{$k|default=\'\'}">{$v|default=\'\'}</option>{/if}',
                $variable,
                $valuePath,
                $variable,
                $valuePath,
                $variable,
                $valuePath,
                $default
            );
            $html .= '{/foreach}';
            return $html;
        }

        $html = '';
        foreach ((array)($field['options'] ?? []) as $value => $label) {
            $value = $context->escape((string)$value);
            $label = $context->escape((string)$label);
            $html .= sprintf(
                '{if (isset(%s.%s) and strval(%s.%s) eq \'%s\') or (!isset(%s.%s) and strval(%s) eq \'%s\')}<option selected value="%s">%s</option>{else}<option value="%s">%s</option>{/if}',
                $variable,
                $valuePath,
                $variable,
                $valuePath,
                addslashes($value),
                $variable,
                $valuePath,
                $default,
                $value,
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
        if (strval($field['vname'] ?? '') !== '') {
            return $this->renderDynamicChoiceOptions($field, $context, $type, $attrs);
        }

        $html = '';
        foreach ((array)$field['options'] as $value => $label) {
            $html .= "\n\t\t\t\t" . sprintf('<label class="think-%s label-required-null">', $type);
            $html .= "\n\t\t\t\t\t" . $this->renderStaticChoiceCondition($context, (string)$value, $type);
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
        $html = "\n\t\t\t\t" . sprintf('<!--{foreach $%s as $k=>$v}item-->', $field['vname']);
        $html .= "\n\t\t\t\t" . sprintf('<label class="think-%s label-required-null">', $type);
        $html .= "\n\t\t\t\t\t" . $this->renderChoiceCondition($context, $type);
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
    private function renderChoiceCondition(FormFieldRenderContext $context, string $type): string
    {
        $valuePath = $context->valuePath();
        $variable = $context->variable();
        if ($type === 'checkbox') {
            $defaults = $context->arrayDefaultLiteral();
            return sprintf(
                '<!--if{if (isset(%s.%s) and is_array(%s.%s) and in_array($k,%s.%s)) or (!isset(%s.%s) and in_array($k,%s))}-->',
                $variable,
                $valuePath,
                $variable,
                $valuePath,
                $variable,
                $valuePath,
                $variable,
                $valuePath,
                $defaults
            );
        }
        return sprintf(
            '<!--if{if (isset(%s.%s) and strval($k)==strval(%s.%s)) or (!isset(%s.%s) and strval($k)==strval(%s))}-->',
            $variable,
            $valuePath,
            $variable,
            $valuePath,
            $variable,
            $valuePath,
            $context->scalarDefaultLiteral()
        );
    }

    private function renderStaticChoiceCondition(FormFieldRenderContext $context, string $value, string $type): string
    {
        $valuePath = $context->valuePath();
        $variable = $context->variable();
        $value = addslashes($value);
        if ($type === 'checkbox') {
            $defaults = $context->arrayDefaultLiteral();
            return sprintf(
                '<!--if{if (isset(%s.%s) and is_array(%s.%s) and in_array(\'%s\',%s.%s)) or (!isset(%s.%s) and in_array(\'%s\',%s))}-->',
                $variable,
                $valuePath,
                $variable,
                $valuePath,
                $value,
                $variable,
                $valuePath,
                $variable,
                $valuePath,
                $value,
                $defaults
            );
        }
        return sprintf(
            '<!--if{if (isset(%s.%s) and strval(\'%s\')==strval(%s.%s)) or (!isset(%s.%s) and strval(\'%s\')==strval(%s))}-->',
            $variable,
            $valuePath,
            $value,
            $variable,
            $valuePath,
            $variable,
            $valuePath,
            $value,
            $context->scalarDefaultLiteral()
        );
    }

}
