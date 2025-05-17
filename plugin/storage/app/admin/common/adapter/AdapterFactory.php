<?php
/**
 * @desc 存储适配器工厂类,用于管理和创建不同的存储适配器实例
 * @author Tinywan(ShaoBo Wan)
 * @date 2025/5/5 23:32
 */
declare(strict_types=1);

namespace plugin\storage\app\admin\common\adapter;

/**
 * 存储适配器工厂类
 * Class AdapterFactory
 * @package plugin\storage\app\admin\common\adapter
 */
class AdapterFactory
{
    /**
     * 存储已注册的适配器类
     * @var array
     */
    protected static array $adapters = [];

    /**
     * 注册存储适配器
     * @param string $name 适配器名称
     * @param string $class 适配器类名
     * @return void
     */
    public static function register(string $name, string $class)
    {
        self::$adapters[$name] = $class;
    }

    /**
     * 获取存储适配器实例
     * @param string $name 适配器名称
     * @param array $config 配置参数
     * @return mixed 返回适配器实例
     * @throws \InvalidArgumentException 当适配器未注册时抛出异常
     */
    public static function get(string $name, array $config = [])
    {
        if (!isset(self::$adapters[$name])) {
            throw new \InvalidArgumentException("存储适配器[{$name}]未注册");
        }
        $class = self::$adapters[$name];
        return new $class($config);
    }

    /**
     * 获取所有已注册的存储适配器
     * @return array 返回适配器数组
     */
    public static function all(): array
    {
        return self::$adapters;
    }
}