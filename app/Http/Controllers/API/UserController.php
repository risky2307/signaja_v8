<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use SebastianBergmann\CodeCoverage\Driver\Driver;
use Mail;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $response = self::$response;

        // if( !AuthsController::verifyToken($_SERVER['HTTP_AUTHORIZATION']) )
        //     return response()->json($response, 401);

        if( $request->query("user") && $request->query("user") == "me" )
            $response['data'] = User::find($request->jwt_data["id"]);
        else
            $response['data']    = User::all();
        $response['success'] = true;          
        
        return response()->json($response, 200);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $response = self::$response;

        $user = new User;
        $user->id           = $request->id;
        $user->name         = $request->name;
        $user->email        = $request->email;
        $user->isadmin      = $request->isadmin;
        $user->iscreator    = $request->iscreator;
        $user->isglobalviewer = $request->isglobalviewer;
        $user->issigner     = $request->issigner;
        $save = $user->save();

        $response['data']    = $user;
        $response['success'] = $save;
        $response['message'] = $save ? 'Create Data Success' : 'Create Data Failed';

        return response()->json($response,201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        $response = self::$response;

        $response['data']    = $user;
        $response['success'] = true;
        return response()->json($response, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $response = self::$response;

        $user->name         = $request->name;
        $user->email        = $request->email;
        $user->isadmin      = $request->isadmin;
        $user->iscreator    = $request->iscreator;
        $user->isglobalviewer = $request->isglobalviewer;
        $user->issigner      = $request->issigner;
        $save = $user->save();

        $response['data']    = $user;
        $response['success'] = $save;
        $response['message'] = $save ? 'Update Data Success' : 'Update Data Failed';

        return response()->json($response, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $response = self::$response;

        try{
            $delete = $user->delete();
            $response['success'] = true;
            $response['message'] = 'Delete User Success';

            return response()->json($response, 204);
        } catch (\Illuminate\Database\QueryException $e) {
            $response['success'] = false;
            $response['message'] = 'Delete User Failed';
            $response["data"] = $e->errorInfo;
            return response()->json($response, 500);
        }
    }
    public function whoAmI(Request $request)
    {
        $response = self::$response;
        $response["data"] = User::find($request->jwt_data["id"]);
        $response['success'] = true;
        $response["message"] = $request->jwt_data;
        return response()->json($response, 200);
    }
}
