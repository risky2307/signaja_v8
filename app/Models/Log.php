<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $table = "logs";
    protected $fillable = ['name', 'description'];
    //protected $primaryKey = 'id';

}
