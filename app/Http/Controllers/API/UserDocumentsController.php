<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Document;
use Illuminate\Http\Request;

class UserDocumentsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, User $user)
    {
        $transactionsDocuments = $user->transactions()->with('documents')->get();

        $data = $transactionsDocuments->map(function($value){
            return $value->documents;
        });

        // dd($user->transactions);

        // if ($request->has('doc_uri')) {
        //     $data->where('doc_uri', $request->doc_uri);
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
    public function store(Request $request, User $user)
    {
        $response = self::$response;

        // dd($request);

        $transaction = $user->transactions()->findOrFail($request->id_transaction);

        $document = new Document;
        $document->doc_uri = $request->doc_uri;
        $document->doc_api_id = $request->doc_api_id;

        $transaction->documents()->save($document);

        $response['success'] = true;
        $response['data'] = $transaction->documents();

        return response()->json($response, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, User $user, $documentId)
    {
        $response = self::$response;

        $data = $user->documents()->with('transactions')->findOrFail($documentId);

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
    public function update(Request $request, User $user, $documentId)
    {
        $response = self::$response;

        $document = $user->documents()->findOrFail($documentId);
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
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user, $documentId)
    {
        $response = self::$response;

        $document = $user->documents()->findOrFail($documentId);
        $save = $document->delete();

        $response['success'] = $save;
        return response()->json($response, 200);
    }
}
