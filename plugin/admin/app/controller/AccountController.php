<?php

namespace plugin\admin\app\controller;

use app\admin\model\Sms;
use plugin\admin\app\common\Auth;
use plugin\admin\app\common\Util;
use plugin\admin\app\model\Admin;
use plugin\admin\app\model\AdminRole;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use Throwable;
use Webman\Captcha\CaptchaBuilder;
use Webman\Captcha\PhraseBuilder;

/**
 * 管理员账户
 */
class AccountController extends Crud
{
    /**
     * 不需要登录的方法
     * @var string[]
     */
    protected $noNeedLogin = ['login', 'logout', 'captcha','register'];

    /**
     * 不需要鉴权的方法
     * @var string[]
     */
    protected $noNeedAuth = ['info'];

    /**
     * @var Admin
     */
    protected $model = null;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->model = new Admin;
    }

    /**
     * 账户设置
     * @return Response
     * @throws Throwable
     */
    public function index()
    {
        return raw_view('account/index');
    }

    /**
     * 登录
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function login(Request $request): Response
    {
        $this->checkDatabaseAvailable();
        $captcha = $request->post('captcha', '');
        if (strtolower($captcha) !== session('captcha-login')) {
            return $this->json(1, '验证码错误');
        }
        $request->session()->forget('captcha-login');
        $username = $request->post('username', '');
        $password = $request->post('password', '');
        if (!$username) {
            return $this->json(1, '用户名不能为空');
        }
        $this->checkLoginLimit($username);
        $admin = Admin::where('username', $username)->first();
        if (!$admin || !Util::passwordVerify($password, $admin->password)) {
            return $this->json(1, '账户不存在或密码错误');
        }
        if ($admin->status != 0) {
            return $this->json(1, '当前账户暂时无法登录');
        }
        $admin->login_at = date('Y-m-d H:i:s');
        $admin->save();
        $this->removeLoginLimit($username);
        $admin = $admin->toArray();
        $session = $request->session();
        $admin['password'] = md5($admin['password']);
        $session->set('admin', $admin);
        return $this->json(0, '登录成功', [
            'nickname' => $admin['nickname'],
            'token' => $request->sessionId(),
        ]);
    }

    /**
     * 注册
     * @param Request $request
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
            AdminRole::create([
                'admin_id' => $admin->id,
                'role_id' => 3,
            ]);
            return $this->success('注册成功');
        }
        return raw_view('account/register');
    }

    /**
     * 退出
     * @param Request $request
     * @return Response
     */
    public function logout(Request $request): Response
    {
        $request->session()->delete('admin');
        return $this->json(0);
    }

    /**
     * 获取登录信息
     * @param Request $request
     * @return Response
     */
    public function info(Request $request): Response
    {
        $admin = admin();
        if (!$admin) {
            return $this->json(1);
        }
        $info = [
            'id' => $admin['id'],
            'username' => $admin['username'],
            'nickname' => $admin['nickname'],
            'avatar' => $admin['avatar'],
            'email' => $admin['email'],
            'mobile' => $admin['mobile'],
            'isSuperAdmin' => Auth::isSuperAdmin(),
            'token' => $request->sessionId(),
        ];
        return $this->json(0, 'ok', $info);
    }

    /**
     * 更新
     * @param Request $request
     * @return Response
     */
    public function update(Request $request): Response
    {
        $allow_column = [
            'nickname' => 'nickname',
            'avatar' => 'avatar',
            'email' => 'email',
            'mobile' => 'mobile',
        ];

        $data = $request->post();
        $update_data = [];
        foreach ($allow_column as $key => $column) {
            if (isset($data[$key])) {
                $update_data[$column] = $data[$key];
            }
        }
        if (isset($update_data['password'])) {
            $update_data['password'] = Util::passwordHash($update_data['password']);
        }
        Admin::where('id', admin_id())->update($update_data);
        $admin = admin();
        unset($update_data['password']);
        foreach ($update_data as $key => $value) {
            $admin[$key] = $value;
        }
        $request->session()->set('admin', $admin);
        return $this->json(0);
    }

    /**
     * 修改密码
     * @param Request $request
     * @return Response
     */
    public function password(Request $request): Response
    {
        $hash = Admin::find(admin_id())['password'];
        $password = $request->post('password');
        if (!$password) {
            return $this->json(2, '密码不能为空');
        }
        if ($request->post('password_confirm') !== $password) {
            return $this->json(3, '两次密码输入不一致');
        }
        if (!Util::passwordVerify($request->post('old_password'), $hash)) {
            return $this->json(1, '原始密码不正确');
        }
        $update_data = [
            'password' => Util::passwordHash($password)
        ];
        Admin::where('id', admin_id())->update($update_data);
        return $this->json(0);
    }

    /**
     * 验证码
     * @param Request $request
     * @param string $type
     * @return Response
     */
    public function captcha(Request $request, string $type = 'login'): Response
    {
        $builder = new PhraseBuilder(4, 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ');
        $captcha = new CaptchaBuilder(null, $builder);
        $captcha->build(120);
        $request->session()->set("captcha-$type", strtolower($captcha->getPhrase()));
        $img_content = $captcha->get();
        return response($img_content, 200, ['Content-Type' => 'image/jpeg']);
    }

    /**
     * 检查登录频率限制
     * @param $username
     * @return void
     * @throws BusinessException
     */
    protected function checkLoginLimit($username)
    {
        $limit_log_path = runtime_path() . '/login';
        if (!is_dir($limit_log_path)) {
            mkdir($limit_log_path, 0777, true);
        }
        $limit_file = $limit_log_path . '/' . md5($username) . '.limit';
        $time = date('YmdH') . ceil(date('i')/5);
        $limit_info = [];
        if (is_file($limit_file)) {
            $json_str = file_get_contents($limit_file);
            $limit_info = json_decode($json_str, true);
        }

        if (!$limit_info || $limit_info['time'] != $time) {
            $limit_info = [
                'username' => $username,
                'count' => 0,
                'time' => $time
            ];
        }
        $limit_info['count']++;
        file_put_contents($limit_file, json_encode($limit_info));
        if ($limit_info['count'] >= 5) {
            throw new BusinessException('登录失败次数过多，请5分钟后再试');
        }
    }

    /**
     * 解除登录频率限制
     * @param $username
     * @return void
     */
    protected function removeLoginLimit($username)
    {
        $limit_log_path = runtime_path() . '/login';
        $limit_file = $limit_log_path . '/' . md5($username) . '.limit';
        if (is_file($limit_file)) {
            unlink($limit_file);
        }
    }

    protected function checkDatabaseAvailable()
    {
        if (!config('plugin.admin.database')) {
            throw new BusinessException('请重启webman');
        }
    }

}
