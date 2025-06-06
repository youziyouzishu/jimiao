<?php
/**
 * @desc ErrorMsg
 * @author Tinywan(ShaoBo Wan)
 * @email 756684177@qq.com
 */
declare(strict_types=1);

namespace plugin\storage\app\admin\common;

trait ErrorMsg
{
    /**
     * 错误消息.
     */
    public $error = [
        'message' => '错误消息',
        'data' => [],
    ];

    /**
     * 设置错误.
     *
     * @param bool   $success 是否成功
     * @param string $message 错误消息
     * @param array  $data    消息体
     */
    public function setError(bool $success, string $message, array $data = []): bool
    {
        $this->error = [
            'message' => $message,
            'data' => $data,
        ];

        return $success;
    }

    /**
     * @desc: 获取错误信息
     *
     * @author Tinywan(ShaoBo Wan)
     */
    public function getError(): array
    {
        return $this->error;
    }

    /**
     * @desc: 获取错误信息
     *
     * @author Tinywan(ShaoBo Wan)
     */
    public function getMessage(): string
    {
        return $this->error['message'];
    }

    /**
     * @desc: 方法描述
     *
     * @author Tinywan(ShaoBo Wan)
     */
    public function returnData(bool $success, string $message = '', int $code = 0, array $data = []): array
    {
        return [
            'success' => $success,
            'message' => $message,
            'code' => $code,
            'data' => $data,
        ];
    }
}