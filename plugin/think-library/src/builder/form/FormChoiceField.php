<?php

declare(strict_types=1);

namespace think\admin\builder\form;

/**
 * 单选/复选字段节点.
 * @class FormChoiceField
 */
class FormChoiceField extends FormField
{
    public function source(string $name): static
    {
        return $this->optionsItem()->source($name)->end();
    }
}
