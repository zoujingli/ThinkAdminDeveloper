<?php

declare(strict_types=1);

namespace think\admin\builder\form\component;

use think\admin\builder\form\FormNode;

/**
 * 表单说明组件。
 * @class NoteComponent
 */
class NoteComponent extends AbstractFormComponent
{
    private string $text = '';
    private string $class = 'help-block color-desc';

    public function text(string $text): static
    {
        $this->text = $text;
        return $this;
    }

    public function class(string $class): static
    {
        $this->class = trim($class) ?: $this->class;
        return $this;
    }

    public function mount(FormNode $parent): FormNode
    {
        return $parent->div()->class($this->class)->html($this->escape($this->text));
    }
}
