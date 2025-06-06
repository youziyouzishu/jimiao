<?php

namespace app\api\controller;

use app\admin\model\User;
use app\api\basic\Base;
use Carbon\Carbon;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\OfficialAccount\Application;
use support\Request;
use Tinywan\Jwt\JwtToken;

class AccountController extends Base
{
    protected array $noNeedLogin = ['*'];

    /**
     * 登录
     * @param Request $request
     * @return \support\Response
     */
    function login(Request $request)
    {
        $code = $request->post('code');
        try {
            $app = new Application(config('wechat.Offical'));
        } catch (\Throwable $e) {
            return $this->fail('微信登录失败');
        }
        $oauth = $app->getOAuth();
        $ret = $oauth->userFromCode($code);
        $openid = $ret->getId();
        $raw = $ret->getRaw();
        $nickname = $raw['nickname'];
        $avatar = $raw['headimgurl'];
        $unionid = $raw['unionid'];
        $user = User::where('unionid', $unionid)->first();
        if (!$user){
            $user = User::create([
                'nickname' => !empty($nickname) ? $nickname : '用户' . mt_rand(1000, 9999),
                'avatar' => !empty($avatar) ? $avatar : 'https://mc.allprocessin.xin/app/admin/avatar.png',
                'join_time' => Carbon::now()->toDateTimeString(),
                'join_ip' => $request->getRealIp(),
                'last_time' => Carbon::now()->toDateTimeString(),
                'last_ip' => $request->getRealIp(),
                'openid' => $openid,
                'unionid' => $unionid
            ]);
        }else{
            if ($user->status == 1){
                return $this->fail('账号已被封禁');
            }
            $user->last_time = Carbon::now()->toDateTimeString();
            $user->last_ip = $request->getRealIp();
            $user->unionid = $unionid;
            $user->save();
        }
        $token = JwtToken::generateToken([
            'id' => $user->id,
            'client' => JwtToken::TOKEN_CLIENT_MOBILE
        ]);
        return $this->success('登陆成功', ['user' => $user, 'token' => $token]);
    }

    function refreshToken()
    {
        $res = JwtToken::refreshToken();
        return $this->success('成功', $res);
    }

}
