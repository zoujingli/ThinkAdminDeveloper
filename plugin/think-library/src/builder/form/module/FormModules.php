<?php

declare(strict_types=1);

namespace think\admin\builder\form\module;

use think\admin\builder\form\FormNode;

/**
 * 表单通用模块。
 * @class FormModules
 */
class FormModules
{
    /**
     * @param array<string, mixed> $config
     */
    public static function intro(FormNode $parent, array $config = []): FormNode
    {
        $node = $parent->section()->class(trim(strval($config['class'] ?? 'layui-card mb15')));
        $body = $node->div()->class(trim(strval($config['body_class'] ?? 'layui-card-body')));
        $eyebrow = trim(strval($config['eyebrow'] ?? ''));
        if ($eyebrow !== '') {
            $body->div()->class(trim(strval($config['eyebrow_class'] ?? 'color-desc fs12')))->html(self::escape($eyebrow));
        }
        $title = trim(strval($config['title'] ?? ''));
        if ($title !== '') {
            $body->node('h2')->class(trim(strval($config['title_class'] ?? 'mt10 mb10')))->html(self::escape($title));
        }
        $description = trim(strval($config['description'] ?? ''));
        if ($description !== '') {
            $body->div()->class(trim(strval($config['description_class'] ?? 'color-desc lh24')))->html(self::escape($description));
        }
        return $node;
    }

    /**
     * @param array<string, mixed> $config
     * @param callable(FormNode): void $callback
     */
    public static function section(FormNode $parent, array $config, callable $callback): FormNode
    {
        $node = $parent->section()->class(trim(strval($config['class'] ?? 'mb20')));
        $head = $node->div()->class(trim(strval($config['head_class'] ?? 'mb15')));
        $title = trim(strval($config['title'] ?? ''));
        if ($title !== '') {
            $head->node('h3')->class(trim(strval($config['title_class'] ?? 'mb5')))->html(self::escape($title));
        }
        $description = trim(strval($config['description'] ?? ''));
        if ($description !== '') {
            $head->node('p')->class(trim(strval($config['description_class'] ?? 'color-desc lh24')))->html(self::escape($description));
        }
        $body = $node->div()->class(trim(strval($config['body_class'] ?? '')));
        $callback($body);
        return $node;
    }

