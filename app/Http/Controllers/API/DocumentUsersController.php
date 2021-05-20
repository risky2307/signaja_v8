<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;

class DocumentUsersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Document $document)
    {
        $transactionsUsers = $document->transactions()->with('users')->get();

        $data = $transactionsUsers->map(function($value){
            return $value->users;
        });


        // if ($request->has('name')) {
        //     $data->where('name', $request->name);
        // }

        $response['success'] = true;
        $response['data'] = $data;

        return response()->json($response, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Document $document)
    {
        $response = self::$response;

        // dd($request);

        $transaction = $document->transactions()->findOrFail($request->id_transaction);
        $user = new User;
        $transaction->users()->save($transaction, [
            'id_user'       => $request->id_user,
            'docrole'       => $request->docrole,
            'firstviewdate' => $request->firstviewdate,
            'level'         => $request->level,
            'note'          => $request->note,
        ]);

        $response['success'] = true;
        $response['data'] = $transaction->users();

        return response()->json($response, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Document  $document
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Document $document, $userId)
    {
        $response = self::$response;

        $data = $document->users()->with('documents')->findOrFail($userId);

        $response['success'] = true;
        $response['data'] = $data;

        return response()->json($response, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Document  $document
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Document $document, $userId)
    {
       //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Document  $document
     * @return \Illuminate\Http\Response
     */
    public function destroy(Document $document, $userId)
    {
        $response = self::$response;

        $user = $document->users()->findOrFail($userId);
        $save = $user->delete();

        $response['success'] = $save;
        return response()->json($response, 200);
    }
}
