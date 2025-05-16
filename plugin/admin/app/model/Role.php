<?php

namespace plugin\admin\app\model;


/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $name 角色名
 * @property string $rules 权限
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property integer $pid 上级id
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role query()
 * @mixin \Eloquent
 */
class Role extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_roles';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @return mixed
     */
    public function getRuleIds()
    {
        return $this->rules ? explode(',', $this->rules) : [];
    }

}
