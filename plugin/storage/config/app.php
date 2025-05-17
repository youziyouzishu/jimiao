<?php

return [
    'debug' => true,
    'controller_suffix' => 'Controller',
    'controller_reuse' => false,
    'version' => '1.6.0',
    'local' => \plugin\storage\app\admin\common\adapter\LocalAdapter::class,
    'oss' => \plugin\storage\app\admin\common\adapter\OssAdapter::class,
    'cos' => \plugin\storage\app\admin\common\adapter\CosAdapter::class,
    'qiniu' => \plugin\storage\app\admin\common\adapter\QiniuAdapter::class,
    's3' => \plugin\storage\app\admin\common\adapter\S3Adapter::class,
];
