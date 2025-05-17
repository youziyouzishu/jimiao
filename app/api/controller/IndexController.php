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
        $withdraw = UserWithdraw::find(1);
        $client = new Client();
        $url = 'http://8.130.185.57:3000/api/disburse/alipay';
        $clientId = 'test_client';
        $secretKey = 'test_secret_key_123';
        $timestamp = time();
        $requestParams = [
            'clientId' => $clientId,
            'receiver' => $withdraw->ali_account,
            'trans_amount' => $withdraw->amount,
            'receiver_name' => $withdraw->ali_name,
            'out_biz_no' => $withdraw->ordersn,
            'timestamp' => $timestamp
        ];

        $signature = Pay::generateSignature(
            $clientId,
            $timestamp,
            $requestParams,
            $secretKey
        );
        $requestData = array_merge($requestParams, ['signature' => $signature]);
        $result = $client->post($url, [
            'json' => $requestData,
        ]);
        dump('请求body');
        dump(json_encode($requestData));
        $result = $result->getBody()->getContents();
        $result = json_decode($result);
        dump('响应body');
        dump($result);
//        if ($result->success == false){
//            $withdraw->status = 2;
//            $withdraw->reason = $result->message;
//        }else{
//            $withdraw->status = 1;
//        }
//        $withdraw->save();
//        return $this->success();
    }

}
