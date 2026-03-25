<?php

declare(strict_types=1);

namespace think\admin\builder\base\render;

/**
 * Builder 节点渲染器工厂基类.
 * @class BuilderNodeRendererFactory
 */
abstract class BuilderNodeRendererFactory
{
    /**
     * @param array<string, mixed> $node
     */
    protected function resolveRendererClass(array $node): string
    {
        $type = strval($node['type'] ?? '');
        return $this->rendererMap()[$type] ?? $this->fallbackRendererClass();
    }

    /**
     * @return array<string, class-string>
     */
    abstract protected function rendererMap(): array;

    /**
     * @return class-string
     */
    abstract protected function fallbackRendererClass(): string;
}
