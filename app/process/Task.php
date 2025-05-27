<?php

namespace app\process;

use app\admin\model\Admin;
use app\admin\model\Orders;
use app\admin\model\User;
use app\admin\model\UserWithdraw;
use app\api\service\Pay;
use Carbon\Carbon;
use GuzzleHttp\Client;
use support\Log;
use Workerman\Crontab\Crontab;

class Task
{

    public function onWorkerStart()
    {
        // 每分钟执行一次
        new Crontab('0 */1 * * * *', function(){
            $orders = Orders::where('end_time', '<', Carbon::now())->where('status', 0)->get();
            foreach ($orders as $order){
                $total_refund_amount = bcadd($order->amount, $order->service_amount, 2);
                $refund_ordersn = Pay::generateOrderSn();
                $order->status = 2;
                $order->refund_ordersn = $refund_ordersn;
                $order->refund_time = $total_refund_amount;
                $order->save();
                Admin::changeMoney($total_refund_amount,$order->admin_id,'订单失效:'.$refund_ordersn,4);
            }
        });

        // 每5分钟执行一次
        new Crontab('0 */5 * * * *', function(){
            $withdraws = UserWithdraw::where('status', 0)->get();
            $client = new Client();
            $url = 'http://8.130.185.57:3000/api/disburse/alipay';
            $clientId = 'test_client';
            $secretKey = 'test_secret_key_123';
            foreach ($withdraws as $withdraw){
                $timestamp = time();
                $requestParams = [
                    'clientId' => $clientId,
                    'receiver' => $withdraw->ali_account,
                    'trans_amount' => floatval($withdraw->amount) ,
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
                $result = $result->getBody()->getContents();
                Log::info('提现请求结果：'.$withdraw->ordersn);
                Log::info($result);
                $result = json_decode($result);
                if ($result->success == false){
                    //提现失败
                    $withdraw->status = 2;
                    User::changeMoney($withdraw->amount,$withdraw->user_id,$withdraw->ordersn,3);
                }else{
                    $withdraw->status = 1;
                }
                $withdraw->reason = $result->message;
                $withdraw->save();
            }
        });

    }

}