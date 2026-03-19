<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdminDeveloper
 * +----------------------------------------------------------------------
 * | Copyright (c) 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | Official Website: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | Licensed: https://mit-license.org
 * | Disclaimer: https://thinkadmin.top/disclaimer
 * | Vip Rights: https://thinkadmin.top/vip-introduce
 * +----------------------------------------------------------------------
 * | Gitee Repository: https://gitee.com/zoujingli/ThinkAdmin
 * | Github Repository: https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace plugin\wechat\client\controller\api;

use plugin\wechat\client\model\WechatNewsArticle;
use plugin\wechat\client\service\MediaService;
use think\admin\Controller;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 微信图文显示.
 * @class View
 */
class View extends Controller
{
    /**
     * 图文列表展示.
     * @param int|string $id 图文ID编号
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function news($id = 0)
    {
        $this->id = $id ?: input('id', 0);
        $this->news = MediaService::news($this->id);
        $this->fetch();
    }

    /**
     * 文章内容展示.
     * @param int|string $id 文章ID编号
     * @throws DbException
     */
    public function item($id = 0)
    {
        $map = ['id' => $id ?: input('id', 0)];
        $modal = WechatNewsArticle::mk()->where($map)->findOrEmpty();
        $modal->isExists() && $modal->newQuery()->where($map)->setInc('read_num');
        $this->fetch('item', ['info' => $modal->toArray()]);
    }

    /**
     * 文本展示.
     */
    public function text()
    {
        $text = strip_tags(input('content', ''), '<a><img>');
        $this->fetch('text', ['content' => $text]);
    }

    /**
     * 图片展示.
     */
    public function image()
    {
        $text = strip_tags(input('content', ''), '<a><img>');
        $this->fetch('image', ['content' => $text]);
    }

    /**
     * 视频展示.
     */
    public function video()
    {
        $this->url = strip_tags(input('url', ''), '<a><img>');
        $this->title = strip_tags(input('title', ''), '<a><img>');
        $this->fetch();
    }

    /**
     * 语音展示.
     */
    public function voice()
    {
        $this->url = strip_tags(input('url', ''), '<a><img>');
        $this->fetch();
    }

    /**
     * 音乐展示.
     */
    public function music()
    {
        $this->url = strip_tags(input('url', ''), '<a><img>');
        $this->desc = strip_tags(input('desc', ''), '<a><img>');
        $this->title = strip_tags(input('title', ''), '<a><img>');
        $this->fetch();
    }
}
