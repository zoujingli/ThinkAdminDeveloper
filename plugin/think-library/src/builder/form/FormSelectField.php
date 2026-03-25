<?php

declare(strict_types=1);

namespace think\admin\builder\form;

/**
 * 下拉字段节点.
 * @class FormSelectField
 */
class FormSelectField extends FormField
{
    public function source(string $name): static
    {
        return $this->optionsItem()->source($name)->end();
    }

    public function search(bool $enabled = true): static
    {
        return $enabled ? $this->setFieldAttr('lay-search', null) : $this->unsetFieldAttr('lay-search');
    }
}
