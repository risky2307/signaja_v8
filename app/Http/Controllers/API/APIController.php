<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use App\Http\Controllers\API\MailController;
use App\Models\Document;
use App\Models\User;
Use App\Models\Transaction;
Use App\Models\Config;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class APIController extends Controller
{
    public $file = "";
    public $url;
    private $token = "";
    private $token_expire = 3600;

    public function __construct()
    {
        $this->url = env("API_URL");
        $this->token = $this->getToken();
    }
    public function prepare($document, $requestData)
    {
        $fileUpload = new \CURLFile($document);

        $documentJson = new \stdClass();
        $documentJson->request_name = $requestData->subject;
        $documentJson->is_sequential = $requestData->sequential;
        $documentJson->actions = array();
        for($i=0; $i<count($requestData->users); $i++) {
            $userApi = new \stdClass();
            $userApi->recipient_name = $requestData->users[$i]->name;
            $userApi->recipient_email = $requestData->users[$i]->email;
            $userApi->action_type = $requestData->users[$i]->docrole;
            $userApi->is_embedded = true;
            $userApi->signing_order = $i;
            array_push($documentJson->actions, $userApi);
            $userApi = null;
        }

        $requests = new \stdClass();
        $requests->requests = $documentJson;

        $POST_DATA = array(
            'file' => $fileUpload,
            'data' => json_encode($requests)
        );
                
        $curl = curl_init($this->url . "/requests");
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Authorization:Zoho-oauthtoken ' . $this->token,
            "Content-Type:multipart/form-data"
            ));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $POST_DATA);
        $responseApi = curl_exec($curl);
        curl_close($curl);

        $jsonbody = new \stdClass();
        $jsonbody = json_decode($responseApi);

        //Error
        if( !$jsonbody || $jsonbody->status != "success") {
            Log::create(["name" => "ERROR: API - Prepare", "description" => "Error when preparing document to Zoho\nTransaction ID: -\nSource: APIController@prepare"]);
            $response['success'] = false;
            $response['message'] = $responseApi;
            return $response;
        }

        //Check if signature tags exist
        foreach ($jsonbody->requests->actions as $signer) {
            if( !count($signer->fields) ) {
                $curl = curl_init($this->url . "/requests/" . $jsonbody->requests->request_id . "/delete");
                curl_setopt($curl, CURLOPT_TIMEOUT, 30);
                curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                    'Authorization:Zoho-oauthtoken ' . $this->token
                    ));
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                $responseApi = curl_exec($curl);
                curl_close($curl);

                Log::create(["name" => "ERROR: API - Prepare", "description" => "Signature tags are required for all signers\nTransaction ID: -\nSource: APIController@prepare"]);

                $response['success'] = false;
                $response['message'] = "Signature tags are required for all signers";
                $response['data'] = $jsonbody;
                return $response;
            }
        }

        $response = $this->send($jsonbody);

        if( !$response["success"] ) {
            return $response;
        }

        //SUCCESS
        $response['data']    = $jsonbody->requests;
        $response['success'] = true;
        $response['message'] = $jsonbody->status;
        return $response;
        //return response()->json($response, 200);
    }

    public function send($apiRequest)  {
        /*
        $actionEmbedded = new \stdClass();
        $actionEmbedded->requests = new \stdClass();
        $actionEmbedded->requests->request_id = $apiRequest->requests->request_id;
        $actionEmbedded->requests->request_name = $apiRequest->requests->request_name;
        $actionEmbedded->requests->actions = array();
        for($i=0; $i<count($apiRequest->requests->actions); $i++) {
            $actionEmbedded->requests->actions[$i] = new \stdClass();
            $actionEmbedded->requests->actions[$i]->action_id = $apiRequest->requests->actions[$i]->action_id;
            $actionEmbedded->requests->actions[$i]->is_embedded = true;
        }
        $POST_DATA = array('data' => json_encode($actionEmbedded));*/
        $curl = curl_init($this->url . "/requests/" . $apiRequest->requests->request_id . "/submit");
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Authorization:Zoho-oauthtoken ' . $this->token
            ));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($curl, CURLOPT_POSTFIELDS, $POST_DATA);
        $responseApi = curl_exec($curl);
        curl_close($curl);

        $jsonbody = new \stdClass();
        $jsonbody = json_decode($responseApi);

        //Error
        if(!$jsonbody || $jsonbody->status != "success") {
            $response['success'] = false;
            //$response['message'] = $apiRequest->requests->request_name . " -- " . $responseApi;            
            $response['data'] = json_encode($actionEmbedded);
            Log::create(["name" => "ERROR: API - Submit Doc", "description" => "Error when submitting document to Zoho\nTransaction ID: -\nSource: APIController@send"]);

            return $response;
        }

        $response['success'] = true;
        $response['data'] = $jsonbody;
        return $response;
    }

    public function getEmbedUrl(Request $request)
    {
        $response = self::$response;
        if( strlen($request->user_id) < 1 || strlen($request->request_id) < 1 ) {
            return response()->json($response, 500);
        }

        $action_id = null;

        if( strlen($request->action_id) < 1 )
        {
            $user_email = User::where('id', $request->user_id)->value('email');
            $doc_details = Http::withHeaders([
                "Authorization" => "Zoho-oauthtoken " . $this->token
            ])->get($this->url . "/requests/" . $request->request_id);
            $response["data"] = $doc_details["requests"];
            //return response()->json($response, 500);
            foreach($doc_details["requests"]["actions"] as $action)
            {
                if( strcasecmp($action["recipient_email"], $user_email) == 0 )
                {
                    $action_id = $action["action_id"];
                }
            }
        }
        else 
        {
            $action_id = $request->action_id;
        }

        if( !$action_id ) {
            $response["message"] = "!action_id - " . $user_email;
            return response()->json($response, 500);
        }

        $responseEmbed = Http::withHeaders([
            "Authorization" => "Zoho-oauthtoken " . $this->token
        ])->post($this->url . "/requests/" . $request->request_id . '/actions/' . $action_id . "/embedtoken", [
            "host" => "https://signaja.onesmartservices.id"
        ]);

        //Error
        if( $responseEmbed["status"] != "success" ) {
            $response["message"] = "Error ($action_id) retrieving embed url: $this->token";
            $response["data"] = $responseEmbed["message"];
            return response()->json($response, 500);
        }

        $response["data"] = $responseEmbed["sign_url"];
        return response()->json($response, 200);
    }

    public function getToken()
    {
        $accessToken = Config::firstWhere('name', 'token');
        $token_updated = strtotime($accessToken->updated_at);
        if( ($this->token_expire - (time() - $token_updated)) <= 45  ) {
            $ref_token = Config::firstWhere('name', 'ref_token')->value;
            $client_id = Config::firstWhere('name', 'client_id')->value;
            $client_secret = Config::firstWhere('name', 'client_secret')->value;

            $zresponse = Http::post('https://accounts.zoho.com/oauth/v2/token?refresh_token=' . $ref_token . '&client_id=' . $client_id . '&client_secret=' . $client_secret . '&grant_type=refresh_token');
            if( !$zresponse->successful() ) {
                $response['success'] = false;
                $response['message'] = "Error refreshing token ($client_id)";
                $response['data'] = $zresponse;
                return $response;
            }
            Config::where('name', 'token')->update(['value' => $zresponse['access_token']]);
            return $zresponse['access_token'];
        }

        return "" . $accessToken->value;
        
    }

    public function webhooks(Request $request) {

        $response = self::$response;        
        
        // $data = $user->transactions();


        //$this->webhook_viewed($request);
        //akses request Type
        // $request->notifications->operation_type;
        if ($request->has('notifications.operation_type')) {
            
            $transaction = Transaction::where('api_id', $request->input('requests.request_id'))->first();
            $user = User::where('email', $request->input('notifications.performed_by_email'))->first();
            switch( $request->input('notifications.operation_type') ) {
                case "RequestViewed":                    
                    $this->webhook_viewed($transaction, $user);
                break;
                case "RequestSigningSuccess":                    
                    $this->webhook_signed($request, $transaction, $user);
                    /*
                    $user_level = $user->transactions()->find($transaction->id_transaction)->value('level');
                    $next_user_id = $transaction->users()
                        ->where([['level', '>=', $user_level], ['statusaction', null]])
                        ->orderBy('level')
                        ->limit(1)
                        ->value('id_user');
                    if($next_user_id) {
                        $next_user_email = User::find($next_user_id)->email;
                    }
                    */
                break;
                case 'RequestCompleted':
                    $this->webhook_completed($request);
                break;
                case 'RequestRecalled':
                    $this->webhook_recalled($transaction);
                break;
            }
        }

    }

    public function webhook_viewed($transaction, $user)
    {
        $trx = $user->transactions()->findOrFail($transaction->id_transaction);

        if (!$trx->pivot->firstviewdate) {
            $user->transactions()->updateExistingPivot($transaction, ['firstviewdate' => NOW(), 'statusaction' => 'VIEWED']);
        }
    }

    public function webhook_signed($request, $transaction, $user) 
    {   
        $data = $request->input();           
        $user->transactions()->updateExistingPivot($transaction, ['statusaction' => 'SIGNED']);

        $user_signing_order = $data["notifications"]["signing_order"];
        if( $user_signing_order >= count($data["requests"]["actions"]) )
            return;   // All signers have signed

        $next_user = $data["requests"]["actions"][$user_signing_order];
        
        $data_email = array(
            'name'          => $next_user["recipient_name"],
            'user_id'       => User::where('email', $next_user["recipient_email"])->value('id'),
            'request_id'    => $data["requests"]["request_id"],
            'action_id'     => $next_user["action_id"],
            'subject'       => $transaction->subject
        );

        $mailClass = new MailController();
        if( !$mailClass->sendMail(
            "Sign Aja - Request to sign: " . $transaction->subject, 
            $next_user["recipient_email"], 
            $next_user["recipient_name"], 
            $data_email)
        )
        {
            Log::create(["name" => "Email Error", "description" => "Error when sending email to next signer\nTransaction ID: " .$transaction->id_transaction . "\nSource: webhook_signed"]);
        }

    }

    public function webhook_declined(Request $request){

        $response = self::$response;

        $transaction = Transaction::where('api_id', $request->requests->request_id);
        $transaction->statusaction = 'Rejected';
        $transaction->save();

        $user = User::where('email', $request->notifications->performed_by_email);

        $user->transactions()->save($transaction, ['statusaction' => 'REJECTED']);

    }

    public function webhook_recalled($transaction) {
        $transaction->statusaction = 'Recalled';
        $transaction->save();
    }

    public function webhook_completed(Request $request){

        $response = self::$response;

        //transaction signed
        $transaction = Transaction::where('api_id', $request->input('requests.request_id'))->first();
        $transaction->statusaction = 'Completed';
        $transaction->save();

        // Downloading file
        $curl = curl_init($this->url . '/requests/' . $request->input("requests.request_id") . '/pdf');
        //$curl = curl_init("https://sign.zoho.com/api/v1/requests/182284000000011007/pdf");
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Authorization:Zoho-oauthtoken ' . $this->token
            ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $downloadedFile = curl_exec($curl);
        $curl = null;
        Storage::disk('local')->put($request->input("requests.request_id") . ".pdf", $downloadedFile);
        //file_put_contents($path . $request->input("requests.request_id") . ".pdf", $downloadedFile);

        // Download Trail/Certificate
        $curl = curl_init($this->url . '/requests/' . $request->input("requests.request_id") . '/completioncertificate');
        //$curl = curl_init("https://sign.zoho.com/api/v1/requests/182284000000011007/pdf");
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Authorization:Zoho-oauthtoken ' . $this->token
            ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $trailDoc = curl_exec($curl);
        $curl = null;
        Storage::disk('local')->put($request->input("requests.request_id") . "_trail.pdf", $trailDoc);

        //document
        $document = new Document;
        $data = new \stdClass();
        $data->doc_uri = Storage::path($request->input("requests.request_id"));
        $data->doc_api_id = $request->input("requests.request_id");

        $document = DocumentController::insert($data, $document);
        $transaction->documents()->save($document);

    }
}
?>
