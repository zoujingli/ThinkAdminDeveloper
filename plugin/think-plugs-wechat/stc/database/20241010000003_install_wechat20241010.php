<?php

use think\admin\extend\PhinxExtend;
use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', -1);

class InstallWechat20241010 extends Migrator
{

    /**
     * 获取脚本名称
     * @return string
     */
    public function getName(): string
    {
        return 'WechatPlugin';
    }

    /**
     * 创建数据库
     */
    public function change()
    {
        $this->_create_wechat_auto();
        $this->_create_wechat_fans();
        $this->_create_wechat_fans_tags();
        $this->_create_wechat_keys();
        $this->_create_wechat_media();
        $this->_create_wechat_news();
        $this->_create_wechat_news_article();
        $this->_create_wechat_payment_record();
        $this->_create_wechat_payment_refund();
    }

    /**
     * 创建数据对象
     * @class WechatAuto
     * @table wechat_auto
     * @return void
     */
    private function _create_wechat_auto()
    {
        // 创建数据表对象
        $table = $this->table('wechat_auto', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '微信-回复',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['type', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '类型(text,image,news)']],
            ['time', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '延迟时间']],
            ['code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '消息编号']],
            ['appid', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '公众号APPID']],
            ['content', 'text', ['default' => NULL, 'null' => true, 'comment' => '文本内容']],
            ['image_url', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '图片链接']],
            ['voice_url', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '语音链接']],
            ['music_title', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '音乐标题']],
            ['music_url', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '音乐链接']],
            ['music_image', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '缩略图片']],
            ['music_desc', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '音乐描述']],
            ['video_title', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '视频标题']],
            ['video_url', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '视频URL']],
            ['video_desc', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '视频描述']],
            ['news_id', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '图文ID']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '状态(0禁用,1启用)']],
            ['create_by', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '创建人']],
            ['create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true, 'comment' => '创建时间']],
        ], [
            'code', 'type', 'time', 'appid', 'status',
        ], true);
    }

    /**
     * 创建数据对象
     * @class WechatFans
     * @table wechat_fans
     * @return void
     */
    private function _create_wechat_fans()
    {
        // 创建数据表对象
        $table = $this->table('wechat_fans', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '微信-粉丝',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['appid', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '公众号APPID']],
            ['unionid', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '粉丝unionid']],
            ['openid', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '粉丝openid']],
            ['tagid_list', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '粉丝标签id']],
            ['is_black', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '是否为黑名单状态']],
            ['subscribe', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '关注状态(0未关注,1已关注)']],
            ['nickname', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '用户昵称']],
            ['sex', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '用户性别(1男性,2女性,0未知)']],
            ['country', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '用户所在国家']],
            ['province', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '用户所在省份']],
            ['city', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '用户所在城市']],
            ['language', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '用户的语言(zh_CN)']],
            ['headimgurl', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '用户头像']],
            ['subscribe_time', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '关注时间']],
            ['subscribe_at', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '关注时间']],
            ['remark', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '备注']],
            ['subscribe_scene', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '扫码关注场景']],
            ['qr_scene', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '二维码场景值']],
            ['qr_scene_str', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '二维码场景内容']],
            ['create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true, 'comment' => '创建时间']],
        ], [
            'appid', 'openid', 'unionid', 'is_black', 'subscribe',
        ], true);
    }

    /**
     * 创建数据对象
     * @class WechatFansTags
     * @table wechat_fans_tags
     * @return void
     */
    private function _create_wechat_fans_tags()
    {
        // 创建数据表对象
        $table = $this->table('wechat_fans_tags', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '微信-标签',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['appid', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '公众号APPID']],
            ['name', 'string', ['limit' => 35, 'default' => '', 'null' => true, 'comment' => '标签名称']],
            ['count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '粉丝总数']],
            ['create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true, 'comment' => '创建日期']],
        ], [
            'id', 'appid',
        ], true);
    }

    /**
     * 创建数据对象
     * @class WechatKeys
     * @table wechat_keys
     * @return void
     */
    private function _create_wechat_keys()
    {
        // 创建数据表对象
        $table = $this->table('wechat_keys', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '微信-规则',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['appid', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '公众号APPID']],
            ['type', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '类型(text,image,news)']],
            ['keys', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '关键字']],
            ['content', 'text', ['default' => NULL, 'null' => true, 'comment' => '文本内容']],
            ['image_url', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '图片链接']],
            ['voice_url', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '语音链接']],
            ['music_title', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '音乐标题']],
            ['music_url', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '音乐链接']],
            ['music_image', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '缩略图片']],
            ['music_desc', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '音乐描述']],
            ['video_title', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '视频标题']],
            ['video_url', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '视频URL']],
            ['video_desc', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '视频描述']],
            ['news_id', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '图文ID']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序字段']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '状态(0禁用,1启用)']],
            ['create_by', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '创建人']],
            ['create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true, 'comment' => '创建时间']],
        ], [
            'type', 'keys', 'sort', 'appid', 'status',
        ], true);
    }

    /**
     * 创建数据对象
     * @class WechatMedia
     * @table wechat_media
     * @return void
     */
    private function _create_wechat_media()
    {
        // 创建数据表对象
        $table = $this->table('wechat_media', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '微信-素材',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['md5', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '文件哈希']],
            ['type', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '媒体类型']],
            ['appid', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '公众号ID']],
            ['media_id', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '永久素材MediaID']],
            ['local_url', 'string', ['limit' => 300, 'default' => '', 'null' => true, 'comment' => '本地文件链接']],
            ['media_url', 'string', ['limit' => 300, 'default' => '', 'null' => true, 'comment' => '远程图片链接']],
            ['create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true, 'comment' => '创建时间']],
        ], [
            'md5', 'type', 'appid', 'media_id',
        ], true);
    }

    /**
     * 创建数据对象
     * @class WechatNews
     * @table wechat_news
     * @return void
     */
    private function _create_wechat_news()
    {
        // 创建数据表对象
        $table = $this->table('wechat_news', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '微信-图文',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['media_id', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '永久素材MediaID']],
            ['local_url', 'string', ['limit' => 300, 'default' => '', 'null' => true, 'comment' => '永久素材外网URL']],
            ['article_id', 'string', ['limit' => 60, 'default' => '', 'null' => true, 'comment' => '关联图文ID(用英文逗号做分割)']],
            ['is_deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删除,1已删除)']],
            ['create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true, 'comment' => '创建时间']],
            ['create_by', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '创建人']],
        ], [
            'media_id', 'article_id',
        ], true);
    }

    /**
     * 创建数据对象
     * @class WechatNewsArticle
     * @table wechat_news_article
     * @return void
     */
    private function _create_wechat_news_article()
    {
        // 创建数据表对象
        $table = $this->table('wechat_news_article', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '微信-文章',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['title', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '素材标题']],
            ['local_url', 'string', ['limit' => 300, 'default' => '', 'null' => true, 'comment' => '永久素材URL']],
            ['show_cover_pic', 'integer', ['limit' => 4, 'default' => 0, 'null' => true, 'comment' => '显示封面(0不显示,1显示)']],
            ['author', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '文章作者']],
            ['digest', 'string', ['limit' => 300, 'default' => '', 'null' => true, 'comment' => '摘要内容']],
            ['content', 'text', ['default' => NULL, 'null' => true, 'comment' => '图文内容']],
            ['content_source_url', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '原文地址']],
            ['read_num', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '阅读数量']],
            ['create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true, 'comment' => '创建时间']],
        ], [], true);
    }

    /**
     * 创建数据对象
     * @class WechatPaymentRecord
     * @table wechat_payment_record
     * @return void
     */
    private function _create_wechat_payment_record()
    {
        // 创建数据表对象
        $table = $this->table('wechat_payment_record', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '微信-支付-行为',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['type', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '交易方式']],
            ['code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '发起支付号']],
            ['appid', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '发起APPID']],
            ['openid', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '用户OPENID']],
            ['order_code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '原订单编号']],
            ['order_name', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '原订单标题']],
            ['order_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '原订单金额']],
            ['payment_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '支付完成时间']],
            ['payment_trade', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '平台交易编号']],
            ['payment_status', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '支付状态(0未付,1已付,2取消)']],
            ['payment_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '实际到账金额']],
            ['payment_bank', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '支付银行类型']],
            ['payment_notify', 'text', ['default' => NULL, 'null' => true, 'comment' => '支付结果通知']],
            ['payment_remark', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '支付状态备注']],
            ['refund_status', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '退款状态(0未退,1已退)']],
            ['refund_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '退款金额']],
            ['create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间']],
        ], [
            'type', 'code', 'appid', 'openid', 'order_code', 'create_time', 'payment_trade', 'payment_status',
        ], true);
    }

    /**
     * 创建数据对象
     * @class WechatPaymentRefund
     * @table wechat_payment_refund
     * @return void
     */
    private function _create_wechat_payment_refund()
    {
        // 创建数据表对象
        $table = $this->table('wechat_payment_refund', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '微信-支付-退款',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '发起支付号']],
            ['record_code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '子支付编号']],
            ['refund_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '支付完成时间']],
            ['refund_trade', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '平台交易编号']],
            ['refund_status', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '支付状态(0未付,1已付,2取消)']],
            ['refund_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '实际到账金额']],
            ['refund_account', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '退款目标账号']],
            ['refund_scode', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '退款状态码']],
            ['refund_remark', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '支付状态备注']],
            ['refund_notify', 'text', ['default' => NULL, 'null' => true, 'comment' => '退款交易通知']],
            ['create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间']],
        ], [
            'code', 'record_code', 'create_time', 'refund_trade', 'refund_status',
        ], true);
    }
}
