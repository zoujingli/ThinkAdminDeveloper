<?php

declare(strict_types=1);

namespace think\admin\builder\form\render;

use think\admin\builder\base\render\InlineScriptRenderer;
use think\admin\builder\base\render\JsonScriptRenderer;
use think\admin\builder\page\render\PageHeaderRenderer;

/**
 * 表单根容器渲染器.
 * @class FormShellRenderer
 */
class FormShellRenderer
{
    /**
     * @param array<string, mixed> $attrs
     * @param array<int, array<string, mixed>> $content
     * @param array<int, string> $fields
     * @param array<int, string> $headerButtons
     * @param array<int, string> $buttons
     */
    public function render(
        array $attrs,
        array $bodyAttrs,
        array $content,
        array $fields,
        array $headerButtons,
        array $buttons,
        array $schema,
        array $scripts,
        FormNodeRenderContext $context
    ): string {
        $isPage = strval($schema['mode'] ?? '') === 'page';
        if ($isPage) {
            return $this->renderPageShell($attrs, $bodyAttrs, $content, $fields, $headerButtons, $buttons, $schema, $scripts, $context);
        }

        $html = sprintf('<form %s>', $context->attrs($attrs));
        $html .= "\n\t" . sprintf('<div %s>', $context->attrs($bodyAttrs));

        if (count($content) > 0) {
            $html .= $context->renderChildren($content);
        } else {
            $html .= join("\n", $fields);
            if (count($buttons) > 0) {
                $html .= "\n\t\t";
                $html .= (new FormActionBarRenderer())->render([
                    'type' => 'actions',
                    'attrs' => ['class' => 'layui-form-item text-center'],
                    'children' => array_map(static fn(string $button): array => ['type' => 'button', 'html' => $button], $buttons),
                ], $context);
            }
        }

        if ($schemaScript = (new JsonScriptRenderer())->render($schema, 'form-builder-schema')) {
            $html .= "\n\t\t" . $schemaScript;
        }
        $html .= "\n\t" . '</div>';

        return $html . "\n</form>" . (new InlineScriptRenderer())->render($scripts);
    }

    private function renderPageShell(
        array $attrs,
        array $bodyAttrs,
        array $content,
        array $fields,
        array $headerButtons,
        array $buttons,
        array $schema,
        array $scripts,
        FormNodeRenderContext $context
    ): string {
        $header = (new PageHeaderRenderer())->render(strval($schema['title'] ?? ''), $headerButtons);
        $form = sprintf('<form %s>', $context->attrs($attrs));
        $form .= "\n\t\t\t\t" . sprintf('<div %s>', $context->attrs($bodyAttrs));

        if (count($content) > 0) {
            $form .= "\n\t\t\t\t\t" . $context->renderChildren($content);
        } else {
            $form .= "\n\t\t\t\t\t" . join("\n", $fields);
            if (count($buttons) > 0) {
                $form .= "\n\t\t\t\t\t";
                $form .= (new FormActionBarRenderer())->render([
                    'type' => 'actions',
                    'attrs' => ['class' => 'layui-form-item text-center'],
                    'children' => array_map(static fn(string $button): array => ['type' => 'button', 'html' => $button], $buttons),
                ], $context);
            }
        }

        if ($schemaScript = (new JsonScriptRenderer())->render($schema, 'form-builder-schema')) {
            $form .= "\n\t\t\t\t\t" . $schemaScript;
        }

        $form .= "\n\t\t\t\t" . '</div>';
        $form .= "\n\t\t\t" . '</form>';
        $form .= (new InlineScriptRenderer())->render($scripts);

        $html = '<div class="layui-card" data-builder-scope="page">';
        if ($header !== '') {
            $html .= "\n\t" . $header;
            $html .= "\n\t" . '<div class="layui-card-line"></div>';
        }
        $html .= "\n\t" . '<div class="layui-card-body">';
        $html .= "\n\t\t" . '<div class="layui-card-table">';
        $html .= "\n\t\t\t" . '<div class="think-box-shadow">';
        $html .= "\n\t\t\t\t" . $form;
        $html .= "\n\t\t\t" . '</div>';
        $html .= "\n\t\t" . '</div>';
        $html .= "\n\t" . '</div>';
        return $html . "\n</div>";
    }
}
