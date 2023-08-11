<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\CustomLibraries\Constants;
use Hidehalo\Nanoid\Client;
use DB;
use Log;

class BangaloreCallOriginateController extends Controller
{
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
        
        if(empty($params['callerNumber'])) {
            $response['error']['callerNumber'] = "Caller Number empty";
        }

        if(empty($params['callerId'])) {
            $response['error']['callerId'] = "Caller Id empty";
        }

        if(empty($params['callFlow'])) {
            $response['error']['callFlow'] = "CallFlow empty";
        }

        if (!empty($params['callFlow']) && !is_array(json_decode(json_encode($params['callFlow'])))) {
            $response['error']['callFlow'] = "Invalid CallFlow Json";
        } 
    
        if(empty($token)) {
            $response['error'] = "Authorization Token empty";
        } 

        if(!empty($params['callerNumber'])) {
            $callerNumberStatus = $this->validateNumber($params['callerNumber']);
            if(!$callerNumberStatus) {
                $response['error']['callerNumber'] = "Invalid Caller Number Number";
            }
        }

        if(!empty($params['callerId'])) {
            $callerIdStatus = $this->validateNumber($params['callerId']);
            if(!$callerIdStatus) {
                $response['error']['callerId'] = "Invalid Caller Id Number";
            }
        }

        if(!empty($params['extraParams'])) {
            if(count($params['extraParams']) > 5) {
                $response['error']['callerId'] = "Extra params limited to 5";
            }
        }
         
