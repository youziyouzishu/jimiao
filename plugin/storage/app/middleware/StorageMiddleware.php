<?php
/**
 * @desc StorageMiddleware.php
 * @author Tinywan(ShaoBo Wan)
 * @date 2024/11/14 上午9:01
 */
declare(strict_types=1);

namespace plugin\storage\app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class StorageMiddleware implements MiddlewareInterface
{
    /**
     * @desc Process an incoming server request.
     * @param Request $request
     * @param callable $handler
     * @return Response
     * @author Tinywan(ShaoBo Wan)
     */
    public function process(Request $request, callable $handler): Response
    {
        if ($request->controller === 'plugin\admin\app\controller\UploadController') {
            if ($request->action === 'avatar') {
                return storage_admin_upload_avatar();
            } elseif ($request->action === 'image') {
                return storage_admin_upload_image();
            } elseif ($request->action === 'file') {
                return storage_admin_upload_file();
            } elseif ($request->action === 'insert' && $request->method() !== 'GET') {
                return storage_admin_upload_attachment();
            }
        }
        return $handler($request);
    }
}