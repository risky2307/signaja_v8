<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class TransactionUsersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Transaction $transaction)
    {
        $data = $transaction->users();

        $response['success'] = true;
        $response['data'] = $data->get();

        return response()->json($response, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Transaction $transaction)
    {
        $response = self::$response;

        $user = new User;
        $user->transactions()->save($transaction, [
            'id_user'       => $request->id_user,
            'docrole'       => $request->docrole,
            'firstviewdate' => $request->firstviewdate,
            'level'         => $request->level,
            'note'          => $request->note,
        ]);

        $response['success'] = true;
        $response['data'] = $transaction;

        return response()->json($response, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function show(Transaction $transaction, $userId)
    {
        $response = self::$response;

        $data = $transaction->users()->findOrFail($userId);

        $response['success'] = true;
        $response['data'] = $data;

        return response()->json($response, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Transaction $transaction, $userId)
    {
        $response = self::$response;

        $user = $transaction->users()->findOrFail($userId);
        $user->save();

        $transaction->users()->updateExistingPivot($userId, [
            'docrole'       => $request->docrole,
            'firstviewdate' => $request->firstviewdate,
            'level'         => $request->level,
            'note'          => $request->note,
        ]);


        $response['success'] = true;
        $response['data'] = $transaction->users()->findOrFail($userId);

        return response()->json($response, 200);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function destroy(Transaction $transaction, $userId)
    {
        $response = self::$response;

        $user = $transaction->users()->findOrFail($userId);
        $save = $user->delete();

        $response['success'] = $save;
        return response()->json($response, 200);
    }
}
