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


        $a = '0.06';
        $a = $this->formatDecimalWithCeil($a);
        return $this->success($a);

    }
    function formatDecimalWithCeil($numberStr)
    {
        // 将字符串转换为浮点数
        $floatVal = (float)$numberStr;

        // 分离整数和小数部分
        $parts = explode('.', number_format($floatVal, 10, '.', '')); // 精确到10位避免精度问题
        $decimalPart = isset($parts[1]) ? $parts[1] : '';

        // 判断是否超过两位小数
        if (strlen($decimalPart) >= 3) {
            // 超过两位小数时，使用 ceil 并指定保留两位
            $rounded = ceil($floatVal * 100) / 100;
            return number_format($rounded, 2, '.', '');
        } else {
            // 否则直接保留两位小数
            return number_format($floatVal, 2, '.', '');
        }
    }
    function ceilWithPrecision($number, $precision = 2)
    {


//        return number_format($aStr, 2,);
    }

    function alipay()
    {

    }

}
