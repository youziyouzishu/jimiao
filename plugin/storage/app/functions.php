<?php

use support\Response;

/**
 * @desc webman-admin 上传头像
 * @return Response
 * @author Tinywan(ShaoBo Wan)
 */
function storage_admin_upload_avatar(): Response
{
    try {
        $avatar = \plugin\storage\api\Storage::uploadFile();
    } catch (Throwable $th) {
        return json(['code' => 1, 'msg' => $th->getMessage()]);
    }
    return json([
        'code' => 0,
        'msg' => '上传成功',
        'data' => [
            'url' => $avatar[0]['url'],
        ]
    ]);
}

/**
 * @desc webman-admin 上传文件
 * @return Response
 * @author Tinywan(ShaoBo Wan)
 */
function storage_admin_upload_file(): Response
{
    try {
        $file = \plugin\storage\api\Storage::uploadFile();
    } catch (Throwable $th) {
        return json(['code' => 1, 'msg' => $th->getMessage()]);
    }
    return json([
        'code' => 0,
        'msg' => '上传成功',
        'data' => [
            'url' => $file[0]['url'],
            'name' => $file[0]['origin_name'],
            'size' => $file[0]['size'],
        ]
    ]);
}

/**
 * @desc webman-admin 上传图片
 * @return Response
 * @author Tinywan(ShaoBo Wan)
 */
function storage_admin_upload_image(): Response
{
    try {
        $image = \plugin\storage\api\Storage::uploadFile();
    } catch (Throwable $th) {
        return json(['code' => 500, 'msg' => $th->getMessage()]);
    }
    return json([
        'code' => 0,
        'msg' => '上传成功',
        'data' => [
            'url' => $image[0]['url'],
            'name' => $image[0]['origin_name'],
            'size' => $image[0]['size'],
        ]
    ]);
}

if (!function_exists('mb_ltrim')) {

}

/**
 * @desc webman-admin 上传附件
 * @return Response
 * @author Tinywan(ShaoBo Wan)
 */
function storage_admin_upload_attachment(): Response
{
    try {
        $attachment = \plugin\storage\api\Storage::uploadFile();
        $data = current($attachment);

        $imageWith = $imageHeight = 0;
        if ($imgInfo = getimagesize($data['url'])) {
            [$imageWith, $imageHeight] = $imgInfo;
        }
        $upload = new \plugin\admin\app\model\Upload;
        $upload->admin_id = admin_id();
        $upload->name = $data['origin_name'];
        $upload->url = $data['url'];
        $upload->file_size = $data['size'];
        $upload->mime_type = $data['mime_type'];
        $upload->image_width = $imageWith;
        $upload->image_height = $imageHeight;
        $upload->ext = $data['extension'];
        $upload->category = request()->post('category');
        $upload->save();
    } catch (Throwable $th) {
        return json(['code' => 500, 'msg' => $th->getMessage()]);
    }
    return json([
        'code' => 0,
        'msg' => '上传成功',
        'data' => [
            'url' => $attachment[0]['url'],
            'name' => $attachment[0]['origin_name'],
            'size' => $attachment[0]['size'],
        ]
    ]);
}