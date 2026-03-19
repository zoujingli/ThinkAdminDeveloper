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

namespace plugin\storage\controller\api;

use plugin\storage\model\SystemFile;
use plugin\storage\service\LocalStorage;
use plugin\storage\service\StorageConfig;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\admin\runtime\SystemContext;
use think\admin\Storage;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\HttpResponseException;
use think\file\UploadedFile;
use think\Response;

/**
 * 文件上传接口.
 * @class Upload
 */
class Upload extends Controller
{
    /**
     * 文件上传脚本.
     * @throws \think\admin\Exception
     */
    public function index(): Response
    {
        $data = ['exts' => []];
        [$uuid, $unid, $exts] = $this->initUnid(false);
        $allows = str2arr((string)StorageConfig::global('allowed_exts', ''));
        if (empty($uuid) && $unid > 0) {
            $allows = array_intersect($exts, $allows);
        }
        foreach ($allows as $ext) {
            $data['exts'][$ext] = Storage::mime($ext);
        }
        $data['exts'] = json_encode($data['exts'], JSON_UNESCAPED_UNICODE);
        $data['nameType'] = (string)StorageConfig::global('naming', 'xmd5');
        return view(dirname(__DIR__, 2) . '/view/api/upload.js', $data)->contentType('application/x-javascript');
    }

