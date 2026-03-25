<?php

declare(strict_types=1);

namespace think\admin\builder\form;

/**
 * 表单按钮节点.
 * @class FormButton
 */
class FormButton extends FormNode
{
    /**
     * @param array<string, mixed> $button
     */
    public function __construct(FormBuilder $builder, private array $button, private string $buttonHtml)
    {
        parent::__construct($builder, 'button', '');
    }

    /**
     * 导出节点数组.
     * @return array<string, mixed>
     */
    public function export(): array
    {
        return [
            'type' => 'button',
            'button' => $this->button,
            'html' => $this->buttonHtml,
        ];
    }
}
