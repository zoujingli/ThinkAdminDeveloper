<?php

declare(strict_types=1);

namespace think\admin\builder\form\component;

use think\admin\builder\form\FormNode;

/**
 * 表单组件接口。
 * @class FormComponentInterface
 */
interface FormComponentInterface
{
    public function mount(FormNode $parent): FormNode;
}