        if(!empty($response['error'])) {
            Log::info("$requestId: Error: ", $response);
            $statusCode = 400;
        } else {
            $stasisApp = self::validateAuthoriztaionToken($token);
            if(!empty($stasisApp)) {
                $client = new Client();
                $callId = $client->generateId($size = 21);
                $resp = self::originateEvent($params,$stasisApp,$callId);
                if($resp['httpcode'] == 200 || $resp['httpcode'] == 204) {
                    $response = [
                        "status" => "success",
                        "requestId" => $requestId,
                        "callId" => $callId
                    ];
                    $statusCode = 200;
                } else {
                    $response = [
                        "status" => "failure",
                        "requestId" => $requestId,
                        "error" => $resp['response']
                    ];
                    $statusCode = $resp['httpcode'];
                }
                Log::info("$requestId: Originate Response: ", $response);
            } else {
                $response['error'] = 'Invalid Authorization Token';
                Log::info("$requestId: Error: ", $response);
                $statusCode = 401;
            }
        }
        $loggerData = [];
        $loggerData['request'] = $params;
        $loggerData['requestId'] = $requestId;
        $loggerData['token'] = $token;
        $loggerData['response'] = $response; 
        $loggerData['statusCode'] = $statusCode;
        $loggerData['executionTime'] = (microtime(true) - $timeStart);
        if(empty($response['error'])) {
            $loggerData['callId'] = $callId;
        }
        self::requestLog($loggerData);
        self::responseLog($loggerData);
        if($statusCode == 0) {
            $statusCode = 504;
        }
        return response()->json($response, $statusCode);
    }

    public function validateAuthoriztaionToken($token)
    {
        $filename = storage_path().'/app/tokens/'.$token;
        if(file_exists($filename)) {
            $handle = fopen($filename, 'r');
            $content = fread($handle,filesize($filename));
            $data = json_decode($content, true);
            if(!empty($data['stasis-app'])) {
                $stasisApp = $data['stasis-app'];
                return $stasisApp;
            } else {
                return FALSE;
            }     
        } else {
            return FALSE;
        }

    }


    private function validateNumber($number)
    {
        if (strlen($number) == 13 && substr($number, 0, 3) == '+91') {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function originateEvent($params,$stasisApp,$callId)
    {
        $apiKey = 'qRb2e8veTMAPRJEg@Q33xsE_BPRI:B-4_ELgXw*BNVVfS';
        $asteriskUrl = 'http://103.31.215.218:8088/ari/events/user/originate-call?application='.$stasisApp;
        $request = ['variables' => [
              'application' => $stasisApp,
              'caller_number' => $params['callerNumber'],
              'caller_id' => $params['callerId'],
              'call_id' => $callId,
              'callflow' => $params['callFlow']
            ]
        ];
        if(!empty($params['extraParams'])) {
            $request['variables'] = array_merge($request['variables'],[
                'extra_params' => $params['extraParams']
            ]);
        }
        Log::info("{$params['requestId']}: Originate Request: ", $request);
        $resp = $this->triggerCurl($request, $asteriskUrl, $apiKey, 5);
        Log::info("{$params['requestId']}: Originate Curl Response: ", $resp);

        // if(isset($resp['httpcode']) && !($resp['httpcode'] >= 200 && $resp['httpcode'] < 300)) {
        //     Log::info("{$params['requestId']}: Retrying through Vodafone IP");
        //     $asteriskUrl = 'http://122.15.44.121:8088/ari/events/user/originate-call?application='.$stasisApp;
        //     $resp = $this->triggerCurl($request, $asteriskUrl, $apiKey, 5);
        //     Log::info("{$params['requestId']}: Originate Curl Response: ", $resp);
        // }

        // JSON to Array on success
        // if ($resp['httpcode'] == 200) {
        //     $resp['response'] = json_decode($resp['response']);
        // } // uncomment when you need the response data

        return $resp;
    }

    private function triggerCurl($request, $asteriskUrl, $apiKey)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $asteriskUrl);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $apiKey);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
        $data = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errorCode = curl_error($ch);
        curl_close($ch);

        // Response Array
        return [
            'response' => $data,
            'httpcode' => $statusCode,
            'error' => $errorCode
        ];
    }

    private function logger($data)
    {
        $logData = '['.date('Y-m-d H:i:s').']'.' '.$data;
        $filename = storage_path().'/app/originateCallLog/BangaloreOriginateCallLog-'.date('Ymd').'.log';
        $handle = fopen($filename, 'a');
        fwrite($handle, $logData . PHP_EOL);
        fclose($handle);
    }

    public static function requestLog($loggerData)
    {
        $csv_data = [
            'date' => date("Y-m-d H:i:s"),
            'requestId' => $loggerData['requestId'],
            'token' => $loggerData['token'],
            'callerNumber' => NULL,
            'callerId' => NULL,
            'callFlow' => NULL,
            'extraParams' => NULL
        ];
        if(!empty($loggerData['request']['callerNumber'])) {
            $csv_data = array_merge($csv_data,[
                'callerNumber' => $loggerData['request']['callerNumber']
            ]);
        }
        if(!empty($loggerData['request']['callerId'])) {
            $csv_data = array_merge($csv_data,[
                'callerId' => $loggerData['request']['callerId']
            ]);
        }
        if(!empty($loggerData['request']['callFlow'])) {
            $csv_data = array_merge($csv_data,[
                'callFlow' => json_encode($loggerData['request']['callFlow'])
            ]);
        }
        if(!empty($loggerData['request']['extraParams'])) {
            $csv_data = array_merge($csv_data,[
                'extraParams' => json_encode($loggerData['request']['extraParams'])
            ]);
        }
        $path = storage_path().'/app/originateCallLog/BangaloreOriginateCallRequestLog.csv';
        $file = fopen($path, 'a');
        fputcsv($file, $csv_data);
        fclose($file);
    }

    public static function responseLog($loggerData) 
    {
        $csv_data = [
            'date' => date("Y-m-d H:i:s"),
            'requestId' => $loggerData['requestId'],
            'callId' => NULL,
            'token' => $loggerData['token'],
            'callerNumber' => NULL,
            'callerId' => NULL,
            'callFlow' => NULL,
            'extraParams' => NULL,
            'response' => json_encode($loggerData['response']),
            'statusCode' => $loggerData['statusCode'],
            'executionTime' => $loggerData['executionTime']
        ];
        if(!empty($loggerData['callId'])) {
            $csv_data = array_merge($csv_data,[
                'callId' => $loggerData['callId']
            ]);
        }
        if(!empty($loggerData['request']['callerNumber'])) {
            $csv_data = array_merge($csv_data,[
                'callerNumber' => $loggerData['request']['callerNumber']
            ]);
        }
        if(!empty($loggerData['request']['callerId'])) {
            $csv_data = array_merge($csv_data,[
                'callerId' => $loggerData['request']['callerId']
            ]);
        }
        if(!empty($loggerData['request']['callFlow'])) {
            $csv_data = array_merge($csv_data,[
                'callFlow' => json_encode($loggerData['request']['callFlow'])
            ]);
        }
        if(!empty($loggerData['request']['extraParams'])) {
            $csv_data = array_merge($csv_data,[
                'extraParams' => json_encode($loggerData['request']['extraParams'])
            ]);
        }
        $path = storage_path().'/app/originateCallLog/BangaloreOriginateCallResponseLog.csv';
        $file = fopen($path, 'a');
        fputcsv($file, $csv_data);
        fclose($file);
    }

    private function getServerPort()
    {
        $filename = storage_path() . '/app/counter_server.txt';
        if (file_exists($filename) && filesize($filename) != 0) {
            $handle = fopen($filename, 'rw');
            $server = fread($handle, filesize($filename));
        } else {
            $handle = fopen($filename, 'w');
            $server = 'B';
        }

        if ($server == 'A') {
            $port = '8886';
            $serverVal = 'B';
        } else {
            $port = '8088';
            $serverVal = 'A';
        }

        fwrite($handle, $serverVal);
        fclose($handle);
        return $port;
    }
}