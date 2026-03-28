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

namespace plugin\system\controller\api;

use plugin\system\builder\UploadImageDialogBuilder;
use plugin\system\model\SystemFile;
use plugin\system\storage\LocalStorage;
use plugin\system\storage\StorageConfig;
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
 * 通用文件上传 API（直传凭证、分片、本地存储等）。
 * @class Upload
 */
class Upload extends Controller
{
    public function index(): Response
    {
        $data = ['exts' => []];
        [$uuid, $unid, $exts] = $this->initUnid(false);
        $allows = str2arr((string)StorageConfig::global('allowed_extensions', ''));
        if (empty($uuid) && $unid > 0) {
            $allows = array_intersect($exts, $allows);
        }
        foreach ($allows as $ext) {
            $data['exts'][$ext] = Storage::mime($ext);
        }
        $data['exts'] = json_encode($data['exts'], JSON_UNESCAPED_UNICODE);
        $data['nameType'] = (string)StorageConfig::global('naming_rule', 'xmd5');
        return view(dirname(__DIR__, 2) . '/storage/extra/upload.js', $data)->contentType('application/x-javascript');
    }

    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function image(): void
    {
        [$uuid, $unid] = $this->initUnid();
        if (!$this->wantsTableOutput()) {
            throw new HttpResponseException(Response::create(UploadImageDialogBuilder::render($this->buildImageDialogContext()), 'html'));
        }
        SystemFile::mQuery()->layTable(function () {
            $this->title = strval(lang('文件选择器'));
        }, function (QueryHelper $query) use ($unid, $uuid) {
            if ($unid && $uuid) {
                $query->where(function ($query) use ($uuid, $unid) {
                    $query->whereOr([['uuid', '=', $uuid], ['unid', '=', $unid]]);
                });
            } else {
                $query->where($unid ? ['unid' => $unid] : ['uuid' => $uuid]);
            }
            $query->where(['status' => 2, 'issafe' => 0]);
            $query->in('xext#type');
            $query->like('name,hash')->dateBetween('create_time')->order('id desc');
        });
    }

