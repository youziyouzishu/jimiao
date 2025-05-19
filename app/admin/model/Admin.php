<?php

namespace app\admin\model;

use plugin\admin\app\model\Base;

/**
 * 
 *
 * @property integer $id ID(主键)
 * @property string $username 用户名
 * @property string $nickname 昵称
 * @property string $password 密码
 * @property string $avatar 头像
 * @property string $email 邮箱
 * @property string $mobile 手机
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $login_at 登录时间
 * @property integer $status 禁用
 * @property string $invitecode 邀请码
 * @property integer $pid 上级
 * @property string $money
 * @property int $type 商户类型:1=普通,2=代理
 * @property-read Admin|null $parent
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin query()
 * @property string $award_amount 贡献奖
 * @property string $max_award_amount 最大贡献奖
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

    function parent()
    {
        return $this->belongsTo(Admin::class, 'pid', 'id');
    }
    
    
    
}
