<?php

declare(strict_types=1);

namespace think\admin\builder\form;

use think\admin\builder\base\BuilderOptionSource;

/**
 * 表单字段选项源对象.
 * @class FormFieldOptions
 */
class FormFieldOptions extends BuilderOptionSource
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(FormField $owner, array $options = [], string $source = '')
    {
        parent::__construct('vname', $options, $source, $owner);
    }
}
