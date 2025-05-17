<?php

namespace app\process;

use app\admin\model\Orders;
use app\admin\model\UserWithdraw;
use app\api\service\Pay;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Workerman\Crontab\Crontab;

class Task
{

    public function onWorkerStart()
    {
        // 每分钟执行一次
        new Crontab('0 */1 * * * *', function(){
            $orders = Orders::where('end_time', '<', Carbon::now())->where('status', 0)->get();
            foreach ($orders as $order){
                $order->status = 2;
                $order->save();
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
                $result = $result->getBody()->getContents();
                $result = json_decode($result);
                if ($result->success == false){
                    $withdraw->status = 2;
                }
            }
        });

    }

}