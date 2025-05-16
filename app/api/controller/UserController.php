<?php

namespace app\api\controller;

use app\admin\model\Orders;
use app\admin\model\User;
use app\admin\model\UserMoneyLog;
use app\admin\model\UserWithdraw;
use app\api\basic\Base;
use Carbon\Carbon;
use plugin\admin\app\model\Admin;
use support\Db;
use support\Request;
use support\Response;

class UserController extends Base
{
    /**
     * 获取个人信息
     * @param Request $request
     * @return \support\Response
     */
    function getUserInfo(Request $request)
    {
        $row = User::find($request->user_id);
        return $this->success('成功', $row);
    }

    /**
     * 兑换口令
     * @param Request $request
     * @return Response
     * @throws \Throwable
     */
    function receive(Request $request):Response
    {
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
        if ($order->admin->money < $order->amount){
            return $this->fail('商家余额不足');
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
            Admin::changeMoney(-$order->amount,$order->admin_id,$order->ordersn,1);
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
        $ali_name = $request->post('ali_name');
        $ali_account = $request->post('ali_account');
        $amount = $request->post('amount');
        if (empty($amount) || $amount <= 0) {
            return $this->fail('提现金额必须大于0');
        }
        $user = User::find($request->user_id);
        if ($amount > $user->money) {
            return $this->fail('余额不足');
        }
        Db::connection('plugin.admin.mysql')->beginTransaction();
        try {
            User::changeMoney(-$amount, $request->user_id, '提现', 2);
            UserWithdraw::create([
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
