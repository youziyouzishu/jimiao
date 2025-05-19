<?php

namespace app\api\controller;

use app\admin\model\User;
use app\api\basic\Base;
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
