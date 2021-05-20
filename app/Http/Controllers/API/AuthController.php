<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Validator;
use Auth;
use Carbon\Carbon;


class AuthController extends Controller
{
    // private static $response = [
    //     'success' => false,
    //     'data'    => null,
    //     'message' => null
    // ];

    function login(Request $request)
    {
        $response = self::$response;

        $credentials = $request->only('email','password');

            if (!$credentials) {
                return response()->json($response['message'] = 'Unauthorized',401);
            }

        $rules = array(
            'email'    => 'required',
            'password'    => 'required',
        );

        $messages = array(
            'email.required' => 'email is required.'
        );

        $validator = Validator::make( $request->all(), $rules, $messages );

            if ( $validator->fails() )
            {
                $response['message'] = 'Unauthorized';
                return response()->json($response,401);
            }

        if (!Auth::attempt($credentials)) {
            $response['message'] = 'Unauthorized';
            return response()->json($response,401);
        }
        //panggil function untuk auth AD
        //checkAD()
        $user = Auth::user();

        $tokenResult = $user->createToken('app-access-token');
        $token = $tokenResult->token;

        $response['success'] = true;
        $response['data'] = [
            'access_token' => $tokenResult->accessToken,
            'token_type'   => 'Bearer',
            'expired_at'   => Carbon::parse($token->expires_at)->toDateTimeString()
        ];

        return response()->json($response);
    }

    function logout(Request $request)
    {
        Auth::user()->token()->revoke();

        $response['success'] = true;
        $response['message'] = 'Successfully logged out';
        return response()->json($response);

    }

    function profile(Request $request)
    {
        $response = self::$response;

        $response['success'] = true;
        $response['data']    = Auth::user();

        return response()->json($response);
    }

}
