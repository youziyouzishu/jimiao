<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use support\Request;
use support\Response;
use Webman\Route;

Route::fallback(function () {
    if (request()->header('accept') == 'application/json'){
        throw new \Tinywan\ExceptionHandler\Exception\RouteNotFoundException();
    }else{
        return new Response(404, [], file_get_contents(base_path('plugin' . DIRECTORY_SEPARATOR. 'admin' . DIRECTORY_SEPARATOR . 'public') . '/demos/error/404.html'));
    }
});

Route::any('/h5/', function (Request $request) {
    $dirPath = public_path() . '/h5/';

    // 获取目录下所有以 index. 开头的文件
    $files = glob($dirPath . 'index*html');

    if (!empty($files)) {
        // 取第一个匹配的文件
        $filePath = $files[0];
        return new Response(200, [], file_get_contents($filePath));
    }

    // 如果没有找到匹配的文件，则返回 404
    return new Response(404, [], file_get_contents(base_path('plugin' . DIRECTORY_SEPARATOR. 'admin' . DIRECTORY_SEPARATOR . 'public') . '/demos/error/404.html'));
});






