<?php

namespace App;

use App\Helpers\Localize;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class News extends Model
{

    const NAME = 'news';

    protected $fillable = [
        'chat_id', 'categories'
    ];

    public function user(){
        return $this->belongsTo(User::class, 'chat_id', 'chat_id');
    }

    public  function FancyCategories(){
        $cat = explode(',',$this->categories);
        $translated = array();
        foreach ($cat as $item)
            $translated[] = ucfirst(Localize::getStringByLocale($this->user->lang, $item));
        return implode(' | ', $translated);
    }



}
