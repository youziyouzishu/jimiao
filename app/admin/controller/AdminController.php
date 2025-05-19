<?php

namespace app\admin\controller;

use app\admin\model\AdminLayer;
use app\admin\model\Sms;
use plugin\admin\app\common\Util;
use plugin\admin\app\model\AdminRole;
use plugin\admin\app\model\Option;
use support\Request;
use support\Response;
use app\admin\model\Admin;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * 商户管理 
 */
class AdminController extends Crud
{
    
    /**
     * @var Admin
     */
    protected $model = null;

    protected $noNeedLogin = ['register'];

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new Admin;
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
        $ids = AdminRole::where('role_id',3)->pluck('admin_id');
        $query = $this->doSelect($where, $field, $order)->whereIn('id',$ids)->with(['parent']);
        return $this->doFormat($query, $format, $limit);
    }
    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('admin/index');
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
        return view('admin/insert');
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
        return view('admin/update');
    }

    /**
     * 注册
     * @param Request $request
     * @return Response
     */
    function register(Request $request)
    {
        if ($request->method() === 'POST') {
            $username = $request->post('username');
            $nickname = $request->post('nickname');
            $password = $request->post('password');
            $password_confirm = $request->post('password_confirm');
            $mobile = $request->post('mobile');
            $captcha = $request->post('captcha');
            $invitecode = $request->post('invitecode');
            if (Admin::where('username', $username)->exists()){
                return $this->fail('用户名已存在');
            }
            if (Admin::where('mobile', $mobile)->exists()){
                return $this->fail('手机号已存在');
            }
            if ($password != $password_confirm) {
                return $this->fail('两次密码不一致');
            }
            if (strlen($password) < 6) {
                return $this->fail('密码长度不能小于6位');
            }
            if (!empty($invitecode) && !$parent = Admin::where('invitecode', $invitecode)->first()) {
                return $this->fail('邀请码不存在');
            }
            $captchaResult = Sms::check($mobile, $captcha, 'register');
            if (!$captchaResult) {
                return $this->fail('验证码错误');
            }
            $admin = Admin::create([
                'username' => $username,
                'nickname' => $nickname,
                'password' => Util::passwordHash($password),
                'avatar' => '/app/admin/avatar.png',
                'mobile' => $mobile,
                'pid' => isset($parent) ? $parent->id : null,
                'invitecode' => Admin::generateInvitecode(),
            ]);

            if (isset($parent)) {
                // 增加直推关系
                AdminLayer::create([
                    'admin_id' => $admin->id,
                    'parent_id' => $parent->id,
                    'layer' => 1
                ]);
                // 收集多层关系数据
                $layersToInsert = [];
                AdminLayer::where('user_id', $parent->id)->get()->each(function (AdminLayer $item) use ($admin, &$layersToInsert) {
                    $layersToInsert[] = [
                        'admin_id' => $admin->id,
                        'parent_id' => $item->parent_id,
                        'layer' => $item->layer + 1
                    ];
                });
                // 批量插入多层关系
                if (!empty($layersToInsert)) {
                    AdminLayer::insert($layersToInsert);
                }
            }

            AdminRole::create([
                'admin_id' => $admin->id,
                'role_id' => 3,
            ]);
            return $this->success('注册成功');
        }
        return view('admin/register');
    }

}
