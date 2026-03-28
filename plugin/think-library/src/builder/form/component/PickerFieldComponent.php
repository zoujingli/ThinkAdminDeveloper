<?php

declare(strict_types=1);

namespace think\admin\builder\form\component;

use think\admin\builder\form\FormNode;

/**
 * 选择器字段组件。
 * @class PickerFieldComponent
 */
class PickerFieldComponent extends AbstractFormComponent
{
    /**
     * @var array<string, mixed>
     */
    private array $config = [];

    /**
     * @param array<string, mixed> $config
     */
    public function config(array $config): static
    {
        $this->config = $this->mergeConfig($this->config, $config);
        return $this;
    }

    public function mount(FormNode $parent): FormNode
    {
        $node = $parent->node('label')->class(trim(strval($this->config['class'] ?? 'relative block')));
        foreach ((array)($this->config['attrs'] ?? []) as $name => $value) {
            $node->attr(strval($name), $value);
        }
        $label = $node->node('span')->class(trim(strval($this->config['label_class'] ?? 'help-label label-required-prev')));
        $title = trim(strval($this->config['title'] ?? ''));
        if ($title !== '') {
            $label->node('b')->html($this->escape($title));
        }
        $subtitle = trim(strval($this->config['subtitle'] ?? ''));
        if ($subtitle !== '') {
            $label->html($this->escape($title === '' ? $subtitle : " {$subtitle}"));
        }

        $hiddenName = trim(strval($this->config['hidden_name'] ?? ''));
        if ($hiddenName !== '') {
            $node->node('input')->attrs([
                'type' => 'hidden',
                'name' => $hiddenName,
                'value' => strval($this->config['hidden_value'] ?? ''),
            ]);
        }

        $input = $node->node('input')->attrs([
            'readonly' => null,
            'class' => trim(strval($this->config['input_class'] ?? 'layui-input')),
            'value' => strval($this->config['value'] ?? ''),
            'title' => strval($this->config['value'] ?? ''),
        ]);
        $inputName = trim(strval($this->config['name'] ?? ''));
        if ($inputName !== '') {
            $input->attr('name', $inputName);
        }
        foreach ((array)($this->config['input_attrs'] ?? []) as $name => $value) {
            $input->attr(strval($name), $value);
        }
        $icon = $node->node('span')->class(trim(strval($this->config['icon_class'] ?? 'input-right-icon layui-icon layui-icon-theme')));
        foreach ((array)($this->config['icon_attrs'] ?? []) as $name => $value) {
            $icon->attr(strval($name), $value);
        }
        $help = trim(strval($this->config['help'] ?? ''));
        if ($help !== '') {
            $node->div()->class(trim(strval($this->config['help_class'] ?? 'help-block color-desc')))->html($this->escape($help));
        }
        return $node;
    }
}
