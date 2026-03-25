<?php

declare(strict_types=1);

namespace think\admin\builder\form;

/**
 * 表单动作条节点.
 * @class FormActionBar
 */
class FormActionBar extends FormNode
{
    public function __construct(FormBuilder $builder)
    {
        parent::__construct($builder, 'actions', 'div');
        $this->class('layui-form-item text-center');
    }
}
