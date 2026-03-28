<?php

declare(strict_types=1);

namespace think\admin\builder\page;

use think\admin\builder\BuilderLang;

/**
 * 页面搜索字段配置归一器.
 * @class PageSearchFieldNormalizer
 */
class PageSearchFieldNormalizer
{
    /**
     * @param array<string, mixed> $field
     * @return array<string, mixed>
     */
    public function field(array $field): array
    {
        $field = array_merge([
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

        $field['type'] = strtolower((string)$field['type']);
        $field['name'] = trim((string)$field['name']);
        $field['label'] = BuilderLang::text((string)$field['label']);
        $field['placeholder'] = BuilderLang::text((string)$field['placeholder']);
        $field['attrs'] = BuilderLang::attrs(is_array($field['attrs']) ? $field['attrs'] : []);
        $field['options'] = BuilderLang::options(is_array($field['options']) ? $field['options'] : []);
        $field['source'] = trim((string)$field['source']);
        $field['class'] = trim((string)$field['class']);
        $field['wrapClass'] = trim((string)$field['wrapClass']);

        return $field;
    }

    /**
     * @return array<string, mixed>
     */
    public function input(string $name, string $label, string $placeholder = '', array $attrs = []): array
    {
        return $this->field([
            'type' => 'input',
            'name' => $name,
            'label' => $label,
            'placeholder' => $placeholder,
            'attrs' => $attrs,
        ]);
    }

    /**
     * @param array<mixed> $options
     * @return array<string, mixed>
     */
    public function select(string $name, string $label, array $options = [], array $attrs = [], string $source = ''): array
    {
        return $this->field([
            'type' => 'select',
            'name' => $name,
            'label' => $label,
            'options' => $options,
            'attrs' => $attrs,
            'source' => $source,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function dateRange(string $name, string $label, string $placeholder = '', array $attrs = []): array
    {
        $attrs['data-date-range'] = $attrs['data-date-range'] ?? null;
        return $this->field([
            'type' => 'input',
            'name' => $name,
            'label' => $label,
            'placeholder' => $placeholder,
            'attrs' => $attrs,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function hidden(string $name, string $value = ''): array
    {
        return $this->field([
            'type' => 'hidden',
            'name' => $name,
            'attrs' => ['value' => $value],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function submit(string $label = '搜 索', array $attrs = []): array
    {
        return $this->field([
            'type' => 'submit',
            'label' => $label,
            'attrs' => $attrs,
        ]);
    }
}
