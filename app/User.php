<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class User extends Model
{

    const NAME = 'user';

    protected $fillable = [
        'first_name', 'last_name', 'username', 'chat_id', 'services', 'function', 'function_state', 'delivery_enabled', 'lang'
    ];

    public function news(){
        return $this->hasOne(News::class, 'chat_id', 'chat_id');
    }

    public function weather(){
        return $this->hasOne(Weather::class, 'chat_id', 'chat_id');
    }
    
    public function schedule(){
        return $this->hasOne(Schedule::class, 'chat_id', 'chat_id');
    }

    public function calendar(){
        return $this->hasOne(Calendar::class, 'chat_id', 'chat_id');
    }

    public  function FancyServices(){
        $locale = app(\App\Helpers\Localize::class);
        $cat = explode(',',$this->services);
        $translated = array();
        foreach ($cat as $item)
            $translated[] = ucfirst($locale->getString($item));
        return implode(' | ', $translated);
    }

}
