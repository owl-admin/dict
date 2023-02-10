<?php

namespace Slowlyo\SlowDict\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Slowlyo\SlowAdmin\Models\BaseModel as Model;

class AdminDict extends Model
{
    use SoftDeletes;

    protected $table = 'admin_dict';

    public function children()
    {
        return $this->hasMany(AdminDict::class, 'parent_id')->orderByDesc('sort');
    }
}
