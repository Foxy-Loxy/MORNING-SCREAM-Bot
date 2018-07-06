<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use \App\Helpers\Localize;

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
        return $this->hasOne(\App\Schedule::class, 'chat_id', 'chat_id');
    }

    public  function FancyServices(){
        $locale = app(Localize::class);
        $cat = explode(',',$this->services);
        $translated = array();
        foreach ($cat as $item)
            $translated[] = ucfirst($locale->getString($item));
        return implode(' | ', $translated);
    }

}
