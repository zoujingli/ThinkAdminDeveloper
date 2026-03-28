<?php

declare(strict_types=1);

namespace think\admin\builder\page\component;

use think\admin\builder\page\PageNode;

/**
 * 键值表格组件。
 * @class KeyValueTableComponent
 */
class KeyValueTableComponent extends AbstractPageComponent
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $rows = [];

    /**
     * @var array<string, mixed>
     */
    private array $config = [];

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public function rows(array $rows): static
    {
        $this->rows = $rows;
        return $this;
    }

    /**
     * @param array<string, mixed> $row
     */
    public function row(array $row): static
    {
        $this->rows[] = $row;
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

    public function mount(PageNode $parent): PageNode
    {
        $wrap = $parent->div()->class(trim(strval($this->config['wrap_class'] ?? 'layui-table-box')));
        $table = $wrap->node('table')->class(trim(strval($this->config['table_class'] ?? 'layui-table')));
        $tbody = $table->node('tbody');
        foreach ($this->rows as $row) {
            $tr = $tbody->node('tr');
            $tr->node('th')->class(trim(strval($this->config['label_class'] ?? 'nowrap text-center')))->text($this->text(strval($row['label'] ?? '')));
            $cell = $tr->node('td');
            if (!empty($row['url'])) {
                $cell->node('a')->attr('target', '_blank')->attr('href', strval($row['url']))->text($this->text(strval($row['value'] ?? '')));
            } else {
                $cell->text($this->text(strval($row['value'] ?? '')));
            }
        }
        return $wrap;
    }
}
