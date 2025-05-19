<?php

namespace plugin\admin\app\controller;

use plugin\admin\app\model\User;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use Throwable;
use Tinywan\Jwt\JwtToken;
use Tinywan\Jwt\RedisHandler;

/**
 * 用户管理 
 */
class UserController extends Crud
{
    
    /**
     * @var User
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new User;
    }

    /**
     * 浏览
     * @return Response
     * @throws Throwable
     */
    public function index(): Response
    {
        return raw_view('user/index');
    }

    /**
     * 插入
     * @param Request $request
     * @return Response
     * @throws BusinessException|Throwable
     */
    public function insert(Request $request): Response
    {
        if ($request->method() === 'POST') {
            return parent::insert($request);
        }
        return raw_view('user/insert');
    }

    /**
     * 更新
     * @param Request $request
     * @return Response
     * @throws BusinessException|Throwable
     */
    public function update(Request $request): Response
    {
        if ($request->method() === 'POST') {
            $id = $request->post('id');
            $status = $request->post('status');
            $row = $this->model->find($id);

            if ($row->status == 0 && $status == 1){
                //禁用了
                $config = config('plugin.tinywan.jwt.app.jwt');
                if ($config['is_single_device']) {
                    RedisHandler::clearToken($config['cache_refresh_token_pre'], JwtToken::TOKEN_CLIENT_MOBILE, (string)$row->id);
                    RedisHandler::clearToken($config['cache_token_pre'], JwtToken::TOKEN_CLIENT_MOBILE, (string)$row->id);
                }
            }
            return parent::update($request);
        }
        return raw_view('user/update');
    }

}
