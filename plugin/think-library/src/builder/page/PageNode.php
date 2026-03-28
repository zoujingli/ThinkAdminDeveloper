<?php

declare(strict_types=1);

namespace think\admin\builder\page;

use think\admin\builder\base\BuilderNode;
use think\admin\builder\base\render\BuilderAttributes;
use think\admin\builder\page\component\PageComponentInterface;

/**
 * 页面节点定义器.
 * @class PageNode
 */
class PageNode extends BuilderNode
{
    protected function createNodeInstance(string $type = 'element', string $tag = 'div'): self
    {
        return new self($this->builder, $type, $tag);
    }

    public function html(string $html): static
    {
        $child = $this->createNodeInstance('html');
        $child->html = $html;
        $this->appendChild($child);
        return $this;
    }

    public function text(string $text): static
    {
        return $this->html(BuilderAttributes::escape($text));
    }

    /**
     * @param (callable(PageNode): void)|null $callback
     */
    public function node(string $tag = 'div', ?callable $callback = null): self
    {
        $child = $this->createNodeInstance('element', trim($tag) ?: 'div');
        $this->appendChild($child);
        if (is_callable($callback)) {
            $callback($child);
        }
        return $child;
    }

    /**
     * @param (callable(PageNode): void)|null $callback
     */
    public function prepend(string $tag = 'div', ?callable $callback = null): self
    {
        $child = $this->createNodeInstance('element', trim($tag) ?: 'div');
        $this->prependNode($child);
        if (is_callable($callback)) {
            $callback($child);
        }
        return $child;
    }

    /**
     * @param (callable(PageNode): void)|null $callback
     */
    public function before(string $tag = 'div', ?callable $callback = null): self
    {
        $child = $this->createNodeInstance('element', trim($tag) ?: 'div');
        $this->beforeNode($child);
        if (is_callable($callback)) {
            $callback($child);
        }
        return $child;
    }

    /**
     * @param (callable(PageNode): void)|null $callback
     */
    public function after(string $tag = 'div', ?callable $callback = null): self
    {
        $child = $this->createNodeInstance('element', trim($tag) ?: 'div');
        $this->afterNode($child);
        if (is_callable($callback)) {
            $callback($child);
        }
        return $child;
    }

    /**
     * @param null|callable(PageNode): void $callback
     */
    public function component(PageComponentInterface $component, ?callable $callback = null): self
    {
        $node = $component->mount($this);
        if (is_callable($callback)) {
            $callback($node);
        }
        return $node;
    }

    public function div(?callable $callback = null): self
    {
        return $this->node('div', $callback);
    }

    public function section(?callable $callback = null): self
    {
        return $this->node('section', $callback);
    }

    public function article(?callable $callback = null): self
    {
        return $this->node('article', $callback);
    }

    public function header(?callable $callback = null): self
    {
        return $this->node('header', $callback);
    }

    public function footer(?callable $callback = null): self
    {
        return $this->node('footer', $callback);
    }

    public function nav(?callable $callback = null): self
    {
        return $this->node('nav', $callback);
    }

    public function ul(?callable $callback = null): self
    {
        return $this->node('ul', $callback);
    }

    public function li(?callable $callback = null): self
    {
        return $this->node('li', $callback);
    }

    /**
     * 创建标准 tabs 卡片容器，并返回内容区节点。
     *
     * @param null|callable(PageNode): void $callback
     */
    public function tabsCard(string $tabsHtml, ?callable $callback = null, array $attrs = []): self
    {
        $attrs['class'] = trim('layui-tab layui-tab-card ' . strval($attrs['class'] ?? ''));
        $wrap = $this->div()->attrs($attrs);
        $wrap->html($tabsHtml);
        $content = $wrap->div()->class('layui-tab-content');
        if (is_callable($callback)) {
            $callback($content);
        }
        return $content;
    }

    /**
     * 创建标准列表页容器（tabs + search + table）。
     *
     * @param null|callable(PageSearch): void $searchCallback
     * @param null|callable(PageTable): void $tableCallback
     */
    public function tabsListCard(
        string $tabsHtml,
        string $tableId = 'PageDataTable',
        ?string $tableUrl = null,
        ?callable $searchCallback = null,
        ?callable $tableCallback = null,
        array $tableAttrs = ['class' => 'mt10'],
        array $tabsAttrs = []
    ): self {
        return $this->tabsCard($tabsHtml, function (PageNode $content) use ($tableId, $tableUrl, $searchCallback, $tableCallback, $tableAttrs) {
            if (is_callable($searchCallback)) {
                $content->searchNode($searchCallback);
            }
            $content->tableNode($tableId, $tableUrl, $tableCallback, $tableAttrs);
        }, $tabsAttrs);
    }

    /**
     * @param (callable(PageSearch): void)|null $callback
     */
    public function searchNode(?callable $callback = null): PageSearch
    {
        $node = new PageSearch($this->builder);
        $this->appendChild($node);
        if (is_callable($callback)) {
            $callback($node);
        }
        return $node;
    }

    /**
     * @param (callable(PageSearch): void)|null $callback
     */
    public function searchArea(?callable $callback = null): PageSearch
    {
        return $this->searchNode($callback);
    }

    /**
     * @param (callable(PageTable): void)|null $callback
     */
    public function tableNode(string $id = 'PageDataTable', ?string $url = null, ?callable $callback = null, array $attrs = []): PageTable
    {
        $node = new PageTable($this->builder, $id, $url, $attrs);
        $this->appendChild($node);
        if (is_callable($callback)) {
            $callback($node);
        }
        return $node;
    }

    /**
     * @param (callable(PageTable): void)|null $callback
     */
    public function tableArea(string $id = 'PageDataTable', ?string $url = null, ?callable $callback = null, array $attrs = []): PageTable
    {
        return $this->tableNode($id, $url, $callback, $attrs);
    }

    /**
     * 导出节点数组.
     * @return array<string, mixed>
     */
    public function export(): array
    {
        if ($this->type === 'html') {
            return $this->exportHtmlNode();
        }
        return $this->exportElementNode();
    }
}
