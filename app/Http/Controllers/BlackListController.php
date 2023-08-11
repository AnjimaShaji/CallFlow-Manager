<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Log;

class BlackListController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function getDetails(Request $request)
    {
    	$params = $request->all();
        Log::info('Input: ', $params);
        $header = $request->header('Authorization');
        $response = [];

        if(empty($params)) {
            $response['error']['request'] = "Request empty";
        }
        
        if(empty($params['customerNumber'])) {
            $response['error']['customerNumber'] = "Customer Number empty";
        }

        if(empty($header)) {
            $response['error'] = "Authorization Token empty";
        } else {
        	$user_id = self::validateAuthoriztaionToken($header);
        }

        if(!empty($params['direction'])) {
        	$direction = ['INBOUND','OUTBOUND'];
        	if(!in_array($params['direction'],$direction)) {
        		$response['error']['direction'] = "Invalid Direction";
        	}
        }

        if(!empty($params['blockLevel'])) {
        	$blockLevel = ['GLOBAL','APP'];
        	if(!in_array($params['blockLevel'],$blockLevel)) {
        		$response['error']['blockLevel'] = "Invalid Block Level";
        	}
        }

        if(!empty($params['customerNumber'])) {
            $numbers = self::checkBlackList($params,$user_id);
            if(!empty($numbers)) {
                $response['error']['customerNumber'] = $params['customerNumber']." - Customer Number already black listed";
            }
        }

        if(!empty($response['error'])) {
            Log::info('Error: ', $response);
            $statusCode = 400;
        } else {
        	if(!empty($user_id)) {
	            $response['succes'] = self::addBlackList($params,$user_id);
	            Log::info('Success: ', $response);
	            $response = $response['succes'];
	            $statusCode = 200;
        	} else {
	            $response['error'] = 'Invalid Authorization Token';
	            Log::info('Error: ', $response);
	            $statusCode = 401;
        	}
        } 

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

    public function addBlackList($params,$user_id)
    {
    	$insertCallFlow = [
            'customer_number' => $params['customerNumber'],
            'user_id' => $user_id,
            'created_at' => date('Y-m-d H:i:s')
        ];

        if(!empty($params['direction'])) {
            $insertCallFlow['direction'] = $params['direction'];
        }

        if(!empty($params['blockLevel'])) {
            $insertCallFlow['block_level'] = $params['blockLevel'];
        }

        DB::table('black_list')->insert($insertCallFlow);

        $response = [
                "customer_numbe" => $params['customerNumber'],
                'status' => 'succes',
                'message' => 'Customer Number Black Listed'
        ];

        return $response;

    }

    public function checkBlackList($params,$user_id)
    {
    	
        $query = DB::table('black_list')
            ->where('customer_number',$params['customerNumber']);
        $query->where(
            function($query2) use ($user_id) {
            return $query2
                ->where('user_id', '=', $user_id)
                ->orWhere('block_level', '=', 'GLOBAL');
        });

        $numbers = $query->value('customer_number');
        return $numbers;
    }
}