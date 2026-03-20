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

namespace plugin\wemall\controller\api\help;

use plugin\wemall\controller\api\Auth;
use plugin\wemall\model\PluginWemallHelpFeedback;
use think\admin\Exception;
use think\admin\helper\QueryHelper;
use think\admin\Storage;

/**
 * 意见反馈管理.
 * @class Feedback
 */
class Feedback extends Auth
{
    /**
     * 获取反馈意见
     */
    public function get()
    {
        PluginWemallHelpFeedback::mQuery(null, function (QueryHelper $query) {
            $query->where(['unid' => $this->unid]);
            $query->like('content#keys');
            $query->equal('id');
            $query->order('sort desc,id desc');
            $this->success('获取反馈意见', $query->page(true, false, false, 10));
        });
    }

    /**
     * 提交反馈意见
     * @throws Exception
     */
    public function set()
    {
        $data = $this->_vali([
            'unid.value' => $this->unid,
            'content.require' => '内容不能为空!',
            'phone.default' => '',
            'images.default' => '',
        ]);
        if (!empty($data['images'])) {
            $images = explode('|', $data['images']);
            foreach ($images as &$image) {
                $image = Storage::saveImage($image, 'feedback')['url'];
            }
            $data['images'] = implode('|', $images);
        }
        if (($model = PluginWemallHelpFeedback::mk())->save($data)) {
            $this->success('提交成功！', $model->toArray());
        } else {
            $this->error('提交失败！');
        }
    }
}
