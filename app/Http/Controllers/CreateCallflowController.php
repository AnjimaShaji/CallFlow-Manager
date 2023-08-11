<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\CustomLibraries\Constants;
use DB;
use Log;

class CreateCallflowController extends Controller
{
	public function getDetails(Request $request)
    {
		$params = $request->all();

        Log::info('Input: ', $params);
        $header = $request->header('Authorization');
        $response = [];

        if(empty($params)) {
            $response['error']['request'] = "Request empty";
        }
        
        if(empty($params['number'])) {
            $response['error']['virtualNumber'] = "Virtual Number empty";
        }

        if(empty($params['app'])) {
            $response['error']['app'] = "Application Name empty";
        }

        if(empty($header)) {
            $response['error'] = "Authorization Token empty";
        } 

        if(!empty($params['number'])) {
            $numbers = self::checkVirtualNoExists($params['number']);
            if(!empty($numbers)) {
                $response['error']['virtualNumber'] = $params['number']." - Virtual Number already exists";
            } else {
                $vnNumberStatus = $this->validateVirtualNumber($params['number']);
                if(!$vnNumberStatus) {
                    $response['error']['virtualNumber'] = "Invalid Virtual Number";
                }

                if(empty($params['callflow'])) {
                    $response['error']['callflow'] = "Callflow empty";
                }

            } 
        }
         
        if(!empty($response['error'])) {
            Log::info('Error: ', $response);
            $statusCode = 400;
        } else if(self::validateAuthoriztaionToken($header)) {
            $response['succes'] = self::createCallflowConfigurations($params);
            Log::info('Success: ', $response);
            $response = $response['succes'];
            $statusCode = 200;
        } else {
            $response['error'] = 'Invalid Authorization Token';
            Log::info('Error: ', $response);
            $statusCode = 401;
        }

        self::setRequestLog($params,$response,$header); 
        return response()->json($response, $statusCode);

    }

    public function validateAuthoriztaionToken($token)
    {
        $user = DB::table('user')
                    ->select('id')
                    ->where('token', '=', $token)
                    ->value('id');;
        return $user;
    }

    public function checkVirtualNoExists($virtualNumber)
    {
        $numbers = DB::table('callflow')
                    ->select('sim_number')
                    ->where('sim_number', '=', $virtualNumber)
                    ->value('sim_number');
        return $numbers;
    }

    private function validateVirtualNumber($number)
    {
        if (strlen($number) == 13 && substr($number, 0, 3) == '+91') {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function createCallflowConfigurations($params)
    {
        $insertCallFlow = [
            'sim_number' => $params['number'],
            'app' => $params['app'],
            'callflow' => json_encode($params['callflow']),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if(!empty($params['last_answered_agent_number'])) {
            $insertCallFlow['last_answered_agent_number'] = $params['last_answered_agent_number'];
        }

        $id = DB::table('callflow')->insertGetId($insertCallFlow);
        $response = [
                "virtual_number" => $params['number'],
                'status' => 'succes',
                'message' => 'Callflow created'
        ];

        return $response;

    }

    public function setRequestLog($params,$response,$header)
    {

        $insertCallFlow = [
            'user_id' => self::validateAuthoriztaionToken($header),
            'sim_number' => $params['number'],
            'app' => $params['app'],
            'action' => "ADD",
            'payload' => json_encode($params),
            'response' => json_encode($response)
        ];

        DB::table('request_log')->insertGetId($insertCallFlow);

    }

}