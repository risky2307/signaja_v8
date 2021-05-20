<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
// use LdapRecord\Laravel\Auth\LdapAuthenticatable;
// use LdapRecord\Laravel\Auth\AuthenticatesWithLdap;
// use Laravel\Passport\HasApiTokens;

class User extends Model
{
    // use Notifiable, AuthenticatesWithLdap, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'remember_token', 'guid', 'domain'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function transactions()
    {
        return $this->belongsToMany('App\Models\Transaction','trx_users' ,'id_user', 'id_transaction')->withTimestamps()->withPivot('docrole','firstviewdate','level','note', 'statusaction');
    }

    public function documents()
    {
       return $this->belongsToMany('App\Models\Document', 'trx_documents', 'id_transaction','id_document');
    }
}

