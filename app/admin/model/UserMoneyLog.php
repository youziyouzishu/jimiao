<?php

namespace app\admin\model;

use plugin\admin\app\model\Base;

/**
 *
 *
 * @property int $id 主键
 * @property int $user_id 用户
 * @property string $money 变更余额
 * @property string $before 变更前余额
 * @property string $after 变更后余额
 * @property string|null $memo 备注
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMoneyLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMoneyLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMoneyLog query()
 * @property int $type 类型:1=兑换,2=提现
 * @property-read mixed $type_text
 * @mixin \Eloquent
 */
class UserMoneyLog extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_user_money_log';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    protected $fillable = [
        'user_id',
        'money',
        'before',
        'after',
        'memo',
        'type',
    ];

    protected $appends = ['type_text'];

    public function getTypeTextAttribute($value)
    {
        $value = $value ?: ($this->type ?? '');
        $list = $this->getTypeList();
        return $list[$value] ?? '';
    }

    public function getTypeList()
    {
        return [
            1 => '兑换',
            2 => '提现',
        ];
    }




}
