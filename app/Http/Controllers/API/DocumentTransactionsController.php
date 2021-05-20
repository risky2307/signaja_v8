<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Transaction;
use Illuminate\Http\Request;

class DocumentTransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Document $document)
    {
        $response = self::$response;

        $data = $document->transactions()->with(['users', 'documents']);

        if ($request->has('statusaction')) {
            $data->where('statusaction', $request->statusaction);
        }

        if ($request->has('subject')) {
            $data->where('subject', $request->subject);
        }

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
    public function store(Request $request, Document $document)
    {
        $response = self::$response;

        $transaction = new Transaction;
        $transaction->subject       = $request->subject;
        $transaction->description   = $request->description;
        $transaction->statusaction  = $request->statusaction;
        $document->transactions()->save($transaction);


        $response['success'] = true;
        $response['data'] = $transaction;

        return response()->json($response, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Document  $document
     * @return \Illuminate\Http\Response
     */
    public function show(Document $document, $transactionId)
    {
        $response = self::$response;

        $data = $document->transactions()->with(['users','documents'])->findOrFail($transactionId);

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
    public function update(Request $request, Document $document, $transactionId)
    {
        $response = self::$response;

        $transaction = $document->transactions()->findOrFail($transactionId);
        $transaction->subject       = $request->subject;
        $transaction->description   = $request->description;
        $transaction->statusaction  = $request->statusaction;
        $transaction->save();

        $response['success'] = true;
        $response['data'] = $document->transactions()->findOrFail($transactionId);

        return response()->json($response, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Document  $document
     * @return \Illuminate\Http\Response
     */
    public function destroy(Document $document)
    {
        //
    }
}
