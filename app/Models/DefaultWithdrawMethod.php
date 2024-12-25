<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefaultWithdrawMethod extends Model
{
    protected $guarded = [];

    public function payable()
    {
        return $this->morphTo();
    }
}
