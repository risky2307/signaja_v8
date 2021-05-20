<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Log;
use App\Http\Middleware\AuthenticateJWT;
use Illuminate\Http\Request;
use Mail;

class TransactionController extends Controller
{
    private $recipient_email;
    private $recipient_name;
    private $email_subject;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $response = self::$response;

        $response['data']    = Transaction::all();
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

        $uploadedFile = $request->file("file");
        $requestData = json_decode($request->input("data"));
        
        if( !$uploadedFile->isValid() ){
            return response()->json($response, 400);
        }

        foreach ($requestData->users as $user) {
            if( !User::where("email", $user->email)->first() ) {
                $response["message"] = "Email \"" . $user->email . "\" not found";
                return response()->json($response, 400);
            }
        }

        move_uploaded_file($uploadedFile->path(), "/tmp/" . $uploadedFile->getClientOriginalName());

        $API = new APIController();
        $API->file = $uploadedFile->path() . ".pdf";
        
        $apiReturn = $API->prepare("/tmp/" . $uploadedFile->getClientOriginalName(), $requestData);

        if( !$apiReturn["success"] )
            return response()->json($apiReturn, 400);
        
        
        $transaction = new Transaction;        

        $transaction->subject          = $requestData->subject;
        $transaction->description      = $requestData->description;
        $transaction->statusaction     = "Waiting";
        $transaction->api_id           = $apiReturn["data"]->request_id;
        $transaction->owner_by         = $request->jwt_data["id"];
        $save = $transaction->save();

        
        $usersJson = array();
        //foreach ($requestData->users as $user) {
        for($i=0; $i<count($requestData->users); $i++) {
            $userDetail = User::where("email", $requestData->users[$i]->email)->first();
            $userDetail->transactions()->save($transaction, [
                'docrole'       => $requestData->users[$i]->docrole,
//              'user_api_id'   => $apiReturn['data']->actions[$i]->action_id,            
                'firstviewdate' => $requestData->users[$i]->firstviewdate,
                'statusaction' => $requestData->users[$i]->statusaction,
                'level'         => $requestData->users[$i]->level,
                'note'          => $requestData->users[$i]->note,
            ]);

            $requestData->users[$i]->name = $userDetail->name;

            //Sending email
            /* try {
                // Data to be used on email template
                $data = array(
                    'name'          => $requestData->users[$i]->name,
                    'user_id'       => $userDetail->id,
                    'request_id'    => $apiReturn['data']->request_id,
                    'action_id'     => $apiReturn['data']->actions[$i]->action_id,
                    'subject'       => $requestData->subject

                );
                $this->recipient_email  = $requestData->users[$i]->email;
                $this->recipient_name   = $requestData->users[$i]->name;
                $this->email_subject    = $requestData->subject;

                 Mail::send('mail', $data, function($message) {
                    $message->to($this->recipient_email, $this->recipient_name)->subject
                       ("Sign Aja - Request to sign: " . $this->email_subject);
                });
            } catch(\Exception $e) {
                return response($e->getMessage(), 422);
            } */
        }

        // Sending to first signer
        $data_email = array(
            'name'          => $requestData->users[0]->name,
            'user_id'       => User::where('email', $requestData->users[0]->email)->value('id'),
            'request_id'    => $apiReturn['data']->request_id,
            'action_id'     => $apiReturn['data']->actions[0]->action_id,
            'subject'       => $requestData->subject
        );
        $mailClass = new MailController();
        if( !$mailClass->sendMail(
            "Sign Aja - Request to sign: " . $requestData->subject, 
            $requestData->users[0]->email, 
            $requestData->users[0]->name, 
            $data_email)
        )
        {
            Log::create(["name" => "Email Error", "description" => "Error when sending email to first signer\nTransaction ID: " .$transaction->id_transaction . "\nSource: APIController@store"]);
            return response($e->getMessage(), 422);
        }      
        

        return response()->json($apiReturn, 200);
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Transaction $transaction)
    {
        $response = self::$response;
        $transaction->load('users','documents');
        $owner = User::where("id", $transaction->owner_by)->first();
        $transaction->owner_by = $owner;
        $response['data']    = $transaction;
        $response['success'] = true;
        return response()->json($response, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Transaction $transaction)
    {
        $response = self::$response;

        $transaction->subject          = $request->subject;
        $transaction->description      = $request->description;
        $transaction->statusaction     = $request->statusaction;
        $save = $transaction->save();

        $response['data']    = $transaction;
        $response['success'] = $save;
        $response['message'] = $save ? 'Update Data Success' : 'Update Data Failed';

        return response()->json($response, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function destroy(Transaction $transaction)
    {
        $response = self::$response;

        $delete = $transaction->delete();
        $response['success'] = $delete;
        $response['message'] = $delete ? 'Delete Data Success' : 'Delete Data Failed';

        return response()->json($response, 200);
    }
    
    public function download($id)
    {
        $response = self::$response;

        $transaction = Transaction::where("id_transaction", $id)->first()->load("users", "documents");
        
        $fullpath = ($transaction->documents[0]->doc_uri) ? $transaction->documents[0]->doc_uri:null;
        $fullpath .= ".pdf";
        //$filename = basename($fullpath);
        
        if( file_exists($fullpath) )
            return response()->file($fullpath, ['Content-Disposition' => "inline; filename=" . $transaction->subject . ".pdf"]);
        else 
            return response()->json($response, 404);
    }

    public function trail($id) 
    {
        $response = self::$response;

        $transaction = Transaction::where("id_transaction", $id)->first()->load("users", "documents");
        
        $fullpath = ($transaction->documents[0]->doc_uri) ? $transaction->documents[0]->doc_uri:null;
        //$filename = basename($fullpath);
        $fullpath .= "_trail.pdf";
        
        if( file_exists($fullpath) )
            return response()->file($fullpath, ['Content-Disposition' => "inline; filename=" . $transaction->subject . "_trail.pdf"]);
        else 
            return response()->json($response, 404);
    }

}
