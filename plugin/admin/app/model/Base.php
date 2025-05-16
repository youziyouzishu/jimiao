<?php

namespace plugin\admin\app\model;

use DateTimeInterface;
use support\Model;


/**
 * 
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Base newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Base newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Base query()
 * @mixin \Eloquent
 */
class Base extends Model
{
    /**
     * @var string
     */
    protected $connection = 'plugin.admin.mysql';

    /**
     * 格式化日期
     *
     * @param DateTimeInterface $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
