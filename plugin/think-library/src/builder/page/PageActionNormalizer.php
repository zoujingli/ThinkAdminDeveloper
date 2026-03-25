<?php

declare(strict_types=1);

namespace think\admin\builder\page;

use think\admin\builder\base\render\BuilderActionRenderer;
use think\admin\builder\base\render\BuilderAttributes;

/**
 * 页面动作配置归一器.
 * @class PageActionNormalizer
 */
class PageActionNormalizer
{
    private BuilderActionRenderer $renderer;

    public function __construct(private string $tableId = 'PageDataTable', ?BuilderActionRenderer $renderer = null)
    {
        $this->renderer = $renderer ?? new BuilderActionRenderer();
    }

    /**
     * @param array<string, mixed> $action
     * @return array<string, mixed>
     */
    public function button(array $action): array
    {
        $action = $this->normalizeBase($action);
        if ($action['type'] === 'html') {
            return $action;
        }

        $attrs = match ($action['type']) {
            'modal' => $this->buildAttrs($action['attrs'], 'layui-btn layui-btn-sm layui-btn-primary', [
                'data-modal' => $action['url'],
            ], [
                'data-title' => $action['title'],
            ]),
            'open' => $this->buildAttrs($action['attrs'], 'layui-btn layui-btn-sm layui-btn-primary', [
                'data-open' => $action['url'],
            ]),
            'load' => $this->buildAttrs($action['attrs'], 'layui-btn layui-btn-sm layui-btn-primary', [
                'data-load' => $action['url'],
                'data-table-id' => strval($action['attrs']['data-table-id'] ?? $this->tableId),
            ]),
            'action' => $this->buildAttrs($action['attrs'], 'layui-btn layui-btn-sm layui-btn-primary', [
                'data-action' => $action['url'],
            ], [
                'data-value' => $action['value'],
                'data-confirm' => $action['confirm'],
            ]),
            'batch-action' => $this->buildAttrs($action['attrs'], 'layui-btn layui-btn-sm layui-btn-primary', [
                'data-table-id' => $this->tableId,
                'data-action' => $action['url'],
                'data-rule' => $action['rule'],
            ], [
                'data-confirm' => $action['confirm'],
            ]),
            default => throw new \InvalidArgumentException("PageBuilder 按钮类型无效: {$action['type']}"),
        };

        return array_merge($action, [
            'attrs' => $attrs,
            'html' => $this->renderer->render(strval($action['label']), $attrs),
        ]);
    }

    /**
     * @param array<string, mixed> $action
     * @return array<string, mixed>
     */
    public function row(array $action): array
    {
        $action = $this->normalizeBase($action);
        if ($action['type'] === 'html') {
            return $action;
        }

        $attrs = match ($action['type']) {
            'modal' => $this->buildAttrs($action['attrs'], 'layui-btn layui-btn-sm', [
                'data-modal' => $action['url'],
            ], [
                'data-title' => $action['title'],
            ]),
            'open' => $this->buildAttrs($action['attrs'], 'layui-btn layui-btn-sm', [
                'data-open' => $action['url'],
            ], [
                'data-title' => $action['title'],
            ]),
            'action' => $this->buildAttrs($action['attrs'], 'layui-btn layui-btn-sm layui-btn-danger', [
                'data-action' => $action['url'],
                'data-value' => $action['value'] === '' ? 'id#{{d.id}}' : $action['value'],
            ], [
                'data-confirm' => $action['confirm'],
            ]),
            default => throw new \InvalidArgumentException("PageBuilder 行操作类型无效: {$action['type']}"),
        };

        return array_merge($action, [
            'attrs' => $attrs,
            'html' => $this->renderer->render(strval($action['label']), $attrs),
        ]);
    }

    /**
     * @param array<string, mixed> $action
     * @return array<string, mixed>
     */
    private function normalizeBase(array $action): array
    {
        $action = array_merge([
            'type' => '',
            'label' => '',
            'url' => '',
            'title' => '',
            'value' => '',
            'confirm' => '',
            'rule' => '',
            'auth' => null,
            'html' => '',
            'attrs' => [],
            'class' => '',
        ], $action);

        $auth = $action['auth'];
        $action['type'] = trim(strval($action['type']));
        $action['label'] = strval($action['label']);
        $action['url'] = strval($action['url']);
        $action['title'] = strval($action['title']);
        $action['value'] = strval($action['value']);
        $action['confirm'] = strval($action['confirm']);
        $action['rule'] = strval($action['rule']);
        $action['html'] = strval($action['html']);
        $action['attrs'] = is_array($action['attrs']) ? $action['attrs'] : [];
        $action['auth'] = $auth === null ? null : (trim(strval($auth)) ?: null);

        $class = trim(strval($action['class']));
        if ($class !== '') {
            $action['attrs']['class'] = BuilderAttributes::mergeClassNames(strval($action['attrs']['class'] ?? ''), $class);
        }

        return $action;
    }

    /**
     * @param array<string, mixed> $attrs
     * @param array<string, mixed> $fixed
     * @param array<string, mixed> $optional
     * @return array<string, mixed>
     */
    private function buildAttrs(array $attrs, string $class, array $fixed = [], array $optional = []): array
    {
        $attrs = BuilderAttributes::make($attrs)->class($class)->all();
        foreach ($fixed as $name => $value) {
            $attrs[$name] = $value;
        }
        foreach ($optional as $name => $value) {
            if ($value !== '') {
                $attrs[$name] = $value;
            }
        }
        return $attrs;
    }
}
