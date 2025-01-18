<?php

namespace App\Http\Controllers\Message;

use App\Http\Controllers\Controller;
use App\Service\Sms\SendSms;
use Illuminate\Http\Request;

class AtSMSController extends Controller
{

    public function send(Request $request)
    {
        $sms_api = new SendSms();
        return $sms_api->send("+254707382488");
    }
}
