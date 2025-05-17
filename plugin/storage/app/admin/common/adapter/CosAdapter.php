<?php
/**
 * @desc CosAdapter
 * @author Tinywan(ShaoBo Wan)
 * @email 756684177@qq.com
 */

declare(strict_types=1);

namespace plugin\storage\app\admin\common\adapter;


use Qcloud\Cos\Client;
use Qcloud\Cos\Exception\CosException;
use support\exception\BusinessException;

class CosAdapter extends AdapterAbstract
{
    /**
     * @var Client|null
     */
    protected ?Client $instance = null;

    /**
     * @desc: 对象存储实例
     */
    public function getInstance(): ?Client
    {
        if (is_null($this->instance)) {
            $this->instance = new Client([
                'region' => $this->config['region'] ?? 'ap-shanghai',
                'schema' => 'https',
                'credentials' => [
                    'secretId' => $this->config['secretId'],
                    'secretKey' => $this->config['secretKey'],
                ],
            ]);
        }

        return $this->instance;
    }

    /**
     * @desc: 方法描述
     *
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
                $this->getInstance()->putObject([
                    'Bucket' => $this->config['bucket'],
                    'Key' => $object,
                    'Body' => fopen($file->getPathname(), 'rb'),
                ]);
                $result[] = $temp;
            }
        } catch (CosException $exception) {
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
        $object = $uniqueId . '.' . $file->getExtension();
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

        $this->getInstance()->putObject([
            'Bucket' => $this->config['bucket'],
            'Key' => $object,
            'Body' => fopen($file->getPathname(), 'rb'),
        ]);

        return $result;
    }

    /**
     * @desc 上传Base64
     * @param string $base64
     * @param string $extension
     * @return array
     * @author Tinywan(ShaoBo Wan)
     */
    public function uploadBase64(string $base64, string $extension = 'png'): array
    {
        $base64 = explode(',', $base64);
        $uniqueId = date('YmdHis') . uniqid();
        $object = $uniqueId . '.' . $extension;
        $dirname = $this->config['dirname'] ?? '';
        if (!empty($dirname)) {
            $object = $dirname . $this->dirSeparator . $object;
        }
        $this->getInstance()->putObject([
            'Bucket' => $this->config['bucket'],
            'Key' => $object,
            'Body' => base64_decode($base64[1]),
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