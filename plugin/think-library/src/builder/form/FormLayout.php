<?php

declare(strict_types=1);

namespace think\admin\builder\form;

use think\admin\builder\base\BuilderAttributeBag;
use think\admin\builder\base\BuilderModule;

/**
 * 表单结构定义器.
 * @class FormLayout
 */
class FormLayout
extends FormNode
{
    public function __construct(FormBuilder $builder)
    {
        parent::__construct($builder, 'root', '');
    }

    public function action(string $url): self
    {
        $this->builder->setAction($url);
        return $this;
    }

    public function title(string $title): self
    {
        $this->builder->setTitle($title);
        return $this;
    }

    public function headerButton(string $name, string $type = 'button', string $confirm = '', array $attrs = [], string $class = ''): self
    {
        $this->builder->addHeaderButton($name, $type, $confirm, $attrs, $class);
        return $this;
    }

    public function headerHtml(string $html, array $schema = []): self
    {
        $this->builder->addHeaderButtonHtml($html, $schema);
        return $this;
    }

    public function variable(string $name): self
    {
        $this->builder->setVariable($name);
        return $this;
    }

    public function attrs(array $attrs): static
    {
        $this->builder->setFormAttrs($attrs);
        return $this;
    }

    public function attr(string $name, mixed $value = null): static
    {
        $this->builder->setFormAttr($name, $value);
        return $this;
    }

    public function attrsItem(): BuilderAttributeBag
    {
        return $this->builder->attachFormAttributes($this->builder->createFormAttributes());
    }

    public function bodyAttrs(array $attrs): static
    {
        $this->builder->setBodyAttrs($attrs);
        return $this;
    }

    public function bodyAttr(string $name, mixed $value = null): static
    {
        $this->builder->setBodyAttr($name, $value);
        return $this;
    }

    public function bodyAttrsItem(): BuilderAttributeBag
    {
        return $this->builder->attachBodyAttributes($this->builder->createBodyAttributes());
    }

    public function class(string|array $class): static
    {
        $this->builder->addFormClass($class);
        return $this;
    }

    public function bodyClass(string|array $class): static
    {
        $this->builder->addBodyClass($class);
        return $this;
    }

    public function data(string $name, mixed $value = null): static
    {
        $this->builder->setFormData($name, $value);
        return $this;
    }

    public function bodyData(string $name, mixed $value = null): static
    {
        $this->builder->setBodyData($name, $value);
        return $this;
    }

    public function module(string $name, array $config = []): static
    {
        $this->builder->attachFormModule($this->builder->createFormModule($name, $config));
        return $this;
    }

    public function moduleItem(string $name, array $config = []): BuilderModule
    {
        return $this->builder->attachFormModule($this->builder->createFormModule($name, $config));
    }

    public function script(string $script): self
    {
        $this->builder->addScript($script);
        return $this;
    }

    public function rules(array $rules): self
    {
        $this->builder->addValidateRules($rules);
        return $this;
    }

    public function rule(string $name, string $rule, string $message): self
    {
        $this->builder->addValidateRule($name, $rule, $message);
        return $this;
    }

    /**
     * @param callable(FormFields): void $callback
     */
    public function fields(callable $callback): static
    {
        parent::fields($callback);
        return $this;
    }

    /**
     * @param callable(FormActions): void $callback
     */
    public function actions(callable $callback): static
    {
        parent::actions($callback);
        return $this;
    }
}