    public function state(): void
    {
        try {
            [$uuid, $unid] = $this->initUnid();
            [$name, $safe] = [input('name'), $this->getSafe()];
            $data = ['uptype' => $this->getType(), 'safe' => intval($safe), 'key' => input('key')];
            $payload = SystemFile::syncPayload($this->_vali([
                'xkey.value' => $data['key'],
                'type.value' => $this->getType(),
                'system_user_id.value' => $uuid,
                'biz_user_id.value' => $unid,
                'name.require' => lang('文件名称不能为空！'),
                'hash.require' => lang('文件哈希不能为空！'),
                'extension.value' => input('extension', input('xext', '')),
                'extension.require' => lang('文件后缀不能为空！'),
                'size.require' => lang('文件大小不能为空！'),
                'mime.default' => '',
                'status.value' => 1,
            ]));
            $file = SystemFile::mk()->data($payload);
            $mime = $file->getAttr('mime');
            if (empty($mime)) {
                $file->setAttr('mime', Storage::mime(strval($file->getAttr('extension') ?: $file->getAttr('xext'))));
            }
            $info = Storage::instance($data['uptype'])->info($data['key'], $safe, $name);
            if (isset($info['url'], $info['key'])) {
                $file->save(SystemFile::syncPayload([
                    'file_url' => $info['url'],
                    'storage_key' => $info['key'],
                    'is_fast_upload' => 1,
                    'is_safe' => $data['safe'],
                ]));
                $this->success(lang('文件已存在。'), array_merge($data, [
                    'id' => $file->id ?? 0,
                    'url' => $info['url'],
                    'key' => $info['key'],
                ]), 200);
            }

            $data = array_merge($data, Storage::authorize($data['uptype'], $data['key'], $safe, $name, input('hash', '')));
            $file->save(SystemFile::syncPayload([
                'file_url' => strval($data['url'] ?? ''),
                'storage_key' => strval($data['key'] ?? ''),
                'is_fast_upload' => 0,
                'is_safe' => $data['safe'],
            ]));
            $this->success(lang('上传授权创建成功。'), array_merge($data, ['id' => $file->id ?? 0]), 404);
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    public function done()
    {
        [$uuid, $unid] = $this->initUnid();
        $data = $this->_vali([
            'id.require' => lang('文件ID不能为空！'),
            'hash.require' => lang('文件哈希不能为空！'),
            'uuid.value' => $uuid,
            'unid.value' => $unid,
        ]);
        $file = SystemFile::mk()->where($data)->findOrEmpty();
        if ($file->isEmpty()) {
            $this->error(lang('文件记录不存在！'));
        }
        if ($file->save(['status' => 2])) {
            $this->success(lang('文件记录状态更新成功！'));
        } else {
            $this->error(lang('文件记录状态更新失败！'));
        }
    }

    /**
     * @throws \think\admin\Exception
     */
    public function file()
    {
        [$uuid, $unid, $unexts] = $this->initUnid();
        $file = $this->getFile();
        $extension = strtolower($file->getOriginalExtension());
        $saveFileName = input('key') ?: Storage::name($file->getPathname(), $extension, '', 'md5_file');
        if (strpos($saveFileName, '..') !== false) {
            $this->error(lang('路径中不能包含 ..'));
        }
        if (strtolower(pathinfo(parse_url($saveFileName, PHP_URL_PATH), PATHINFO_EXTENSION)) !== $extension) {
            $this->error(lang('文件扩展名不匹配。'));
        }
        if (!in_array($extension, str2arr((string)StorageConfig::global('allowed_extensions', '')))) {
            $this->error(lang('该文件类型不允许上传。'));
        }
        if (empty($uuid) && $unid > 0 && !in_array($extension, $unexts)) {
            $this->error(lang('当前上传场景不允许该扩展名。'));
        }
        if (in_array($extension, ['sh', 'asp', 'bat', 'cmd', 'exe', 'php'])) {
            $this->error(lang('禁止上传可执行文件。'));
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
                        $this->error(lang('图片安全校验未通过。'));
                    }
                    [$width, $height] = getimagesize($distName);
                    if (($width < 1 || $height < 1) && $local->del($saveFileName)) {
                        $this->error(lang('无法读取图片尺寸。'));
                    }
                }
            } else {
                $binary = file_get_contents($file->getPathname());
                $info = Storage::instance($type)->set($saveFileName, $binary, $safeMode, $file->getOriginalName());
            }

            if (isset($info['url'])) {
                $this->success(lang('文件上传成功！'), ['url' => $safeMode ? $saveFileName : $info['url']]);
            }
            $this->error(lang('上传失败，请稍后再试。'));
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
     * @throws \think\admin\Exception
     */
    private function getType(): string
    {
        $type = strtolower(input('uptype', ''));
        if (in_array($type, array_keys(Storage::types()), true)) {
            return $type;
        }
        return strtolower((string)StorageConfig::global('default_driver', 'local'));
    }

    private function getFile(): UploadedFile
    {
        try {
            $file = $this->request->file('file');
            if ($file instanceof UploadedFile) {
                return $file;
            }
            $this->error(lang('读取上传文件失败。'));
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            trace_file($exception);
            $this->error(lang($exception->getMessage()));
        }
    }

    private function initUnid(bool $check = true): array
    {
        $context = SystemContext::instance();
        $uuid = $context->getUserId();
        [$unid, $exts] = $context->withUploadUnid();
        if ($check && empty($uuid) && empty($unid)) {
            $this->error(lang('上传前请先登录。'));
        }
        return [$uuid, $unid, $exts];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildImageDialogContext(): array
    {
        $get = $this->request->get();
        $file = strtolower(trim(strval($get['file'] ?? 'image'))) === 'images' ? 'images' : 'image';
        return [
            'id' => trim(strval($get['id'] ?? '')),
            'file' => $file,
            'type' => trim(strval($get['type'] ?? 'gif,png,jpg,jpeg')) ?: 'gif,png,jpg,jpeg',
            'path' => trim(strval($get['path'] ?? '')),
            'size' => intval($get['size'] ?? 0),
            'cutWidth' => intval($get['cutWidth'] ?? 0),
            'cutHeight' => intval($get['cutHeight'] ?? 0),
            'maxWidth' => intval($get['maxWidth'] ?? 0),
            'maxHeight' => intval($get['maxHeight'] ?? 0),
            'removeAllowed' => auth('system/file/remove'),
            'removeUrl' => sysuri('system/file/remove', [], false, false),
            'imageUrl' => apiuri('system/upload/image', [], false, false),
        ];
    }

    private function wantsTableOutput(): bool
    {
        return in_array(strtolower(strval($this->request->request('output', 'default'))), ['json', 'layui.table'], true);
    }

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
