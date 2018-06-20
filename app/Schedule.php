<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class Schedule extends Model
{
    protected $fillable = [
        'chat_id', 'time', 'utc'
    ];
    
    protected $table = 'schedule';
    
    const NAME = 'schedule';

    public function user(){
        return $this->belongsTo(User::class, 'chat_id', 'chat_id');
    }


    public function scheduleCall(User $user, string $category){

    }

    public function scheduleConfirm(User $user){

    }


}
