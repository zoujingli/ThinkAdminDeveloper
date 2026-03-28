<?php

declare(strict_types=1);

namespace think\admin\builder\form\module;

use think\admin\builder\form\FormComponents;
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
        return FormComponents::intro()->config($config)->mount($parent);
    }

    /**
     * @param array<string, mixed> $config
     * @param callable(FormNode): void $callback
     */
    public static function section(FormNode $parent, array $config, callable $callback): FormNode
    {
        return FormComponents::section()->config($config)->body($callback)->mount($parent);
    }

    public static function note(FormNode $parent, string $text, string $class = 'help-block color-desc'): FormNode
    {
        return FormComponents::note($text)->class($class)->mount($parent);
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function readonlyField(FormNode $parent, array $config = []): FormNode
    {
        return FormComponents::readonlyField()->config($config)->mount($parent);
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function pickerField(FormNode $parent, array $config = []): FormNode
    {
        return FormComponents::pickerField()->config($config)->mount($parent);
    }

    /**
     * @param array<string, array<string, mixed>> $themes
     * @param array<string, mixed> $config
     */
    public static function themePalette(FormNode $parent, array $themes, string $current = '', array $config = []): FormNode
    {
        return FormComponents::themePalette($themes, $current)->config($config)->mount($parent);
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
