<?php

namespace App\Http\Middleware;
use App\Http\Controllers\API\AuthsController;
use \Firebase\JWT\JWT;
use Closure;

class AuthenticateJWT
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
    public function handle($request, Closure $next)
    {
        $key = env("SECRET_JWT");
        $jwt_data = $this->verifyToken($request->header('Authorization'), $key);
        if( !$jwt_data ) {            
            header('HTTP/1.1 401 Authorization Required');
            header('WWW-Authenticate: Basic realm="Access denied"');
            header('Access-Control-Allow-Origin: *');
            exit;
        }
        //$request->headers->set('Authorization','Refreshed 12345343');
        $jwt_data = array( "jwt_data" => (array) $jwt_data->data );
        $request->merge($jwt_data);
        return $next($request);
    }
    public static function verifyToken($authHeader, $key) {
                
        $jwt = explode(" ", $authHeader);
        if( empty($jwt[1]) )
            return null;
        try {
            $decoded = JWT::decode($jwt[1], $key, array('HS256'));
            return $decoded;
        }
        catch(\Firebase\JWT\ExpiredException | \Firebase\JWT\SignatureInvalidException $e) {
            return null;
        }
        catch(\Exception $e) {
            return null;
        }        

    }
}