    public static function note(FormNode $parent, string $text, string $class = 'help-block color-desc'): FormNode
    {
        return $parent->div()->class($class)->html(self::escape($text));
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function readonlyField(FormNode $parent, array $config = []): FormNode
    {
        $node = $parent->node('label')->class(trim(strval($config['class'] ?? 'relative block')));
        $label = $node->node('span')->class(trim(strval($config['label_class'] ?? 'help-label label-required-prev')));
        $title = trim(strval($config['title'] ?? ''));
        if ($title !== '') {
            $label->node('b')->html(self::escape($title));
        }
        $subtitle = trim(strval($config['subtitle'] ?? ''));
        if ($subtitle !== '') {
            $label->html(($title === '' ? '' : '') . self::escape($title === '' ? $subtitle : " {$subtitle}"));
        }
        $wrap = $node->node('label')->class(trim(strval($config['wrap_class'] ?? 'relative block')));
        $inputAttrs = [
            'readonly' => null,
            'class' => trim(strval($config['input_class'] ?? 'layui-input layui-bg-gray')),
            'value' => strval($config['value'] ?? ''),
        ];
        $inputName = trim(strval($config['name'] ?? ''));
        if ($inputName !== '') {
            $inputAttrs['name'] = $inputName;
        }
        $input = $wrap->node('input')->attrs($inputAttrs);
        foreach ((array)($config['input_attrs'] ?? []) as $name => $value) {
            $input->attr(strval($name), $value);
        }
        $copy = trim(strval($config['copy'] ?? ''));
        if ($copy !== '') {
            $wrap->node('a')->class(trim(strval($config['copy_class'] ?? 'layui-icon layui-icon-release input-right-icon')))->attr('data-copy', $copy);
        }
        $help = trim(strval($config['help'] ?? ''));
        if ($help !== '') {
            self::note($node, $help);
        }
        return $node;
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function pickerField(FormNode $parent, array $config = []): FormNode
    {
        $node = $parent->node('label')->class(trim(strval($config['class'] ?? 'relative block')));
        foreach ((array)($config['attrs'] ?? []) as $name => $value) {
            $node->attr(strval($name), $value);
        }
        $label = $node->node('span')->class(trim(strval($config['label_class'] ?? 'help-label label-required-prev')));
        $title = trim(strval($config['title'] ?? ''));
        if ($title !== '') {
            $label->node('b')->html(self::escape($title));
        }
        $subtitle = trim(strval($config['subtitle'] ?? ''));
        if ($subtitle !== '') {
            $label->html(($title === '' ? '' : '') . self::escape($title === '' ? $subtitle : " {$subtitle}"));
        }

        $hiddenName = trim(strval($config['hidden_name'] ?? ''));
        if ($hiddenName !== '') {
            $node->node('input')->attrs([
                'type' => 'hidden',
                'name' => $hiddenName,
                'value' => strval($config['hidden_value'] ?? ''),
            ]);
        }

        $input = $node->node('input')->attrs([
            'readonly' => null,
            'class' => trim(strval($config['input_class'] ?? 'layui-input')),
            'value' => strval($config['value'] ?? ''),
            'title' => strval($config['value'] ?? ''),
        ]);
        $inputName = trim(strval($config['name'] ?? ''));
        if ($inputName !== '') {
            $input->attr('name', $inputName);
        }
        foreach ((array)($config['input_attrs'] ?? []) as $name => $value) {
            $input->attr(strval($name), $value);
        }
        $icon = $node->node('span')->class(trim(strval($config['icon_class'] ?? 'input-right-icon layui-icon layui-icon-theme')));
        foreach ((array)($config['icon_attrs'] ?? []) as $name => $value) {
            $icon->attr(strval($name), $value);
        }
        $help = trim(strval($config['help'] ?? ''));
        if ($help !== '') {
            self::note($node, $help);
        }
        return $node;
    }

    /**
     * @param array<string, array<string, mixed>> $themes
     * @param array<string, mixed> $config
     */
    public static function themePalette(FormNode $parent, array $themes, string $current = '', array $config = []): FormNode
    {
        $node = $parent->div()->class(trim(strval($config['class'] ?? 'layui-form-item mb5 label-required-prev')));

        $label = $node->div()->class(trim(strval($config['label_class'] ?? 'help-label')));
        $title = trim(strval($config['title'] ?? ''));
        if ($title !== '') {
            $label->node('b')->html(self::escape($title));
        }

        $subtitle = trim(strval($config['subtitle'] ?? ''));
        if ($subtitle !== '') {
            $label->node('span')->class(trim(strval($config['subtitle_class'] ?? 'color-desc')))->html(self::escape($subtitle));
        }

        $description = trim(strval($config['description'] ?? ''));
        if ($description !== '') {
            $node->div()->class(trim(strval($config['description_class'] ?? 'help-block')))->html(self::escape($description));
        }

        $palette = $node->div()->class(trim(strval($config['palette_class'] ?? 'theme-palette')));
        $inputName = trim(strval($config['input_name'] ?? 'site_theme'));

        foreach ($themes as $key => $theme) {
            $labelText = trim(strval($theme['label'] ?? $key));
            $card = $palette->node('label')->class(trim('theme-palette-card' . ($current === $key ? ' active' : '')));
            $card->data('theme-card', $key)
                ->data('theme-label', $labelText)
                ->attr('title', trim($labelText . ' / ' . $key, ' /'));

            $input = $card->node('input')->attrs([
                'name' => $inputName,
                'type' => 'radio',
                'value' => strval($key),
            ]);
            if ($current === $key) {
                $input->attr('checked', null);
            }

            $preview = $card->node('span')->class(trim('theme-palette-preview ' . strval($theme['layout'] ?? 'default')));
            $preview->attr('style', self::themePreviewStyle($theme));
            foreach ([
                'theme-palette-preview-header',
                'theme-palette-preview-side',
                'theme-palette-preview-side-alt',
                'theme-palette-preview-card theme-palette-preview-hero',
                'theme-palette-preview-card theme-palette-preview-panel',
                'theme-palette-preview-card theme-palette-preview-panel-right',
                'theme-palette-preview-line theme-palette-preview-tone',
                'theme-palette-preview-line theme-palette-preview-copy-1',
                'theme-palette-preview-line theme-palette-preview-copy-2',
                'theme-palette-preview-line theme-palette-preview-copy-3',
                'theme-palette-preview-line theme-palette-preview-copy-4',
            ] as $class) {
                $preview->node('span')->class($class);
            }

            $meta = $card->node('span')->class('theme-palette-meta');
            $meta->node('span')->class('theme-palette-title')->html(self::escape($labelText));
            $meta->node('span')->class('theme-palette-layout')->html(self::escape(strval($theme['layout_label'] ?? '')));
            $card->node('span')->class('theme-palette-check layui-icon layui-icon-ok');
        }

        $help = trim(strval($config['help'] ?? ''));
        if ($help !== '') {
            $node->node('p')->class(trim(strval($config['help_class'] ?? 'help-block')))->html(self::escape($help));
        }

        return $node;
    }

    /**
     * @param array<string, mixed> $theme
     */
    private static function themePreviewStyle(array $theme): string
    {
        $styles = [];
        foreach ([
            'theme-accent' => strval($theme['primary'] ?? ''),
            'theme-header' => strval($theme['header'] ?? ''),
            'theme-side' => strval($theme['side'] ?? ''),
            'theme-surface' => strval($theme['surface'] ?? ''),
            'theme-body' => strval($theme['body'] ?? ''),
        ] as $name => $value) {
            if ($value !== '') {
                $styles[] = '--' . $name . ':' . $value;
            }
        }
        return join(';', $styles);
    }

    private static function escape(string $content): string
    {
        return htmlentities($content, ENT_QUOTES, 'UTF-8');
    }
}
