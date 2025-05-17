<?php
/**
 * @desc 存储设置
 * @author Tinywan(ShaoBo Wan)
 * @email 756684177@qq.com
 */
declare(strict_types=1);

namespace plugin\storage\app\admin\controller;


use plugin\admin\app\model\Option;
use plugin\storage\api\Storage;
use support\Request;
use support\Response;

class SettingController
{
    /**
     * @desc 设置首页
     * @return Response
     * @author Tinywan(ShaoBo Wan)
     */
    public function index(): Response
    {
        return view('setting/index');
    }

    /**
     * @desc 获取设置
     * @return Response
     * @author Tinywan(ShaoBo Wan)
     */
    public function get(): Response
    {
        $settingList = Option::where('name', 'like', Storage::SETTING_OPTION_NAME . '_%')
            ->select(['name', 'value'])
            ->get()
            ->toArray();
        $result = [];
        foreach ($settingList as $setting) {
            if ($setting['name'] === Storage::SETTING_OPTION_NAME . '_' . Storage::MODE_LOCAL) {
                $result['saveLocalSetting'] = json_decode($setting['value'], true);
            } elseif ($setting['name'] === Storage::SETTING_OPTION_NAME . '_' . Storage::MODE_OSS) {
                $result['saveOssSetting'] = json_decode($setting['value'], true);
            } elseif ($setting['name'] === Storage::SETTING_OPTION_NAME . '_' . Storage::MODE_COS) {
                $result['saveCosSetting'] = json_decode($setting['value'], true);
            } elseif ($setting['name'] === Storage::SETTING_OPTION_NAME . '_' . Storage::MODE_QINIU) {
                $result['saveQiniuSetting'] = json_decode($setting['value'], true);
            } elseif ($setting['name'] === Storage::SETTING_OPTION_NAME . '_' . Storage::MODE_S3) {
                $result['saveS3Setting'] = json_decode($setting['value'], true);
            } elseif ($setting['name'] === Storage::SETTING_OPTION_NAME . '_basic') {
                $result['saveBasicSetting'] = json_decode($setting['value'], true);
            }
        }
        return json(['code' => 0, 'msg' => 'ok', 'data' => $result]);
    }

    /**
     * 基础设置
     * @param Request $request
     * @return Response
     */
    public function saveBasic(Request $request): Response
    {
        $data = [
            'default' => $request->post('default'),
            'single_limit' => $request->post('single_limit'),
            'total_limit' => $request->post('total_limit'),
            'nums' => $request->post('nums'),
            'allow_extension' => [],
        ];
        $allowExtension = $request->post('allow_extension');
        if (!empty($allowExtension)) {
            $data['allow_extension'] = explode(',', $allowExtension);
        }
        $value = json_encode($data, JSON_UNESCAPED_UNICODE);
        $name = Storage::SETTING_OPTION_NAME . '_basic';
        $option = Option::where('name', $name)->first();
        if ($option) {
            Option::where('name', $name)->update(['value' => $value]);
        } else {
            $option = new Option();
            $option->name = $name;
            $option->value = $value;
            $option->save();
        }
        return json(['code' => 0, 'msg' => 'ok']);
    }

    /**
     * 本地设置
     * @param Request $request
     * @return Response
     */
    public function saveLocal(Request $request): Response
    {
        $dataFormat = [
            'root' => str_replace(' ', '', $request->post('root')),
            'dirname' => str_replace(' ', '', $request->post('dirname')),
            'domain' => str_replace(' ', '', $request->post('domain')),
        ];

        $value = json_encode($dataFormat, JSON_UNESCAPED_UNICODE);
        $name = Storage::SETTING_OPTION_NAME . '_' . Storage::MODE_LOCAL;
        $option = Option::where('name', $name)->first();
        if ($option) {
            Option::where('name', $name)->update(['value' => $value]);
        } else {
            $option = new Option();
            $option->name = $name;
            $option->value = $value;
            $option->save();
        }
        return json(['code' => 0, 'msg' => 'ok']);
    }

