<?php
/**
 * @desc AdapterAbstract
 * @author Tinywan(ShaoBo Wan)
 * @date 2024/1/14 20:32
 */

declare(strict_types=1);

namespace plugin\storage\app\admin\common\adapter;

use plugin\storage\app\admin\common\ErrorMsg;
use support\exception\BusinessException;
use Webman\Http\UploadFile;

abstract class AdapterAbstract implements AdapterInterface
{
    use ErrorMsg;

    /**
     * @var bool
     */
    public $_isFileUpload;

    /**
     * @var string
     */
    public string $dirSeparator = '/';

    /**
     * 文件存储对象
     */
    protected $files;

    /**
     * 被允许的文件类型列表
     * @var array|mixed
     */
    protected array $allowExtension = [];

    /**
     * 单个文件的最大字节数.
     */
    protected int $singleLimit;

    /**
     * 多个文件的最大数量.
     */
    protected int $totalLimit;

    /**
     * 文件上传的最大数量.
     */
    protected int $nums;

    /**
     * 当前存储配置.
     *
     * @var array
     */
    protected array $config;

    /**
     * 命名规则 eg：md5：对文件使用md5_file散列生成，sha1：对文件使用sha1_file散列生成.
     *
     * @var string
     */
    protected string $algo = 'md5';

    /**
     * @desc AdapterAbstract constructor.
     * @param array $config
     * @throws BusinessException
     */
    public function __construct(array $config = [])
    {
        $this->loadConfig($config);
        $this->_isFileUpload = $config['_is_file_upload'] ?? true;
        if ($this->_isFileUpload) {
            $this->files = request()->file();
            if (!empty($this->config['allow_extension'])) {
                $this->allowExtension = array_merge($this->allowExtension, $this->config['allow_extension']);
            }
            $this->singleLimit = 0;
            $this->totalLimit = 0;
            $this->nums = 0;
            $this->verify();
        }
    }

    /**
     * @param string $base64
     * @param string $extension
     * @return array|bool
     */
    public function uploadBase64(string $base64, string $extension = 'png')
    {
        return $this->setError(false, '暂不支持');
    }

    /**
     * @param string $filePath
     * @return array|bool
     */
    public function uploadServerFile(string $filePath)
    {
        return $this->setError(false, '暂不支持');
    }

    /**
     * @desc 加载配置文件
     * @param array $config
     * @author Tinywan(ShaoBo Wan)
     */
    protected function loadConfig(array $config)
    {
        $this->config = $config;
        if (isset($this->config['dirname']) && is_callable($this->config['dirname'])) {
            $this->config['dirname'] = (string) $this->config['dirname']() ?: $this->config['dirname'];
        }
    }

    /**
     * @desc: 文件验证
     * @throws BusinessException
     * @author Tinywan(ShaoBo Wan)
     */
    protected function verify()
    {
        if (!$this->files) {
            throw new BusinessException('未找到符合条件的文件资源');
        }
        foreach ($this->files as $file) {
            if (!$file->isValid()) {
                throw new BusinessException('未选择文件或者无效的文件');
            }
        }
        $this->allowExtension();
    }

    /**
     * @desc: 获取文件大小
     * @author Tinywan(ShaoBo Wan)
     */
    protected function getSize(UploadFile $file): int
    {
        return $file->getSize();
    }

    /**
     * @desc getUniqueId
     * @param string $pathname
     * @return string
     * @author Tinywan(ShaoBo Wan)
     */
    protected function getUniqueId(string $pathname): string
    {
        return hash_file('sha1', $pathname);
    }

    /**
     * @desc 允许上传文件
     * @throws BusinessException
     * @author Tinywan(ShaoBo Wan)
     * @return bool
     */
    protected function allowExtension(): bool
    {
        if ((!empty($this->allowExtension))) {
            foreach ($this->files as $file) {
                $fileExtension = $file->getUploadExtension();
                if (!in_array($fileExtension, $this->allowExtension,true)){
                    throw new BusinessException('暂不支持文件扩展名 .'.$fileExtension);
                }
            }
        }
        return true;
    }

}