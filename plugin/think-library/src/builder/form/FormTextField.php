<?php

declare(strict_types=1);

namespace think\admin\builder\form;

/**
 * 文本类字段节点.
 * @class FormTextField
 */
class FormTextField extends FormField
{
    public function maxlength(int $length): static
    {
        return $this->setFieldAttr('maxlength', $length);
    }

    public function minlength(int $length): static
    {
        return $this->setFieldAttr('minlength', $length);
    }
}
