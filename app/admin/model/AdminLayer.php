<?php

namespace app\admin\model;

use plugin\admin\app\model\Admin;
use plugin\admin\app\model\Base;

/**
 * 
 *
 * @property int $id 主键
 * @property int $admin_id
 * @property int $parent_id
 * @property int $layer
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminLayer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminLayer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminLayer query()
 * @mixin \Eloquent
 */
class AdminLayer extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_admin_layer';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = ['admin_id', 'parent_id', 'layer'];





}
