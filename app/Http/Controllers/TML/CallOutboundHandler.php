<?php

namespace App\Http\Controllers\TML;

use App\CustomLibraries\CommonUtils;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BangaloreCallOriginateController;
use Illuminate\Http\Request;
use App\CustomLibraries\Constants;
use Hidehalo\Nanoid\Client;
use DB;
use Log;

class CallOutboundHandler extends controller 
{
    public function CallOriginateBanglore($request)
    {
        $apiKey = 'Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkRpYWxlciIsImlhdCI6MTUxNjIzOTAyMn0.3zvoGy50DUB1hgYmbU3WIgZoz2n-1jDP1APjrYnz5Bg';
        $bangloreUrl = 'http://callflow-uat.waybeo.com/originate';
        $resp = CommonUtils::triggerCurl($request, $bangloreUrl, $apiKey);
        print_r($resp);
        return $resp;
    }

    public function getDetails(Request $request)
    {
        $timeStart = microtime(true);
        $params = $request->all();
        $requestId = uniqid();
        Log::info("$requestId: Input: ", $params);
        $params['requestId'] = $requestId;
        $token= $request->bearerToken();
        Log::info("$requestId: Token: $token");
        $response = [];

        if(empty($params)) {
            $response['error']['request'] = "Request empty";
        }

        if(empty($token)) {
            $response['error'] = "Authorization Token empty";
        } 

        if(!empty($response['error'])) {
            Log::info("$requestId: Error: ", $response);
            $statusCode = 400;
        } else {
            //if(!empty($stasisApp)) {             
                $resp = self::CallOriginateBanglore($params);
                print_r($resp);
                $customer_number = $params['callFlow'][0]['CONNECT_GROUP']['participants'][0]['number'];
                $agent_number = $params['callerNumber'];

                if($resp['httpcode'] == 200 || $resp['httpcode'] == 204) {
                    $call_id = $resp['call_id'];
                    $gateway = "BANGALORE";
                    $data = array('call_id' => $call_id,'agent_number' => $agent_number,'customer_number' => $customer_number,'gateway' => $gateway);
                    DB::table('live_calls')->insert($data);                  
                }
            // } else {
            //     $response['error'] = 'Invalid Authorization Token';
            //     Log::info("$requestId: Error: ", $response);
            //     $statusCode = 401;
            // }
        }
    
        return $resp;
    }
    
    private function validateNumber($number)
    {
        if (strlen($number) == 13 && substr($number, 0, 3) == '+91') {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}
?>