<?php

namespace plugin\admin\app\model;

use app\admin\model\AdminMoneyLog;
use app\admin\model\AdminRealinfo;
use plugin\admin\app\model\Base;
use support\Db;


/**
 * 
 *
 * @property int $id ID
 * @property string $username 用户名
 * @property string $nickname 昵称
 * @property string $password 密码
 * @property string|null $avatar 头像
 * @property string|null $email 邮箱
 * @property string|null $mobile 手机
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property string|null $login_at 登录时间
 * @property int|null $status 禁用
 * @property string|null $invitecode 邀请码
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin query()
 * @property int|null $pid 上级
 * @property-read AdminRealinfo|null $realinfo
 * @property string $money
 * @mixin \Eloquent
 */
class Admin extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_admins';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = [
        'username',
        'nickname',
        'password',
        'avatar',
        'email',
        'mobile',
        'created_at',
        'updated_at',
        'login_at',
        'roles',
        'status',
        'invitecode'
    ];

    public static function generateInvitecode()
    {
        do {
            $invitecode = mt_rand(10000, 99999);
        } while (self::where(['invitecode' => $invitecode])->exists());
        return $invitecode;
    }

    function realinfo()
    {
        return $this->hasOne(AdminRealinfo::class, 'admin_id', 'id');
    }


    /**
     * 变更商户金额
     * @param int $money 金额
     * @param int $admin_id 商户ID
     * @param string $memo 备注
     * @param int $type 类型
     * @throws \Throwable
     */
    public static function changeMoney($money, $admin_id, $memo, $type)
    {
        Db::connection('plugin.admin.mysql')->beginTransaction();
        try {
            $admin = Admin::lockForUpdate()->find($admin_id);
            if ($admin && $money != 0) {
                $before = $admin->money;
                $after = function_exists('bcadd') ? bcadd($admin->money, $money, 2) : $admin->money + $money;
                //更新会员信息
                $admin->money = $after;
                $admin->save();
                //写入日志
                AdminMoneyLog::create(['admin_id' => $admin->id, 'money' => $money, 'before' => $before, 'after' => $after, 'memo' => $memo, 'type' => $type]);
            }
            Db::connection('plugin.admin.mysql')->commit();
        } catch (\Throwable $e) {
            Db::connection('plugin.admin.mysql')->rollback();
        }
    }


    
    
    
    
}
