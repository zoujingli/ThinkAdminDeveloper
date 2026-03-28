<?php

declare(strict_types=1);

namespace think\admin\builder\form\component;

use think\admin\builder\form\FormNode;

/**
 * 表单只读字段组件。
 * @class ReadonlyFieldComponent
 */
class ReadonlyFieldComponent extends AbstractFormComponent
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
        $label = $node->node('span')->class(trim(strval($this->config['label_class'] ?? 'help-label label-required-prev')));
        $title = trim(strval($this->config['title'] ?? ''));
        if ($title !== '') {
            $label->node('b')->html($this->escape($title));
        }
        $subtitle = trim(strval($this->config['subtitle'] ?? ''));
        if ($subtitle !== '') {
            $label->html($this->escape($title === '' ? $subtitle : " {$subtitle}"));
        }
        $wrap = $node->node('label')->class(trim(strval($this->config['wrap_class'] ?? 'relative block')));
        $inputAttrs = [
            'readonly' => null,
            'class' => trim(strval($this->config['input_class'] ?? 'layui-input layui-bg-gray')),
            'value' => strval($this->config['value'] ?? ''),
        ];
        $inputName = trim(strval($this->config['name'] ?? ''));
        if ($inputName !== '') {
            $inputAttrs['name'] = $inputName;
        }
        $input = $wrap->node('input')->attrs($inputAttrs);
        foreach ((array)($this->config['input_attrs'] ?? []) as $name => $value) {
            $input->attr(strval($name), $value);
        }
        $copy = trim(strval($this->config['copy'] ?? ''));
        if ($copy !== '') {
            $wrap->node('a')->class(trim(strval($this->config['copy_class'] ?? 'layui-icon layui-icon-release input-right-icon')))->attr('data-copy', $copy);
        }
        $help = trim(strval($this->config['help'] ?? ''));
        if ($help !== '') {
            $node->div()->class(trim(strval($this->config['help_class'] ?? 'help-block color-desc')))->html($this->escape($help));
        }
        return $node;
    }
}
