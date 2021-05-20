<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Ldap\User;

class TestController extends Controller
{
    public function index()
    {
        $user = User::where('mail','=','Risky.Yulianti@sinarmasland.com')->get();
        dd($user);
    }
}
