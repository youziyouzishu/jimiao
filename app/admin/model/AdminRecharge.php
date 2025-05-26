<?php

namespace app\admin\model;

use plugin\admin\app\model\Admin;
use plugin\admin\app\model\Base;

/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property integer $admin_id 商户
 * @property string $ordersn 订单编号
 * @property string $amount 金额
 * @property string $bankcard_no 银行卡号
 * @property string $truename 持卡人姓名
 * @property string $idcard_no 身份证号码
 * @property string $mobile 手机号
 * @property integer $status 状态:0=未支付,1=已支付
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminRecharge newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminRecharge newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminRecharge query()
 * @property string|null $reason 驳回原因
 * @property string $into_amount 到账金额
 * @property string $service_amount 服务费
 * @property-read Admin|null $admin
 * @property string $recharge_bankcard_no 收款卡号
 * @property string $recharge_truename 收款人
 * @property string $recharge_bankname 收款银行
 * @property string $recharge_branch 收款支行
 * @mixin \Eloquent
 */
class AdminRecharge extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_admin_recharge';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = [
        'admin_id',
        'ordersn',
        'amount',
        'bankcard_no',
        'truename',
        'idcard_no',
        'mobile',
        'status',
        'into_amount',
        'service_amount'
    ];

    function admin()
    {
        return $this->belongsTo(Admin::class,'admin_id','id');
    }
    
    
    
}
