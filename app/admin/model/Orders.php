<?php

namespace app\admin\model;

use Illuminate\Database\Eloquent\SoftDeletes;
use plugin\admin\app\model\Admin;
use plugin\admin\app\model\Base;


/**
 * 
 *
 * @property int $id 主键
 * @property int $admin_id 商户
 * @property string $ordersn 订单号
 * @property string $amount 金额
 * @property int $status 状态:0=待领取,1=已领取,2=已过期
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Orders newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Orders newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Orders query()
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \app\admin\model\OrdersReceive> $receive
 * @property int|null $user_id 用户
 * @property \Illuminate\Support\Carbon|null $deleted_at 删除时间
 * @property-read Admin|null $admin
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Orders onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Orders withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Orders withoutTrashed()
 * @property \Illuminate\Support\Carbon $start_time 开始时间
 * @property \Illuminate\Support\Carbon $end_time 结束时间
 * @property \Illuminate\Support\Carbon|null $receive_time 领取时间
 * @mixin \Eloquent
 */
class Orders extends Base
{
    use SoftDeletes;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_orders';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'receive_time' => 'datetime',
    ];

    protected $fillable = [
        'admin_id',
        'ordersn',
        'amount',
        'start_time',
        'end_time',
        'status',
        'user_id',
        'receive_time',
    ];



    function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'id');
    }




}
