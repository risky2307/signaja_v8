<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Log;
use Mail;

class MailController extends Controller
{
    private $recipient_email;
    private $recipient_name;
    private $email_subject;

    function sendMail($subject="Coba Email", $to_email="webmaster@samaktamitra.com", $to_name="Sign Aja", $data=null)
    {
        $response = self::$response;
        $this->email_subject = $subject;
        $this->recipient_email = $to_email;
        $this->recipient_name = $to_name;
        //if(!$data) $data = ["name" => $to_name, "subject" => "Hehehe"];
        try {
            Mail::send('mail', $data, function($message) {
                $message->to($this->recipient_email, $this->recipient_name)->subject
                ($this->email_subject);
            });
        } catch(\Exception $e) {
	    return false;
        }
        return true;
    }
}

?>
