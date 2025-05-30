<?php

namespace app\admin\controller;

use app\admin\model\AdminRealinfo;
use app\api\service\Pay;
use GuzzleHttp\Client;
use plugin\admin\app\model\Admin;
use support\Request;
use support\Response;
use app\admin\model\AdminRecharge;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;
use Webman\RateLimiter\Limiter;
use Webman\RateLimiter\RateLimitException;

/**
 * 商户充值记录
 */
class AdminRechargeController extends Crud
{

    /**
     * @var AdminRecharge
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new AdminRecharge;
    }

    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        $roles = admin('roles');
        if (in_array(3, $roles)) {
            $show = true;
        } else {
            $show = false;
        }
        return view('admin-recharge/index', ['show' => $show]);
    }

    /**
     * 查询
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function select(Request $request): Response
    {
        [$where, $format, $limit, $field, $order] = $this->selectInput($request);
        $query = $this->doSelect($where, $field, $order)->with(['admin']);
        if (in_array(3, admin('roles'))) {
            $query->where('admin_id', admin_id());
        }
        return $this->doFormat($query, $format, $limit);
    }

    /**
     * 插入
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function insert(Request $request): Response
    {
        if ($request->method() === 'POST') {
            $amount = $request->post('amount');
            $bankcard_no = $request->post('bankcard_no');
            $truename = $request->post('truename');
            $idcard_no = $request->post('idcard_no');
            $mobile = $request->post('mobile');
            $realinfo = AdminRealinfo::where('admin_id', admin_id())->where('status', 1)->first();
            if (empty($realinfo)) {
                return $this->fail('请先完善实名认证信息');
            }
            if ($realinfo->truename != $truename) {
                return $this->fail('请使用实名姓名');
            }
            if (empty($amount) || $amount <= 0) {
                return $this->fail('充值金额必须大于0');
            }
            //充值金额必须是整数
            if (!filter_var($amount, FILTER_VALIDATE_INT)) {
                return $this->fail('充值金额必须为整数');
            }

            try {
                #限流器 每个用户1秒内只能请求1次
                Limiter::check('admin_' . admin_id(), 1, 5);
            } catch (RateLimitException $e) {
                return $this->fail('请求频繁');
            }


            $service_amount = bcmul($amount, '0.01', 2);
            $into_amount = bcsub($amount, $service_amount, 2);

            $ordersn = Pay::generateOrderSn();
            $client = new Client();
            $url = 'http://8.130.185.57:3000/api/payment-request';
            $clientId = 'test_client';
            $timestamp = time();
            $secretKey = 'test_secret_key_123';
            $requestParams = [
                'clientId' => $clientId,
                'amount' => $amount,
                'title' => '商户充值',
                'description' => '商户充值',
                'bankVerification' => [
                    'accountNo' => $bankcard_no,
                    'name' => $truename,
                    'idCardCode' => $idcard_no,
                    'bankPreMobile' => $mobile
                ],
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

            if ($result->success == false) {
                return $this->fail($result->message);
            }
            $admin_id = admin_id();
            $params = [
                'ordersn' => $ordersn,
                'admin_id' => $admin_id,
                'service_amount' => $service_amount,
                'into_amount' => $into_amount,
                'recharge_bankcard_no' => $result->bankInfo->accountNumber,
                'recharge_truename' => $result->bankInfo->accountName,
                'recharge_bankname' => $result->bankInfo->bankName,
                'recharge_branch' => $result->bankInfo->branch,
            ];
            $request->setParams('post', $params);
            $data = $this->insertInput($request);
            $id = $this->doInsert($data);
            return $this->json(0, 'ok', $params);
        }
        $recharge_info_last = AdminRecharge::where('admin_id', admin_id())->orderBy('id', 'desc')->first();
        return view('admin-recharge/insert',  ['recharge_info_last' => $recharge_info_last]);
    }

    /**
     * 更新
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function update(Request $request): Response
    {
        if ($request->method() === 'POST') {
            $status = $request->post('status');
            $id = $request->post('id');
            $row = $this->model->find($id);
            if ($row->status == 0 && $status == 1) {
                try {
                    #限流器 每个用户1秒内只能请求1次
                    Limiter::check('admin_' . admin_id(), 1, 5);
                } catch (RateLimitException $e) {
                    return $this->fail('请求频繁');
                }
                //审核通过  增加余额
                Admin::changeMoney($row->into_amount, $row->admin_id, '充值：'.$row->amount.'元', 2);
                //如果此商户有上级  并且此商户是普通商户
                if ($row->admin->parent) {
                    $award_amount = bcmul($row->amount, '0.005', 2);#贡献奖
                    if ($row->admin->max_award_amount > $row->admin->award_amount) {
                        if ($row->admin->max_award_amount - $row->admin->award_amount < $award_amount) {
                            $award_amount = $row->admin->max_award_amount - $row->admin->award_amount;
                        }
                        Admin::changeMoney($award_amount, $row->admin->parent->id, '充值订单:' . $row->ordersn, 3);
                        $row->admin->award_amount += $award_amount;
                        $row->admin->save();
                    }
                }
            }

            return parent::update($request);
        }
        return view('admin-recharge/update');
    }

}
