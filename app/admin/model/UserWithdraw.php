<?php

namespace app\admin\model;

use plugin\admin\app\model\Base;


/**
 * 
 *
 * @property int $id 主键
 * @property int $user_id 用户
 * @property string $amount 提现金额
 * @property int $status 状态:0=待打款,1=已打款,2=打款失败
 * @property string $reason 失败原因
 * @property string $ali_account 支付宝账号
 * @property string $ali_name 支付宝姓名
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property-read mixed $status_text
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserWithdraw newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserWithdraw newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserWithdraw query()
 * @property string $ordersn 订单号
 * @mixin \Eloquent
 */
class UserWithdraw extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_user_withdraw';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'amount',
        'status',
        'reason',
        'ali_account',
        'ali_name',
        'ordersn'
    ];

    protected $appends = ['status_text'];

    public function getStatusTextAttribute($value)
    {
        $value = $value ?: ($this->status ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }

    public function getStatusList()
    {
        return [
            0 => '待打款',
            1 => '已打款',
            2 => '打款失败',
        ];
    }
    
}
