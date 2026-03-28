<?php

declare(strict_types=1);

namespace think\admin\builder\form;

use think\admin\builder\form\component\IntroComponent;
use think\admin\builder\form\component\NoteComponent;
use think\admin\builder\form\component\PickerFieldComponent;
use think\admin\builder\form\component\ReadonlyFieldComponent;
use think\admin\builder\form\component\SectionComponent;
use think\admin\builder\form\component\ThemePaletteComponent;

/**
 * 表单组件工厂。
 * @class FormComponents
 */
class FormComponents
{
    public static function intro(): IntroComponent
    {
        return IntroComponent::make();
    }

    public static function section(): SectionComponent
    {
        return SectionComponent::make();
    }

    public static function note(string $text = ''): NoteComponent
    {
        return NoteComponent::make()->text($text);
    }

    public static function readonlyField(): ReadonlyFieldComponent
    {
        return ReadonlyFieldComponent::make();
    }

    public static function pickerField(): PickerFieldComponent
    {
        return PickerFieldComponent::make();
    }

    /**
     * @param array<string, array<string, mixed>> $themes
     */
    public static function themePalette(array $themes = [], string $current = ''): ThemePaletteComponent
    {
        return ThemePaletteComponent::make()->themes($themes)->current($current);
    }
}
