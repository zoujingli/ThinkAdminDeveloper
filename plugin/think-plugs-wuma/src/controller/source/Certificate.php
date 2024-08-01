<?php

// +----------------------------------------------------------------------
// | Wuma Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 收费插件 ( https://thinkadmin.top/fee-introduce.html )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wuma
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wuma
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wuma\controller\source;

use plugin\wuma\model\PluginWumaSourceCertificate;
use plugin\wuma\service\CertService;
use think\admin\Controller;
use think\admin\extend\CodeExtend;
use think\admin\helper\QueryHelper;
use think\exception\HttpResponseException;

/**
 * 区块链确权证书管理
 * @class Certificate
 * @package plugin\wuma\controller\source
 */
class Certificate extends Controller
{
    /**
     * 区块链确权证书管理
     * @menu true
     * @auth true
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->type = $this->get['type'] ?? 'index';
        PluginWumaSourceCertificate::mQuery()->layTable(function () {
            $this->title = '区块链确权证书';
        }, function (QueryHelper $query) {
            $query->like('code,name')->dateBetween('create_time');
            $query->where(['status' => intval($this->type === 'index'), 'deleted' => 0]);
        });
    }

    /**
     * 添加区块链确权证书
     * @auth true
     * @return void
     */
    public function add()
    {
        $this->title = '添加区块链确权证书';
        PluginWumaSourceCertificate::mForm('form');
    }

    /**
     * 编辑区块链确权证书
     * @auth true
     * @return void
     */
    public function edit()
    {
        $this->title = '编辑区块链确权证书';
        PluginWumaSourceCertificate::mForm('form');
    }

    /**
     * 表单结果处理
     * @param array $data
     * @return void
     * @throws \think\db\exception\DbException
     */
    protected function _form_filter(array &$data)
    {
        if (empty($data['code'])) {
            $data['code'] = CodeExtend::uniqidNumber(16, 'CT');
        }
        if ($this->request->isPost()) {
            // 检查产品编号
            $map = [['id', '<>', $data['id'] ?? 0], ['code', '=', $data['code']]];
            if (PluginWumaSourceCertificate::mk()->where($map)->count() > 0) {
                $this->error("证书编号已经存在！");
            }
        }
    }

    /**
     * 表单结果处理
     * @param boolean $result
     */
    protected function _form_result(bool $result)
    {
        if ($result && $this->request->isPost()) {
            $this->success('证书编辑成功！', 'javascript:history.back()');
        }
    }

    /**
     * 预览授权书生成
     * @auth true
     * @return void
     */
    public function show()
    {
        $data = $this->_vali([
            'image.require' => '图片链接不能为空！',
            'items.require' => '绘制规则不能为空！',
        ]);
        $default = [
            // name: numb:comp:prod:date:hash:
            'numb' => CodeExtend::uniqidNumber(16, 'CT'),
            'comp' => $this->user['company_name'] ?? '',
            'hash' => strtolower(md5(uniqid())),
            'date' => format_datetime(time()),
        ];
        $items = json_decode($data['items'], true);
        foreach ($items as $key => &$item) {
            $item['value'] = ($default[$key] ?? '') ?: $item['value'];
        }
        try {
            $base64 = CertService::create($data['image'], $items);
            $this->success('生成证书图片', ['base64' => $base64]);
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 修改区块链确权证书状态
     * @auth true
     */
    public function state()
    {
        PluginWumaSourceCertificate::mSave();
    }

    /**
     * 删除区块链确权证书
     * @auth true
     */
    public function remove()
    {
        PluginWumaSourceCertificate::mDelete();
    }
}