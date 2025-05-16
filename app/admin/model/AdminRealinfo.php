<?php

namespace app\admin\model;

use plugin\admin\app\model\Admin;
use plugin\admin\app\model\Base;

/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property integer $admin_id 商户
 * @property string $card_front 身份证正面
 * @property string $card_side 身份证反面
 * @property string $card_num 身份证号码
 * @property string $truename 真实姓名
 * @property integer $status 状态:0=待审核,1=通过,2=驳回
 * @property string $reason 原因
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property-read Admin|null $admin
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminRealinfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminRealinfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminRealinfo query()
 * @mixin \Eloquent
 */
class AdminRealinfo extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_admin_realinfo';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    function admin()
    {
        return $this->belongsTo(Admin::class,'admin_id','id');
    }
    
    
    
}
