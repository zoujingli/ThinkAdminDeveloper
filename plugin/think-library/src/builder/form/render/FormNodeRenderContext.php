<?php

declare(strict_types=1);

namespace think\admin\builder\form\render;

use think\admin\builder\base\render\BuilderNodeRenderContext;

/**
 * 表单节点渲染上下文.
 * @class FormNodeRenderContext
 */
class FormNodeRenderContext extends BuilderNodeRenderContext
{
    private bool $identityRendered = false;

    /**
     * @param callable(array<int, array<string, mixed>>): string $contentRenderer
     * @param callable(array<string, mixed>): string $attrsRenderer
     * @param callable(array<string, mixed>): string $fieldRenderer
     */
    public function __construct(
        private string $variable,
        callable $contentRenderer,
        callable $attrsRenderer,
        private $fieldRenderer,
    ) {
        parent::__construct($contentRenderer, $attrsRenderer);
    }

    public function variable(): string
    {
        return $this->variable;
    }

    public function variableName(): string
    {
        return ltrim($this->variable, '$');
    }

    /**
     * @param array<string, mixed> $field
     */
    public function renderField(array $field): string
    {
        return ($this->fieldRenderer)($field);
    }

    public function renderIdentityField(): string
    {
        if ($this->identityRendered) {
            return '';
        }
        $this->identityRendered = true;
        return sprintf('{notempty name="%s.id"}<input type="hidden" value="{%s.id}" name="id">{/notempty}', $this->variableName(), $this->variable);
    }
}
