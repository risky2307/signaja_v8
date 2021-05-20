<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Document;
use Illuminate\Http\Request;

class TransactionDocumentsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Transaction $transaction)
    {
        $data = $transaction->documents();

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

        $document = new Document;
        $document->doc_uri = $request->doc_uri;
        $document->doc_api_id = $request->doc_api_id;
        $transaction->documents()->save($document);

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
    public function show(Transaction $transaction, $documentId)
    {
        $response = self::$response;

        $data = $transaction->documents()->findOrFail($documentId);

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
    public function update(Request $request, Transaction $transaction, $documentId)
    {
        $response = self::$response;

        $document = $transaction->documents()->findOrFail($documentId);
        $document->doc_uri = $request->doc_uri;
        $document->doc_api_id = $request->doc_api_id;
        $save = $document->save();

        $response['success'] = $save;
        $response['data'] = $document;

        return response()->json($response, 200);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function destroy(Transaction $transaction, $documentId)
    {
        $response = self::$response;

        $document = $transaction->documents()->findOrFail($documentId);
        $save = $document->delete();

        $response['success'] = $save;
        return response()->json($response, 200);
    }
}
