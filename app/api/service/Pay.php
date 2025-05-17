<?php

namespace app\api\service;

use support\exception\BusinessException;
use Yansongda\Artful\Rocket;
use Yansongda\Supports\Collection;

class Pay
{
    /**
     * 支付
     * @param $pay_type *支付类型:1=微信
     * @param  $pay_amount
     * @param  $order_no
     * @param $mark
     * @param $attach
     * @return Rocket|Collection
     * @throws \Exception
     */
    public static function pay($pay_type, $pay_amount, $order_no, $mark, $attach)
    {
        $config = config('payment');
        if ($pay_type == 1) {
            $result = \Yansongda\Pay\Pay::wechat($config)->mini([
                'out_trade_no' => $order_no,
                'description' => $mark,
                'amount' => [
                    'total' => function_exists('bcmul') ? (int)bcmul($pay_amount, 100, 2) : $pay_amount * 100,
                    'currency' => 'CNY',
                ],
                'payer' => [
                    'openid' => request()->openid,
                ],
                'attach' => $attach
            ]);
        } else {
            throw new \Exception('支付类型错误');
        }
        return $result;
    }

    #退款
    public static function refund($pay_type, $pay_amount, $order_no, $refund_order_no, $reason)
    {
        $config = config('payment');
        return match ($pay_type) {
            1 => \Yansongda\Pay\Pay::wechat($config)->refund([
                'out_trade_no' => $order_no,
                'out_refund_no' => $refund_order_no,
                'amount' => [
                    'refund' => (int)bcmul($pay_amount, 100, 2),
                    'total' => (int)bcmul($pay_amount, 100, 2),
                    'currency' => 'CNY',
                ],
                'reason' => $reason
            ]),
            default => throw new \Exception('支付类型错误'),
        };
    }

    public static function generateOrderSn()
    {
        return date('Ymd') . mb_strtoupper(uniqid());
    }

    public static function generateSignature($clientId, $timestamp, $params, $secretKey)
    {
        // 1. 移除不需要签名的字段
        $signParams = $params;
        unset($signParams['signature']);

        // 2. 按参数名排序
        ksort($signParams);

        // 3. 拼接参数
        $signString = '';
        foreach ($signParams as $key => $value) {
            // 如果是数组或对象，转为JSON字符串
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value,JSON_UNESCAPED_UNICODE);
            }
            $signString .= $key . '=' . $value . '&';
        }

        // 4. 拼接clientId, timestamp和密钥
        $stringToSign = $clientId . '&' . $timestamp . '&' . $signString . $secretKey;
        dump('构建签名字符串');
        dump($stringToSign);
        // 5. 使用SHA256计算签名
        return hash('sha256', $stringToSign);
    }
}