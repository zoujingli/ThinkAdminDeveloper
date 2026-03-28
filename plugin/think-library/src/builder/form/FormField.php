<?php

declare(strict_types=1);

namespace think\admin\builder\form;

/**
 * 表单字段节点.
 * @class FormField
 */
class FormField extends FormNode
{
    /**
     * 字段子节点.
     * @var array<string, FormFieldPart>
     */
    private array $parts = [];

    /**
     * @param array<string, mixed> $field
     */
    public function __construct(FormBuilder $builder, private FormNode $parent, protected array $field)
    {
        parent::__construct($builder, 'field', '');
    }

    public function name(string $name): static
    {
        $name = trim($name);
        if ($name !== '') {
            $this->field['name'] = $name;
        }
        return $this;
    }

    public function title(string $title): static
    {
        $this->field['title'] = trim($title);
        return $this;
    }

    public function subtitle(string $subtitle): static
    {
        $this->field['subtitle'] = $subtitle;
        return $this;
    }

    public function remark(string $remark): static
    {
        $this->field['remark'] = $remark;
        return $this;
    }

    public function variable(string $name): static
    {
        $this->field['vname'] = trim($name);
        return $this;
    }

    public function options(array $options): static
    {
        return $this->optionsItem()->options($options)->end();
    }

    public function option(string|int $value, string $label): static
    {
        return $this->optionsItem()->option($value, $label)->end();
    }

    public function optionsItem(): FormFieldOptions
    {
        $options = is_array($this->field['options'] ?? null) ? $this->field['options'] : [];
        $source = trim(strval($this->field['vname'] ?? ''));
        return (new FormFieldOptions($this, $options, $source))
            ->attach(fn(array $state): array => $this->replaceOptionsState($state));
    }

    public function required(bool $required = true, string $message = ''): static
    {
        $this->field['required'] = $required;
        if ($message !== '') {
            $this->setFieldAttr('required-error', $message);
        } elseif (!$required) {
            $this->unsetFieldAttr('required-error');
        }
        return $this;
    }

    public function pattern(?string $pattern, string $message = ''): static
    {
        $this->field['pattern'] = $pattern === null || trim($pattern) === '' ? null : trim($pattern);
        if ($message !== '') {
            $this->setFieldAttr('pattern-error', $message);
        } elseif ($this->field['pattern'] === null) {
            $this->unsetFieldAttr('pattern-error');
        }
        return $this;
    }

    public function rule(string $rule, string $message): static
    {
        $rule = trim($rule);
        if ($rule !== '') {
            $this->field['rules'] = is_array($this->field['rules'] ?? null) ? $this->field['rules'] : [];
            $this->field['rules'][$rule] = $message;
        }
        return $this;
    }

    public function rules(array $rules): static
    {
        foreach ($rules as $rule => $message) {
            if (is_string($rule)) {
                $this->rule($rule, strval($message));
            }
        }
        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        return $this->setFieldAttr('placeholder', $placeholder);
    }

    public function defaultValue(mixed $value): static
    {
        $this->field['default'] = $value;
        return $this;
    }

    public function readonly(bool $readonly = true): static
    {
        return $readonly ? $this->setFieldAttr('readonly', null) : $this->unsetFieldAttr('readonly');
    }

    public function disabled(bool $disabled = true): static
    {
        return $disabled ? $this->setFieldAttr('disabled', null) : $this->unsetFieldAttr('disabled');
    }

    public function label(?callable $callback = null): FormFieldPart
    {
        return $this->part('label', $callback);
    }

    public function input(?callable $callback = null): FormFieldPart
    {
        return $this->part('input', $callback);
    }

    public function control(?callable $callback = null): FormFieldPart
    {
        return $this->part('input', $callback);
    }

    public function body(?callable $callback = null): FormFieldPart
    {
        return $this->part('body', $callback);
    }

    public function remarkNode(?callable $callback = null): FormFieldPart
    {
        return $this->part('remark', $callback);
    }

    public function field(array $field): self
    {
        return $this->builder->addFieldToNode($this->parent, $field);
    }

    public function text(string $name, string $title, string $subtitle = '', bool $required = false, string $remark = '', ?string $pattern = null, array $attrs = []): self
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

    public function textarea(string $name, string $title, string $subtitle = '', bool $required = false, string $remark = '', array $attrs = []): self
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

    public function password(string $name, string $title, string $subtitle = '', bool $required = false, string $remark = '', ?string $pattern = null, array $attrs = []): self
    {
        $attrs['type'] = 'password';
        return $this->text($name, $title, $subtitle, $required, $remark, $pattern, $attrs);
    }

    public function select(string $name, string $title, string $subtitle = '', bool $required = false, string $remark = '', array $options = [], string $variable = '', array $attrs = []): self
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

    public function checkbox(string $name, string $title, string $subtitle, string $variable, bool $required = false, array $attrs = []): self
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

    public function radio(string $name, string $title, string $subtitle, string $variable, bool $required = false, array $attrs = []): self
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

    public function image(string $name, string $title, string $subtitle = '', bool $required = false, array $attrs = []): self
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

    public function video(string $name, string $title, string $subtitle = '', bool $required = false, array $attrs = []): self
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

    public function images(string $name, string $title, string $subtitle = '', bool $required = false, array $attrs = []): self
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

    /**
     * 导出节点数组.
     * @return array<string, mixed>
     */
    public function export(): array
    {
        $parts = [];
        foreach ($this->parts as $name => $part) {
            if ($part->configured()) {
                $parts[$name] = $part->export();
            }
        }

        return [
            'type' => 'field',
            'attrs' => $this->buildAttrs(),
            'modules' => $this->modules,
            'parts' => $parts,
            'field' => $this->field,
        ];
    }

    private function part(string $name, ?callable $callback = null): FormFieldPart
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('FormField 子节点名称不能为空');
        }
        $part = $this->parts[$name] ?? new FormFieldPart($this, $name);
        $this->parts[$name] = $part;
        if (is_callable($callback)) {
            $callback($part);
        }
        return $part;
    }

    protected function setFieldAttr(string $name, mixed $value = null): static
    {
        $name = trim($name);
        if ($name !== '') {
            $attrs = is_array($this->field['attrs'] ?? null) ? $this->field['attrs'] : [];
            $attrs[$name] = $value;
            $this->field['attrs'] = $attrs;
        }
        return $this;
    }

    protected function unsetFieldAttr(string $name): static
    {
        $name = trim($name);
        if ($name !== '' && is_array($this->field['attrs'] ?? null) && array_key_exists($name, $this->field['attrs'])) {
            unset($this->field['attrs'][$name]);
        }
        return $this;
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function replaceOptionsState(array $state): array
    {
        $this->field['options'] = is_array($state['options'] ?? null) ? $state['options'] : [];
        $this->field['vname'] = trim(strval($state['vname'] ?? ''));
        return [
            'options' => is_array($this->field['options'] ?? null) ? $this->field['options'] : [],
            'vname' => trim(strval($this->field['vname'] ?? '')),
        ];
    }
}
