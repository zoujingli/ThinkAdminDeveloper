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

class Upload extends Controller
{
    /**
     * @throws \think\admin\Exception
     */
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
        return view(dirname(__DIR__, 2) . '/storage/upload.js', $data)
            ->contentType('application/x-javascript');
    }

    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function image()
    {
        [$uuid, $unid] = $this->initUnid();
        SystemFile::mQuery()->layTable(function () {
            $this->title = 'File Picker';
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
                'name.require' => 'File name is required.',
                'hash.require' => 'File hash is required.',
                'xext.require' => 'File extension is required.',
                'size.require' => 'File size is required.',
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
                $this->success('File already exists.', array_merge($data, [
                    'id' => $file->id ?? 0,
                    'url' => $info['url'],
                    'key' => $info['key'],
                ]), 200);
            }

            $data = array_merge($data, Storage::authorize($data['uptype'], $data['key'], $safe, $name, input('hash', '')));
            $file->save(['xurl' => $data['url'], 'isfast' => 0, 'issafe' => $data['safe']]);
            $this->success('Upload authorization created.', array_merge($data, ['id' => $file->id ?? 0]), 404);
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
            'id.require' => 'File id is required.',
            'hash.require' => 'File hash is required.',
            'uuid.value' => $uuid,
            'unid.value' => $unid,
        ]);
        $file = SystemFile::mk()->where($data)->findOrEmpty();
        if ($file->isEmpty()) {
            $this->error('File record does not exist.');
        }
        if ($file->save(['status' => 2])) {
            $this->success('Upload state updated.');
        } else {
            $this->error('Failed to update upload state.');
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
            $this->error('Path traversal is not allowed.');
        }
        if (strtolower(pathinfo(parse_url($saveFileName, PHP_URL_PATH), PATHINFO_EXTENSION)) !== $extension) {
            $this->error('Invalid file extension.');
        }
        if (!in_array($extension, str2arr((string)StorageConfig::global('allowed_extensions', '')))) {
            $this->error('File type is not allowed.');
        }
        if (empty($uuid) && $unid > 0 && !in_array($extension, $unexts)) {
            $this->error('Upload extension is not allowed.');
        }
        if (in_array($extension, ['sh', 'asp', 'bat', 'cmd', 'exe', 'php'])) {
            $this->error('Executable files are blocked.');
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
                        $this->error('Image failed security validation.');
                    }
                    [$width, $height] = getimagesize($distName);
                    if (($width < 1 || $height < 1) && $local->del($saveFileName)) {
                        $this->error('Failed to read image size.');
                    }
                }
            } else {
                $binary = file_get_contents($file->getPathname());
                $info = Storage::instance($type)->set($saveFileName, $binary, $safeMode, $file->getOriginalName());
            }

            if (isset($info['url'])) {
                $this->success('File uploaded successfully.', ['url' => $safeMode ? $saveFileName : $info['url']]);
            }
            $this->error('Upload failed, please try again later.');
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
            $this->error('Failed to read uploaded file.');
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
            $this->error('Login is required before upload.');
        }
        return [$uuid, $unid, $exts];
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
