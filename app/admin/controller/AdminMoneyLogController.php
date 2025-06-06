<?php

namespace app\admin\controller;

use support\Request;
use support\Response;
use app\admin\model\AdminMoneyLog;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * 商户账变记录 
 */
class AdminMoneyLogController extends Crud
{
    
    /**
     * @var AdminMoneyLog
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new AdminMoneyLog;
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
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        if (in_array(3, admin('roles'))) {
            $type = 'merchant';
        }else{
            $type = 'admin';
        }
        return view('admin-money-log/index',['type' => $type]);
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
            return parent::insert($request);
        }
        return view('admin-money-log/insert');
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
        return view('admin-money-log/update');
    }

}
