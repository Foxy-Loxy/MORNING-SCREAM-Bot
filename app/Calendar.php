<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class Calendar extends Model
{

    const NAME = 'calendar';

    protected $fillable = [
        'chat_id', 'access_token', 'refresh_token'
    ];

    public function user(){
        return $this->belongsTo(User::class, 'chat_id', 'chat_id');
    }

}
