<?php

declare(strict_types=1);

namespace think\admin\builder\form;

/**
 * 表单动作定义器.
 * @class FormActions
 */
class FormActions
{
    public function __construct(private FormBuilder $builder, private FormActionBar $parent)
    {
    }

    public function submit(string $name = '保存数据', string $confirm = '', array $attrs = [], string $class = ''): self
    {
        $this->builder->addSubmitButtonToNode($this->parent, $name, $confirm, $attrs, $class);
        return $this;
    }

    public function cancel(string $name = '取消编辑', string $confirm = '确定要取消编辑吗？', array $attrs = [], string $class = 'layui-btn-danger'): self
    {
        $this->builder->addCancelButtonToNode($this->parent, $name, $confirm, $attrs, $class);
        return $this;
    }

    public function button(string $name, string $type = 'button', string $confirm = '', array $attrs = [], string $class = ''): self
    {
        $this->builder->addActionButtonToNode($this->parent, $name, $type, $confirm, $attrs, $class);
        return $this;
    }

    public function html(string $html, array $schema = []): self
    {
        $this->builder->addButtonHtmlToNode($this->parent, $html, $schema);
        return $this;
    }
}
