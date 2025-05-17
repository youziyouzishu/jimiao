<?php
/**
 * @desc OssAdapter
 * @author Tinywan(ShaoBo Wan)
 * @email 756684177@qq.com
 */

declare(strict_types=1);

namespace plugin\storage\app\admin\common\adapter;


use OSS\Core\OssException;
use OSS\Http\RequestCore_Exception;
use OSS\OssClient;
use support\exception\BusinessException;

class OssAdapter extends AdapterAbstract
{
    /**
     * @var OssClient|null
     */
    protected ?OssClient $instance = null;

    /**
     * @desc: OSS实例
     *
     */
    public function getInstance(): ?OssClient
    {
        if (is_null($this->instance)) {
            $this->instance = new OssClient(
                $this->config['accessKeyId'],
                $this->config['accessKeySecret'],
                $this->config['endpoint']
            );
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
                $upload = $this->getInstance()->uploadFile($this->config['bucket'], $object, $file->getPathname());
                if (!isset($upload['info']) && 200 != $upload['info']['http_code']) {
                    throw new BusinessException((string)$upload);
                }
                $result[] = $temp;
            }
        } catch (\Throwable | OssException $exception) {
            throw new BusinessException($exception->getMessage());
        }

        return $result;
    }

    /**
     * @desc 上传Base64
     * @param string $base64
     * @param string $extension
     * @return array|bool
     * @throws RequestCore_Exception
     * @author Tinywan(ShaoBo Wan)
     */
    public function uploadBase64(string $base64, string $extension = 'png')
    {
        $base64 = explode(',', $base64);
        $uniqueId = date('YmdHis') . uniqid();
        $object =  $uniqueId . '.' . $extension;
        $dirname = $this->config['dirname'] ?? '';
        if (!empty($dirname)) {
            $object = $dirname . $this->dirSeparator . $object;
        }
        try {
            $result = $this->getInstance()->putObject($this->config['bucket'], $object, base64_decode($base64[1]));
            if (!isset($result['info']) && 200 != $result['info']['http_code']) {
                return $this->setError(false, (string)$result);
            }
        } catch (OssException $e) {
            return $this->setError(false, $e->getMessage());
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

    /**
     * @desc 上传服务端文件
     * @param string $filePath
     * @return array
     * @throws BusinessException
     * @throws OssException
     * @throws RequestCore_Exception
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
        $upload = $this->getInstance()->uploadFile($this->config['bucket'], $object, $file->getRealPath());
        if (!isset($upload['info']) && 200 != $upload['info']['http_code']) {
            throw new BusinessException((string)$upload);
        }

        return $result;
    }

    // 初始化分片上传
    public function initMultipartUpload($object): array
    {
        $ossClient = $this->getInstance();
        $bucket = $this->config['bucket'];
        try {
            $uploadId = $ossClient->initiateMultipartUpload($bucket, $object);
            return ['code' => 0, 'upload_id' => $uploadId];
        } catch (OssException $e) {
            return ['code' => 1, 'msg' => $e->getMessage()];
        }
    }

    // 上传单个分片
    public function uploadPart($object, $uploadId, $partNumber, $content): array
    {
        $ossClient = $this->getInstance();
        $bucket = $this->config['bucket'];
        try {
            $eTag = $ossClient->uploadPart($bucket, $object, $uploadId, [
                'body' => $content,
                'partNumber' => $partNumber
            ]);
            return ['code' => 0, 'etag' => $eTag];
        } catch (OssException $e) {
            return ['code' => 1, 'msg' => $e->getMessage()];
        }
    }

    // 完成分片上传

    /**
     * @throws RequestCore_Exception
     */
    public function completeMultipartUpload($object, $uploadId, $parts): array
    {
        $ossClient = $this->getInstance();
        $bucket = $this->config['bucket'];
        // $parts 需为 [['PartNumber'=>1,'ETag'=>'"etag1"'], ...]，且按 PartNumber 升序
        usort($parts, fn($a, $b) => $a['PartNumber'] - $b['PartNumber']);
        try {
            $result = $ossClient->completeMultipartUpload($bucket, $object, $uploadId, $parts);
            return ['code' => 0, 'url' => $result['info']['url'] ?? ''];
        } catch (OssException $e) {
            return ['code' => 1, 'msg' => $e->getMessage()];
        }
    }

}