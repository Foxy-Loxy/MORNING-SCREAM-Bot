<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class Weather extends Model
{
    protected $fillable = [
        'chat_id', 'location', 'lon', 'lat'
    ];

    const NAME = 'weather';

    public function user(){
        return $this->belongsTo(User::class, 'chat_id', 'chat_id');
    }




}
