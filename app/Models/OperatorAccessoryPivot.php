<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class OperatorAccessoryPivot extends Pivot
{
    protected $table = 'operator_accessories';

    protected $casts = [
        'is_default' => 'boolean',
    ];
}
