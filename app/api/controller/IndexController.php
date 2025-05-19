<?php

namespace app\api\controller;

use app\admin\model\User;
use app\admin\model\UserWithdraw;
use app\api\basic\Base;
use app\api\service\Pay;
use GuzzleHttp\Client;
use Overtrue\EasySms\Exceptions\InvalidArgumentException;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;
use plugin\sms\api\Sms;
use support\Log;
use support\Request;
use Tinywan\Jwt\JwtToken;

class IndexController extends Base
{

    protected array $noNeedLogin = ['*'];
    public function index(Request $request)
    {

        $user = User::find(1);
        $token = JwtToken::generateToken([
            'id' => $user->id,
            'client' => JwtToken::TOKEN_CLIENT_MOBILE
        ]);

        return $this->success('发送成功', ['user'=>$user,'token' => $token]);
    }

    function alipay()
    {


    }

}
