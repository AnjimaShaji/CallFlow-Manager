<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\CustomLibraries\Constants;
use DB;
use Log;

class UpdateCallflowController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function updateDetails(Request $request)
    {
        $params = $request->all();
        Log::info('Input: ', $params);
        $header = $request->header('Authorization');
        $response = [];

        if(empty($params)) {
            $response['error'] = "Request empty";
        }

        if(empty($params['number'])) {
            $response['error']['virtualNumber'] = "Virtual Number empty";
        }

        if(empty($params['app'])) {
            $response['error']['app'] = "Application Name empty";
        }

        if(empty($params['callflow'])) {
            $response['error']['callflow'] = "Callflow empty";
        }

        if(!empty($params['number'])) {
            $numbers = self::checkVirtualNoExists($params['number']);
            if(empty($numbers)) {
                $response['error']['virtualNumber'] = $params['number']." - Virtual Number not exists";
            }
        }

        if(!empty($params['app'])) {
            $app = self::checkApplication($params['app']);
            if(empty($app)) {
                $response['error']['app'] = "Application Name Not Matching";
            }
        }

        if(empty($header)) {
            $response['error'] = "Authorization Token empty";
        } 
       
        if(!empty($response['error'])) {
            Log::info('Error: ', $response);
            $statusCode = 400;
        } else if(self::validateAuthoriztaionToken($header)) {
            $response['succes'] = self::updateCallflowConfigurations($params);
            Log::info('Success: ', $response);
            $response = $response['succes'];
            $statusCode = 200;
        } else {
            $response['error'] = 'Invalid Authorization Token';
            Log::info('Error: ', $response);
            $statusCode = 401;
        }
        self::setRequestLog($params,$response,$header,'EDIT'); 
        return response()->json($response, $statusCode); 
    }

    public function validateAuthoriztaionToken($token)
    {
        $user = DB::table('user')
                    ->select('id')
                    ->where('token', '=', $token)
                    ->value('id');
                    
        return $user;
    }


    public function updateCallflowConfigurations($params)
    {

        $callflow = DB::table('callflow')
                        ->select('callflow')
                        ->where('sim_number',$params['number'])
                        ->value('callflow');
        $callFlowArr = json_decode($callflow,true);
        Log::info('Old Callflow: ', $callFlowArr);
        Log::info('New Callflow:', $params['callflow']);
        $updateCallFlow = [
            'callflow' => json_encode($params['callflow']),
            'app' => $params['app'],
            'updated_at' => date('Y-m-d H:i:s')
        ];
        if(!empty($params['last_answered_agent_number'])) {
            $insertCallFlow['last_answered_agent_number'] = $params['last_answered_agent_number'];
        }

        DB::table('callflow')
            ->where('sim_number', $params['number'])
            ->update($updateCallFlow);

        $response = [
            "virtual_number" => $params['number'],
            'status' => 'succes',
            'message' => 'Callflow updated'
        ];

        return $response;

    }

    public function checkVirtualNoExists($virtualNumber)
    {
        $numbers = DB::table('callflow')
                    ->select('sim_number')
                    ->where('sim_number', '=', $virtualNumber)
                    ->value('sim_number');
        return $numbers;
    }

    public function checkApplication($app)
    {
        $app = DB::table('callflow')
                    ->select('app')
                    ->where('app', '=', $app)
                    ->value('app');
        return $app;
    }

    public function setRequestLog($params,$response,$header,$action)
    {

        $insertCallFlow = [
            'user_id' => self::validateAuthoriztaionToken($header),
            'sim_number' => $params['number'],
            'app' => $params['app'],
            'action' => $action,
            'payload' => json_encode($params),
            'response' => json_encode($response)
        ];

        DB::table('request_log')->insertGetId($insertCallFlow);

    }

    public function delete(Request $request)
    {

        $header = $request->header('Authorization');
        $params = $request->all();
        Log::info('Input: ', $params);
        $response = [];

        if(empty($params['number'])) {
            $response['error']['virtualNumber'] = "Virtual Number empty";
        }

        if(!empty($params['number'])) {
            $numbers = self::checkVirtualNoExists($params['number']);
            if(empty($numbers)) {
                $response['error']['virtualNumber'] = $params['number']." - Virtual Number not exists";
            }
        }

        if(!empty($response['error'])) {
            Log::info('Error: ', $response);
            $statusCode = 400;
        } else if(self::validateAuthoriztaionToken($header)) {
            DB::table('callflow')
                ->where('sim_number', $params['number'])
                ->delete();
            $response = [
                "virtual_number" => $params['number'],
                'status' => 'succes',
                'message' => 'Callflow deleted'
            ];
            Log::info('Success: ', $response);
            $statusCode = 200;
            
        } else {
            $response['error'] = 'Invalid Authorization Token';
            Log::info('Error: ', $response);
            $statusCode = 401;
        }

        self::setRequestLog($params,$response,$header,"DELETE"); 
        return response()->json($response, $statusCode);
        
    }
}