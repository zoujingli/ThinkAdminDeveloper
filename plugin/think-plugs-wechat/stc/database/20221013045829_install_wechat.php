<?php

// +----------------------------------------------------------------------
// | Wechat Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2024 Anyon <zoujingli@qq.com>
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wechat
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wechat
// +----------------------------------------------------------------------

use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', -1);

/**
 * 微信模块数据表
 */
class InstallWechat extends Migrator
{

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

        // 当前数据表
        $table = 'wechat_auto';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '微信-回复',
        ])
            ->addColumn('type', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '类型(text,image,news)'])
            ->addColumn('time', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '延迟时间'])
            ->addColumn('code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '消息编号'])
            ->addColumn('appid', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '公众号APPID'])
            ->addColumn('content', 'text', ['default' => NULL, 'null' => true, 'comment' => '文本内容'])
            ->addColumn('image_url', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '图片链接'])
            ->addColumn('voice_url', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '语音链接'])
            ->addColumn('music_title', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '音乐标题'])
            ->addColumn('music_url', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '音乐链接'])
            ->addColumn('music_image', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '缩略图片'])
            ->addColumn('music_desc', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '音乐描述'])
            ->addColumn('video_title', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '视频标题'])
            ->addColumn('video_url', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '视频URL'])
            ->addColumn('video_desc', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '视频描述'])
            ->addColumn('news_id', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '图文ID'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '状态(0禁用,1启用)'])
            ->addColumn('create_by', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '创建人'])
            ->addColumn('create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true, 'comment' => '创建时间'])
            ->addIndex('code', ['name' => 'i15cee0aa7_code'])
            ->addIndex('type', ['name' => 'i15cee0aa7_type'])
            ->addIndex('time', ['name' => 'i15cee0aa7_time'])
            ->addIndex('appid', ['name' => 'i15cee0aa7_appid'])
            ->addIndex('status', ['name' => 'i15cee0aa7_status'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class WechatFans
     * @table wechat_fans
     * @return void
     */
    private function _create_wechat_fans()
    {

        // 当前数据表
        $table = 'wechat_fans';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '微信-粉丝',
        ])
            ->addColumn('appid', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '公众号APPID'])
            ->addColumn('unionid', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '粉丝unionid'])
            ->addColumn('openid', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '粉丝openid'])
            ->addColumn('tagid_list', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '粉丝标签id'])
            ->addColumn('is_black', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '是否为黑名单状态'])
            ->addColumn('subscribe', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '关注状态(0未关注,1已关注)'])
            ->addColumn('nickname', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '用户昵称'])
            ->addColumn('sex', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '用户性别(1男性,2女性,0未知)'])
            ->addColumn('country', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '用户所在国家'])
            ->addColumn('province', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '用户所在省份'])
            ->addColumn('city', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '用户所在城市'])
            ->addColumn('language', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '用户的语言(zh_CN)'])
            ->addColumn('headimgurl', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '用户头像'])
            ->addColumn('subscribe_time', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '关注时间'])
            ->addColumn('subscribe_at', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '关注时间'])
            ->addColumn('remark', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '备注'])
            ->addColumn('subscribe_scene', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '扫码关注场景'])
            ->addColumn('qr_scene', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '二维码场景值'])
            ->addColumn('qr_scene_str', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '二维码场景内容'])
            ->addColumn('create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true, 'comment' => '创建时间'])
            ->addIndex('appid', ['name' => 'ic99bc7baf_appid'])
            ->addIndex('openid', ['name' => 'ic99bc7baf_openid'])
            ->addIndex('unionid', ['name' => 'ic99bc7baf_unionid'])
            ->addIndex('is_black', ['name' => 'ic99bc7baf_is_black'])
            ->addIndex('subscribe', ['name' => 'ic99bc7baf_subscribe'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class WechatFansTags
     * @table wechat_fans_tags
     * @return void
     */
    private function _create_wechat_fans_tags()
    {

        // 当前数据表
        $table = 'wechat_fans_tags';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '微信-标签',
        ])
            ->addColumn('appid', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '公众号APPID'])
            ->addColumn('name', 'string', ['limit' => 35, 'default' => '', 'null' => true, 'comment' => '标签名称'])
            ->addColumn('count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '粉丝总数'])
            ->addColumn('create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true, 'comment' => '创建日期'])
            ->addIndex('id', ['name' => 'i1e2a8a9a3_id'])
            ->addIndex('appid', ['name' => 'i1e2a8a9a3_appid'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class WechatKeys
     * @table wechat_keys
     * @return void
     */
    private function _create_wechat_keys()
    {

        // 当前数据表
        $table = 'wechat_keys';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '微信-规则',
        ])
            ->addColumn('appid', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '公众号APPID'])
            ->addColumn('type', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '类型(text,image,news)'])
            ->addColumn('keys', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '关键字'])
            ->addColumn('content', 'text', ['default' => NULL, 'null' => true, 'comment' => '文本内容'])
            ->addColumn('image_url', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '图片链接'])
            ->addColumn('voice_url', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '语音链接'])
            ->addColumn('music_title', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '音乐标题'])
            ->addColumn('music_url', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '音乐链接'])
            ->addColumn('music_image', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '缩略图片'])
            ->addColumn('music_desc', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '音乐描述'])
            ->addColumn('video_title', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '视频标题'])
            ->addColumn('video_url', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '视频URL'])
            ->addColumn('video_desc', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '视频描述'])
            ->addColumn('news_id', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '图文ID'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序字段'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '状态(0禁用,1启用)'])
            ->addColumn('create_by', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '创建人'])
            ->addColumn('create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true, 'comment' => '创建时间'])
            ->addIndex('type', ['name' => 'i23d2c7f47_type'])
            ->addIndex('keys', ['name' => 'i23d2c7f47_keys'])
            ->addIndex('sort', ['name' => 'i23d2c7f47_sort'])
            ->addIndex('appid', ['name' => 'i23d2c7f47_appid'])
            ->addIndex('status', ['name' => 'i23d2c7f47_status'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class WechatMedia
     * @table wechat_media
     * @return void
     */
    private function _create_wechat_media()
    {

        // 当前数据表
        $table = 'wechat_media';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '微信-素材',
        ])
            ->addColumn('md5', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '文件哈希'])
            ->addColumn('type', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '媒体类型'])
            ->addColumn('appid', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '公众号ID'])
            ->addColumn('media_id', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '永久素材MediaID'])
            ->addColumn('local_url', 'string', ['limit' => 300, 'default' => '', 'null' => true, 'comment' => '本地文件链接'])
            ->addColumn('media_url', 'string', ['limit' => 300, 'default' => '', 'null' => true, 'comment' => '远程图片链接'])
            ->addColumn('create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true, 'comment' => '创建时间'])
            ->addIndex('md5', ['name' => 'i7f6418618_md5'])
            ->addIndex('type', ['name' => 'i7f6418618_type'])
            ->addIndex('appid', ['name' => 'i7f6418618_appid'])
            ->addIndex('media_id', ['name' => 'i7f6418618_media_id'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class WechatNews
     * @table wechat_news
     * @return void
     */
    private function _create_wechat_news()
    {

        // 当前数据表
        $table = 'wechat_news';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '微信-图文',
        ])
            ->addColumn('media_id', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '永久素材MediaID'])
            ->addColumn('local_url', 'string', ['limit' => 300, 'default' => '', 'null' => true, 'comment' => '永久素材外网URL'])
            ->addColumn('article_id', 'string', ['limit' => 60, 'default' => '', 'null' => true, 'comment' => '关联图文ID(用英文逗号做分割)'])
            ->addColumn('is_deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删除,1已删除)'])
            ->addColumn('create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true, 'comment' => '创建时间'])
            ->addColumn('create_by', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '创建人'])
            ->addIndex('media_id', ['name' => 'ib3c69027e_media_id'])
            ->addIndex('article_id', ['name' => 'ib3c69027e_article_id'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class WechatNewsArticle
     * @table wechat_news_article
     * @return void
     */
    private function _create_wechat_news_article()
    {

        // 当前数据表
        $table = 'wechat_news_article';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '微信-文章',
        ])
            ->addColumn('title', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '素材标题'])
            ->addColumn('local_url', 'string', ['limit' => 300, 'default' => '', 'null' => true, 'comment' => '永久素材URL'])
            ->addColumn('show_cover_pic', 'integer', ['limit' => 4, 'default' => 0, 'null' => true, 'comment' => '显示封面(0不显示,1显示)'])
            ->addColumn('author', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '文章作者'])
            ->addColumn('digest', 'string', ['limit' => 300, 'default' => '', 'null' => true, 'comment' => '摘要内容'])
            ->addColumn('content', 'text', ['default' => NULL, 'null' => true, 'comment' => '图文内容'])
            ->addColumn('content_source_url', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '原文地址'])
            ->addColumn('read_num', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '阅读数量'])
            ->addColumn('create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true, 'comment' => '创建时间'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class WechatPaymentRecord
     * @table wechat_payment_record
     * @return void
     */
    private function _create_wechat_payment_record()
    {

        // 当前数据表
        $table = 'wechat_payment_record';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '微信-支付-行为',
        ])
            ->addColumn('type', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '交易方式'])
            ->addColumn('code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '发起支付号'])
            ->addColumn('appid', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '发起APPID'])
            ->addColumn('openid', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '用户OPENID'])
            ->addColumn('order_code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '原订单编号'])
            ->addColumn('order_name', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '原订单标题'])
            ->addColumn('order_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '原订单金额'])
            ->addColumn('payment_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '支付完成时间'])
            ->addColumn('payment_trade', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '平台交易编号'])
            ->addColumn('payment_status', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '支付状态(0未付,1已付,2取消)'])
            ->addColumn('payment_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '实际到账金额'])
            ->addColumn('payment_bank', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '支付银行类型'])
            ->addColumn('payment_notify', 'text', ['default' => NULL, 'null' => true, 'comment' => '支付结果通知'])
            ->addColumn('payment_remark', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '支付状态备注'])
            ->addColumn('refund_status', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '退款状态(0未退,1已退)'])
            ->addColumn('refund_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '退款金额'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('type', ['name' => 'i43926536a_type'])
            ->addIndex('code', ['name' => 'i43926536a_code'])
            ->addIndex('appid', ['name' => 'i43926536a_appid'])
            ->addIndex('openid', ['name' => 'i43926536a_openid'])
            ->addIndex('order_code', ['name' => 'i43926536a_order_code'])
            ->addIndex('create_time', ['name' => 'i43926536a_create_time'])
            ->addIndex('payment_trade', ['name' => 'i43926536a_payment_trade'])
            ->addIndex('payment_status', ['name' => 'i43926536a_payment_status'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class WechatPaymentRefund
     * @table wechat_payment_refund
     * @return void
     */
    private function _create_wechat_payment_refund()
    {

        // 当前数据表
        $table = 'wechat_payment_refund';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '微信-支付-退款',
        ])
            ->addColumn('code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '发起支付号'])
            ->addColumn('record_code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '子支付编号'])
            ->addColumn('refund_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '支付完成时间'])
            ->addColumn('refund_trade', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '平台交易编号'])
            ->addColumn('refund_status', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '支付状态(0未付,1已付,2取消)'])
            ->addColumn('refund_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '实际到账金额'])
            ->addColumn('refund_account', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '退款目标账号'])
            ->addColumn('refund_scode', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '退款状态码'])
            ->addColumn('refund_remark', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '支付状态备注'])
            ->addColumn('refund_notify', 'text', ['default' => NULL, 'null' => true, 'comment' => '退款交易通知'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('code', ['name' => 'i5a815074f_code'])
            ->addIndex('record_code', ['name' => 'i5a815074f_record_code'])
            ->addIndex('create_time', ['name' => 'i5a815074f_create_time'])
            ->addIndex('refund_trade', ['name' => 'i5a815074f_refund_trade'])
            ->addIndex('refund_status', ['name' => 'i5a815074f_refund_status'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }
}