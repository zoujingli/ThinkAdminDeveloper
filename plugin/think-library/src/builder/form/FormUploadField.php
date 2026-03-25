<?php

declare(strict_types=1);

namespace think\admin\builder\form;

/**
 * 上传字段节点.
 * @class FormUploadField
 */
class FormUploadField extends FormTextField
{
    public function types(string $types): static
    {
        return $this->uploadConfig()->types($types)->end();
    }

    public function uploadConfig(): FormUploadConfig
    {
        $config = is_array($this->field['upload'] ?? null) ? $this->field['upload'] : [];
        return (new FormUploadConfig($this, $config))
            ->attach(fn(array $config): array => $this->replaceUploadConfig($config));
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function replaceUploadConfig(array $config): array
    {
        $this->field['upload'] = $this->normalizeUploadConfig($config);
        return $this->field['upload'];
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function normalizeUploadConfig(array $config): array
    {
        $config = array_merge([
            'types' => '',
            'trigger' => [],
            'runtime' => [],
        ], $config);

        $config['types'] = trim(strval($config['types']));
        $config['trigger'] = is_array($config['trigger']) ? $config['trigger'] : [];
        $config['runtime'] = is_array($config['runtime']) ? $config['runtime'] : [];
        $config['trigger']['attrs'] = is_array($config['trigger']['attrs'] ?? null) ? $config['trigger']['attrs'] : [];
        if (isset($config['trigger']['class'])) {
            $config['trigger']['class'] = trim(strval($config['trigger']['class']));
        }
        if (isset($config['trigger']['icon'])) {
            $config['trigger']['icon'] = trim(strval($config['trigger']['icon']));
        }
        if (isset($config['runtime']['method'])) {
            $config['runtime']['method'] = trim(strval($config['runtime']['method']));
        }
        if (isset($config['runtime']['selector'])) {
            $config['runtime']['selector'] = trim(strval($config['runtime']['selector']));
        }

        return $config;
    }
}