    /**
     * 阿里云
     * @param Request $request
     * @return Response
     */
    public function saveOss(Request $request): Response
    {
        $dataFormat = [
            'accessKeyId' => str_replace(' ', '', $request->post('accessKeyId')),
            'accessKeySecret' => str_replace(' ', '', $request->post('accessKeySecret')),
            'bucket' => str_replace(' ', '', $request->post('bucket')),
            'domain' => str_replace(' ', '', $request->post('domain')),
            'endpoint' => str_replace(' ', '', $request->post('endpoint')),
            'dirname' => str_replace(' ', '', $request->post('dirname')),
        ];

        $value = json_encode($dataFormat, JSON_UNESCAPED_UNICODE);
        $name = Storage::SETTING_OPTION_NAME . '_' . Storage::MODE_OSS;
        $option = Option::where('name', $name)->first();
        if ($option) {
            Option::where('name', $name)->update(['value' => $value]);
        } else {
            $option = new Option();
            $option->name = $name;
            $option->value = $value;
            $option->save();
        }
        return json(['code' => 0, 'msg' => 'ok']);
    }

    /**
     * 腾讯云
     * @param Request $request
     * @return Response
     */
    public function saveCos(Request $request): Response
    {
        $dataFormat = [
            'secretId' => str_replace(' ', '', $request->post('secretId')),
            'secretKey' => str_replace(' ', '', $request->post('secretKey')),
            'bucket' => str_replace(' ', '', $request->post('bucket')),
            'domain' => str_replace(' ', '', $request->post('domain')),
            'region' => str_replace(' ', '', $request->post('region')),
            'dirname' => str_replace(' ', '', $request->post('dirname')),
        ];
        $value = json_encode($dataFormat, JSON_UNESCAPED_UNICODE);
        $name = Storage::SETTING_OPTION_NAME . '_' . Storage::MODE_COS;
        $option = Option::where('name', $name)->first();
        if ($option) {
            Option::where('name', $name)->update(['value' => $value]);
        } else {
            $option = new Option();
            $option->name = $name;
            $option->value = $value;
            $option->save();
        }
        return json(['code' => 0, 'msg' => 'ok']);
    }

    /**
     * saveQiniu
     * @param Request $request
     * @return Response
     */
    public function saveQiniu(Request $request): Response
    {
        $dataFormat = [
            'accessKey' => str_replace(' ', '', $request->post('accessKey')),
            'secretKey' => str_replace(' ', '', $request->post('secretKey')),
            'bucket' => str_replace(' ', '', $request->post('bucket')),
            'domain' => str_replace(' ', '', $request->post('domain')),
            'dirname' => str_replace(' ', '', $request->post('dirname')),
        ];
        $value = json_encode($dataFormat, JSON_UNESCAPED_UNICODE);
        $name = Storage::SETTING_OPTION_NAME . '_' . Storage::MODE_QINIU;
        $option = Option::where('name', $name)->first();
        if ($option) {
            Option::where('name', $name)->update(['value' => $value]);
        } else {
            $option = new Option();
            $option->name = $name;
            $option->value = $value;
            $option->save();
        }
        return json(['code' => 0, 'msg' => 'ok']);
    }

    /**
     * saveS3
     * @param Request $request
     * @return Response
     */
    public function saveS3(Request $request): Response
    {
        $value = json_encode([
            'key' => str_replace(' ', '', $request->post('key')),
            'secret' => str_replace(' ', '', $request->post('secret')),
            'bucket' => str_replace(' ', '', $request->post('bucket')),
            'dirname' => str_replace(' ', '', $request->post('dirname')),
            'domain' => str_replace(' ', '', $request->post('domain')),
            'region' => str_replace(' ', '', $request->post('region')),
            'version' => str_replace(' ', '', $request->post('version')),
            'endpoint' => str_replace(' ', '', $request->post('endpoint')),
            'acl' => str_replace(' ', '', $request->post('acl')),
        ], JSON_UNESCAPED_UNICODE);
        $name = Storage::SETTING_OPTION_NAME . '_' . Storage::MODE_S3;
        $option = Option::where('name', $name)->first();
        if ($option) {
            Option::where('name', $name)->update(['value' => $value]);
        } else {
            $option = new Option();
            $option->name = $name;
            $option->value = $value;
            $option->save();
        }
        return json(['code' => 0, 'msg' => 'ok']);
    }

    /**
     * @desc 测试上传
     * @param Request $request
     * @return Response
     */
    public function testUpload(Request $request): Response
    {
        /** 阿里云OSS上传 */
        $res = Storage::uploadFile();

        /** 服务端文件上传 */
        // $serverFile = runtime_path() . DIRECTORY_SEPARATOR . '2024.png';
        // $res = Storage::disk(Storage::MODE_OSS, false)->uploadServerFile($serverFile);
        return json(['code' => 0, 'msg' => 'ok', 'data' => $res]);
    }
}