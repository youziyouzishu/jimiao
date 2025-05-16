<?php

namespace app\admin\controller;

use app\api\service\Pay;
use GuzzleHttp\Client;
use support\Request;
use support\Response;
use app\admin\model\AdminRecharge;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

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
        }else{
            $show = false;
        }
        return view('admin-recharge/index',['show'=>$show]);
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
        $query = $this->doSelect($where, $field, $order);
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
            $ordersn = Pay::generateOrderSn();
            $client = new Client();
            $url = 'http://8.130.185.57:8080/api/payment-request';
            $result = $client->post($url, [
                'json' => [
                    'clientId' => 'test_client',
                    'amount' => $amount,
                    'title' => '商户充值',
                    'description' => '商户充值',
                    'notifyUrl' => config('app.pay.notifyUrl'),
                    'returnUrl' => config('app.pay.returnUrl'),
                    'extraData' => json_encode(['ordersn'=>$ordersn]),
                    'bankVerification' => [
                        'accountNo' => $bankcard_no,
                        'name' => $truename,
                        'idCardCode' => $idcard_no,
                        'bankPreMobile' => $mobile
                    ]
                ]
            ]);
            $result = $result->getBody()->getContents();
            $result = json_decode($result);
            if ($result->success == false){
                return $this->fail($result->message);
            }

            $admin_id = admin_id();
            $request->setParams('post',[
                'order_sn' => $ordersn,
                'admin_id' => $admin_id,
            ]);
            $data = $this->insertInput($request);
            $id = $this->doInsert($data);
            return $this->success();
        }
        return view('admin-recharge/insert');
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
            return parent::update($request);
        }
        return view('admin-recharge/update');
    }

}
