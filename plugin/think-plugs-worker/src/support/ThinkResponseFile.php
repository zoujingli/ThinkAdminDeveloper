<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
 * +----------------------------------------------------------------------
 * | 版权所有 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | 官方网站: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | 开源协议 ( https://mit-license.org )
 * | 免责声明 ( https://thinkadmin.top/disclaimer )
 * | 会员特权 ( https://thinkadmin.top/vip-introduce )
 * +----------------------------------------------------------------------
 * | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
 * | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace plugin\worker\support;

use think\Exception;
use think\exception\HttpResponseException;
use think\Response;

/**
 * Worker-optimized file response.
 */
class ThinkResponseFile extends Response
{
    protected $name;

    protected $mimeType;

    protected $force = true;

    protected $expire = 360;

    protected $isContent = false;

    public function __construct($data = '', int $code = 200)
    {
        $this->init($data, $code);
    }

    /**
     * Throw the response back to ThinkPHP so Worker can marshal it.
     */
    public function send(): void
    {
        $this->prepareDownload();
        throw new HttpResponseException($this);
    }

    public function isContent(bool $content = true)
    {
        $this->isContent = $content;
        return $this;
    }

    public function expire(int $expire)
    {
        $this->expire = $expire;
        return $this;
    }

    public function mimeType(string $mimeType)
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function force(bool $force)
    {
        $this->force = $force;
        return $this;
    }

    public function name(string $filename, bool $extension = true)
    {
        $this->name = $filename;
        if ($extension && !str_contains($filename, '.')) {
            $this->name .= '.' . pathinfo((string)$this->data, PATHINFO_EXTENSION);
        }

        return $this;
    }

    public function isFileResponse(): bool
    {
        return !$this->isContent && is_string($this->data) && is_file($this->data);
    }

    public function getFilePath(): string
    {
        return (string)$this->data;
    }

    public function prepareDownload(): self
    {
        $data = $this->data;
        if (!$this->isContent && !is_file($data)) {
            throw new Exception('file not exists:' . $data);
        }

        $name = $this->name ?: (!$this->isContent ? pathinfo($data, PATHINFO_BASENAME) : '');
        $name = str_replace(['"', "\r", "\n"], '', $name);
        $encoded = rawurlencode($name);

        if ($this->isContent) {
            $mimeType = $this->mimeType;
            $size = strlen((string)$data);
            $lastModified = time();
        } else {
            $mimeType = $this->getMimeType((string)$data);
            $size = filesize((string)$data);
            $lastModified = filemtime((string)$data);
        }

        $this->header['Pragma'] = 'public';
        $this->header['Content-Type'] = $mimeType ?: 'application/octet-stream';
        $this->header['Cache-control'] = 'max-age=' . $this->expire;
        $this->header['Content-Disposition'] = ($this->force ? 'attachment; ' : '') . 'filename="' . $encoded . '"; filename*=UTF-8\'\'' . $encoded;
        $this->header['Content-Length'] = $size;
        $this->header['Content-Transfer-Encoding'] = 'binary';
        $this->header['Expires'] = gmdate('D, d M Y H:i:s', time() + $this->expire) . ' GMT';
        $this->lastModified(gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');

        return $this;
    }

    /**
     * Render body content only when Worker cannot stream a file directly.
     * @param mixed $data
     */
    protected function output($data)
    {
        $this->prepareDownload();
        return $this->isContent ? $data : file_get_contents($data);
    }

    protected function getMimeType(string $filename): string
    {
        if (!empty($this->mimeType)) {
            return $this->mimeType;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        return finfo_file($finfo, $filename);
    }
}