    /**
     * 文件选择器.
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function image()
    {
        [$uuid, $unid] = $this->initUnid();
        SystemFile::mQuery()->layTable(function () {
            $this->title = '文件选择器';
        }, function (QueryHelper $query) use ($unid, $uuid) {
            if ($unid && $uuid) {
                $query->where(function ($query) use ($uuid, $unid) {
                    /* @var \think\db\Query $query */
                    $query->whereOr([['uuid', '=', $uuid], ['unid', '=', $unid]]);
                });
            } else {
                $query->where($unid ? ['unid' => $unid] : ['uuid' => $uuid]);
            }
            $query->where(['status' => 2, 'issafe' => 0])->in('xext#type');
            $query->like('name,hash')->dateBetween('create_time')->order('id desc');
        });
    }

    /**
     * 文件上传检查.
     */
    public function state()
    {
        try {
            [$uuid, $unid] = $this->initUnid();
            [$name, $safe] = [input('name'), $this->getSafe()];
            $data = ['uptype' => $this->getType(), 'safe' => intval($safe), 'key' => input('key')];
            $file = SystemFile::mk()->data($this->_vali([
                'xkey.value' => $data['key'],
                'type.value' => $this->getType(),
                'uuid.value' => $uuid,
                'unid.value' => $unid,
                'name.require' => '名称不能为空！',
                'hash.require' => '哈希不能为空！',
                'xext.require' => '后缀不能为空！',
                'size.require' => '大小不能为空！',
                'mime.default' => '',
                'status.value' => 1,
            ]));
            $mime = $file->getAttr('mime');
            if (empty($mime)) {
                $file->setAttr('mime', Storage::mime($file->getAttr('xext')));
            }
            $info = Storage::instance($data['uptype'])->info($data['key'], $safe, $name);
            if (isset($info['url'], $info['key'])) {
                $file->save(['xurl' => $info['url'], 'isfast' => 1, 'issafe' => $data['safe']]);
                $extr = ['id' => $file->id ?? 0, 'url' => $info['url'], 'key' => $info['key']];
                $this->success('文件已经上传', array_merge($data, $extr), 200);
            } else {
                $data = array_merge($data, Storage::authorize($data['uptype'], $data['key'], $safe, $name, input('hash', '')));
            }
            $file->save(['xurl' => $data['url'], 'isfast' => 0, 'issafe' => $data['safe']]);
            $this->success('获取上传授权参数', array_merge($data, ['id' => $file->id ?? 0]), 404);
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 更新文件状态
     */
    public function done()
    {
        [$uuid, $unid] = $this->initUnid();
        $data = $this->_vali([
            'id.require' => '编号不能为空！',
            'hash.require' => '哈希不能为空！',
            'uuid.value' => $uuid,
            'unid.value' => $unid,
        ]);
        $file = SystemFile::mk()->where($data)->findOrEmpty();
        if ($file->isEmpty()) {
            $this->error('文件不存在！');
        }
        if ($file->save(['status' => 2])) {
            $this->success('更新成功！');
        } else {
            $this->error('更新失败！');
        }
    }

    /**
     * 文件上传入口.
     * @throws \think\admin\Exception
     */
    public function file()
    {
        [$uuid, $unid, $unexts] = $this->initUnid();
        $file = $this->getFile();
        $extension = strtolower($file->getOriginalExtension());
        $saveFileName = input('key') ?: Storage::name($file->getPathname(), $extension, '', 'md5_file');
        if (strpos($saveFileName, '..') !== false) {
            $this->error('文件路径不能出现跳级操作！');
        }
        if (strtolower(pathinfo(parse_url($saveFileName, PHP_URL_PATH), PATHINFO_EXTENSION)) !== $extension) {
            $this->error('文件后缀异常，请重新上传文件！');
        }
        if (!in_array($extension, str2arr((string)StorageConfig::global('allowed_exts', '')))) {
            $this->error('文件类型受限，请在后台配置规则！');
        }
        if (empty($uuid) && $unid > 0 && !in_array($extension, $unexts)) {
            $this->error('文件类型受限，请上传允许的文件类型！');
        }
        if (in_array($extension, ['sh', 'asp', 'bat', 'cmd', 'exe', 'php'])) {
            $this->error('文件安全保护，禁止上传可执行文件！');
        }
        try {
            $safeMode = $this->getSafe();
            if (($type = $this->getType()) === 'local') {
                $local = LocalStorage::instance();
                $distName = $local->path($saveFileName, $safeMode);
                if (PHP_SAPI === 'cli') {
                    is_dir(dirname($distName)) || mkdir(dirname($distName), 0777, true);
                    rename($file->getPathname(), $distName);
                } else {
                    $file->move(dirname($distName), basename($distName));
                }
                $info = $local->info($saveFileName, $safeMode, $file->getOriginalName());
                if (in_array($extension, ['jpg', 'gif', 'png', 'bmp', 'jpeg', 'wbmp'])) {
                    if ($this->imgNotSafe($distName) && $local->del($saveFileName)) {
                        $this->error('图片未通过安全检查！');
                    }
                    [$width, $height] = getimagesize($distName);
                    if (($width < 1 || $height < 1) && $local->del($saveFileName)) {
                        $this->error('读取图片的尺寸失败！');
                    }
                }
            } else {
                $bina = file_get_contents($file->getPathname());
                $info = Storage::instance($type)->set($saveFileName, $bina, $safeMode, $file->getOriginalName());
            }
            if (isset($info['url'])) {
                $this->success('文件上传成功！', ['url' => $safeMode ? $saveFileName : $info['url']]);
            } else {
                $this->error('文件处理失败，请稍候再试！');
            }
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            trace_file($exception);
            $this->error($exception->getMessage());
        }
    }

    private function getSafe(): bool
    {
        return boolval(input('safe', '0'));
    }

    /**
     * 获取上传方式.
     * @throws \think\admin\Exception
     */
    private function getType(): string
    {
        $type = strtolower(input('uptype', ''));
        if (in_array($type, array_keys(Storage::types()))) {
            return $type;
        }
        return strtolower((string)StorageConfig::global('driver', 'local'));
    }

    /**
     * 获取文件对象
     * @return UploadedFile|void
     */
    private function getFile(): UploadedFile
    {
        try {
            $file = $this->request->file('file');
            if ($file instanceof UploadedFile) {
                return $file;
            }
            $this->error('读取临时文件失败！');
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            trace_file($exception);
            $this->error(lang($exception->getMessage()));
        }
    }

    /**
     * 初始化用户状态
     */
    private function initUnid(bool $check = true): array
    {
        $uuid = SystemContext::getUserId();
        [$unid, $exts] = SystemContext::withUploadUnid();
        if ($check && empty($uuid) && empty($unid)) {
            $this->error('未登录，禁止使用文件上传！');
        } else {
            return [$uuid, $unid, $exts];
        }
    }

    /**
     * 检查图片是否安全.
     */
    private function imgNotSafe(string $filename): bool
    {
        $source = fopen($filename, 'rb');
        if (($size = filesize($filename)) > 512) {
            $hexs = bin2hex(fread($source, 512));
            fseek($source, $size - 512);
            $hexs .= bin2hex(fread($source, 512));
        } else {
            $hexs = bin2hex(fread($source, $size));
        }
        if (is_resource($source)) {
            fclose($source);
        }
        $bins = hex2bin($hexs);
        foreach (['<?php ', '<% ', '<script '] as $key) {
            if (stripos($bins, $key) !== false) {
                return true;
            }
        }
        $result = preg_match('/(3c25.*?28.*?29.*?253e)|(3c3f.*?28.*?29.*?3f3e)|(3C534352495054)|(2F5343524950543E)|(3C736372697074)|(2F7363726970743E)/is', $hexs);
        return $result === false || $result > 0;
    }
}
