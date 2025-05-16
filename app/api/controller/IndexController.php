<?php

namespace app\api\controller;

use app\api\basic\Base;
use Overtrue\EasySms\Exceptions\InvalidArgumentException;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;
use plugin\sms\api\Sms;
use support\Request;

class IndexController extends Base
{

    protected array $noNeedLogin = ['*'];
    public function index(Request $request)
    {


        try {
            $tagName = 'sendCaptcha';
            Sms::sendByTag('13781176253', $tagName, [
                'code' => '1234'
            ]);
        } catch (InvalidArgumentException|NoGatewayAvailableException $e) {
            return $this->fail($e->getMessage());
        }

        return $this->success('发送成功');
    }

}
