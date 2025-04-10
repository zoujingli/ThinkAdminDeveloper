<?php

// +----------------------------------------------------------------------
// | Worker Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-worker
// | github 代码仓库：https://github.com/zoujingli/think-plugs-worker
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\worker\support;

use think\Exception;
use think\exception\HttpResponseException;
use think\Response;

/**
 * Think File Response
 * @class ThinkResponseFile
 * @package plugin\worker\support
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
     * 处理数据
     * @access protected
     * @param mixed $data 要处理的数据
     * @return mixed
     * @throws \Exception
     */
    protected function output($data)
    {
        if (!$this->isContent && !is_file($data)) {
            throw new Exception('file not exists:' . $data);
        }
        if (!empty($this->name)) {
            $name = $this->name;
        } else {
            $name = !$this->isContent ? pathinfo($data, PATHINFO_BASENAME) : '';
        }
        if ($this->isContent) {
            // $size = strlen($data);
            $mimeType = $this->mimeType;
        } else {
            // $size = filesize($data);
            $mimeType = $this->getMimeType($data);
        }
        $this->header['Pragma'] = 'public';
        $this->header['Content-Type'] = $mimeType ?: 'application/octet-stream';
        $this->header['Cache-control'] = 'max-age=' . $this->expire;
        $this->header['Content-Disposition'] = ($this->force ? 'attachment; ' : '') . 'filename="' . $name . '"';
        // $this->header['Content-Length'] = $size;
        // $this->header['Transfer-Encoding'] = 'binary';
        $this->header['Content-Transfer-Encoding'] = 'binary';
        $this->header['Expires'] = gmdate("D, d M Y H:i:s", time() + $this->expire) . ' GMT';
        $this->lastModified(gmdate('D, d M Y H:i:s', time()) . ' GMT');
        return $this->isContent ? $data : file_get_contents($data);
    }

    /**
     * 发送数据到客户端
     * @access public
     * @return void
     * @throws \InvalidArgumentException
     */
    public function send(): void
    {
        $this->getContent();
        throw new HttpResponseException($this);
    }

    /**
     * 设置是否为内容 必须配合mimeType方法使用
     * @access public
     * @param bool $content
     * @return $this
     */
    public function isContent(bool $content = true)
    {
        $this->isContent = $content;
        return $this;
    }

    /**
     * 设置有效期
     * @access public
     * @param integer $expire 有效期
     * @return $this
     */
    public function expire(int $expire)
    {
        $this->expire = $expire;
        return $this;
    }

    /**
     * 设置文件类型
     * @access public
     * @param string $mimeType
     * @return $this
     */
    public function mimeType(string $mimeType)
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    /**
     * 设置文件强制下载
     * @access public
     * @param bool $force 强制浏览器下载
     * @return $this
     */
    public function force(bool $force)
    {
        $this->force = $force;
        return $this;
    }

    /**
     * 获取文件类型信息
     * @access public
     * @param string $filename 文件名
     * @return string
     */
    protected function getMimeType(string $filename): string
    {
        if (!empty($this->mimeType)) {
            return $this->mimeType;
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        return finfo_file($finfo, $filename);
    }

    /**
     * 设置下载文件的显示名称
     * @access public
     * @param string $filename 文件名
     * @param bool $extension 后缀自动识别
     * @return $this
     */
    public function name(string $filename, bool $extension = true)
    {
        $this->name = $filename;
        if ($extension && !strpos($filename, '.')) {
            $this->name .= '.' . pathinfo($this->data, PATHINFO_EXTENSION);
        }
        return $this;
    }
}
