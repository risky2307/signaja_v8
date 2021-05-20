<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $primaryKey = 'id_document';

    public function users()
    {
        return $this->belongsToMany('App\Models\User', 'trx_users', 'id_transaction','id_user')->withTimestamps()->withPivot('docrole','firstviewdate','level','note');
    }

    public function transactions()
    {
        return $this->belongsToMany('App\Models\Transaction','trx_documents' ,'id_document', 'id_transaction');
    }

}
