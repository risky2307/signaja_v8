<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDocument extends Model
{
    public function users()
    {
        return $this->belongsTo('App\Models\User','id_user','id');
    }

    public function documents()
    {
        return $this->hasMany('App\Models\Document','id_document','id_document');
    }

    public function transactions()
    {
        return $this->hasMany('App\Models\Transaction', 'id_transaction', 'id_transaction');
    }
}
