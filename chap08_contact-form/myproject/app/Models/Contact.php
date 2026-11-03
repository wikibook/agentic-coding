<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    /**
     * 일괄 대입을 허용하는 컬럼
     */
    protected $fillable = [
        'name',
        'email',
        'subject',
        'body',
        'status',
    ];
}
