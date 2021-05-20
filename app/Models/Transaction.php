<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $primaryKey = 'id_transaction';

    public function users()
    {
        return $this->belongsToMany('App\Models\User', 'trx_users', 'id_transaction','id_user')->withTimestamps()->withPivot('docrole','firstviewdate','level','note', 'statusaction');
    }

    public function documents()
    {
        return $this->belongsToMany('App\Models\Document', 'trx_documents', 'id_transaction','id_document');
    }

    public function transactions()
    {
        return $this->belongsToMany('App\Models\Transaction','trx_documents' ,'id_transaction', 'id_transaction');
    }

}

