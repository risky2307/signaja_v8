<?php

namespace App\Http\Middleware;

use Closure;

class CheckRole
{
    protected static $response = [
        'success' => false,
        'data'    => null,
        'message' => null,
    ];
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function handle($request, Closure $next, $role)
    {
        $key = env("SECRET_JWT");

        $userdata = AuthenticateJWT::verifyToken($request->header('Authorization'), $key);
        $strRole = '$userdata->data->role->' . $role;
        eval( "\$accessRole = " . $strRole . ";" );
        if( !$accessRole ) {
            header('HTTP/1.1 403 Forbidden access');            
            header('Access-Control-Allow-Origin: *');
            exit;
        }
        return $next($request);
    }
}
