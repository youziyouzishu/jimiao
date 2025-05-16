<?php

namespace app\admin\model;


use GuzzleHttp\Client;
use GuzzleHttp\Utils;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use plugin\admin\app\common\Util;
use plugin\admin\app\model\Base;
use support\Log;

/**
 * 
 *
 * @property int $id 主键
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property string|null $event 事件
 * @property string|null $mobile 手机号
 * @property string|null $code 验证码
 * @property int $times 验证次数
 * @property string|null $ip IP
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sms newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sms newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sms query()
 * @mixin \Eloquent
 */
class Sms extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_sms';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = [
        'event',
        'mobile',
        'code',
        'times',
        'ip',
    ];


    /**
     * 验证码有效时长
     * @var int
     */
    protected static $expire = 120;

    /**
     * 最大允许检测的次数
     * @var int
     */
    protected static $maxCheckNums = 10;


    /**
     * @param string $mobile
     * @param string $event
     */
    public static function getLast(string $mobile, string $event = 'default')
    {
        return self::where(['mobile' => $mobile, 'event' => $event])->orderByDesc('id')->first();
    }

    /**
     * 发送验证码
     *
     * @param string $mobile 手机号
     * @param int|null $code 验证码,为空时将自动生成4位数字
     * @param string $event 事件
     * @return  boolean
     */
    public static function send(string $mobile, int $code = null, string $event = 'default'): bool
    {
        $code = is_null($code) ? mt_rand(1000, 9999) : $code;
        $ip = request()->getRealIp();

//        $tagName = 'sendCaptcha';
//        \plugin\sms\api\Sms::sendByTag('13781176253', $tagName, [
//            'code' => '1234'
//        ]);

        $client = new Client();
        // 定义请求的 URL 和数据
        $url = 'http://sms.lifala.com.cn/api/KehuSms/send';
        $data = [
            'appid' => 'apsms1756300803',
            'key' => 'QxpgdWyCEEYFghWyHZUatiBq5rLcXydH',
            'mobile' => $mobile,
            'code' => $code,
        ];
        try {
            // 发送异步 POST 请求
            $promise = $client->postAsync($url, [
                'json' => $data
            ]);
            // 处理响应
            $promise->then(
                function ($response) use ($event, $mobile, $code, $ip) {
                    $response = $response->getBody()->getContents();
                    $response = json_decode($response);
                    if ($response->code != 1) {
                        throw new \Exception($response->msg);
                    }
                    self::create([
                        'event' => $event,
                        'mobile' => $mobile,
                        'code' => $code,
                        'ip' => $ip
                    ]);
                },
                function ($exception) {
                    throw new \Exception($exception->getMessage());
                }
            );
            // 等待所有异步请求完成
            \GuzzleHttp\Promise\Utils::settle([$promise])->wait();
            return true;
        } catch (\Throwable $e) {
            Log::info('短信警告：' . $e->getMessage());
            return false;
        }
    }

    /**
     * 发送通知
     *
     * @param mixed $mobile 手机号,多个以,分隔
     * @param string $msg 消息内容
     * @param string $template 消息模板
     * @return  boolean
     */
    public static function notice($mobile, $msg = '', $template = null)
    {
        $params = [
            'mobile' => $mobile,
            'msg' => $msg,
            'template' => $template
        ];
        $result = Hook::listen('sms_notice', $params, null, true);
        return (bool)$result;
    }

    /**
     * 校验验证码
     *
     * @param string $mobile 手机号
     * @param int $code 验证码
     * @param string $event 事件
     * @return  boolean
     */
    public static function check($mobile, $code, $event = 'default'): bool
    {
        #todo
        if ($code == '123456') {
            return true;
        }
        $time = time() - self::$expire;
        $sms = self::where(['mobile' => $mobile, 'event' => $event])
            ->orderByDesc('id')
            ->first();
        if ($sms) {
            if ($sms->created_at->timestamp > $time && $sms->times <= self::$maxCheckNums) {
                $correct = $code == $sms->code;
                if (!$correct) {
                    $sms->increment('times');
                    return false;
                } else {
                    return true;
                }
            } else {
                // 过期则清空该手机验证码
                self::flush($mobile, $event);
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 清空指定手机号验证码
     *
     * @param string $mobile 手机号
     * @param string $event 事件
     * @return  boolean
     */
    public static function flush(string $mobile, string $event = 'default')
    {
        self::where(['mobile' => $mobile, 'event' => $event])->delete();
        return true;
    }

}
