<?php
/**
 * @desc AdapterInterface
 * @author Tinywan(ShaoBo Wan)
 * @date 2024/1/14 20:32
 */

declare(strict_types=1);
namespace plugin\storage\app\admin\common\adapter;

interface AdapterInterface
{
    /**
     * @desc: 上传文件
     *
     * @return mixed
     */
    public function uploadFile(array $options);

    /**
     * @desc: 上传服务端文件
     *
     * @return mixed
     */
    public function uploadServerFile(string $filePath);

    /**
     * @desc: Base64上传文件
     *
     * @return mixed
     */
    public function uploadBase64(string $base64, string $extension = 'png');
}