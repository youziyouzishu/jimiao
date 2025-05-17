<?php
/**
 * @desc Storage 接口
 * @author Tinywan(ShaoBo Wan)
 * @email 756684177@qq.com
 * @date 2024/1/14 18:23
 */

declare(strict_types=1);

namespace plugin\storage\api;


use plugin\admin\app\model\Option;
use plugin\storage\app\admin\common\adapter\AdapterFactory;
use plugin\storage\app\admin\common\adapter\AdapterInterface;
use plugin\storage\app\admin\common\adapter\CosAdapter;
use plugin\storage\app\admin\common\adapter\LocalAdapter;
use plugin\storage\app\admin\common\adapter\OssAdapter;
use plugin\storage\app\admin\common\adapter\QiniuAdapter;
use plugin\storage\app\admin\common\adapter\S3Adapter;

/**
 * @see Storage
 * @mixin Storage
 *
 * @method static array uploadFile(array $config = [])  上传文件
 * @method static array uploadBase64(string $base64, string $extension = 'png') 上传Base64文件
 * @method static array uploadServerFile(string $file_path)  上传服务端文件
 */
class Storage
{
    /** Option表的name字段值 */
    const SETTING_OPTION_NAME = 'storage';

    /** 本地对象存储. */
    public const MODE_LOCAL = 'local';

    /** 阿里云对象存储. */
    public const MODE_OSS = 'oss';

    /** 腾讯云对象存储. */
    public const MODE_COS = 'cos';

    /** 七牛云对象存储. */
    public const MODE_QINIU = 'qiniu';

    /** S3对象存储. */
    public const MODE_S3 = 's3';

    /** 支持存储. */
    static array $allowStorage = [
        self::MODE_LOCAL,
        self::MODE_OSS,
        self::MODE_COS,
        self::MODE_QINIU,
        self::MODE_S3
    ];

    protected static bool $initialized = false;

    protected static function initAdapters()
    {
        if (self::$initialized) {
            return;
        }
        AdapterFactory::register(self::MODE_LOCAL, LocalAdapter::class);
        AdapterFactory::register(self::MODE_OSS, OssAdapter::class);
        AdapterFactory::register(self::MODE_COS, CosAdapter::class);
        AdapterFactory::register(self::MODE_QINIU, QiniuAdapter::class);
        AdapterFactory::register(self::MODE_S3, S3Adapter::class);
        self::$initialized = true;
    }

    /**
     * @desc 获取配置
     * @param string|null $name
     * @return array
     * @author Tinywan(ShaoBo Wan)
     */
    public static function getConfig(string $name = null): ?array
    {
        $name = Storage::SETTING_OPTION_NAME . '_' . $name;
        $config = Option::where('name', $name)->value('value');
        return $config ? json_decode($config, true) : [];
    }

    /**
     * @desc 获取默认配置
     * @return array
     * @author Tinywan(ShaoBo Wan)
     */
    public static function getDefaultConfig(): ?array
    {
        $basicConfig = Option::where('name', Storage::SETTING_OPTION_NAME . '_basic')->value('value');
        if (empty($basicConfig)) {
            $basicConfig = [
                'default' => self::MODE_LOCAL,
                'single_limit' => 1024,
                'total_limit' => 1024,
                'nums' => 1,
                'include' => ['png'],
                'exclude' => ['mp4'],
            ];
        } else {
            $basicConfig = json_decode($basicConfig, true);
        }
        return $basicConfig;
    }

    /**
     * @desc 存储磁盘
     * @param string|null $storage
     * @param bool $is_file_upload
     * @return AdapterInterface
     * @author Tinywan(ShaoBo Wan)
     */
    public static function disk(string $storage = null, bool $is_file_upload = true): AdapterInterface
    {
        self::initAdapters();
        $defaultConfig = self::getDefaultConfig();
        if (empty($storage)) {
            $adapter = $defaultConfig['default'];
            $adapterConfig = self::getConfig($adapter);
        } else {
            $adapter = $storage;
            $adapterConfig = self::getConfig($storage);
        }
        $config = array_merge($defaultConfig, $adapterConfig, ['_is_file_upload' => $is_file_upload]);
//        $obj = config('plugin.storage.app.' . $adapter);
//        return new $obj($config);
        return AdapterFactory::get($adapter, $config);
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @author Tinywan(ShaoBo Wan)
     */
    public static function __callStatic($name, $arguments)
    {
        return static::disk()->{$name}(...$arguments);
    }

}