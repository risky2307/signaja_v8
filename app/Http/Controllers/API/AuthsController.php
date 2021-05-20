<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Log;
use Illuminate\Http\Request;
// use \Firebase\JWT\JWT;
use Cookie;

class AuthsController extends Controller
{

     /**
     * Login.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
      $response = self::$response;

      $sanitized_email = filter_var($request->email, FILTER_SANITIZE_EMAIL);
      $sanitized_pass = filter_var($request->password, FILTER_SANITIZE_STRING);

      if ( filter_var($sanitized_email, FILTER_VALIDATE_EMAIL) && $sanitized_pass ) {
        $existed_user = User::where("email", $sanitized_email)->first();
        if( !$existed_user ) {
            $response["message"] = "Unauthorized email and password.";
            Log::create(["name" => "401", "description" => $sanitized_email]);
            return response()->json($response, 401);
        }
      }
      else {
          $response["message"] = "Invalid email address and password";
          return response()->json($response, 400);
      }
      $ldap_ip = env("LDAP_IP");
      $ldapconn = ldap_connect("ldap://$ldap_ip");

      if( !$ldapconn ) {
        $response["message"] = "Could not connect to AD/LDAP server";
        Log::create(["name" => "401", "description" => "Could not connect to AD/LDAP server"]);
        return response()->json($response, 401);
      }

      ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
      ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

      // binding to ldap server
      $ldapbind = @ldap_bind($ldapconn, $sanitized_email, $sanitized_pass);

      // verify binding
      if (!$ldapbind) {
          $response["message"] = "Unauthorized email and password";
          Log::create(["name" => "401", "description" => "AD/LDAP: " . $sanitized_email]);
          return response()->json($response, 401);
      }

      $key = env("SECRET_JWT");
      $jwtData = array(
        "email" => $sanitized_email,
        "id"    => $existed_user->id,
        "role"  => [
          "isadmin"         => $existed_user->isadmin,
          "iscreator"       => $existed_user->iscreator,
          "isglobalviewer"  => $existed_user->isglobalviewer,
          "issigner"        => $existed_user->issigner
        ]
      );
    //   $jwt = $this::issueJWT($key, $jwtData);

      $response["success"] = true;
      $response["data"] = array(
        //   "jwt" => $jwt,
          "selor" => array (
            "isa" => $existed_user->isadmin,
            "isc" => $existed_user->iscreator,
            "isg" => $existed_user->isglobalviewer,
            "iss" => $existed_user->issigner
          ),
          "name" => $existed_user->name,
          "email" => $sanitized_email,
          "id" => $existed_user->id
      );

      $response["message"] = "Authorized";
      //Cookie::queue('test', 'Dimas', 120);

      //Log::create(["name" => "User logged in", "description" => $existed_user->name . " - " . $sanitized_email]);
      return response()->json($response, 200, ["Access-Control-Allow-Origin" => "*"]);
    }

//     public static function issueJWT($key, $data) {
//       $issuedAt = time();
//       $notBefore = $issuedAt + 0;
//       $expire = $notBefore + 900;

//       $payload = array(
//         "iss" => "http://signaja.com",
//         "aud" => "http://signaja.com",
//         "iat" => $issuedAt,
//         "nbf" => $notBefore,
//         "exp" => $expire,
//         "data" => $data
//       );

//         /**
//          * IMPORTANT:
//          * You must specify supported algorithms for your application. See
//          * https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40
//          * for a list of spec-compliant algorithms.
//          */
//         $jwt = JWT::encode($payload, $key);
//         return $jwt;
//     }
}
