<?php

namespace app\admin\controller;

use plugin\admin\app\model\Admin;
use support\Request;
use support\Response;
use app\admin\model\AdminRealinfo;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * 商户实名记录 
 */
class AdminRealinfoController extends Crud
{
    
    /**
     * @var AdminRealinfo
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new AdminRealinfo;
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
        $roles = admin('roles');
        if (in_array(3, $roles)) {
            if ($this->model->where('admin_id', admin_id())->where('status',1)->exists()){
                $show = false;
            }else{
                $show = true;
            }

        }else{
            $show = false;
        }
        return view('admin-realinfo/index',['show'=>$show]);
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
            $row = $this->model->where('admin_id', admin_id())->where('status',0)->exists();
            if ($row) {
                return $this->fail('您已提交过实名信息，请等待审核！');
            }
            $row = $this->model->where('admin_id', admin_id())->where('status',1)->exists();
            if ($row) {
                return $this->fail('您已提交过实名信息，请勿重复提交！');
            }
            $request->setParams('post',[
                'admin_id' => admin_id(),
            ]);
            return parent::insert($request);
        }
        return view('admin-realinfo/insert');
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
        return view('admin-realinfo/update');
    }

}
