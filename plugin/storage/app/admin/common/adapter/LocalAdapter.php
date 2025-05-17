<?php
/**
 * @desc LocalAdapter
 * @author Tinywan(ShaoBo Wan)
 * @email 756684177@qq.com
 */

declare(strict_types=1);

namespace plugin\storage\app\admin\common\adapter;

use support\exception\BusinessException;

class LocalAdapter extends AdapterAbstract
{
    /**
     * @desc uploadFile
     * @param array $options
     * @return array
     * @throws BusinessException
     * @author Tinywan(ShaoBo Wan)
     */
    public function uploadFile(array $options = []): array
    {
        $result = [];
        $root = $this->config['root'] ?? '';
        if (empty($root)) {
            $root = runtime_path();
        } elseif ($root === 'public') {
            $root = public_path();
        } elseif ($root === 'runtime') {
            $root = runtime_path();
        }
        $dirname = $this->config['dirname'] ?? '';
        $basePath = $root . DIRECTORY_SEPARATOR;
        if (!$this->createDir($basePath)) {
            throw new BusinessException('文件夹创建失败，请核查是否有对应权限。');
        }
        $domain = $this->config['domain'] ?? '';
        foreach ($this->files as $key => $file) {
            $uniqueId = $this->getUniqueId($file->getPathname());
            $saveFilename = $uniqueId . '.' . $file->getUploadExtension();
            $savePath = $basePath . $saveFilename;
            $url = $domain . $this->dirSeparator . $saveFilename;
            if (!empty($dirname)) {
                $savePath = $root . $this->dirSeparator . $dirname . $this->dirSeparator . $saveFilename;
                $url = $domain . $this->dirSeparator . $dirname . $this->dirSeparator . $saveFilename;
            }
            $temp = [
                'key' => $key,
                'origin_name' => $file->getUploadName(),
                'save_name' => $saveFilename,
                'save_path' => $savePath,
                'url' => $url,
                'unique_id' => $uniqueId,
                'size' => $file->getSize(),
                'mime_type' => $file->getUploadMimeType(),
                'extension' => $file->getUploadExtension(),
            ];
            $file->move($savePath);
            $result[] = $temp;
        }

        return $result;
    }

    /**
     * @desc createDir
     * @param string $path
     * @return bool
     * @author Tinywan(ShaoBo Wan)
     */
    protected function createDir(string $path): bool
    {
        if (is_dir($path)) {
            return true;
        }

        $parent = dirname($path);
        if (!is_dir($parent)) {
            if (!$this->createDir($parent)) {
                return false;
            }
        }

        return mkdir($path);
    }
}