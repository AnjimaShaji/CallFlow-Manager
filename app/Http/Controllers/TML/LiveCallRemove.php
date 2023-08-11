<?php

namespace App\Http\Controllers;

use App\CustomLibraries\CommonUtils;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BangaloreCallOriginateController;
use Illuminate\Http\Request;
use App\CustomLibraries\Constants;
use Hidehalo\Nanoid\Client;
use DB;
use Log;

class LiveCallRemove extends controller{

    public function getCallback(Request $request)
    {
        $token = $request -> bearerToken();
        $cdr = $request->all(); 
        Log::info("Token:$token");
        Log::info("Request:$cdr");
        if(empty(CommonUtils::validateAuthoriztaionToken($token))){
            $response['error']['autorization token'] = "Authorization token is empty";
            } else {
                if(empty($cdr['gateway'])){
                    $response['error']['gateway'] = "Gateway is empty";
                } else{
                    if($cdr['gateway'] == "BANGALORE"){
                        if(empty($cdr['callId'])){
                            $response['error']['callId'] = "CallId is empty";
                        }
                        else{
                            DB::table('live_calls')->where('call_id', '=', $cdr['callId'])->delete();
                            $apiKey = 'way_tata_ob:tataob#oiZCbuEGwr';
                            $Url = 'https://tata.waybeo.com/api/call-center-ob/callback';
                            $resp = CommonUtils::triggerCurl($cdr, $Url, $apiKey);
                            Log::info("Response:$resp");
                        }
                    }
                }
            }

    }
}
?>