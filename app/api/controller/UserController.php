<?php

namespace app\api\controller;

use app\admin\model\Orders;
use app\admin\model\User;
use app\admin\model\UserMoneyLog;
use app\admin\model\UserWithdraw;
use app\api\basic\Base;
use app\api\service\Pay;
use Carbon\Carbon;
use plugin\admin\app\model\Admin;
use support\Db;
use support\Request;
use support\Response;
use Webman\RateLimiter\Limiter;
use Webman\RateLimiter\RateLimitException;

class UserController extends Base
{
    /**
     * 获取个人信息
     * @param Request $request
     * @return \support\Response
     */
    function getUserInfo(Request $request)
    {
        $user = User::find($request->user_id);
        $user->setAttribute('withdraw_amount',$user->withdraw()->where('status',1)->sum('amount'));
        return $this->success('成功', $user);
    }

    /**
     * 绑定支付宝账号
     * @param Request $request
     */
    function setAliAccount(Request $request)
    {
        $ali_name = $request->post('ali_name');
        $ali_account = $request->post('ali_account');
        $user = User::find($request->user_id);
        $user->ali_name = $ali_name;
        $user->ali_account = $ali_account;
        $user->save();
        return $this->success('成功');
    }

    /**
     * 兑换口令
     * @param Request $request
     * @return Response
     * @throws \Throwable
     */
    function receive(Request $request):Response
    {
        try {
            #限流器 每个用户1秒内只能请求1次
            Limiter::check('user_' . $request->user_id, 1, 3);
        } catch (RateLimitException $e) {
            return $this->fail('请求频繁');
        }

        $ordersn = $request->post('ordersn');
        $order = Orders::where('ordersn',$ordersn)->first();
        if(!$order){
            return $this->fail('口令错误');
        }
        if($order->status != 0){
            return $this->fail('口令无效');
        }
        if($order->end_time->isPast()){
            return $this->fail('口令已过期');
        }
        $total_amount = $order->amount + $order->service_amount;
        if ($order->admin->money < $total_amount){
            return $this->fail('商家余额不足，请联系商户处理');
        }

        #进行领取
        Db::connection('plugin.admin.mysql')->beginTransaction();
        try {
            $order->status = 1;
            $order->user_id = $request->user_id;
            $order->receive_time = Carbon::now();
            $order->save();
            #增加用户余额
            User::changeMoney($order->amount,$order->user_id,$order->ordersn,1);
            #减少商家余额
            Admin::changeMoney(-$total_amount,$order->admin_id,$order->ordersn,1);
            Db::connection('plugin.admin.mysql')->commit();
        } catch (\Throwable $e) {
            Db::connection('plugin.admin.mysql')->rollback();
            return $this->fail('领取失败');
        }
        return $this->success('领取成功');
    }

    /**
     * 获取余额变动记录
     * @param Request $request
     * @return \support\Response
     */
    function getMoneyLog(Request $request)
    {
        $rows = UserMoneyLog::where('user_id', $request->user_id)
            ->latest()
            ->paginate()
            ->getCollection()
            ->each(function (UserMoneyLog $item) {
                if ($item->money > 0) {
                    $item->money = '+' . $item->money;
                }
            });
        return $this->success('成功', $rows);
    }


    /**
     * 提现
     * @param Request $request
     * @return Response
     * @throws \Throwable
     */
    function withdraw(Request $request)
    {
        try {
            #限流器 每个用户1秒内只能请求1次
            Limiter::check('user_' . $request->user_id, 1, 5);
        } catch (RateLimitException $e) {
            return $this->fail('请求频繁');
        }


        $amount = $request->post('amount');
        if (empty($amount) || $amount <= 0) {
            return $this->fail('提现金额必须大于0');
        }
        $user = User::find($request->user_id);
        if ($amount > $user->money) {
            return $this->fail('余额不足');
        }
        $ali_name = $user->ali_name;
        $ali_account = $user->ali_account;
        if (empty($ali_name) || empty($ali_account)) {
            return $this->fail('请先绑定支付宝账号');
        }
        $ordersn = Pay::generateOrderSn();
        Db::connection('plugin.admin.mysql')->beginTransaction();
        try {
            User::changeMoney(-$amount, $request->user_id, $ordersn, 2);
            UserWithdraw::create([
                'ordersn' => $ordersn,
                'user_id' => $request->user_id,
                'amount' => $amount,
                'ali_name' => $ali_name,
                'ali_account' => $ali_account,
            ]);
            Db::connection('plugin.admin.mysql')->commit();
        } catch (\Throwable $e) {
            Db::connection('plugin.admin.mysql')->rollback();
            return $this->fail('提现失败');
        }
        return $this->success('请等待审核');
    }

    /**
     * 提现记录
     * @param Request $request
     * @return Response
     */
    function getWithdrawLog(Request $request)
    {
        $rows = UserWithdraw::where('user_id', $request->user_id)->latest()->paginate()->items();
        return $this->success('成功', $rows);
    }








}
