<?php

declare(strict_types=1);

namespace think\admin\builder\form;

/**
 * 表单字段定义器.
 * @class FormFields
 */
class FormFields
{
    public function __construct(private FormBuilder $builder, private FormNode $parent)
    {
    }

    public function field(array $field): FormField
    {
        return $this->builder->addFieldToNode($this->parent, $field);
    }

    public function text(string $name, string $title, string $subtitle = '', bool $required = false, string $remark = '', ?string $pattern = null, array $attrs = []): FormField
    {
        return $this->builder->addFieldToNode($this->parent, [
            'type' => $attrs['type'] ?? 'text',
            'name' => $name,
            'title' => $title,
            'subtitle' => $subtitle,
            'required' => $required,
            'remark' => $remark,
            'pattern' => $pattern,
            'attrs' => $attrs,
        ]);
    }

    public function textarea(string $name, string $title, string $subtitle = '', bool $required = false, string $remark = '', array $attrs = []): FormField
    {
        return $this->builder->addFieldToNode($this->parent, [
            'type' => 'textarea',
            'name' => $name,
            'title' => $title,
            'subtitle' => $subtitle,
            'required' => $required,
            'remark' => $remark,
            'attrs' => $attrs,
        ]);
    }

    public function password(string $name, string $title, string $subtitle = '', bool $required = false, string $remark = '', ?string $pattern = null, array $attrs = []): FormField
    {
        $attrs['type'] = 'password';
        return $this->text($name, $title, $subtitle, $required, $remark, $pattern, $attrs);
    }

    public function select(string $name, string $title, string $subtitle = '', bool $required = false, string $remark = '', array $options = [], string $variable = '', array $attrs = []): FormField
    {
        return $this->builder->addFieldToNode($this->parent, [
            'type' => 'select',
            'name' => $name,
            'title' => $title,
            'subtitle' => $subtitle,
            'required' => $required,
            'remark' => $remark,
            'options' => $options,
            'vname' => $variable,
            'attrs' => $attrs,
        ]);
    }

    public function checkbox(string $name, string $title, string $subtitle, string $variable, bool $required = false, array $attrs = []): FormField
    {
        return $this->builder->addFieldToNode($this->parent, [
            'type' => 'checkbox',
            'name' => $name,
            'title' => $title,
            'subtitle' => $subtitle,
            'required' => $required,
            'attrs' => $attrs,
            'vname' => $variable,
        ]);
    }

    public function radio(string $name, string $title, string $subtitle, string $variable, bool $required = false, array $attrs = []): FormField
    {
        return $this->builder->addFieldToNode($this->parent, [
            'type' => 'radio',
            'name' => $name,
            'title' => $title,
            'subtitle' => $subtitle,
            'required' => $required,
            'attrs' => $attrs,
            'vname' => $variable,
        ]);
    }

    public function image(string $name, string $title, string $subtitle = '', bool $required = false, array $attrs = []): FormField
    {
        return $this->builder->addFieldToNode($this->parent, [
            'type' => 'image',
            'name' => $name,
            'title' => $title,
            'subtitle' => $subtitle,
            'required' => $required,
            'attrs' => $attrs,
        ]);
    }

    public function video(string $name, string $title, string $subtitle = '', bool $required = false, array $attrs = []): FormField
    {
        return $this->builder->addFieldToNode($this->parent, [
            'type' => 'video',
            'name' => $name,
            'title' => $title,
            'subtitle' => $subtitle,
            'required' => $required,
            'attrs' => $attrs,
        ]);
    }

    public function images(string $name, string $title, string $subtitle = '', bool $required = false, array $attrs = []): FormField
    {
        return $this->builder->addFieldToNode($this->parent, [
            'type' => 'images',
            'name' => $name,
            'title' => $title,
            'subtitle' => $subtitle,
            'required' => $required,
            'attrs' => $attrs,
        ]);
    }
}
