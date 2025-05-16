<?php

namespace app\process;

use app\admin\model\Orders;
use app\admin\model\UserWithdraw;
use Carbon\Carbon;
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
        });

    }

}