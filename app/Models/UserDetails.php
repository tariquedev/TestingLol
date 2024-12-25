<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDetails extends Model
{
    protected $fillable = [
        'user_id','bio', 'occupation','social_links'
    ];
    protected $casts = [
        'social_links' => 'json',
    ];
    function user(){
        return $this->belongsTo(User::class);
    }
}
