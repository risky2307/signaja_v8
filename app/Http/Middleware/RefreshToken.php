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
        if( !$this->verifyToken($request->header('Authorization'), $key) ) {            
            header('HTTP/1.1 401 Authorization Required');
            header('WWW-Authenticate: Basic realm="Access denied"');
            header('Access-Control-Allow-Origin: *');
            exit;
        }
        AuthsController::issueJWT($key, $data);
        // return redirect('');
        return $next($request);
    }
    private function verifyToken($authHeader, $key) {
                
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
