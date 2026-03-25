<?php

declare(strict_types=1);

namespace think\admin\builder\form\render;

use think\admin\builder\base\render\BuilderAttributes;
use think\admin\builder\base\render\BuilderAttributesRenderContext;
use think\admin\builder\base\render\BuilderAttributesRenderer;

/**
 * 表单字段渲染上下文.
 * @class FormFieldRenderContext
 */
class FormFieldRenderContext
extends BuilderAttributesRenderContext
{
    private ?FormOptionRenderer $optionRenderer = null;

    /**
     * @param array<string, mixed> $field
     */
    public function __construct(private array $field, private string $variable)
    {
        parent::__construct([new BuilderAttributesRenderer(), 'render']);
    }

    /**
     * 获取字段配置.
     * @return array<string, mixed>
     */
    public function field(): array
    {
        return $this->field;
    }

    public function type(): string
    {
        return strval($this->field['type'] ?? 'text');
    }

    public function valueExpression(?string $name = null): string
    {
        return sprintf('{%s.%s|default=\'\'}', $this->variable, $this->valuePath($name));
    }

    public function joinedValueExpression(string $separator = '|', ?string $name = null): string
    {
        $path = $this->valuePath($name);
        $separator = addslashes($separator);
        return sprintf('{:%s.%s ?? null ? (is_array(%s.%s) ? join(\'%s\', %s.%s) : %s.%s) : \'\'}', $this->variable, $path, $this->variable, $path, $separator, $this->variable, $path, $this->variable, $path);
    }

    public function valuePath(?string $name = null): string
    {
        $name = $name === null ? strval($this->field['name'] ?? '') : $name;
        $name = preg_replace('/\[\]$/', '', $name) ?? $name;
        $name = str_replace(['][', '[', ']'], ['.', '.', ''], $name);
        $name = preg_replace('/\.{2,}/', '.', $name) ?? $name;
        return trim($name, '.');
    }

    public function variable(): string
    {
        return $this->variable;
    }

    public function openContainer(string $tag, string $class): string
    {
        $attrs = $this->buildFieldContainerAttrs($class);
        return sprintf('<%s %s>', $tag, ltrim($this->attrs($attrs)));
    }

    public function renderLabel(): string
    {
        $part = $this->resolveFieldPart('label');
        $attrs = BuilderAttributes::make($this->buildPartAttrs($part))
            ->class('help-label' . (!empty($this->field['required']) ? ' label-required-prev' : ''))
            ->all();
        $content = $part['content'] !== '' ? $part['content'] : sprintf('<b>%s</b>%s', $this->field['title'], $this->field['subtitle']);
        return sprintf('<span %s>%s</span>', $this->attrs($attrs), $content);
    }

    public function renderBody(string $content, string $class = '', bool $alwaysWrap = false): string
    {
        $part = $this->resolveFieldPart('body');
        if (!$alwaysWrap && $part['content'] === '' && count($part['attrs']) < 1 && count($part['modules']) < 1 && $class === '') {
            return $content;
        }
        if ($part['content'] !== '') {
            return $part['content'];
        }
        $attrs = BuilderAttributes::make($this->buildPartAttrs($part))->class($class)->all();
        return sprintf('<div %s>%s</div>', $this->attrs($attrs), $content);
    }

    public function renderRemark(): string
    {
        $part = $this->resolveFieldPart('remark');
        $content = $part['content'] !== '' ? $part['content'] : strval($this->field['remark'] ?? '');
        if ($content === '') {
            return '';
        }
        $attrs = BuilderAttributes::make($this->buildPartAttrs($part))->class('help-block')->all();
        return sprintf('<span %s>%s</span>', $this->attrs($attrs), $content);
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveInputAttrs(string $class = ''): array
    {
        $attrs = is_array($this->field['attrs'] ?? null) ? $this->field['attrs'] : [];
        return BuilderAttributes::make($attrs)
            ->merge($this->buildPartAttrs($this->resolveFieldPart('input')))
            ->class($class)
            ->all();
    }

    public function renderSelectOptions(): string
    {
        return $this->optionRenderer()->renderSelectOptions($this->field, $this);
    }

    /**
     * @param array<string, mixed> $attrs
     */
    public function renderChoiceOptions(array $attrs, ?string $type = null): string
    {
        $type = $type ?: $this->type();
        return $this->optionRenderer()->renderChoiceOptions($this->field, $this, $type, $attrs);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildFieldContainerAttrs(string $class): array
    {
        $modules = is_array($this->field['container_modules'] ?? null) ? $this->field['container_modules'] : [];
        return BuilderAttributes::make(is_array($this->field['container_attrs'] ?? null) ? $this->field['container_attrs'] : [])
            ->class($class)
            ->default('data-field-name', $this->field['name'])
            ->modules($modules)
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveFieldPart(string $name): array
    {
        $parts = is_array($this->field['parts'] ?? null) ? $this->field['parts'] : [];
        $part = is_array($parts[$name] ?? null) ? $parts[$name] : [];
        $part['attrs'] = is_array($part['attrs'] ?? null) ? $part['attrs'] : [];
        $part['modules'] = is_array($part['modules'] ?? null) ? $part['modules'] : [];
        $part['content'] = isset($part['content']) ? strval($part['content']) : '';
        return $part;
    }

    /**
     * @param array<string, mixed> $part
     * @param array<string, mixed> $attrs
     * @return array<string, mixed>
     */
    private function buildPartAttrs(array $part, array $attrs = []): array
    {
        $modules = is_array($part['modules'] ?? null) ? $part['modules'] : [];
        return BuilderAttributes::make($attrs)
            ->merge(is_array($part['attrs'] ?? null) ? $part['attrs'] : [])
            ->modules($modules)
            ->all();
    }

    private function optionRenderer(): FormOptionRenderer
    {
        return $this->optionRenderer ??= new FormOptionRenderer();
    }
}
