<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Cookie;

class UserTransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, User $user)
    {
        $response = self::$response;
        //$request->jwt_data["email"];

        $transactions = Transaction::with(['documents', 'users']);
        //$ownedTransactions = $transactions->where('users.id', 18)-get();

        //$response['data'] = $user->id;

        //search for transactions owened or signed by $user        
        $response['data'] = array();
        foreach ($transactions->get() as $transaction) {
            $owner = User::where("id", $transaction->owner_by)->first();
            $transaction->owner_by = $owner;

            if ($transaction->owner_by->id == $user->id) {                
                $response['data'][] = $transaction;
                continue;
            }
            foreach ($transaction->users as $trxuser) {
                if ($trxuser->id == $user->id) {
                    $response['data'][] = $transaction;
                    break;
                }
            }

        }             
        /* $data = $user->transactions()->with(['documents', 'users']);

        if ($request->has('statusaction')) {
            $data->where('statusaction', $request->statusaction);
        }

        if ($request->has('subject')) {
            $data->where('subject', $request->subject);
        }
 */
        //$merged = $data->merge($transactions);
        //array_push($transactions, $data)

        $response['success'] = true;
        //$response['message'] = $request->header();
        //$response['data'] = $transactions->get();
        //$response['data']->append( $data->get() );
             
        return response()->json($response, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, User $user)
    {
        $response = self::$response;

        $transaction = new Transaction;
        $transaction->subject       = $request->subject;
        $transaction->description   = $request->description;
        $transaction->statusaction  = $request->statusaction;
        $user->transactions()->save($transaction, [
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
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, User $user, $transactionId)
    {
        $response = self::$response;

        $data = $user->transactions()->with('documents')->findOrFail($transactionId);

        $response['success'] = true;
        $response['data'] = $data;

        return response()->json($response, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user, $transactionId)
    {
        $response = self::$response;

        $transaction = $user->transactions()->findOrFail($transactionId);
        $transaction->subject       = $request->subject;
        $transaction->description   = $request->description;
        $transaction->statusaction  = $request->statusaction;
        $transaction->save();

        $user->transactions()->updateExistingPivot($transactionId, [
            'docrole'       => $request->docrole,
            'firstviewdate' => $request->firstviewdate,
            'statusaction' => $request->statusaction,
            'level'         => $request->level,
            'note'          => $request->note,
        ]);


        $response['success'] = true;
        $response['data'] = $user->transactions()->findOrFail($transactionId);

        return response()->json($response, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user, $transactionId)
    {
        $response = self::$response;

        $transaction = $user->transactions()->findOrFail($transactionId);
        $save = $transaction->delete();

        $response['success'] = $save;
        return response()->json($response, 204);
    }
}
