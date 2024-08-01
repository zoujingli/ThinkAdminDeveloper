<?php

// +----------------------------------------------------------------------
// | WeMall Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wemall
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wemall
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wemall\controller\api\help;

use plugin\wemall\controller\api\Auth;
use plugin\wemall\model\PluginWemallHelpFeedback;
use think\admin\helper\QueryHelper;
use think\admin\Storage;

/**
 * 意见反馈管理
 * @class Feedback
 * @package app\data\controller\api\auth
 */
class Feedback extends Auth
{
    /**
     * 获取反馈意见
     * @return void
     */
    public function get()
    {
        PluginWemallHelpFeedback::mQuery(null, function (QueryHelper $query) {
            $query->where(['unid' => $this->unid])->like('content#keys')->equal('id');
            $this->success('获取反馈意见', $query->order('sort desc,id desc')->page(true, false, false, 10));
        });
    }

    /**
     * 提交反馈意见
     * @return void
     * @throws \think\admin\Exception
     */
    public function set()
    {
        $data = $this->_vali([
            'unid.value'      => $this->unid,
            'content.require' => '内容不能为空!',
            'phone.default'   => '',
            'images.default'  => '',
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