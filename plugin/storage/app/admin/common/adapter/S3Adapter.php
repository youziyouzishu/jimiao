<?php
/**
 * @desc AWS 对象存储适配器
 * @author Tinywan(ShaoBo Wan)
 */
declare(strict_types=1);

namespace plugin\storage\app\admin\common\adapter;

use Aws\S3\S3Client;
use support\exception\BusinessException;
use Throwable;

class S3Adapter extends AdapterAbstract
{
    /**
     * @var S3Client|null
     */
    protected ?S3Client $instance = null;

    /**
     * @desc 对象存储实例
     * @author Tinywan(ShaoBo Wan)
     */
    public function getInstance(): ?S3Client
    {
        if (is_null($this->instance)) {
            $this->instance = new S3Client([
                'version' => $this->config['version'],
                'endpoint' => $this->config['endpoint'],
                'region' => $this->config['region'],
                'use_path_style_endpoint' => $this->config['use_path_style_endpoint'] ?? true,
                'credentials' => [
                    'key' => $this->config['key'],
                    'secret' => $this->config['secret'],
                ],
            ]);
        }

        return $this->instance;
    }

    /**
     * @desc 上传文件
     * @param array $options
     * @return array
     * @throws BusinessException
     * @author Tinywan(ShaoBo Wan)
     */
    public function uploadFile(array $options = []): array
    {
        try {
            $result = [];
            foreach ($this->files as $key => $file) {
                $uniqueId = hash_file($this->algo, $file->getPathname());
                $saveName = $uniqueId . '.' . $file->getUploadExtension();
                $object = $saveName;
                if (!empty($this->config['dirname'])) {
                    $object = $this->config['dirname'] . $this->dirSeparator . $saveName;
                }
                $temp = [
                    'key' => $key,
                    'origin_name' => $file->getUploadName(),
                    'save_name' => $saveName,
                    'save_path' => $object,
                    'url' => $this->config['domain'] . $this->dirSeparator . $object,
                    'unique_id' => $uniqueId,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getUploadMineType(),
                    'extension' => $file->getUploadExtension(),
                ];
                $this->getInstance()->putObject([
                    'Bucket' => $this->config['bucket'],
                    'Key' => $object,
                    'Body' => fopen($file->getPathname(), 'rb'),
                    'ACL' => $this->config['acl']
                ]);
                $result[] = $temp;
            }
        } catch (Throwable $exception) {
            throw new BusinessException($exception->getMessage());
        }

        return $result;
    }

    /**
     * @desc 上传服务端文件
     * @param string $file_path
     * @return array
     * @author Tinywan(ShaoBo Wan)
     */
    public function uploadServerFile(string $file_path): array
    {
        $file = new \SplFileInfo($file_path);
        if (!$file->isFile()) {
            throw new BusinessException('不是一个有效的文件');
        }

        $dirname = $this->config['dirname'] ?? '';
        $uniqueId = hash_file($this->algo, $file->getPathname());
        $object = $uniqueId . '.' . $file->getExtension();
        if (!empty($dirname)) {
            $object = $dirname . $this->dirSeparator . $uniqueId . '.' . $file->getExtension();
        }

        $result = [
            'origin_name' => $file->getRealPath(),
            'save_path' => $object,
            'url' => $this->config['domain'] . $this->dirSeparator . $object,
            'unique_id' => $uniqueId,
            'size' => $file->getSize(),
            'extension' => $file->getExtension(),
        ];

        $this->getInstance()->putObject([
            'Bucket' => $this->config['bucket'],
            'Key' => $object,
            'Body' => fopen($file->getPathname(), 'rb'),
            'ACL' => $this->config['acl']
        ]);

        return $result;
    }

    /**
     * @desc: 上传Base64
     * @param string $base64
     * @param string $extension
     * @return array
     * @author Tinywan(ShaoBo Wan)
     */
    public function uploadBase64(string $base64, string $extension = 'png'): array
    {
        $base64 = explode(',', $base64);
        $uniqueId = date('YmdHis') . uniqid();
        $object = $this->config['dirname'] . $this->dirSeparator . $uniqueId . '.' . $extension;

        $this->getInstance()->putObject([
            'Bucket' => $this->config['bucket'],
            'Key' => $object,
            'Body' => base64_decode($base64[1]),
            'ACL' => $this->config['acl']
        ]);

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
