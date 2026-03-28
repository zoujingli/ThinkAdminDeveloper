<?php

declare(strict_types=1);

namespace think\admin\builder\form\component;

use think\admin\builder\form\FormNode;

/**
 * 主题卡片组件。
 * @class ThemePaletteComponent
 */
class ThemePaletteComponent extends AbstractFormComponent
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $themes = [];

    private string $current = '';

    /**
     * @var array<string, mixed>
     */
    private array $config = [];

    /**
     * @param array<string, array<string, mixed>> $themes
     */
    public function themes(array $themes): static
    {
        $this->themes = $themes;
        return $this;
    }

    public function current(string $current): static
    {
        $this->current = $current;
        return $this;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function config(array $config): static
    {
        $this->config = $this->mergeConfig($this->config, $config);
        return $this;
    }

    public function mount(FormNode $parent): FormNode
    {
        $node = $parent->div()->class(trim(strval($this->config['class'] ?? 'layui-form-item mb5 label-required-prev')));
        $label = $node->div()->class(trim(strval($this->config['label_class'] ?? 'help-label')));
        $title = trim(strval($this->config['title'] ?? ''));
        if ($title !== '') {
            $label->node('b')->html($this->escape($title));
        }
        $subtitle = trim(strval($this->config['subtitle'] ?? ''));
        if ($subtitle !== '') {
            $label->node('span')->class(trim(strval($this->config['subtitle_class'] ?? 'color-desc')))->html($this->escape($subtitle));
        }
        $description = trim(strval($this->config['description'] ?? ''));
        if ($description !== '') {
            $node->div()->class(trim(strval($this->config['description_class'] ?? 'help-block')))->html($this->escape($description));
        }

        $palette = $node->div()->class(trim(strval($this->config['palette_class'] ?? 'theme-palette')));
        $inputName = trim(strval($this->config['input_name'] ?? 'site_theme'));
        foreach ($this->themes as $key => $theme) {
            $labelText = trim(strval($theme['label'] ?? $key));
            $card = $palette->node('label')->class(trim('theme-palette-card' . ($this->current === $key ? ' active' : '')));
            $card->data('theme-card', $key)
                ->data('theme-label', $labelText)
                ->attr('title', trim($labelText . ' / ' . $key, ' /'));

            $input = $card->node('input')->attrs([
                'name' => $inputName,
                'type' => 'radio',
                'value' => strval($key),
                'lay-ignore' => 'true',
                'class' => 'theme-palette-input',
            ]);
            if ($this->current === $key) {
                $input->attr('checked', null);
            }

            $preview = $card->node('span')->class(trim('theme-palette-preview ' . strval($theme['layout'] ?? 'default')));
            $preview->attr('style', $this->themePreviewStyle($theme));
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
            $meta->node('span')->class('theme-palette-title')->html($this->escape($labelText));
            $meta->node('span')->class('theme-palette-layout')->html($this->escape(strval($theme['layout_label'] ?? '')));
            $card->node('span')->class('theme-palette-check layui-icon layui-icon-ok');
        }

        $help = trim(strval($this->config['help'] ?? ''));
        if ($help !== '') {
            $node->node('p')->class(trim(strval($this->config['help_class'] ?? 'help-block')))->html($this->escape($help));
        }
        return $node;
    }

    /**
     * @param array<string, mixed> $theme
     */
    private function themePreviewStyle(array $theme): string
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
}
