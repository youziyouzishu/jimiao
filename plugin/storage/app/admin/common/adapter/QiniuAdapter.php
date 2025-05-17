<?php
/**
 * @desc QiniuAdapter
 * @author Tinywan(ShaoBo Wan)
 * @email 756684177@qq.com
 */

declare(strict_types=1);

namespace plugin\storage\app\admin\common\adapter;


use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use support\exception\BusinessException;

class QiniuAdapter extends AdapterAbstract
{
    /**
     * @var UploadManager|null
     */
    protected ?UploadManager $instance = null;

    /**
     * @var string|null
     */
    protected ?string $uploadToken = null;

    /**
     * @desc: 实例
     */
    public function getInstance(): ?UploadManager
    {
        if (!$this->instance) {
            $this->instance = new UploadManager();
        }

        return $this->instance;
    }

    /**
     * @desc: 获取上传令牌
     * @return string
     * @author Tinywan(ShaoBo Wan)
     */
    public function getUploadToken(): string
    {
        if (!$this->uploadToken) {
            $auth = new Auth($this->config['accessKey'], $this->config['secretKey']);
            $this->uploadToken = $auth->uploadToken($this->config['bucket']);
        }

        return $this->uploadToken;
    }

    /**
     * @desc: 上传文件
     * @param array $options
     * @return array
     * @throws BusinessException
     * @author Tinywan(ShaoBo Wan)
     */
    public function uploadFile(array $options = []): array
    {
        try {
            $result = [];
            $dirname = $this->config['dirname'] ?? '';
            $domain = trim($this->config['domain']);
            foreach ($this->files as $key => $file) {
                $uniqueId = $this->getUniqueId($file->getPathname());
                $saveName = $uniqueId . '.' . $file->getUploadExtension();
                $object = $saveName;
                if (!empty($dirname)) {
                    $object = $dirname . $this->dirSeparator . $saveName;
                }
                $temp = [
                    'key' => $key,
                    'origin_name' => $file->getUploadName(),
                    'save_name' => $saveName,
                    'save_path' => $object,
                    'url' => $domain . $this->dirSeparator . $object,
                    'unique_id' => $uniqueId,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getUploadMimeType(),
                    'extension' => $file->getUploadExtension(),
                ];
                list($ret, $err) = $this->getInstance()->putFile($this->getUploadToken(), $object, $file->getPathname());
                if (!empty($err)) {
                    throw new BusinessException((string)$err);
                }
                array_push($result, $temp);
            }
        } catch (\Throwable $exception) {
            throw new BusinessException($exception->getMessage());
        }

        return $result;
    }

    /**
     * @desc 上传服务端文件
     * @param string $filePath
     * @return array
     * @throws BusinessException
     * @author Tinywan(ShaoBo Wan)
     */
    public function uploadServerFile(string $filePath): array
    {
        $file = new \SplFileInfo($filePath);
        if (!$file->isFile()) {
            throw new BusinessException('请检查上传文件是否是一个有效的文件，文件不存在'.$filePath);
        }
        $uniqueId = hash_file($this->algo, $file->getPathname());
        $dirname = $this->config['dirname'] ?? '';
        $object =  $uniqueId . '.' . $file->getExtension();
        if (!empty($dirname)) {
            $object = $dirname . $this->dirSeparator . $object;
        }

        $result = [
            'origin_name' => $file->getRealPath(),
            'save_path' => $object,
            'url' => $this->config['domain'] . $this->dirSeparator . $object,
            'unique_id' => $uniqueId,
            'size' => $file->getSize(),
            'extension' => $file->getExtension(),
        ];

        list($ret, $err) = $this->getInstance()->putFile($this->getUploadToken(), $object, $file->getPathname());
        if (!empty($err)) {
            throw new BusinessException((string)$err);
        }

        return $result;
    }

    /**
     * @desc 上传Base64
     * @param string $base64
     * @param string $extension
     * @return array
     * @throws BusinessException
     * @author Tinywan(ShaoBo Wan)
     */
    public function uploadBase64(string $base64, string $extension = 'png'): array
    {
        $base64 = explode(',', $base64);
        $uniqueId = date('YmdHis') . uniqid();
        $object =  $uniqueId . '.' . $extension;
        $dirname = $this->config['dirname'] ?? '';
        if (!empty($dirname)) {
            $object = $dirname . $this->dirSeparator . $object;
        }

        list($ret, $err) = $this->getInstance()->put($this->getUploadToken(), $object, base64_decode($base64[1]));
        if (!empty($err)) {
            throw new BusinessException((string)$err);
        }

        $imgLen = strlen($base64['1']);
        $fileSize = $imgLen - ($imgLen / 8) * 2;

        return [
            'save_path' => $object,
            'url' => $this->config['domain'] . $this->dirSeparator . $object,
            'unique_id' => $uniqueId,
            'size' => $fileSize,
            'extension' => $extension,
        ];
    }
}