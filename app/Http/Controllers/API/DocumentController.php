<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $response = self::$response;

        $response['data']    = Document::all();
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

        $data = [
            'doc_uri' => $request->doc_uri,
            'doc_api_id' => $request->doc_api_id
        ];

        $document = new Document;

        $document = $this->insert($data, $document);

        $response['data']    = $document;
        $response['success'] = $document ? true : false;
        $response['message'] = $document ? 'Create Data Success' : 'Create Data Failed';
        return response()->json($response, 201);
    }

    public static function insert($data, Document $document)
    {
        $data = is_object($data) ? $data : (object) $data;
        $document->doc_uri    = $data->doc_uri;
        $document->doc_api_id = $data->doc_api_id;
        if($document->save())
            return($document);
        else
            return(false);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Document  $document
     * @return \Illuminate\Http\Response
     */
    public function show(Document $document)
    {
        $response = self::$response;

        $response['data']    = $document;
        $response['success'] = true;
        return response()->json($response, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Document  $document
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Document $document)
    {
        $response = self::$response;

        $document->doc_uri    = $request->doc_uri;
        $document->doc_api_id = $request->doc_api_id;
        $save = $document->save();

        $response['data']    = $document;
        $response['success'] = $save;
        $response['message'] = $save ? 'Update Data Success' : 'Update Data Failed';

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
        $response = self::$response;

        $delete = $document->delete();
        $response['success'] = $delete;
        $response['message'] = $delete ? 'Delete Data Success' : 'Delete Data Failed';

        return response()->json($response, 200);
    }
}
